const config = require('../../../config')
const auth = require('../../../services/auth')
const ui = require('../../../utils/ui')
const urlUtil = require('../../../utils/url')
const userApi = require('../../../services/api/user')

function pad2(n) {
  return n < 10 ? `0${n}` : String(n)
}

function formatDateTime(sec) {
  const s = Number(sec)
  if (!Number.isFinite(s) || s <= 0) return '暂未打卡'
  const d = new Date(s * 1000)
  const y = d.getFullYear()
  const m = pad2(d.getMonth() + 1)
  const day = pad2(d.getDate())
  const hh = pad2(d.getHours())
  const mm = pad2(d.getMinutes())
  const ss = pad2(d.getSeconds())
  return `${y}-${m}-${day} ${hh}:${mm}:${ss}`
}

function normalizeImages(images, baseUrl) {
  const raw = typeof images === 'string' ? images : ''
  if (!raw) return []
  return raw
    .split(',')
    .map((it) => it.trim())
    .filter(Boolean)
    .map((u) => urlUtil.normalizeNetworkUrl(u, baseUrl) || u)
}

function normalizeItem(item, baseUrl, key) {
  if (!item || typeof item !== 'object') return null
  const tags = Number(item.tags || 0)
  const isClock = Number(item.is_clock || 0) === 1
  const addressText = item.address && item.address !== 0 ? String(item.address) : '暂未打卡'
  return {
    key: String(key || ''),
    id: item.id,
    tags,
    tagText: tags === 1 ? '景区' : '酒店',
    couponTitle: item.coupon_title || '',
    createTimeText: formatDateTime(item.create_time),
    tourName: item.tour_name || '',
    addressText,
    clockTimeText: isClock ? formatDateTime(item.clock_time) : '暂未打卡',
    isClock,
    images: normalizeImages(item.images, baseUrl),
    raw: item,
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    list: [],
    requesting: false,
    empty: false,
    error: null,
  },
  onLoad() {
    wx.setNavigationBarTitle({ title: '打卡任务' })
    this.refresh()
  },
  onShow() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    this.setData({ hasLogin })
    if (hasLogin) this.refresh({ showLoading: false })
  },
  onPullDownRefresh() {
    this.refresh({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
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
    return true
  },
  onTapGoLogin() {
    const redirect = '/subpackages/user/signIn/signIn'
    wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
  },
  refresh({ showLoading = true } = {}) {
    if (!this.ensureReady()) return Promise.resolve()
    if (this.data.requesting) return Promise.resolve()

    this.setData({ requesting: true, empty: false, error: null })

    const uid = auth.getUid()

    return userApi
      .getClockList({ uid }, { showLoading })
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
        const normalized = data.map((it, index) => normalizeItem(it, this.data.baseUrl, `${index}`)).filter(Boolean)

        if (!normalized.length) {
          this.setData({ list: [], empty: true })
          return
        }

        normalized.sort((a, b) => Number(a.isClock) - Number(b.isClock))
        this.setData({ list: normalized, empty: false })
      })
      .catch((err) => this.setData({ error: String((err && (err.errMsg || err.message)) || err) }))
      .finally(() => this.setData({ requesting: false }))
  },
  onTapClock(e) {
    const index = Number(e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.index)
    const item = this.data.list && this.data.list[index]
    if (!item) return
    if (item.isClock) return
    wx.navigateTo({ url: `/subpackages/user/signIn/info?id=${encodeURIComponent(String(item.id))}&tags=${encodeURIComponent(String(item.tags))}` })
  },
  onPreviewImage(e) {
    const index = Number(e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.index)
    const imgIndex = Number(e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.imgindex)
    const item = this.data.list && this.data.list[index]
    if (!item) return
    const urls = item.images || []
    if (!urls.length) return
    const current = urls[imgIndex] || urls[0]
    wx.previewImage({ current, urls })
  },
  onRetry() {
    this.refresh({ showLoading: false })
  },
})

