const config = require('../../config')
const request = require('../../services/request')
const urlUtil = require('../../utils/url')

function parseTags(raw) {
  if (Array.isArray(raw)) return raw.map((it) => String(it || '').trim()).filter(Boolean)
  return String(raw || '')
    .split(',')
    .map((it) => String(it || '').trim())
    .filter(Boolean)
}

function parsePhotos(raw, baseUrl) {
  if (!raw) return []
  let data = raw
  if (typeof data === 'string') {
    try {
      data = JSON.parse(data)
    } catch (e) {
      return []
    }
  }
  if (Array.isArray(data)) {
    return data
      .map((it) => (typeof it === 'object' ? it.image || it.url : it))
      .map((src) => urlUtil.normalizeNetworkUrl(src, baseUrl))
      .filter(Boolean)
  }
  if (data && typeof data === 'object') {
    return Object.keys(data)
      .map((key) => data[key] && (data[key].image || data[key].url))
      .map((src) => urlUtil.normalizeNetworkUrl(src, baseUrl))
      .filter(Boolean)
  }
  return []
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    lineId: '',
    info: null,
    empty: false,
    loading: false,
    error: '',
  },
  onLoad(options) {
    const lineId = (options && options.id) || ''
    this.setData({ lineId: String(lineId) })
    this.fetchDetail()
  },
  onPullDownRefresh() {
    this.fetchDetail({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  fetchDetail({ showLoading = true } = {}) {
    if (!this.data.hasBaseUrl) return Promise.resolve()
    if (!this.data.lineId) {
      this.setData({ empty: true, error: '缺少线路参数' })
      return Promise.resolve()
    }
    this.setData({ loading: true, error: '', empty: false })
    return request({
      path: '/coupon/line_detail',
      method: 'POST',
      data: { line_id: this.data.lineId },
      showLoading,
    })
      .then((res) => {
        if (!res || typeof res !== 'object' || res.code !== 0 || !res.data) {
          this.setData({
            empty: true,
            error: (res && res.msg) || '查询失败',
          })
          return
        }
        const source = res.data
        const info = {
          title: source.title || '-',
          image: urlUtil.normalizeNetworkUrl(source.images, config.baseUrl),
          tags: parseTags(source.tags),
          lineCategoryName:
            source.lineCategory && typeof source.lineCategory === 'object' ? source.lineCategory.name || '-' : '-',
          content: urlUtil.normalizeRichTextHtml(source.content, config.baseUrl),
          feeinclude: source.feeinclude || '-',
          notice: urlUtil.normalizeRichTextHtml(source.notice, config.baseUrl),
          photos: parsePhotos(source.photo || source.photos, config.baseUrl),
        }
        this.setData({ info, empty: false })
        wx.setNavigationBarTitle({ title: info.title || '线路详情' })
      })
      .catch((err) =>
        this.setData({
          empty: true,
          error: String((err && (err.errMsg || err.message)) || err),
        })
      )
      .finally(() => this.setData({ loading: false }))
  },
  onTapPhoto(e) {
    const index = Number(e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.index)
    const photos = (this.data.info && this.data.info.photos) || []
    if (!photos.length || Number.isNaN(index) || index < 0) return
    wx.previewImage({
      urls: photos,
      current: photos[index],
    })
  },
})
