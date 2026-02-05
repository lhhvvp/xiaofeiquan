const config = require('../../config')
const ui = require('../../utils/ui')
const locationService = require('../../services/location')
const couponApi = require('../../services/api/coupon')

function normalizeItem(item) {
  if (!item || typeof item !== 'object') return null
  const distance = Number(item.distance)
  return {
    id: item.id,
    nickname: item.nickname || '',
    address: item.address || '',
    distanceKm: Number.isFinite(distance) ? distance : null,
    distanceText: Number.isFinite(distance) ? `${distance.toFixed(1)}km` : '',
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    id: 0,
    keyword: '',
    list: [],
    offset: 0,
    limit: 15,
    loadingStatus: 'more',
    loadingLock: false,
    empty: false,
    latitude: 0,
    longitude: 0,
  },
  onLoad(options) {
    const id = Number(options && options.id) || 0
    this.setData({ id })

    locationService.getLocation().then((coord) => {
      this.setData({ latitude: coord.latitude, longitude: coord.longitude })
      this.fetchNextPage()
    })
  },
  onReachBottom() {
    this.fetchNextPage()
  },
  onInputKeyword(e) {
    this.setData({ keyword: (e && e.detail && e.detail.value) || '' })
  },
  onTapSearch() {
    const keyword = String(this.data.keyword || '').trim()
    if (!keyword) {
      ui.toast('请输入关键字')
      return
    }
    this.setData({
      list: [],
      offset: 0,
      loadingStatus: 'more',
      loadingLock: false,
      empty: false,
    })
    this.fetchNextPage()
  },
  fetchNextPage() {
    if (!this.data.hasBaseUrl) {
      ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
      return
    }
    if (this.data.loadingLock) return

    this.setData({ loadingStatus: 'loading' })

    couponApi
      .getApplicableMerchants({
        id: this.data.id,
        latitude: this.data.latitude,
        longitude: this.data.longitude,
        page: this.data.offset,
        limit: this.data.limit,
        keyword: this.data.keyword,
      })
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          return
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '请求失败')
          return
        }

        const data = Array.isArray(res.data) ? res.data : []
        const normalized = data.map(normalizeItem).filter(Boolean)

        if (this.data.offset === 0 && normalized.length === 0) {
          this.setData({ empty: true, loadingLock: true, loadingStatus: 'no-more' })
          return
        }

        const list = this.data.list.concat(normalized)
        const nextOffset = this.data.offset + this.data.limit

        const noMore = normalized.length === 0 || normalized.length < this.data.limit
        this.setData({
          list,
          offset: nextOffset,
          loadingStatus: noMore ? 'no-more' : 'more',
          loadingLock: noMore,
        })
      })
      .catch(() => {
        this.setData({ loadingStatus: 'more' })
        ui.toast('请求失败，请稍后重试')
      })
  },
  onTapItem(e) {
    const id = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({ url: `/subpackages/merchant/info/info?id=${encodeURIComponent(String(id))}` })
  },
})
