const config = require('../../config')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')
const urlUtil = require('../../utils/url')
const ticketsApi = require('../../services/api/tickets')

function normalizeItem(item, baseUrl) {
  if (!item || typeof item !== 'object') return null
  const seller = item.info_seller && typeof item.info_seller === 'object' ? item.info_seller : {}
  const order = item.info_order && typeof item.info_order === 'object' ? item.info_order : {}
  return {
    id: item.id,
    tradeNo: item.trade_no || '',
    statusText: item.status_text || '',
    refundFee: item.refund_fee || '',
    sellerNickname: seller.nickname || '',
    sellerImage: urlUtil.normalizeNetworkUrl(seller.image, baseUrl),
    orderAmountPrice: order.amount_price || '',
    createTimeText: item.create_time || '',
    raw: item,
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    list: [],
    page: 1,
    pageSize: 12,
    requesting: false,
    loadingStatus: 'more',
    loadingLock: false,
    empty: false,
    error: null,
  },
  onShow() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    this.setData({ hasLogin })
    if (!hasLogin) return
    if (!this.data.hasBaseUrl) return
    this.refresh({ showLoading: false })
  },
  onPullDownRefresh() {
    this.refresh({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
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
        content: '查看售后记录需要先登录，是否现在去登录？',
        confirmText: '去登录',
        cancelText: '取消',
      })
      .then((res) => {
        if (res && res.confirm) {
          const redirect = '/subpackages/user/my_order_refund'
          wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
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
    if (this.data.loadingLock || this.data.requesting) return Promise.resolve()
    this.setData({ requesting: true, loadingStatus: 'loading' })

    return ticketsApi
      .getRefundLogList({ page: this.data.page, page_size: this.data.pageSize }, { showLoading })
      .then((res) => {
        const body = res && typeof res === 'object' && typeof res.code !== 'undefined' ? res : null
        if (body && body.code !== 0) {
          ui.toast(body.msg || '请求失败')
          this.setData({ loadingStatus: 'error' })
          return
        }
        const rawList = body ? body.data : res && res.data ? res.data : res
        const list = Array.isArray(rawList) ? rawList : []
        const normalized = list.map((it) => normalizeItem(it, this.data.baseUrl)).filter(Boolean)

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
      .catch((err) => {
        this.setData({ error: String((err && (err.errMsg || err.message)) || err), loadingStatus: 'error' })
      })
      .finally(() => this.setData({ requesting: false }))
  },
  onTapItem(e) {
    const id = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({ url: `/subpackages/user/order_refund_detail?id=${encodeURIComponent(String(id))}` })
  },
  onRetry() {
    this.refresh({ showLoading: false })
  },
})

