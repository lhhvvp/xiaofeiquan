const config = require('../../../config')
const auth = require('../../../services/auth')
const ui = require('../../../utils/ui')
const urlUtil = require('../../../utils/url')
const locationService = require('../../../services/location')
const userApi = require('../../../services/api/user')

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
    const ss = String(date.getSeconds()).padStart(2, '0')
    return `${y}-${m}-${d} ${hh}:${mm}:${ss}`
  }
  const parsed = Date.parse(String(input))
  if (!Number.isNaN(parsed)) return formatDateTime(parsed)
  return String(input)
}

function buildTimeText(issue) {
  if (!issue || typeof issue !== 'object') return ''
  const isPermanent = Number(issue.is_permanent)
  if (isPermanent === 1) return '有效期：永久'
  if (isPermanent === 2) {
    const start = Number(issue.coupon_time_start) || 0
    const end = Number(issue.coupon_time_end) || 0
    const startText = start ? formatDateTime(start) : '-'
    const endText = end ? formatDateTime(end) : '-'
    return `有效期：${startText} 至 ${endText}`
  }
  if (typeof issue.day !== 'undefined') return `有效期：${issue.day}天`
  return ''
}

function normalizeInfo(data, baseUrl) {
  if (!data || typeof data !== 'object') return null
  const issue = data.couponIssue && typeof data.couponIssue === 'object' ? data.couponIssue : {}
  const tour = data.tour && typeof data.tour === 'object' ? data.tour : {}
  const seller = data.seller && typeof data.seller === 'object' ? data.seller : {}
  const tourists = Array.isArray(data.tourist) ? data.tourist : []
  return {
    id: data.id,
    status: data.status,
    couponTitle: issue.coupon_title || '',
    remarkHtml: urlUtil.normalizeRichTextHtml(issue.remark, baseUrl),
    timeText: buildTimeText(issue),
    number: tourists.length,
    tourName: tour.name || '',
    tourTerm: tour.term || '',
    sellerName: seller.nickname || '',
    raw: data,
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    id: '',
    mid: 0,
    qrcodeUrl: '',
    coord: null,
    vrLatitude: 0,
    vrLongitude: 0,

    info: null,
    showMask: false,
    mask: { title: '请等候...', message: '正在核销，请稍候..', success: null },
    writeoffDone: false,
    submitting: false,
    error: null,
  },
  onLoad(options) {
    const id = (options && options.id) || ''
    const mid = Number(options && options.mid) || 0
    let qrcodeUrl = (options && (options.qrcode_url || options.qrcodeUrl)) || ''
    try {
      qrcodeUrl = decodeURIComponent(String(qrcodeUrl))
    } catch (e) {
      qrcodeUrl = String(qrcodeUrl)
    }
    const coord = safeParseJson(options && options.coord)

    this.setData({ id: String(id), mid, qrcodeUrl: String(qrcodeUrl), coord })

    if (!this.data.id || !this.data.mid || !this.data.qrcodeUrl) {
      ui
        .showModal({ title: '提示', content: '参数错误（缺少 id/mid/qrcode_url）', showCancel: false })
        .then(() => wx.navigateBack())
      return
    }

    this.coordPromise = locationService.getLocation().then((c) => {
      this.setData({ vrLatitude: c.latitude, vrLongitude: c.longitude })
      return c
    })
  },
  onShow() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    this.setData({ hasLogin })
    if (!hasLogin) return
    if (!this.data.hasBaseUrl) return
    if (this.data.writeoffDone) return
    if (!this.data.id || !this.data.mid || !this.data.qrcodeUrl) return
    this.init({ showLoading: false })
  },
  buildRedirectUrl() {
    const coord = this.data.coord ? encodeURIComponent(JSON.stringify(this.data.coord)) : ''
    return (
      `/subpackages/coupon/coupon_CAV_Group/coupon_CAV_Group` +
      `?id=${encodeURIComponent(String(this.data.id || ''))}` +
      `&mid=${encodeURIComponent(String(this.data.mid || ''))}` +
      `&qrcode_url=${encodeURIComponent(String(this.data.qrcodeUrl || ''))}` +
      `&coord=${coord}`
    )
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

    this.setData({
      submitting: true,
      error: null,
      showMask: false,
      mask: { title: '核销中', message: '正在核销，请稍候..', success: null },
    })

    return this.ensureLoginOrPrompt()
      .then((uid) => {
        if (!uid) return null
        return userApi
          .tourCouponGroup({ id: this.data.id }, { showLoading })
          .then((res) => {
            if (!res || typeof res !== 'object') {
              ui.toast('返回数据异常')
              return null
            }
            if (res.code !== 0) {
              ui.toast(res.msg || '请求失败')
              return null
            }
            const info = normalizeInfo(res.data, this.data.baseUrl)
            if (!info) {
              ui.toast('返回数据异常')
              return null
            }
            this.setData({ info })
            this.setData({ showMask: true, mask: { title: '核销中', message: '正在核销，请稍候..', success: null } })
            return this.writeoff({ showLoading })
          })
          .catch(() => null)
      })
      .finally(() => this.setData({ submitting: false }))
  },
  writeoff({ showLoading = true } = {}) {
    const uid = auth.getUid()
    const coord = this.data.coord || {}
    const latitude = Number(coord.latitude)
    const longitude = Number(coord.longitude)
    return Promise.resolve(this.coordPromise)
      .then(() =>
        userApi.writeoffTour(
          {
            userid: uid,
            mid: this.data.mid,
            coupon_issue_user_id: this.data.id,
            use_min_price: 999999,
            qrcode_url: this.data.qrcodeUrl,
            orderid: 0,
            latitude: Number.isFinite(latitude) ? latitude : 0,
            longitude: Number.isFinite(longitude) ? longitude : 0,
            vr_latitude: this.data.vrLatitude,
            vr_longitude: this.data.vrLongitude,
          },
          { showLoading }
        )
      )
      .then((res) => {
        if (!res || typeof res !== 'object') {
          this.setData({ error: '核销失败：返回数据异常', mask: { title: '核销失败', message: '返回数据异常', success: false } })
          return
        }
        const success = res.code === 0
        this.setData({
          writeoffDone: success,
          mask: { title: success ? '核销成功' : '核销失败', message: res.msg || (success ? '核销成功' : '核销失败'), success },
        })
      })
      .catch(() => this.setData({ error: '核销失败，请稍后重试', mask: { title: '核销失败', message: '核销失败，请稍后重试', success: false } }))
  },
  onTapMaskConfirm() {
    const success = this.data.mask && this.data.mask.success
    if (success) {
      this.setData({ showMask: false })
      return
    }
    wx.navigateBack()
  },
  onRetry() {
    this.init({ showLoading: true })
  },
})

