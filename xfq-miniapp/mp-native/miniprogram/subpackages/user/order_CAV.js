const config = require('../../config')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')
const couponApi = require('../../services/api/coupon')

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

function normalizeItem(item) {
  if (!item || typeof item !== 'object') return null
  return {
    id: item.id,
    title: item.coupon_title || '',
    price: item.coupon_price || '',
    createTimeText: formatDateTime(item.create_time),
    raw: item,
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    mid: 0,

    list: [],
    page: 1,
    limit: 20,
    requesting: false,
    loadingStatus: 'more',
    loadingLock: false,
    empty: false,
    error: null,
  },
  onLoad(options) {
    const mid = Number(options && options.mid) || 0
    this.setData({ mid })
    if (!mid) {
      ui.showModal({ title: '提示', content: '参数错误（缺少 mid）', showCancel: false }).then(() => wx.navigateBack())
    }
  },
  onShow() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    this.setData({ hasLogin })
    if (!hasLogin) return
    if (!this.data.hasBaseUrl) return
    if (!this.data.mid) return
    this.refresh({ showLoading: false })
  },
  onPullDownRefresh() {
    this.refresh({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  onReachBottom() {
    this.fetchNextPage()
  },
  buildRedirectUrl() {
    return `/subpackages/user/order_CAV?mid=${encodeURIComponent(String(this.data.mid || ''))}`
  },
  ensureLoginOrPrompt() {
    const uid = auth.getUid()
    if (uid) return Promise.resolve(uid)
    return ui
      .showModal({
        title: '提示',
        content: '查看核销记录需要先登录，是否现在去登录？',
        confirmText: '去登录',
        cancelText: '取消',
      })
      .then((res) => {
        if (res && res.confirm) {
          wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(this.buildRedirectUrl())}` })
        }
        return null
      })
  },
  refresh({ showLoading = true } = {}) {
    if (!this.data.hasBaseUrl) {
      ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
      return Promise.resolve()
    }

    if (!this.data.mid) return Promise.resolve()

    return this.ensureLoginOrPrompt().then((uid) => {
      if (!uid) return
      this.setData({
        list: [],
        page: 1,
        requesting: false,
        loadingStatus: 'more',
        loadingLock: false,
        empty: false,
        error: null,
      })
      return this.fetchNextPage({ showLoading })
    })
  },
  fetchNextPage({ showLoading = true } = {}) {
    const uid = auth.getUid()
    if (!uid) return Promise.resolve()
    if (this.data.loadingLock || this.data.requesting) return Promise.resolve()

    this.setData({ requesting: true, loadingStatus: 'loading' })

    return couponApi
      .getWriteoffLog(
        {
          page: this.data.page,
          limit: this.data.limit,
          userid: uid,
          mid: this.data.mid,
        },
        { showLoading }
      )
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          this.setData({ loadingStatus: 'error' })
          return
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '请求失败')
          this.setData({ loadingStatus: 'error' })
          return
        }

        const data = Array.isArray(res.data) ? res.data : []
        const normalized = data.map(normalizeItem).filter(Boolean)

        if (this.data.page === 1 && normalized.length === 0) {
          this.setData({ empty: true, loadingStatus: 'no-more', loadingLock: true })
          return
        }

        const nextList = (this.data.list || []).concat(normalized)
        const noMore = normalized.length === 0 || normalized.length < this.data.limit
        this.setData({
          list: nextList,
          page: this.data.page + 1,
          loadingStatus: noMore ? 'no-more' : 'more',
          loadingLock: noMore,
        })
      })
      .catch((err) => {
        this.setData({ error: String((err && (err.errMsg || err.message)) || err), loadingStatus: 'error' })
      })
      .finally(() => this.setData({ requesting: false }))
  },
  onTapItem(e) {
    const id = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({
      url:
        `/subpackages/user/order_CAV_info?id=${encodeURIComponent(String(id))}` +
        `&mid=${encodeURIComponent(String(this.data.mid || ''))}`,
    })
  },
  onRetry() {
    this.refresh({ showLoading: false })
  },
})

