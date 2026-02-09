const config = require('../../../config')
const auth = require('../../../services/auth')
const request = require('../../../services/request')
const ui = require('../../../utils/ui')

const TOURIST_KEY = '__group_tourists'
const HOTEL_CTX_KEY = '__group_hotel_ctx'

function safeSetStorage(key, value) {
  try {
    wx.setStorageSync(key, value)
  } catch (e) {}
}

function normalizeGuideItem(item) {
  if (!item || typeof item !== 'object') return null
  const tour = item.tour && typeof item.tour === 'object' ? item.tour : {}
  const tourist = Array.isArray(item.tourist) ? item.tourist : []
  const spotIds = String(tour.spot_ids || '')
    .split(',')
    .map((it) => String(it || '').trim())
    .filter(Boolean)
  return {
    id: tour.id || item.id || item.tid || '',
    tid: item.tid || tour.id || item.id || '',
    title: tour.name || '旅行团',
    createTime: item.create_time || '',
    ticketCount: spotIds.length || tourist.length || 0,
    planner: tour.planner || '',
    mobile: tour.mobile || '',
    status: Number(tour.status || 0),
    tourist,
    raw: item,
  }
}

function normalizeCouponFallback(item) {
  if (!item || typeof item !== 'object') return null
  const issue = item.couponIssue && typeof item.couponIssue === 'object' ? item.couponIssue : {}
  const cid = Number(issue.cid || item.cid || 0)
  return {
    id: item.id || '',
    tid: item.id || '',
    title: item.coupon_title || issue.coupon_title || '旅行团消费券',
    createTime: item.create_time || '',
    ticketCount: 1,
    planner: '',
    mobile: '',
    status: 0,
    tourist: [],
    cid,
    raw: item,
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,
    loading: false,
    list: [],
    empty: false,
    error: '',
  },
  onShow() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    this.setData({ hasLogin })
    if (!hasLogin) return
    this.fetchList()
  },
  onPullDownRefresh() {
    this.fetchList({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  ensureReady() {
    if (!this.data.hasBaseUrl) {
      ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 miniprogram/config/local.js 并设置 { baseUrl }',
        showCancel: false,
      })
      return false
    }
    if (!this.data.hasLogin) {
      ui.toast('请先登录')
      auth.reLaunchLogin({ redirect: '/pages/user/GroupCoupon/GroupCoupon' })
      return false
    }
    return true
  },
  fetchList({ showLoading = true } = {}) {
    if (!this.ensureReady()) return Promise.resolve()
    if (this.data.loading) return Promise.resolve()

    const uid = auth.getUid()
    this.setData({ loading: true, empty: false, error: '' })

    return request({
      path: '/user/guide_tour',
      method: 'POST',
      data: { uid },
      showLoading,
    })
      .then((res) => {
        if (res && res.code === 0 && Array.isArray(res.data) && res.data.length) {
          const list = res.data.map(normalizeGuideItem).filter(Boolean)
          this.setData({ list, empty: list.length === 0 })
          return true
        }
        return false
      })
      .then((ok) => {
        if (ok) return
        return request({
          path: '/user/coupon_issue_user',
          method: 'POST',
          data: { uid, status: '', page: 1, limit: 50 },
          showLoading: false,
        }).then((res) => {
          const rows = (res && res.data && Array.isArray(res.data.data) && res.data.data) || []
          const list = rows
            .map(normalizeCouponFallback)
            .filter(Boolean)
            .filter((it) => Number(it.cid) === 3 || Number(it.cid) === 4)
          this.setData({ list, empty: list.length === 0 })
        })
      })
      .catch((err) => {
        this.setData({ error: String((err && (err.errMsg || err.message)) || err), empty: true })
      })
      .finally(() => this.setData({ loading: false }))
  },
  onTapCoupons(e) {
    const item = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.item
    if (!item || !item.id) return
    const title = item.title || '旅行团'
    wx.navigateTo({
      url: `/pages/user/GroupCoupon/list?id=${encodeURIComponent(String(item.id))}&title=${encodeURIComponent(title)}`,
    })
  },
  onTapTourists(e) {
    const item = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.item
    const tourist = (item && item.tourist) || []
    safeSetStorage(TOURIST_KEY, tourist)
    wx.navigateTo({
      url: `/pages/user/GroupCoupon/touristInfo/touristInfo?title=${encodeURIComponent(String((item && item.title) || '旅行团'))}`,
    })
  },
  onTapHotels(e) {
    const item = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.item
    if (!item || !item.tid) {
      ui.toast('缺少旅行团参数')
      return
    }
    safeSetStorage(HOTEL_CTX_KEY, item)
    wx.navigateTo({
      url: `/pages/user/GroupCoupon/hotelList?tid=${encodeURIComponent(String(item.tid))}&title=${encodeURIComponent(String(item.title || '旅行团'))}`,
    })
  },
})
