const config = require('../../../config')
const auth = require('../../../services/auth')
const ui = require('../../../utils/ui')
const locationService = require('../../../services/location')
const qrcodeUtil = require('../../../utils/qrcode')
const apptApi = require('../../../services/api/appt')

function buildSubscribeQrText({ qrcodeStr, beId, useLat, useLng } = {}) {
  return JSON.stringify({
    qrcode_str: qrcodeStr,
    be_type: 'all',
    be_id: beId,
    use_lat: useLat,
    use_lng: useLng,
    type: 'subscribe',
  })
}

function normalizeDetail(data) {
  if (!data || typeof data !== 'object') return null
  return {
    id: data.id,
    status: data.status,
    statusText: data.status_text || '',
    fullname: data.fullname || '',
    phone: data.phone || '',
    number: data.number || 0,
    code: data.code || '',
    start: data.start || '',
    timeEndText: data.time_end_text || '',
    qrcodeStr: data.qrcode_str || '',
    touristList: Array.isArray(data.tourist_list) ? data.tourist_list : [],
    raw: data,
  }
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
    qrText: '',
    error: null,
    canceling: false,
  },
  onLoad(options) {
    const id = (options && (options.id || options.log_id)) || ''
    this.setData({ id: String(id) })
    wx.setNavigationBarTitle({ title: '预约详情' })

    this.coordPromise = locationService.getLocation().then((coord) => {
      this.setData({ useLat: coord.latitude, useLng: coord.longitude })
      return coord
    })
  },
  onShow() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    this.setData({ hasLogin })
    if (hasLogin) this.fetchDetail({ showLoading: false })
  },
  onPullDownRefresh() {
    this.fetchDetail({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
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
    if (!this.data.id) return false
    return true
  },
  onTapGoLogin() {
    const redirect = `/subpackages/user/subscribe/detail?id=${encodeURIComponent(String(this.data.id || ''))}`
    wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
  },
  fetchDetail({ showLoading = true } = {}) {
    if (!this.ensureReady()) return Promise.resolve()
    this.setData({ error: null })
    return Promise.resolve(this.coordPromise)
      .then(() => apptApi.getDetail({ id: this.data.id }, { showLoading }))
      .then((res) => {
        if (!res || typeof res !== 'object') {
          this.setData({ error: '返回数据异常' })
          return
        }
        if (res.code !== 0) {
          this.setData({ error: res.msg || '请求失败' })
          return
        }
        const normalized = normalizeDetail(res.data)
        if (!normalized) {
          this.setData({ error: '返回数据异常' })
          return
        }
        this.setData({ detail: normalized })

        if (String(normalized.status) === '0' && normalized.qrcodeStr) {
          const qrText = buildSubscribeQrText({
            qrcodeStr: normalized.qrcodeStr,
            beId: normalized.id,
            useLat: this.data.useLat,
            useLng: this.data.useLng,
          })
          this.setData({ qrText })
          return qrcodeUtil.drawToCanvas({ canvasId: 'qrcodeCanvas', text: qrText, size: 260, margin: 8, page: this })
        }

        this.setData({ qrText: '' })
      })
      .catch((err) => this.setData({ error: String((err && (err.errMsg || err.message)) || err) }))
  },
  onTapCopyCode() {
    const code = this.data.detail && this.data.detail.code
    if (!code) return
    wx.setClipboardData({ data: String(code) })
  },
  onTapCopyQrText() {
    const text = this.data.qrText
    if (!text) return
    wx.setClipboardData({ data: String(text) })
  },
  onTapOpenQrPage() {
    const text = this.data.qrText
    if (!text) return
    wx.navigateTo({ url: `/subpackages/user/qrcode?text=${encodeURIComponent(String(text))}&title=${encodeURIComponent('预约核销码')}` })
  },
  onTapCancel() {
    if (this.data.canceling) return
    const detail = this.data.detail
    if (!detail) return
    if (String(detail.status) !== '0') return

    ui
      .showModal({
        title: '提示',
        content: '确定取消预约吗？',
        confirmText: '取消预约',
        cancelText: '返回',
      })
      .then((res) => {
        if (!res || !res.confirm) return
        this.setData({ canceling: true })
        return apptApi.cancelAppt({ log_id: detail.id }, { showLoading: true }).then((apiRes) => {
          if (!apiRes || typeof apiRes !== 'object') {
            ui.toast('返回数据异常')
            return
          }
          if (apiRes.code !== 0) {
            ui.toast(apiRes.msg || '取消失败')
            return
          }
          ui.toast(apiRes.msg || '已取消', { icon: 'success' })
          setTimeout(() => wx.navigateBack(), 600)
        })
      })
      .catch(() => {})
      .finally(() => this.setData({ canceling: false }))
  },
  onRetry() {
    this.fetchDetail({ showLoading: false })
  },
})

