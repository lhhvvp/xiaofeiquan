const config = require('../../config')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')
const urlUtil = require('../../utils/url')
const userApi = require('../../services/api/user')

const FALLBACK_ICON = 'https://oss.wlxfq.dianfengcms.com/admins/d2d20480c4d629fc0336b4b639498830.png'

function formatDateTime(input) {
  if (input === null || typeof input === 'undefined') return ''
  if (typeof input === 'number') {
    const date = new Date(input < 1e12 ? input * 1000 : input)
    if (Number.isNaN(date.getTime())) return String(input)
    const y = String(date.getFullYear())
    const m = String(date.getMonth() + 1).padStart(2, '0')
    const d = String(date.getDate()).padStart(2, '0')
    const hh = String(date.getHours()).padStart(2, '0')
    const mm = String(date.getMinutes()).padStart(2, '0')
    return `${y}-${m}-${d} ${hh}:${mm}`
  }
  const parsed = Date.parse(String(input))
  if (!Number.isNaN(parsed)) return formatDateTime(parsed)
  return String(input)
}

function normalizeDetail(data, baseUrl) {
  if (!data || typeof data !== 'object') return null
  const detail = data.detail && typeof data.detail === 'object' ? data.detail : {}
  const paymentStatus = Number(data.payment_status)
  const statusText = paymentStatus === 0 ? '未支付' : paymentStatus === 1 ? '已支付' : String(data.payment_status || '')
  return {
    title: detail.coupon_title || '',
    icon: urlUtil.normalizeNetworkUrl(detail.coupon_icon, baseUrl) || FALLBACK_ICON,
    price: data.origin_price || '',
    statusText,
    orderNo: data.order_no || '',
    createTimeText: formatDateTime(data.create_time),
    payTimeText: formatDateTime(data.update_time),
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,
    orderNo: '',
    detail: null,
    error: null,
  },
  onLoad(options) {
    const orderNo = (options && (options.order_no || options.orderNo)) || ''
    this.setData({ orderNo: String(orderNo) })
  },
  onShow() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    this.setData({ hasLogin })
    if (!hasLogin) return
    if (!this.data.hasBaseUrl) return
    if (!this.data.orderNo) return
    this.fetchDetail({ showLoading: false })
  },
  ensureLoginOrPrompt() {
    const uid = auth.getUid()
    if (uid) return Promise.resolve(uid)
    return ui
      .showModal({
        title: '提示',
        content: '查看订单详情需要先登录，是否现在去登录？',
        confirmText: '去登录',
        cancelText: '取消',
      })
      .then((res) => {
        if (res && res.confirm) {
          const redirect = `/subpackages/user/pay_detail?order_no=${encodeURIComponent(String(this.data.orderNo || ''))}`
          wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
        }
        return null
      })
  },
  fetchDetail({ showLoading = true } = {}) {
    if (!this.data.orderNo) return Promise.resolve()
    const uid = auth.getUid()
    if (!uid) return Promise.resolve()
    this.setData({ error: null })
    return userApi
      .getCouponOrderDetail({ uid, order_no: this.data.orderNo }, { showLoading })
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          return
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '请求失败')
          return
        }
        const normalized = normalizeDetail(res.data, this.data.baseUrl)
        this.setData({ detail: normalized })
        if (normalized && normalized.title) wx.setNavigationBarTitle({ title: normalized.title })
      })
      .catch((err) => {
        this.setData({ error: String((err && (err.errMsg || err.message)) || err) })
      })
  },
  onRetry() {
    this.fetchDetail({ showLoading: false })
  },
})

