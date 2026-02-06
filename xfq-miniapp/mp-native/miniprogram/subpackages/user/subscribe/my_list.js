const config = require('../../../config')
const auth = require('../../../services/auth')
const ui = require('../../../utils/ui')
const urlUtil = require('../../../utils/url')
const apptApi = require('../../../services/api/appt')

function normalizeItem(item, baseUrl, key) {
  if (!item || typeof item !== 'object') return null
  const seller = item.seller && typeof item.seller === 'object' ? item.seller : {}
  return {
    key: String(key || ''),
    id: item.id,
    statusText: item.status_text || '',
    sellerName: seller.nickname || '',
    sellerImage: urlUtil.normalizeNetworkUrl(seller.image, baseUrl),
    fullname: item.fullname || '',
    number: item.number || 0,
    start: item.start || '',
    timeEndText: item.time_end_text || '',
    raw: item,
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    tabs: [
      { title: '全部', status: '' },
      { title: '待核销', status: '0' },
      { title: '已核销', status: '1' },
      { title: '已取消', status: '2' },
    ],
    tabIndex: 0,
    status: '',

    list: [],
    page: 1,
    pageSize: 12,
    requesting: false,
    loadingStatus: 'more',
    loadingLock: false,
    empty: false,
    error: null,
  },
  onLoad() {
    wx.setNavigationBarTitle({ title: '我的预约' })
    this.refresh()
  },
  onShow() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    this.setData({ hasLogin })
    if (hasLogin) this.refresh({ showLoading: false })
  },
  onPullDownRefresh() {
    this.refresh({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  onReachBottom() {
    this.fetchNextPage({ showLoading: false })
  },
  ensureReady() {
    if (!this.data.hasBaseUrl) {
      ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
      return false
    }
    if (!this.data.hasLogin) {
      ui.toast('请先登录')
      return false
    }
    return true
  },
  onTapGoLogin() {
    const redirect = '/subpackages/user/subscribe/my_list'
    wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
  },
  onTapTab(e) {
    const index = Number(e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.index)
    const tab = this.data.tabs && this.data.tabs[index]
    if (!tab) return
    this.setData({ tabIndex: index, status: tab.status })
    this.refresh({ showLoading: false })
  },
  refresh({ showLoading = true } = {}) {
    if (!this.ensureReady()) return Promise.resolve()
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
  },
  fetchNextPage({ showLoading = true } = {}) {
    if (!this.ensureReady()) return Promise.resolve()
    if (this.data.loadingLock || this.data.requesting) return Promise.resolve()

    this.setData({ requesting: true, loadingStatus: 'loading', error: null })

    return apptApi
      .getList(
        {
          page: this.data.page,
          page_size: this.data.pageSize,
          status: this.data.status,
        },
        { showLoading }
      )
      .then((res) => {
        if (!res || typeof res !== 'object') {
          this.setData({ error: '返回数据异常', loadingStatus: 'error' })
          return
        }
        if (res.code !== 0) {
          this.setData({ error: res.msg || '请求失败', loadingStatus: 'error' })
          return
        }
        const data = Array.isArray(res.data) ? res.data : []
        const normalized = data
          .map((it, index) => normalizeItem(it, this.data.baseUrl, `${this.data.page}-${index}`))
          .filter(Boolean)

        if (this.data.page === 1 && normalized.length === 0) {
          this.setData({ empty: true, loadingStatus: 'no-more', loadingLock: true })
          return
        }

        const nextList = (this.data.list || []).concat(normalized)
        const noMore = normalized.length === 0 || normalized.length < this.data.pageSize
        this.setData({
          list: nextList,
          page: this.data.page + 1,
          loadingStatus: noMore ? 'no-more' : 'more',
          loadingLock: noMore,
        })
      })
      .catch((err) => this.setData({ error: String((err && (err.errMsg || err.message)) || err), loadingStatus: 'error' }))
      .finally(() => this.setData({ requesting: false }))
  },
  onTapItem(e) {
    const id = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({ url: `/subpackages/user/subscribe/detail?id=${encodeURIComponent(String(id))}` })
  },
  onRetry() {
    this.refresh({ showLoading: false })
  },
})

