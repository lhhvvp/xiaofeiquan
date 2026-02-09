const config = require('../../config')
const request = require('../../services/request')
const ui = require('../../utils/ui')
const paymentUtil = require('../../utils/payment')

function decodeScene(rawScene) {
  if (!rawScene) return ''
  try {
    return decodeURIComponent(rawScene)
  } catch (e) {
    return String(rawScene)
  }
}

function parseScene(sceneStr) {
  if (!sceneStr) return {}
  const obj = {}
  String(sceneStr)
    .split('*')
    .filter(Boolean)
    .forEach((part) => {
      const seg = part.split('/')
      if (seg.length >= 2) obj[seg[0]] = seg[1]
    })
  return obj
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    sceneRaw: '',
    scene: {},
    tradeNo: '',
    detail: null,
    loading: false,
    paying: false,
    error: '',
  },
  onLoad(options) {
    const sceneRaw = decodeScene(options && options.scene)
    const scene = parseScene(sceneRaw)
    const tradeNo =
      (options && (options.trade_no || options.tradeNo || options.id)) || (scene && scene.trade_no) || ''
    this.setData({
      sceneRaw,
      scene,
      tradeNo: String(tradeNo),
    })
    this.fetchDetail()
  },
  onPullDownRefresh() {
    this.fetchDetail({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  fetchDetail({ showLoading = true } = {}) {
    if (!this.data.hasBaseUrl) return Promise.resolve()
    if (!this.data.tradeNo) {
      this.setData({ error: '参数异常：缺少 trade_no' })
      return Promise.resolve()
    }
    this.setData({ loading: true, error: '' })
    return request({
      path: '/ticket/getTravelOrderDetail',
      method: 'GET',
      data: { trade_no: this.data.tradeNo },
      showLoading,
    })
      .then((res) => {
        if (!res || typeof res !== 'object' || res.code !== 0 || !res.data) {
          this.setData({ error: (res && res.msg) || '查询失败' })
          return
        }
        this.setData({ detail: res.data })
      })
      .catch((err) => this.setData({ error: String((err && (err.errMsg || err.message)) || err) }))
      .finally(() => this.setData({ loading: false }))
  },
  copyTradeNo() {
    const tradeNo = this.data.detail && this.data.detail.trade_no
    if (!tradeNo) return
    wx.setClipboardData({ data: String(tradeNo) })
  },
  orderPay() {
    if (this.data.paying) return
    if (!this.data.tradeNo) {
      ui.toast('缺少订单号')
      return
    }

    this.setData({ paying: true })
    wx.login({
      success: (loginRes) => {
        const code = loginRes && loginRes.code
        if (!code) {
          this.setData({ paying: false })
          ui.toast('获取登录态失败')
          return
        }
        request({
          path: '/ticket/travelOrderPay',
          method: 'POST',
          data: {
            code,
            trade_no: this.data.tradeNo,
          },
          showLoading: true,
        })
          .then((res) => {
            if (!res || typeof res !== 'object' || res.code !== 0) {
              ui.toast((res && res.msg) || '支付失败')
              return
            }
            return paymentUtil.requestWxPayment(res.data || res).then(() => {
              wx.redirectTo({ url: '/pages/user/paySuccess?order=1' })
            })
          })
          .catch(() => ui.toast('支付失败'))
          .finally(() => this.setData({ paying: false }))
      },
      fail: () => {
        this.setData({ paying: false })
        ui.toast('获取登录态失败')
      },
    })
  },
})
