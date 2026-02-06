const config = require('../../../config')
const auth = require('../../../services/auth')
const ui = require('../../../utils/ui')
const locationService = require('../../../services/location')
const couponApi = require('../../../services/api/coupon')
const userApi = require('../../../services/api/user')

function normalizeRecord(item, baseUrl) {
  if (!item || typeof item !== 'object') return null
  const seller = item.Seller && typeof item.Seller === 'object' ? item.Seller : item.seller || {}
  const sellerImage = seller.image || ''
  const normalizedImage = baseUrl && sellerImage && /^https?:\/\//.test(sellerImage) ? sellerImage : sellerImage
  return {
    id: item.id,
    sellerId: seller.id,
    sellerName: seller.nickname || '',
    sellerImage: normalizedImage,
    createTimeText: item.create_time || '',
    raw: item,
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    couponId: '',
    promptText: '请确认打卡券是否是需要领取的券',
    latitude: 0,
    longitude: 0,

    list: [],
    submitting: false,
    empty: false,
    error: null,
  },
  onLoad(options) {
    const couponId = (options && (options.couponId || options.id || options.taskId)) || ''
    const title = options && options.couponTitle
    this.setData({
      couponId: String(couponId),
      promptText: title ? `${title}：请确认打卡券是否是需要领取的券` : this.data.promptText,
    })
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
  onTapGoLogin() {
    const redirect = `/subpackages/user/task/detail?couponId=${encodeURIComponent(String(this.data.couponId || ''))}`
    wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
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
    if (!this.data.couponId) {
      ui.toast('缺少 couponId')
      return false
    }
    return true
  },
  refresh({ showLoading = true } = {}) {
    if (!this.ensureReady()) return Promise.resolve()
    this.setData({ error: null, empty: false })
    return locationService
      .getLocation({ cacheFirst: true, promptSetting: true })
      .then((coord) => {
        this.setData({ latitude: coord.latitude, longitude: coord.longitude })
        return couponApi.getUserCouponRecordList(
          { userid: auth.getUid() || 0, couponId: this.data.couponId },
          { showLoading }
        )
      })
      .then((res) => {
        if (!res || typeof res !== 'object') {
          this.setData({ error: '返回数据异常' })
          return
        }
        if (res.code !== 0) {
          this.setData({ error: res.msg || '请求失败' })
          return
        }
        const data = Array.isArray(res.data) ? res.data : []
        const normalized = data.map((it) => normalizeRecord(it, this.data.baseUrl)).filter(Boolean)
        this.setData({ list: normalized, empty: normalized.length === 0 })
      })
      .catch((err) => this.setData({ error: String((err && (err.errMsg || err.message)) || err) }))
  },
  onTapScanClock() {
    if (!this.ensureReady()) return
    if (this.data.submitting) return
    this.setData({ submitting: true })

    wx.scanCode({
      success: (res) => {
        const result = res && res.result
        if (!result) {
          ui.toast('未识别到二维码内容')
          return
        }
        return userApi
          .userClock(
            {
              uid: auth.getUid() || 0,
              couponId: this.data.couponId,
              latitude: this.data.latitude,
              longitude: this.data.longitude,
              qrcode_url: result,
            },
            { showLoading: true }
          )
          .then((apiRes) => {
            if (!apiRes || typeof apiRes !== 'object') {
              ui.toast('返回数据异常')
              return
            }
            if (apiRes.code !== 0) {
              ui.showModal({ title: '提示', content: apiRes.msg || '打卡失败', showCancel: false })
              return
            }
            return ui
              .showModal({ title: '提示', content: apiRes.msg || '打卡成功', showCancel: false })
              .then(() => this.refresh({ showLoading: false }))
          })
      },
      fail: () => {},
      complete: () => {
        this.setData({ submitting: false })
      },
    })
  },
  onRetry() {
    this.refresh({ showLoading: false })
  },
})

