const config = require('../../config')
const auth = require('../../services/auth')
const request = require('../../services/request')
const systemService = require('../../services/system')
const ui = require('../../utils/ui')
const urlUtil = require('../../utils/url')

const PAGE_LIMIT = 12

function parseState(raw, fallbackId, fallbackTitle) {
  if (raw) {
    const text = decodeURIComponent(String(raw))
    try {
      const obj = JSON.parse(text)
      return {
        id: Number(obj && obj.id) || Number(fallbackId) || 0,
        title: (obj && obj.title) || fallbackTitle || '消费券列表',
      }
    } catch (e) {}
  }
  return {
    id: Number(fallbackId) || 0,
    title: fallbackTitle || '消费券列表',
  }
}

function normalizeCoupon(item, fallbackTitle) {
  if (!item || typeof item !== 'object') return null
  const cid = Number(item.cid || 0)
  const isGroupCoupon = cid === 3 || cid === 4
  return {
    id: item.id || '',
    cid,
    couponTitle: item.coupon_title || '-',
    couponPrice: Number(item.coupon_price || 0),
    displayLeftTitle: isGroupCoupon ? item.coupon_title || '-' : fallbackTitle || '消费券',
    displayLeftSub: isGroupCoupon ? String(Number(item.coupon_price || 0)) : item.coupon_title || '-',
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    title: '消费券列表',
    cid: 0,
    slideUrl: '',
    page: 1,
    list: [],
    loading: false,
    loadingLock: false,
    loadingStatus: 'more',
    empty: false,
    error: '',
  },
  onLoad(options) {
    const state = parseState(options && options.state, options && options.id, options && options.title)
    this.setData({
      title: state.title,
      cid: state.id,
    })
    wx.setNavigationBarTitle({ title: state.title })

    if (!this.data.hasBaseUrl) return
    this.fetchSystemSlide()
    this.fetchNextPage({ showLoading: true })
  },
  onPullDownRefresh() {
    this.setData({
      page: 1,
      list: [],
      loadingLock: false,
      loadingStatus: 'more',
      empty: false,
      error: '',
    })
    this.fetchNextPage({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  onReachBottom() {
    this.fetchNextPage({ showLoading: false })
  },
  fetchSystemSlide() {
    return systemService
      .fetchSystem({ force: false, showLoading: false })
      .then((system) => {
        const slide = system && system.slide && system.slide.image
        if (!slide) return
        this.setData({
          slideUrl: urlUtil.normalizeNetworkUrl(slide, config.baseUrl),
        })
      })
      .catch(() => {})
  },
  fetchNextPage({ showLoading = true } = {}) {
    if (!this.data.hasBaseUrl) return Promise.resolve()
    if (this.data.loading || this.data.loadingLock) return Promise.resolve()

    this.setData({ loading: true, loadingStatus: 'loading', error: '' })
    return request({
      path: '/coupon/list',
      method: 'POST',
      data: {
        cid: this.data.cid,
        userid: auth.getUid() || 0,
        page: this.data.page,
        limit: PAGE_LIMIT,
      },
      showLoading,
    })
      .then((res) => {
        if (!res || typeof res !== 'object' || res.code !== 0) {
          this.setData({ loadingStatus: 'more' })
          ui.toast((res && res.msg) || '查询失败')
          return
        }
        const rows = Array.isArray(res.data) ? res.data : []
        const nextRows = rows.map((item) => normalizeCoupon(item, this.data.title)).filter(Boolean)
        if (this.data.page === 1 && nextRows.length === 0) {
          this.setData({
            empty: true,
            loadingLock: true,
            loadingStatus: 'no-more',
          })
          return
        }
        const noMore = nextRows.length < PAGE_LIMIT
        this.setData({
          list: this.data.list.concat(nextRows),
          page: this.data.page + 1,
          empty: false,
          loadingLock: noMore,
          loadingStatus: noMore ? 'no-more' : 'more',
        })
      })
      .catch((err) =>
        this.setData({
          loadingStatus: 'more',
          error: String((err && (err.errMsg || err.message)) || err),
        })
      )
      .finally(() => this.setData({ loading: false }))
  },
  onTapCoupon(e) {
    const id = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({ url: `/subpackages/coupon/coupon?id=${encodeURIComponent(String(id))}` })
  },
})
