const config = require('../../../config')
const auth = require('../../../services/auth')
const ui = require('../../../utils/ui')
const userApi = require('../../../services/api/user')

const NAV_PAYLOAD_KEY = '__nav_coupon_issue_user'

const TABS = [
  { title: '全部', statusParam: '' },
  { title: '未使用', statusParam: 0 },
  { title: '已使用', statusParam: 1 },
  { title: '已过期', statusParam: 2 },
]

function formatDateTime(input) {
  if (input === null || typeof input === 'undefined') return ''
  if (typeof input === 'number') {
    const date = new Date(input < 1e12 ? input * 1000 : input)
    if (Number.isNaN(date.getTime())) return String(input)
    const m = String(date.getMonth() + 1).padStart(2, '0')
    const d = String(date.getDate()).padStart(2, '0')
    const hh = String(date.getHours()).padStart(2, '0')
    const mm = String(date.getMinutes()).padStart(2, '0')
    return `${m}-${d} ${hh}:${mm}`
  }
  const parsed = Date.parse(String(input))
  if (!Number.isNaN(parsed)) return formatDateTime(parsed)
  return String(input)
}

function toTimestamp(input) {
  if (input === null || typeof input === 'undefined') return 0
  if (typeof input === 'number') return input > 1e12 ? Math.floor(input / 1000) : input
  const parsed = Date.parse(String(input))
  if (Number.isNaN(parsed)) return 0
  return Math.floor(parsed / 1000)
}

function buildValidTime(item, createTime) {
  const issue = item && item.couponIssue && typeof item.couponIssue === 'object' ? item.couponIssue : {}
  const isPermanent = Number(issue.is_permanent)
  if (isPermanent === 1) return '有效时间：永久有效'
  if (isPermanent === 2) {
    const start = toTimestamp(issue.coupon_time_start)
    const end = toTimestamp(issue.coupon_time_end)
    const startText = start ? formatDateTime(start) : '-'
    const endText = end ? formatDateTime(end) : '-'
    return `有效时间：${startText} 至 ${endText}`
  }
  const day = Number(issue.day || 0)
  if (day > 0 && createTime) {
    const expire = createTime + day * 86400
    return `有效时间：${formatDateTime(createTime)} 至 ${formatDateTime(expire)}`
  }
  return '有效时间：-'
}

function safeStorageSet(key, value) {
  try {
    wx.setStorageSync(key, value)
  } catch (e) {}
}

function normalizeItem(item) {
  if (!item || typeof item !== 'object') return null
  const createTime = toTimestamp(item.create_time)
  const status = Number(item.status)
  const statusText = status === 0 ? '未使用' : status === 1 ? '已使用' : status === 2 ? '已过期' : String(item.status)
  return {
    id: item.id,
    title: item.coupon_title || '',
    price: item.coupon_price || '',
    createTimeText: formatDateTime(createTime),
    validTimeText: buildValidTime(item, createTime),
    status,
    statusText,
    raw: item,
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    tabs: TABS,
    currentIndex: 0,
    tabState: TABS.map(() => ({
      list: [],
      page: 1,
      limit: 8,
      loadingStatus: 'more',
      loadingLock: false,
      requesting: false,
      empty: false,
    })),
    error: null,
  },
  onLoad(options) {
    const state = Number(options && options.state)
    if (Number.isInteger(state) && state >= 0 && state < TABS.length) {
      this.setData({ currentIndex: state })
    }
  },
  onShow() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    this.setData({ hasLogin })
    if (!hasLogin) return

    this.fetchUserCouponIds()

    const current = this.data.tabState[this.data.currentIndex]
    if (current && current.list && current.list.length) return
    this.fetchNextPage()
  },
  onPullDownRefresh() {
    this.refreshCurrent({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  onReachBottom() {
    this.fetchNextPage()
  },
  ensureLoginOrPrompt() {
    const uid = auth.getUid()
    if (uid) return Promise.resolve(uid)
    return ui
      .showModal({
        title: '提示',
        content: '请先登录后查看我的券',
        confirmText: '去登录',
        cancelText: '取消',
      })
      .then((res) => {
        if (res && res.confirm) {
          wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent('/subpackages/user/order/order')}` })
        }
        return null
      })
  },
  fetchUserCouponIds() {
    const uid = auth.getUid()
    if (!uid) return Promise.resolve()
    return userApi
      .getUserCouponIds({ uid }, { showLoading: false })
      .then((res) => {
        if (res && res.code === 0) safeStorageSet('coupon_id', res.data)
      })
      .catch(() => {})
  },
  refreshCurrent({ showLoading = true } = {}) {
    return this.ensureLoginOrPrompt().then((uid) => {
      if (!uid) return
      const i = this.data.currentIndex
      this.setData({
        [`tabState[${i}].list`]: [],
        [`tabState[${i}].page`]: 1,
        [`tabState[${i}].loadingStatus`]: 'more',
        [`tabState[${i}].loadingLock`]: false,
        [`tabState[${i}].requesting`]: false,
        [`tabState[${i}].empty`]: false,
        error: null,
      })
      return this.fetchNextPage({ showLoading })
    })
  },
  fetchNextPage({ showLoading = true } = {}) {
    const uid = auth.getUid()
    if (!uid) return Promise.resolve()

    const i = this.data.currentIndex
    const state = this.data.tabState[i]
    if (!state || state.loadingLock || state.requesting) return Promise.resolve()

    const tab = this.data.tabs[i] || TABS[0]
    this.setData({ [`tabState[${i}].requesting`]: true, [`tabState[${i}].loadingStatus`]: 'loading' })

    return userApi
      .getCouponIssueUserList(
        {
          uid,
          status: tab.statusParam,
          page: state.page,
          limit: state.limit,
        },
        { showLoading }
      )
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          return
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '请求失败')
          return
        }

        const data = res.data && res.data.data
        const list = Array.isArray(data) ? data : []
        const normalized = list.map(normalizeItem).filter(Boolean)

        if (state.page === 1 && normalized.length === 0) {
          this.setData({
            [`tabState[${i}].empty`]: true,
            [`tabState[${i}].loadingLock`]: true,
            [`tabState[${i}].loadingStatus`]: 'no-more',
          })
          return
        }

        const nextList = (state.list || []).concat(normalized)
        const perPage = Number(res.data && res.data.per_page) || state.limit
        const noMore = normalized.length === 0 || normalized.length < perPage
        this.setData({
          [`tabState[${i}].list`]: nextList,
          [`tabState[${i}].page`]: state.page + 1,
          [`tabState[${i}].loadingStatus`]: noMore ? 'no-more' : 'more',
          [`tabState[${i}].loadingLock`]: noMore,
        })
      })
      .catch(() => {
        ui.toast('请求失败，请稍后重试')
      })
      .finally(() => {
        this.setData({ [`tabState[${i}].requesting`]: false })
      })
  },
  onRetry() {
    this.refreshCurrent({ showLoading: false })
  },
  onTapTab(e) {
    const index = Number(e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.index)
    if (!Number.isInteger(index) || index < 0 || index >= TABS.length) return
    if (index === this.data.currentIndex) return
    this.setData({ currentIndex: index })

    const state = this.data.tabState[index]
    if (state && state.list && state.list.length) return
    if (!this.data.hasLogin) return
    this.fetchNextPage()
  },
  onTapItem(e) {
    const id = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.id
    const index = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.index
    if (!id) return
    const i = this.data.currentIndex
    const item = this.data.tabState[i] && this.data.tabState[i].list && this.data.tabState[i].list[index]
    if (item && item.raw) safeStorageSet(NAV_PAYLOAD_KEY, item.raw)
    wx.navigateTo({ url: `/subpackages/coupon/my_coupon?cuid=${encodeURIComponent(String(id))}` })
  },
})
