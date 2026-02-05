const config = require('../../config')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')
const urlUtil = require('../../utils/url')
const ticketsApi = require('../../services/api/tickets')

const TABS = [
  { title: '全部', statusParam: '' },
  { title: '待支付', statusParam: 'created' },
  { title: '已支付', statusParam: 'paid' },
  { title: '已使用', statusParam: 'used' },
]

function statusName(status, order) {
  const s = String(status || '')
  if (s) return s === 'created' ? '待支付' : s === 'paid' ? '已支付' : s === 'used' ? '已使用' : s === 'refunded' ? '已退款' : s
  if (order && typeof order === 'object' && order.order_status_text) return order.order_status_text
  return '-'
}

function normalizeOrder(item, baseUrl) {
  if (!item || typeof item !== 'object') return null
  const seller = item.seller && typeof item.seller === 'object' ? item.seller : {}
  const detailList = Array.isArray(item.detail_list) ? item.detail_list : []
  const first = detailList[0] || {}
  return {
    id: item.id,
    sellerNickname: seller.nickname || '',
    sellerImage: urlUtil.normalizeNetworkUrl(seller.image, baseUrl),
    status: item.order_status || '',
    statusText: item.order_status_text || statusName(item.order_status, item),
    ticketTitle:
      first.ticket_title ||
      (item.ticket_info && item.ticket_info.title) ||
      (item.ticket_info && item.ticket_info.nickname) ||
      '',
    count: detailList.length,
    amountPrice: item.amount_price || '',
    raw: item,
  }
}

function inferTabIndexFromQuery(options) {
  const state = options && typeof options.state !== 'undefined' ? String(options.state) : ''
  if (!state) return 0
  const index = TABS.findIndex((t) => String(t.statusParam) === state)
  return index >= 0 ? index : 0
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    tabs: TABS,
    currentIndex: 0,

    list: [],
    page: 1,
    pageSize: 12,
    requesting: false,
    loadingStatus: 'more',
    loadingLock: false,
    empty: false,
    error: null,
  },
  onLoad(options) {
    this.setData({ currentIndex: inferTabIndexFromQuery(options) })
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
        content: '查看门票订单需要先登录，是否现在去登录？',
        confirmText: '去登录',
        cancelText: '取消',
      })
      .then((res) => {
        if (res && res.confirm) {
          const redirect = '/subpackages/user/my_order'
          wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
        }
        return null
      })
  },
  onTapTab(e) {
    const index = Number(e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.index)
    if (!Number.isInteger(index) || index < 0 || index >= TABS.length) return
    if (index === this.data.currentIndex) return
    this.setData({ currentIndex: index })
    if (!this.data.hasLogin) return
    this.refresh({ showLoading: false })
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

    const tab = this.data.tabs[this.data.currentIndex] || TABS[0]
    this.setData({ requesting: true, loadingStatus: 'loading' })

    return ticketsApi
      .getOrderList(
        {
          page: this.data.page,
          page_size: this.data.pageSize,
          status: tab.statusParam,
        },
        { showLoading }
      )
      .then((res) => {
        const body = res && typeof res === 'object' && typeof res.code !== 'undefined' ? res : null
        const listRaw = body ? body.data : res && res.data ? res.data : res
        if (body && body.code !== 0) {
          ui.toast(body.msg || '请求失败')
          this.setData({ loadingStatus: 'error' })
          return
        }
        const list = Array.isArray(listRaw) ? listRaw : []
        const normalized = list.map((it) => normalizeOrder(it, this.data.baseUrl)).filter(Boolean)

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
    wx.navigateTo({ url: `/subpackages/user/order_detail?id=${encodeURIComponent(String(id))}` })
  },
  onRetry() {
    this.refresh({ showLoading: false })
  },
})

