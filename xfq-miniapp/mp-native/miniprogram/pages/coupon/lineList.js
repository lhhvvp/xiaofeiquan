const config = require('../../config')
const request = require('../../services/request')
const ui = require('../../utils/ui')
const urlUtil = require('../../utils/url')

const PAGE_LIMIT = 12

function normalizeItem(item) {
  if (!item || typeof item !== 'object') return null
  const rawTags = Array.isArray(item.tags) ? item.tags : String(item.tags || '').split(',')
  const tags = rawTags.map((it) => String(it || '').trim()).filter(Boolean)
  return {
    id: item.id || '',
    title: item.title || '',
    image: urlUtil.normalizeNetworkUrl(item.images, config.baseUrl),
    tags,
    price: Number(item.price || 0),
    accessCount: Number(item.access_count || 0),
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    couponId: 0,
    flag: '',
    page: 1,
    loading: false,
    loadingLock: false,
    loadingStatus: 'more',
    list: [],
    empty: false,
    error: '',
  },
  onLoad(options) {
    const couponId = Number(options && options.id) || 0
    const flag = (options && options.flag) || ''
    this.setData({ couponId, flag: String(flag) })
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
  fetchNextPage({ showLoading = true } = {}) {
    if (!this.data.hasBaseUrl) return Promise.resolve()
    if (this.data.loading || this.data.loadingLock) return Promise.resolve()
    this.setData({ loading: true, loadingStatus: 'loading', error: '' })
    return request({
      path: '/coupon/line_list',
      method: 'POST',
      data: {
        couponId: this.data.couponId,
        page: this.data.page,
        limit: PAGE_LIMIT,
        flag: this.data.flag,
      },
      showLoading,
    })
      .then((res) => {
        if (!res || typeof res !== 'object' || res.code !== 0) {
          ui.toast((res && res.msg) || '查询失败')
          this.setData({ loadingStatus: 'more' })
          return
        }
        const rows = Array.isArray(res.data) ? res.data : []
        const nextRows = rows.map(normalizeItem).filter(Boolean)
        if (this.data.page === 1 && nextRows.length === 0) {
          this.setData({
            empty: true,
            loadingLock: true,
            loadingStatus: 'no-more',
          })
          return
        }
        const nextList = this.data.list.concat(nextRows)
        const noMore = nextRows.length < PAGE_LIMIT
        this.setData({
          list: nextList,
          empty: false,
          page: this.data.page + 1,
          loadingLock: noMore,
          loadingStatus: noMore ? 'no-more' : 'more',
        })
      })
      .catch((err) => {
        this.setData({
          loadingStatus: 'more',
          error: String((err && (err.errMsg || err.message)) || err),
        })
      })
      .finally(() => this.setData({ loading: false }))
  },
  onTapItem(e) {
    const id = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({ url: `/pages/coupon/lineInfo?id=${encodeURIComponent(String(id))}` })
  },
})
