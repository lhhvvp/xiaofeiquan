const config = require('../../config')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')
const locationService = require('../../services/location')
const couponApi = require('../../services/api/coupon')
const urlUtil = require('../../utils/url')

function formatDateTime(input) {
  if (input === null || typeof input === 'undefined') return ''
  if (typeof input === 'number') {
    const date = new Date(input < 1e12 ? input * 1000 : input)
    if (Number.isNaN(date.getTime())) return String(input)
    const m = String(date.getMonth() + 1).padStart(2, '0')
    const d = String(date.getDate()).padStart(2, '0')
    const hh = String(date.getHours()).padStart(2, '0')
    const mm = String(date.getMinutes()).padStart(2, '0')
    return `${m}-${d} ${hh}:${mm}`
  }
  const parsed = Date.parse(String(input))
  if (!Number.isNaN(parsed)) return formatDateTime(parsed)
  return String(input)
}

function normalizeDetail(data, baseUrl) {
  if (!data || typeof data !== 'object') return null
  const couponIssueId = data.coupon_issue_id
  return {
    couponTitle: data.coupon_title || '',
    couponPrice: data.coupon_price || '',
    createTimeText: formatDateTime(data.create_time),
    couponIssueId: Number(couponIssueId) || 0,
    remarkHtml: urlUtil.normalizeRichTextHtml(data.remark, baseUrl),
    raw: data,
  }
}

function normalizeSuitable(list) {
  const first = Array.isArray(list) ? list[0] : null
  if (!first || typeof first !== 'object') return { address: '', distanceText: '' }
  const distance = Number(first.distance)
  return {
    address: first.nickname || '',
    distanceText: Number.isFinite(distance) ? `${distance.toFixed(2)}km` : '',
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    id: '',
    mid: 0,
    coord: { latitude: 0, longitude: 0 },

    detail: null,
    suitable: { address: '', distanceText: '' },
    error: null,
  },
  onLoad(options) {
    const id = (options && options.id) || ''
    const mid = Number(options && options.mid) || 0
    this.setData({ id: String(id), mid })
    this.coordPromise = locationService.getLocation().then((coord) => {
      this.setData({ coord })
      return coord
    })

    if (!id || !mid) {
      ui.showModal({ title: '提示', content: '参数错误（缺少 id/mid）', showCancel: false }).then(() => wx.navigateBack())
    }
  },
  onShow() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    this.setData({ hasLogin })
    if (!hasLogin) return
    if (!this.data.hasBaseUrl) return
    if (!this.data.id || !this.data.mid) return
    this.fetchDetail({ showLoading: false })
  },
  buildRedirectUrl() {
    return (
      `/subpackages/user/order_CAV_info?id=${encodeURIComponent(String(this.data.id || ''))}` +
      `&mid=${encodeURIComponent(String(this.data.mid || ''))}`
    )
  },
  ensureLoginOrPrompt() {
    const uid = auth.getUid()
    if (uid) return Promise.resolve(uid)
    return ui
      .showModal({
        title: '提示',
        content: '查看核销详情需要先登录，是否现在去登录？',
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
  fetchDetail({ showLoading = true } = {}) {
    if (!this.data.id) return Promise.resolve()
    this.setData({ error: null })
    return this.ensureLoginOrPrompt()
      .then((uid) => {
        if (!uid) return null
        return couponApi.getWriteoffDetail(
          { userid: uid, mid: this.data.mid, id: this.data.id },
          { showLoading }
        )
      })
      .then((res) => {
        if (!res) return null
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          return null
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '请求失败')
          return null
        }

        const detail = normalizeDetail(res.data, this.data.baseUrl)
        if (!detail) {
          ui.toast('返回数据异常')
          return null
        }
        this.setData({ detail })
        return detail
      })
      .then((detail) => {
        if (!detail || !detail.couponIssueId) return
        return Promise.resolve(this.coordPromise)
          .then((coord) =>
            couponApi.getApplicableMerchants(
              { id: detail.couponIssueId, latitude: coord.latitude, longitude: coord.longitude, page: 0, limit: 1 },
              { showLoading: false }
            )
          )
          .then((res) => {
            if (!res || typeof res !== 'object') return
            if (res.code !== 0) return
            this.setData({ suitable: normalizeSuitable(res.data) })
          })
          .catch(() => {})
      })
      .catch((err) => {
        this.setData({ error: String((err && (err.errMsg || err.message)) || err) })
      })
  },
  onTapOpenApplicableMerchants() {
    const id = this.data.detail && this.data.detail.couponIssueId
    if (!id) return
    wx.navigateTo({ url: `/subpackages/coupon/list?id=${encodeURIComponent(String(id))}` })
  },
  onRetry() {
    this.fetchDetail({ showLoading: false })
  },
})

