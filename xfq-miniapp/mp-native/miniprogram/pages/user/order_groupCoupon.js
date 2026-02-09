const config = require('../../config')
const auth = require('../../services/auth')
const request = require('../../services/request')
const ui = require('../../utils/ui')

function normalizeItem(item) {
  if (!item || typeof item !== 'object') return null
  return {
    id: item.tour_coupon_group_id || item.id || '',
    title: item.coupon_title || '团券记录',
    time: item.create_time || '',
    price: item.coupon_price || 0,
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    mid: 0,
    page: 1,
    limit: 20,
    loading: false,
    loadingLock: false,
    loadingStatus: 'more',
    list: [],
    empty: false,
    error: '',
  },
  onLoad(options) {
    const mid = Number(options && options.mid) || 0
    this.setData({ mid })
    this.fetchNextPage()
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
    const uid = auth.getUid()
    if (!uid) {
      ui.toast('请先登录')
      return Promise.resolve()
    }
    this.setData({ loading: true, error: '', loadingStatus: 'loading' })
    const payload = {
      page: this.data.page,
      limit: this.data.limit,
      userid: uid,
      mid: this.data.mid,
    }

    const tryFetch = (path) =>
      request({
        path,
        method: 'POST',
        data: payload,
        showLoading,
      })

    return tryFetch('/coupon/tourwriteofflog')
      .catch(() => tryFetch('/coupon/writeofflog'))
      .then((res) => {
        if (!res || typeof res !== 'object' || res.code !== 0) {
          ui.toast((res && res.msg) || '查询失败')
          this.setData({ loadingStatus: 'more' })
          return
        }
        const rows = Array.isArray(res.data) ? res.data : []
        const list = rows.map(normalizeItem).filter(Boolean)
        if (this.data.page === 1 && list.length === 0) {
          this.setData({ empty: true, loadingLock: true, loadingStatus: 'no-more' })
          return
        }
        const nextList = this.data.list.concat(list)
        const noMore = list.length === 0 || list.length < this.data.limit
        this.setData({
          list: nextList,
          page: this.data.page + 1,
          loadingLock: noMore,
          loadingStatus: noMore ? 'no-more' : 'more',
        })
      })
      .catch((err) => this.setData({ error: String((err && (err.errMsg || err.message)) || err), loadingStatus: 'more' }))
      .finally(() => this.setData({ loading: false }))
  },
  onTapItem(e) {
    const id = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({ url: `/pages/user/GroupCoupon/my_coupon?id=${encodeURIComponent(String(id))}` })
  },
})
