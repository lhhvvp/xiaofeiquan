const config = require('../../config')
const ui = require('../../utils/ui')
const urlUtil = require('../../utils/url')
const locationService = require('../../services/location')
const ticketsApi = require('../../services/api/tickets')

const AREA_OPTIONS = [
  { id: 0, name: '全部' },
  { id: 1, name: '榆阳区' },
  { id: 2, name: '横山区' },
  { id: 3, name: '神木市' },
  { id: 4, name: '府谷县' },
  { id: 5, name: '靖边县' },
  { id: 6, name: '定边县' },
  { id: 7, name: '绥德县' },
  { id: 8, name: '米脂县' },
  { id: 9, name: '佳县' },
  { id: 10, name: '吴堡县' },
  { id: 11, name: '清涧县' },
  { id: 12, name: '子洲县' },
]

const SORT_OPTIONS = [
  { id: 0, name: '智能排序' },
  { id: 'distance', name: '距离优先' },
  { id: 'comment', name: '好评优先' },
]

function normalizeItem(item, baseUrl) {
  if (!item || typeof item !== 'object') return null
  const distance = Number(item.distance)
  const distanceText = Number.isFinite(distance) ? `${distance.toFixed(2)}km` : ''
  return {
    id: item.id,
    image: urlUtil.normalizeNetworkUrl(item.image, baseUrl),
    nickname: item.nickname || '',
    areaText: item.area_text || '',
    distanceText,
    commentRate: item.comment_rate || 0,
    commentNum: item.comment_num || 0,
    minPrice: item.min_price || 0,
  }
}

Page({
  data: {
    envVersion: config.envVersion,
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),

    latitude: 0,
    longitude: 0,

    areaOptions: AREA_OPTIONS,
    sortOptions: SORT_OPTIONS,
    areaIndex: 0,
    sortIndex: 0,

    list: [],
    page: 1,
    pageSize: 12,
    requesting: false,
    loadingStatus: 'more',
    loadingLock: false,
    empty: false,
  },
  onLoad() {
    this.coordPromise = locationService.getLocation().then((coord) => {
      this.setData({ latitude: coord.latitude, longitude: coord.longitude })
      return coord
    })

    if (this.data.hasBaseUrl) this.resetAndFetch()
  },
  onPullDownRefresh() {
    if (!this.data.hasBaseUrl) {
      wx.stopPullDownRefresh()
      return
    }
    this.resetAndFetch().finally(() => wx.stopPullDownRefresh())
  },
  onReachBottom() {
    this.fetchNextPage()
  },
  onAreaChange(e) {
    const index = Number(e && e.detail && e.detail.value)
    if (!Number.isInteger(index)) return
    if (index === this.data.areaIndex) return
    this.setData({ areaIndex: index })
    this.resetAndFetch()
  },
  onSortChange(e) {
    const index = Number(e && e.detail && e.detail.value)
    if (!Number.isInteger(index)) return
    if (index === this.data.sortIndex) return
    this.setData({ sortIndex: index })
    this.resetAndFetch()
  },
  resetAndFetch() {
    this.setData({
      list: [],
      page: 1,
      requesting: false,
      loadingStatus: 'more',
      loadingLock: false,
      empty: false,
    })
    return this.fetchNextPage()
  },
  fetchNextPage() {
    if (!this.data.hasBaseUrl) {
      ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
      return Promise.resolve()
    }
    if (this.data.loadingLock || this.data.requesting) return Promise.resolve()

    const areaOpt = this.data.areaOptions[this.data.areaIndex] || AREA_OPTIONS[0]
    const sortOpt = this.data.sortOptions[this.data.sortIndex] || SORT_OPTIONS[0]

    const baseUrl = this.data.baseUrl
    this.setData({ requesting: true, loadingStatus: 'loading' })

    return Promise.resolve(this.coordPromise)
      .then(() =>
        ticketsApi.getScenicList(
          {
            area: areaOpt.id,
            orderby: sortOpt.id,
            page: this.data.page,
            page_size: this.data.pageSize,
            latitude: this.data.latitude,
            longitude: this.data.longitude,
            hasTicket: true,
          },
          { showLoading: false }
        )
      )
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          this.setData({ requesting: false, loadingStatus: 'more' })
          return
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '请求失败')
          this.setData({ requesting: false, loadingStatus: 'more' })
          return
        }

        const data = Array.isArray(res.data) ? res.data : []
        const normalized = data.map((it) => normalizeItem(it, baseUrl)).filter(Boolean)

        if (this.data.page === 1 && normalized.length === 0) {
          this.setData({
            empty: true,
            requesting: false,
            loadingStatus: 'no-more',
            loadingLock: true,
          })
          return
        }

        const nextList = (this.data.list || []).concat(normalized)
        const noMore = normalized.length === 0 || normalized.length < this.data.pageSize
        this.setData({
          list: nextList,
          page: this.data.page + 1,
          requesting: false,
          loadingStatus: noMore ? 'no-more' : 'more',
          loadingLock: noMore,
        })
      })
      .catch(() => {
        this.setData({ requesting: false, loadingStatus: 'more' })
        ui.toast('请求失败，请稍后重试')
      })
  },
  onTapItem(e) {
    const id = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({ url: `/subpackages/tickets/info?seller_id=${encodeURIComponent(String(id))}` })
  },
})
