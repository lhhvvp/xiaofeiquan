const config = require('../../config')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')
const urlUtil = require('../../utils/url')
const locationService = require('../../services/location')
const userApi = require('../../services/api/user')

function normalizeMerchant(item, baseUrl) {
  if (!item || typeof item !== 'object') return null
  const distance = Number(item.distance)
  const distanceText = Number.isFinite(distance) ? `${distance.toFixed(2)}km` : ''
  return {
    id: item.id,
    image: urlUtil.normalizeNetworkUrl(item.image, baseUrl),
    nickname: item.nickname || '',
    mobile: item.mobile || '',
    do_business_time: item.do_business_time || '',
    address: item.address || '',
    distanceText,
    raw: item,
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    latitude: 0,
    longitude: 0,

    list: [],
    page: 0,
    limit: 8,
    loadingStatus: 'more',
    loadingLock: false,
    empty: false,
    error: null,
  },
  onLoad() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    this.setData({ hasLogin })

    this.coordPromise = locationService.getLocation().then((coord) => {
      this.setData({ latitude: coord.latitude, longitude: coord.longitude })
      return coord
    })

    if (this.data.hasBaseUrl && hasLogin) this.refresh()
  },
  onShow() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    if (hasLogin !== this.data.hasLogin) this.setData({ hasLogin })
    if (this.data.hasBaseUrl && hasLogin && !this.data.list.length && !this.data.loadingLock) {
      this.refresh({ showLoading: false })
    }
  },
  onPullDownRefresh() {
    if (!this.ensureReady({ showToast: false })) {
      wx.stopPullDownRefresh()
      return
    }
    this.refresh({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  onReachBottom() {
    this.fetchNextPage({ showLoading: false })
  },
  ensureReady({ showToast = true } = {}) {
    if (!this.data.hasBaseUrl) {
      ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
      return false
    }
    if (!this.data.hasLogin) {
      if (showToast) ui.toast('请先登录')
      return false
    }
    return true
  },
  onTapGoLogin() {
    const redirect = '/subpackages/user/collect'
    wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
  },
  refresh({ showLoading = true } = {}) {
    if (!this.ensureReady()) return Promise.resolve()
    this.setData({
      list: [],
      page: 0,
      loadingStatus: 'more',
      loadingLock: false,
      empty: false,
      error: null,
    })
    return this.fetchNextPage({ showLoading })
  },
  fetchNextPage({ showLoading = true } = {}) {
    if (!this.ensureReady()) return Promise.resolve()
    if (this.data.loadingLock || this.data.loadingStatus === 'loading') return Promise.resolve()

    const uid = auth.getUid()
    if (!uid) return Promise.resolve()

    const baseUrl = this.data.baseUrl
    this.setData({ loadingStatus: 'loading', error: null })

    return Promise.resolve(this.coordPromise)
      .then(() =>
        userApi.getCollectionList(
          {
            uid,
            page: this.data.page,
            limit: this.data.limit,
            latitude: this.data.latitude,
            longitude: this.data.longitude,
          },
          { showLoading }
        )
      )
      .then((res) => {
        if (!res || typeof res !== 'object') {
          this.setData({ loadingStatus: 'error', error: '返回数据异常' })
          return
        }
        if (res.code !== 0) {
          this.setData({ loadingStatus: 'error', error: res.msg || '请求失败' })
          return
        }

        const data = Array.isArray(res.data) ? res.data : []
        const normalized = data.map((it) => normalizeMerchant(it, baseUrl)).filter(Boolean)

        if (this.data.page === 0 && normalized.length === 0) {
          this.setData({
            empty: true,
            loadingStatus: 'no-more',
            loadingLock: true,
          })
          return
        }

        const nextList = (this.data.list || []).concat(normalized)
        const noMore = normalized.length === 0 || normalized.length < this.data.limit
        this.setData({
          list: nextList,
          page: this.data.page + 1,
          loadingStatus: noMore ? 'no-more' : 'more',
          loadingLock: noMore,
        })
      })
      .catch((err) => this.setData({ loadingStatus: 'error', error: String((err && (err.errMsg || err.message)) || err) }))
  },
  onTapMerchant(e) {
    const id = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({ url: `/subpackages/merchant/info/info?id=${encodeURIComponent(String(id))}` })
  },
  onRetry() {
    if (!this.ensureReady()) return
    this.fetchNextPage({ showLoading: false })
  },
})

