const config = require('../../config')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')
const ticketsApi = require('../../services/api/tickets')

function normalizeTicket(item) {
  if (!item || typeof item !== 'object') return null
  return {
    fullname: item.tourist_fullname || '',
    mobile: item.tourist_mobile || '',
    certId: item.tourist_cert_id || '',
    raw: item,
  }
}

function normalizeDetail(data) {
  if (!data || typeof data !== 'object') return null
  const seller = data.info_seller && typeof data.info_seller === 'object' ? data.info_seller : {}
  const order = data.info_order && typeof data.info_order === 'object' ? data.info_order : {}
  const detailList = Array.isArray(data.info_order_detail) ? data.info_order_detail : []
  return {
    statusText: data.status_text || '',
    refuseDesc: data.refuse_desc || '',
    refundFee: data.refund_fee || '',
    sellerNickname: seller.nickname || '',
    orderAmountPrice: order.amount_price || '',
    createTimeText: data.create_time || '',
    tradeNo: data.trade_no || '',
    tickets: detailList.map(normalizeTicket).filter(Boolean),
    raw: data,
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,
    id: '',
    detail: null,
    error: null,
  },
  onLoad(options) {
    const id = (options && options.id) || ''
    this.setData({ id: String(id) })
  },
  onShow() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    this.setData({ hasLogin })
    if (!hasLogin) return
    if (!this.data.hasBaseUrl) return
    if (!this.data.id) return
    this.fetchDetail({ showLoading: false })
  },
  ensureLoginOrPrompt() {
    const uid = auth.getUid()
    if (uid) return Promise.resolve(uid)
    return ui
      .showModal({
        title: '提示',
        content: '查看售后详情需要先登录，是否现在去登录？',
        confirmText: '去登录',
        cancelText: '取消',
      })
      .then((res) => {
        if (res && res.confirm) {
          const redirect = `/subpackages/user/order_refund_detail?id=${encodeURIComponent(String(this.data.id || ''))}`
          wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
        }
        return null
      })
  },
  fetchDetail({ showLoading = true } = {}) {
    if (!this.data.id) return Promise.resolve()
    this.setData({ error: null })
    return this.ensureLoginOrPrompt()
      .then((uid) => {
        if (!uid) return null
        return ticketsApi.getRefundLogDetail({ id: this.data.id }, { showLoading })
      })
      .then((res) => {
        if (!res) return
        const body = res && typeof res === 'object' && typeof res.code !== 'undefined' ? res : null
        if (body && body.code !== 0) {
          ui.toast(body.msg || '请求失败')
          return
        }
        const raw = body ? body.data : res && res.data ? res.data : res
        const normalized = normalizeDetail(raw)
        if (!normalized) {
          ui.toast('返回数据异常')
          return
        }
        this.setData({ detail: normalized })
      })
      .catch((err) => {
        this.setData({ error: String((err && (err.errMsg || err.message)) || err) })
      })
  },
  onTapCopyTradeNo() {
    const no = this.data.detail && this.data.detail.tradeNo
    if (!no) return
    wx.setClipboardData({ data: String(no) })
  },
  onRetry() {
    this.fetchDetail({ showLoading: false })
  },
})

