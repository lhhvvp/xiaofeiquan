const config = require('../../config')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')
const urlUtil = require('../../utils/url')
const locationService = require('../../services/location')
const ticketsApi = require('../../services/api/tickets')
const qrcodeUtil = require('../../utils/qrcode')
const paymentUtil = require('../../utils/payment')

function waitForNextTick() {
  return new Promise((resolve) => {
    if (typeof wx.nextTick === 'function') {
      wx.nextTick(() => resolve())
      return
    }
    setTimeout(resolve, 30)
  })
}

function refundProgressText(val) {
  switch (String(val || '')) {
    case 'pending_review':
      return '退款状态：审核中'
    case 'refuse':
      return '退款状态：已拒绝'
    case 'approved':
      return '退款状态：已通过'
    case 'completed':
      return '退款状态：完成退款'
    case 'init':
      return '退款状态：未退款'
    default:
      return ''
  }
}

function normalizeRightsOptions(list) {
  const raw = Array.isArray(list) ? list : []
  return raw
    .map((item) => {
      if (!item || typeof item !== 'object') return null
      const rightsNum = Number(item.rights_num || 0)
      const writeoffNum = Number(item.writeoff_num || 0)
      const disabled = Number.isFinite(rightsNum) && Number.isFinite(writeoffNum) ? rightsNum === writeoffNum : false
      return {
        id: item.id,
        value: item.rights_id,
        text: item.rights_title || '',
        qrcodeStr: item.qrcode_str || '',
        disabled,
      }
    })
    .filter(Boolean)
}

function normalizeTourist(item) {
  if (!item || typeof item !== 'object') return null
  const refundProgress = item.refund_progress
  const refundKey = String(refundProgress || '')
  const enterTime = Number(item.enter_time || 0)
  return {
    id: item.id,
    fullname: item.tourist_fullname || '',
    mobile: item.tourist_mobile || '',
    qrcodeStr: item.qrcode_str || '',
    rightsList: Array.isArray(item.rights_list) ? item.rights_list : [],
    refundProgress,
    refundText: refundProgressText(refundProgress),
    outTradeNo: item.out_trade_no,
    canCancelRefund: refundKey === 'pending_review',
    canRefund: enterTime === 0 && refundKey !== 'pending_review' && refundKey !== 'completed',
    enterTime,
    raw: item,
  }
}

function normalizeDetail(data, baseUrl) {
  if (!data || typeof data !== 'object') return null
  const seller = data.seller && typeof data.seller === 'object' ? data.seller : {}
  const ticketInfo = data.ticket_info && typeof data.ticket_info === 'object' ? data.ticket_info : {}
  const detailList = Array.isArray(data.detail_list) ? data.detail_list : []
  const orderStatus = data.order_status || ''
  const tourists = detailList.map(normalizeTourist).filter(Boolean)
  const canRefundAll = orderStatus === 'paid' && tourists.length > 0 && tourists.every((t) => t && t.canRefund)

  return {
    id: data.id,
    orderStatus,
    orderStatusText: data.order_status_text || '',
    amountPrice: data.amount_price || '',
    sellerNickname: seller.nickname || '',
    sellerImage: urlUtil.normalizeNetworkUrl(seller.image, baseUrl),
    ticketTitle: ticketInfo.title || '',
    ticketDate: ticketInfo.date || '',
    ticketCount: detailList.length,
    tradeNo: data.trade_no || '',
    outTradeNo: data.out_trade_no,
    qrcodeStr: data.qrcode_str || '',
    explainBuyHtml: urlUtil.normalizeRichTextHtml(ticketInfo.explain_buy, baseUrl),
    explainUseHtml: urlUtil.normalizeRichTextHtml(ticketInfo.explain_use, baseUrl),
    rightsOptions: normalizeRightsOptions(data.rights_qrcode_list),
    tourists,
    canRefundAll,
    raw: data,
  }
}

function buildQrPayload({ qrcodeStr, beId, useLat, useLng, type, id }) {
  const payload = { qrcode_str: qrcodeStr, be_id: beId, use_lat: useLat, use_lng: useLng, type }
  if (id) payload.id = id
  return JSON.stringify(payload)
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    id: '',
    useLat: 0,
    useLng: 0,

    detail: null,
    rightsOptions: [],
    rightIndex: 0,
    qrcodeDisabled: false,
    orderQrText: '',
    error: null,

    paying: false,

    qrModal: {
      show: false,
      title: '',
      qrText: '',
    },
  },
  onLoad(options) {
    const id = (options && (options.id || options.order_id)) || ''
    this.setData({ id: String(id) })
    this.coordPromise = locationService.getLocation().then((coord) => {
      this.setData({ useLat: coord.latitude, useLng: coord.longitude })
      return coord
    })
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
  onPullDownRefresh() {
    this.fetchDetail({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  ensureLoginOrPrompt() {
    const uid = auth.getUid()
    if (uid) return Promise.resolve(uid)
    return ui
      .showModal({
        title: '提示',
        content: '查看订单需要先登录，是否现在去登录？',
        confirmText: '去登录',
        cancelText: '取消',
      })
      .then((res) => {
        if (res && res.confirm) {
          const redirect = `/subpackages/user/order_detail?id=${encodeURIComponent(String(this.data.id || ''))}`
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
        return Promise.resolve(this.coordPromise).then(() => ticketsApi.getOrderDetail({ order_id: this.data.id }, { showLoading }))
      })
      .then((res) => {
        if (!res) return
        const body = res && typeof res === 'object' && typeof res.code !== 'undefined' ? res : null
        if (body && body.code !== 0) {
          ui.toast(body.msg || '请求失败')
          return
        }
        const raw = body ? body.data : res && res.data ? res.data : res
        const normalized = normalizeDetail(raw, this.data.baseUrl)
        if (!normalized) {
          ui.toast('返回数据异常')
          return
        }

        const rightsOptions = normalized.rightsOptions || []
        let rightIndex = this.data.rightIndex || 0
        if (rightsOptions.length) {
          const exists = rightsOptions[rightIndex]
          if (!exists) rightIndex = 0
        } else {
          rightIndex = 0
        }

        const selected = rightsOptions.length ? rightsOptions[rightIndex] : null
        const useLat = this.data.useLat
        const useLng = this.data.useLng

        let orderQrText = ''
        let qrcodeDisabled = false

        if (selected && selected.qrcodeStr) {
          qrcodeDisabled = !!selected.disabled
          orderQrText = buildQrPayload({
            qrcodeStr: selected.qrcodeStr,
            beId: selected.id,
            useLat,
            useLng,
            type: 'order',
          })
        } else if (normalized.qrcodeStr) {
          orderQrText = buildQrPayload({
            qrcodeStr: normalized.qrcodeStr,
            beId: normalized.id,
            useLat,
            useLng,
            type: 'order',
          })
        }

        this.setData({
          detail: normalized,
          rightsOptions,
          rightIndex,
          qrcodeDisabled,
          orderQrText,
        })

        if (normalized.orderStatus === 'paid' && orderQrText && !qrcodeDisabled) {
          return this.drawOrderQrCode(orderQrText)
        }
      })
      .catch((err) => {
        this.setData({ error: String((err && (err.errMsg || err.message)) || err) })
      })
  },
  drawOrderQrCode(text) {
    const value = String(text || '').trim()
    if (!value) return Promise.resolve()
    return waitForNextTick().then(() =>
      qrcodeUtil.drawToCanvas({
        canvasId: 'orderQrcode',
        text: value,
        size: 220,
        margin: 0,
        page: this,
      })
    )
  },
  drawTouristQrCode(text) {
    const value = String(text || '').trim()
    if (!value) return Promise.resolve()
    return waitForNextTick().then(() =>
      qrcodeUtil.drawToCanvas({
        canvasId: 'touristQrcode',
        text: value,
        size: 220,
        margin: 0,
        page: this,
      })
    )
  },
  onRightChange(e) {
    const index = Number(e && e.detail && e.detail.value)
    const rightsOptions = this.data.rightsOptions || []
    if (!Number.isInteger(index) || index < 0 || index >= rightsOptions.length) return
    const selected = rightsOptions[index]
    if (selected && selected.disabled) {
      ui.toast('该二维码已核销，请选择其他二维码')
      return
    }
    this.setData({ rightIndex: index, qrcodeDisabled: false })

    if (!this.data.detail) return
    const useLat = this.data.useLat
    const useLng = this.data.useLng
    const orderQrText = buildQrPayload({
      qrcodeStr: selected.qrcodeStr,
      beId: selected.id,
      useLat,
      useLng,
      type: 'order',
    })
    this.setData({ orderQrText })
    if (this.data.detail.orderStatus === 'paid') this.drawOrderQrCode(orderQrText)
  },
  onTapCopyTradeNo() {
    const no = this.data.detail && this.data.detail.tradeNo
    if (!no) return
    wx.setClipboardData({ data: String(no) })
  },
  onTapRefresh() {
    this.fetchDetail({ showLoading: false })
  },
  onTapShowTouristQr(e) {
    const index = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.index
    const detail = this.data.detail
    if (!detail) return
    const tourists = detail.tourists || []
    const tourist = tourists[index]
    if (!tourist) return

    const useLat = this.data.useLat
    const useLng = this.data.useLng

    let qrText = ''
    let name = `${tourist.fullname}-${tourist.mobile}`

    const rightsList = tourist.rightsList || []
    if (!rightsList.length) {
      if (!tourist.qrcodeStr) {
        ui.toast('暂无核销码')
        return
      }
      qrText = buildQrPayload({
        qrcodeStr: tourist.qrcodeStr,
        beId: tourist.id,
        useLat,
        useLng,
        type: 'order_user',
        id: detail.id,
      })
    } else {
      const selected = (this.data.rightsOptions || [])[this.data.rightIndex]
      const selectedValue = selected && selected.value
      const found = rightsList.find((r) => String(r && r.rights_id) === String(selectedValue))
      if (!found) {
        ui.toast('未找到对应权益码')
        return
      }
      if (Number(found.status)) {
        ui.toast('该二维码已核销，请选择其他二维码')
        return
      }
      name = found.rights_title ? `${name}（${found.rights_title}）` : name
      qrText = buildQrPayload({
        qrcodeStr: found.qrcode_str,
        beId: found.id,
        useLat,
        useLng,
        type: 'order_user',
        id: found.detail_id,
      })
    }

    this.setData({ qrModal: { show: true, title: name, qrText } })
    this.drawTouristQrCode(qrText).catch(() => ui.toast('绘制失败'))
  },
  onCloseQrModal() {
    this.setData({ qrModal: { show: false, title: '', qrText: '' } })
  },
  onTapCancelRefund(e) {
    const index = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.index
    const detail = this.data.detail
    if (!detail) return
    const tourists = detail.tourists || []
    const tourist = tourists[index]
    if (!tourist || !tourist.canCancelRefund) return

    ui
      .showModal({
        title: '提示',
        content: '您确定要取消退款吗？',
        confirmText: '确定',
        cancelText: '取消',
      })
      .then((res) => {
        if (!res || !res.confirm) return
        return ticketsApi.cancelRefund({ type: 'order_detail', id: tourist.id }, { showLoading: true }).then((apiRes) => {
          if (!apiRes || typeof apiRes !== 'object') {
            ui.toast('返回数据异常')
            return
          }
          if (apiRes.code !== 0) {
            ui.toast(apiRes.msg || '操作失败')
            return
          }
          ui.toast(apiRes.msg || '已取消退款')
          this.fetchDetail({ showLoading: false })
        })
      })
      .catch(() => {})
  },
  onTapRefundSingle(e) {
    const index = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.index
    const detail = this.data.detail
    if (!detail || detail.orderStatus !== 'paid') return
    const tourist = detail.tourists && detail.tourists[index]
    if (!tourist || !tourist.canRefund) return

    const tradeNo = tourist.outTradeNo || detail.outTradeNo || detail.tradeNo
    if (!tradeNo) {
      ui.toast('缺少订单号')
      return
    }

    ui
      .showModal({
        title: '提示',
        content: '您确定要退款吗？',
        confirmText: '确定',
        cancelText: '取消',
      })
      .then((res) => {
        if (!res || !res.confirm) return
        wx.navigateTo({
          url:
            `/pages/user/refunded?trade_no=${encodeURIComponent(String(tradeNo))}` +
            `&single=${encodeURIComponent('true')}`,
        })
      })
      .catch(() => {})
  },
  onTapRefundAll() {
    const detail = this.data.detail
    if (!detail || detail.orderStatus !== 'paid' || !detail.canRefundAll) return
    const tradeNo = detail.outTradeNo || detail.tradeNo
    if (!tradeNo) {
      ui.toast('缺少订单号')
      return
    }

    ui
      .showModal({
        title: '提示',
        content: '您确定要全部退款吗？',
        confirmText: '确定',
        cancelText: '取消',
      })
      .then((res) => {
        if (!res || !res.confirm) return
        wx.navigateTo({ url: `/pages/user/refunded?trade_no=${encodeURIComponent(String(tradeNo))}` })
      })
      .catch(() => {})
  },
  onTapOrderPay() {
    const detail = this.data.detail
    if (!detail || detail.orderStatus !== 'created') return
    if (this.data.paying) return

    const user = auth.getUser()
    const uuid = user && user.uuid
    const openid = user && user.openid
    if (!uuid || !openid) {
      ui.toast('登录信息缺失（openid/uuid）')
      return
    }

    this.setData({ paying: true })

    ticketsApi
      .orderPay({ uuid, openid, trade_no: detail.tradeNo }, { showLoading: true })
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          return
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '支付失败')
          return
        }
        return paymentUtil.requestWxPayment(res).then(
          () => wx.redirectTo({ url: '/pages/user/paySuccess' }),
          (err) => {
            const msg = String((err && (err.errMsg || err.message)) || err)
            if (msg.indexOf('cancel') >= 0) {
              ui.toast('已取消支付')
              return
            }
            ui.toast('支付失败，请稍后重试')
          }
        )
      })
      .catch(() => ui.toast('支付失败，请稍后重试'))
      .finally(() => this.setData({ paying: false }))
  },
  onRetry() {
    this.fetchDetail({ showLoading: false })
  },
  noop() {},
})
