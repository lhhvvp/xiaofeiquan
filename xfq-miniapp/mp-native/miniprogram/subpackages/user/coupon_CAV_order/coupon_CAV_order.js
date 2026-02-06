const config = require('../../../config')
const auth = require('../../../services/auth')
const ui = require('../../../utils/ui')
const urlUtil = require('../../../utils/url')
const ticketsApi = require('../../../services/api/tickets')

function safeParseJson(val) {
  if (!val) return null
  const raw = String(val).trim()
  if (!raw) return null
  try {
    return JSON.parse(raw)
  } catch (e) {}
  try {
    return JSON.parse(decodeURIComponent(raw))
  } catch (e) {}
  return null
}

function normalizeNumber(val) {
  const n = Number(val)
  return Number.isFinite(n) ? n : 0
}

function normalizePayload(input) {
  if (!input || typeof input !== 'object') return null
  let qrcodeStr = input.qrcode_str || input.qrcodeStr || ''
  qrcodeStr = String(qrcodeStr)
  try {
    qrcodeStr = decodeURIComponent(qrcodeStr)
  } catch (e) {}

  const beId = input.be_id || input.beId
  const useLat = normalizeNumber(input.use_lat || input.useLat)
  const useLng = normalizeNumber(input.use_lng || input.useLng)

  if (!qrcodeStr || typeof beId === 'undefined' || beId === null) return null

  return {
    qrcodeStr,
    beId: String(beId),
    useLat,
    useLng,
  }
}

function normalizeOrderDetail(data, baseUrl) {
  if (!data || typeof data !== 'object') return null
  const seller = data.seller && typeof data.seller === 'object' ? data.seller : {}
  const ticketInfo = data.ticket_info && typeof data.ticket_info === 'object' ? data.ticket_info : {}
  return {
    sellerNickname: seller.nickname || '',
    amountPrice: data.amount_price || '',
    orderStatusText: data.order_status_text || '',
    ticketDate: ticketInfo.date || '',
    tradeNo: data.trade_no || '',
    explainBuyHtml: urlUtil.normalizeRichTextHtml(ticketInfo.explain_buy, baseUrl),
    explainUseHtml: urlUtil.normalizeRichTextHtml(ticketInfo.explain_use, baseUrl),
    raw: data,
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    payload: null,
    detail: null,
    writeoffInfo: null,
    showMask: false,
    mask: {
      title: '请等候...',
      message: '正在核销，请稍候..',
      success: null,
    },
    writeoffDone: false,
    submitting: false,
    error: null,
  },
  onLoad(options) {
    const payload = normalizePayload(safeParseJson(options && options.data))
    if (!payload) {
      ui.toast('参数错误（缺少核销信息）')
      setTimeout(() => wx.navigateBack(), 1200)
      return
    }
    this.setData({ payload })
  },
  onShow() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    this.setData({ hasLogin })
    if (!hasLogin) return
    if (!this.data.hasBaseUrl) return
    if (!this.data.payload) return
    if (this.data.writeoffDone) return
    this.init({ showLoading: false })
  },
  buildRedirectUrl() {
    const payload = this.data.payload
    if (!payload) return '/pages/user/user'
    const data = encodeURIComponent(
      JSON.stringify({
        qrcode_str: payload.qrcodeStr,
        be_id: payload.beId,
        use_lat: payload.useLat,
        use_lng: payload.useLng,
        type: 'order',
      })
    )
    return `/subpackages/user/coupon_CAV_order/coupon_CAV_order?data=${data}`
  },
  ensureLoginOrPrompt() {
    const uid = auth.getUid()
    if (uid) return Promise.resolve(uid)
    return ui
      .showModal({
        title: '提示',
        content: '核销需要先登录，是否现在去登录？',
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
  init({ showLoading = true } = {}) {
    if (this.data.submitting) return Promise.resolve()
    if (this.data.writeoffDone) return Promise.resolve()
    if (!this.data.payload) return Promise.resolve()

    this.setData({
      submitting: true,
      error: null,
      showMask: true,
      mask: { title: '核销中', message: '正在核销，请稍候..', success: null },
    })

    return this.ensureLoginOrPrompt()
      .then((uid) => {
        if (!uid) return null
        const payload = this.data.payload
        if (payload.beId && payload.beId !== 'ota') {
          return ticketsApi
            .getOrderDetail({ order_id: payload.beId }, { showLoading })
            .then((res) => {
              if (!res || typeof res !== 'object') {
                ui.toast('订单详情返回异常')
                return
              }
              if (res.code !== 0) {
                ui.toast(res.msg || '获取订单失败')
                return
              }
              this.setData({ detail: normalizeOrderDetail(res.data, this.data.baseUrl) })
            })
            .catch(() => {})
            .then(() => this.writeOff({ showLoading }))
        }

        return this.writeOff({ showLoading }).then((res) => {
          if (!res || typeof res !== 'object') return
          if (payload.beId !== 'ota') return
          const nextId = res.code === 0 && res.data && res.data.id
          if (!nextId) return
          const nextPayload = { ...payload, beId: String(nextId) }
          this.setData({ payload: nextPayload })
          return ticketsApi
            .getOrderDetail({ order_id: nextPayload.beId }, { showLoading: false })
            .then((d) => {
              if (d && d.code === 0) this.setData({ detail: normalizeOrderDetail(d.data, this.data.baseUrl) })
            })
            .catch(() => {})
        })
      })
      .finally(() => this.setData({ submitting: false }))
  },
  writeOff({ showLoading = true } = {}) {
    const payload = this.data.payload
    if (!payload) return Promise.resolve()
    return ticketsApi
      .writeOff(
        {
          qrcode_str: payload.qrcodeStr,
          be_id: payload.beId,
          use_lat: payload.useLat,
          use_lng: payload.useLng,
        },
        { showLoading }
      )
      .then((res) => {
        if (!res || typeof res !== 'object') {
          this.setData({ error: '核销失败：返回数据异常', mask: { title: '核销失败', message: '返回数据异常', success: false } })
          return null
        }
        const info = res.data && typeof res.data === 'object' ? res.data : null
        const success = res.code === 0
        this.setData({
          writeoffInfo: info,
          writeoffDone: success,
          mask: { title: success ? '核销成功' : '核销失败', message: res.msg || (success ? '核销成功' : '核销失败'), success },
        })
        return res
      })
      .catch((err) => {
        this.setData({
          error: String((err && (err.errMsg || err.message)) || err),
          mask: { title: '核销失败', message: '核销失败，请稍后重试', success: false },
        })
        return null
      })
  },
  onTapMaskConfirm() {
    const success = this.data.mask && this.data.mask.success
    if (success) {
      this.setData({ showMask: false })
      return
    }
    wx.navigateBack()
  },
  onTapCopyTradeNo() {
    const no = this.data.detail && this.data.detail.tradeNo
    if (!no) return
    wx.setClipboardData({ data: String(no) })
  },
  onRetry() {
    if (!this.data.hasBaseUrl) return
    if (!this.data.hasLogin) return this.ensureLoginOrPrompt()
    if (this.data.writeoffDone) return
    this.init({ showLoading: true })
  },
})
