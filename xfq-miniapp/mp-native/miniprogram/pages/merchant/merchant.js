const config = require('../../config')
const ui = require('../../utils/ui')
const urlUtil = require('../../utils/url')
const locationService = require('../../services/location')
const merchantApi = require('../../services/api/merchant')

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
  }
}

Page({
  data: {
    envVersion: config.envVersion,
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),

    latitude: 0,
    longitude: 0,

    categories: [],
    categoryData: [],
    currentIndex: 0,
  },
  onLoad() {
    this.coordPromise = locationService.getLocation().then((coord) => {
      this.setData({ latitude: coord.latitude, longitude: coord.longitude })
      return coord
    })

    if (this.data.hasBaseUrl) this.refresh()
  },
  onPullDownRefresh() {
    if (!this.data.hasBaseUrl) {
      wx.stopPullDownRefresh()
      return
    }
    this.refresh().finally(() => wx.stopPullDownRefresh())
  },
  onReachBottom() {
    this.fetchNextPage()
  },
  refresh() {
    this.setData({ categories: [], categoryData: [], currentIndex: 0 })
    return this.fetchCategories().then(() => this.fetchNextPage({ index: 0 }))
  },
  fetchCategories() {
    return merchantApi
      .getCategories()
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          return
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '请求失败')
          return
        }
        const cate = res.data && res.data.cate
        const categories = Array.isArray(cate) ? cate : []
        const categoryData = categories.map((c) => ({
          classId: c.id,
          className: c.class_name || '',
          list: [],
          page: 0,
          limit: 8,
          loadingStatus: 'more',
          loadingLock: false,
          empty: false,
        }))
        this.setData({ categories, categoryData, currentIndex: 0 })
      })
      .catch(() => {
        ui.toast('分类加载失败，请稍后重试')
      })
  },
  fetchNextPage({ index } = {}) {
    const i = Number.isInteger(index) ? index : Number(this.data.currentIndex) || 0
    const state = this.data.categoryData && this.data.categoryData[i]
    if (!state) return Promise.resolve()
    if (!this.data.hasBaseUrl) {
      ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
      return Promise.resolve()
    }
    if (state.loadingLock || state.loadingStatus === 'loading') return Promise.resolve()

    const baseUrl = this.data.baseUrl
    this.setData({ [`categoryData[${i}].loadingStatus`]: 'loading' })

    return Promise.resolve(this.coordPromise)
      .then(() =>
        merchantApi.getMerchantList(
          {
            class_id: state.classId,
            page: state.page,
            limit: state.limit,
            latitude: this.data.latitude,
            longitude: this.data.longitude,
          },
          { showLoading: false }
        )
      )
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          this.setData({ [`categoryData[${i}].loadingStatus`]: 'more' })
          return
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '请求失败')
          this.setData({ [`categoryData[${i}].loadingStatus`]: 'more' })
          return
        }

        const data = Array.isArray(res.data) ? res.data : []
        const normalized = data.map((it) => normalizeMerchant(it, baseUrl)).filter(Boolean)

        if (state.page === 0 && normalized.length === 0) {
          this.setData({
            [`categoryData[${i}].empty`]: true,
            [`categoryData[${i}].loadingLock`]: true,
            [`categoryData[${i}].loadingStatus`]: 'no-more',
          })
          return
        }

        const nextList = (state.list || []).concat(normalized)
        const nextPage = state.page + state.limit
        const noMore = normalized.length === 0 || normalized.length < state.limit
        this.setData({
          [`categoryData[${i}].list`]: nextList,
          [`categoryData[${i}].page`]: nextPage,
          [`categoryData[${i}].loadingStatus`]: noMore ? 'no-more' : 'more',
          [`categoryData[${i}].loadingLock`]: noMore,
        })
      })
      .catch(() => {
        this.setData({ [`categoryData[${i}].loadingStatus`]: 'more' })
        ui.toast('请求失败，请稍后重试')
      })
  },
  onTapTab(e) {
    const index = Number(e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.index)
    if (!Number.isInteger(index)) return
    if (index === this.data.currentIndex) return
    this.setData({ currentIndex: index })

    const state = this.data.categoryData && this.data.categoryData[index]
    if (!state) return
    if ((state.list && state.list.length) || state.empty || state.loadingLock) return
    this.fetchNextPage({ index })
  },
  onTapSearch() {
    wx.navigateTo({ url: '/subpackages/merchant/search/search' })
  },
  onTapMerchant(e) {
    const id = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({ url: `/subpackages/merchant/info/info?id=${encodeURIComponent(String(id))}` })
  },
})
