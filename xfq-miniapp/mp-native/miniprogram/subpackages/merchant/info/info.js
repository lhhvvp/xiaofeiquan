const config = require('../../../config')
const ui = require('../../../utils/ui')
const urlUtil = require('../../../utils/url')
const locationService = require('../../../services/location')
const merchantApi = require('../../../services/api/merchant')

function normalizeDetail(detail, baseUrl) {
  if (!detail || typeof detail !== 'object') return null
  const distance = Number(detail.distance)
  const distanceText = Number.isFinite(distance) ? `${distance.toFixed(2)}km` : ''
  return {
    nickname: detail.nickname || '',
    image: urlUtil.normalizeNetworkUrl(detail.image, baseUrl),
    doBusinessTime: detail.do_business_time || '',
    address: detail.address || '',
    mobile: detail.mobile || '',
    commentRate: Number(detail.comment_rate || 0),
    commentNum: Number(detail.comment_num || 0),
    distanceText,
    latitude: detail.latitude,
    longitude: detail.longitude,
    contentHtml: urlUtil.normalizeRichTextHtml(detail.content, baseUrl),
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    id: '',
    latitude: 0,
    longitude: 0,
    detail: null,
    error: null,
  },
  onLoad(options) {
    const id = (options && (options.id || options.seller_id || options.sellerId)) || ''
    this.setData({ id: String(id) })

    this.coordPromise = locationService.getLocation().then((coord) => {
      this.setData({ latitude: coord.latitude, longitude: coord.longitude })
      return coord
    })

    if (this.data.hasBaseUrl) this.fetchDetail()
  },
  onPullDownRefresh() {
    if (!this.data.hasBaseUrl) {
      wx.stopPullDownRefresh()
      return
    }
    this.fetchDetail({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  fetchDetail({ showLoading = true } = {}) {
    if (!this.data.id) {
      ui.toast('缺少商家 id')
      return Promise.resolve()
    }
    if (!this.data.hasBaseUrl) {
      return ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
    }

    this.setData({ error: null })

    return Promise.resolve(this.coordPromise)
      .then(() =>
        merchantApi.getMerchantDetail(
          {
            seller_id: this.data.id,
            latitude: this.data.latitude,
            longitude: this.data.longitude,
          },
          { showLoading }
        )
      )
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          return
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '请求失败')
          return
        }

        const detail = res.data && res.data.detail
        const normalized = normalizeDetail(detail, this.data.baseUrl)
        this.setData({ detail: normalized || null })

        if (normalized && normalized.nickname) {
          wx.setNavigationBarTitle({ title: normalized.nickname })
        }
      })
      .catch((err) => {
        this.setData({ error: String((err && (err.errMsg || err.message)) || err) })
        ui.toast('请求失败，请稍后重试')
      })
  },
  onTapCall() {
    const mobile = this.data.detail && this.data.detail.mobile
    if (!mobile) {
      ui.toast('暂无电话')
      return
    }
    wx.makePhoneCall({ phoneNumber: String(mobile) })
  },
  onTapNav() {
    const detail = this.data.detail
    if (!detail) return
    const latitude = Number(detail.latitude)
    const longitude = Number(detail.longitude)
    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
      ui.toast('暂无定位信息')
      return
    }
    wx.openLocation({
      name: detail.address || detail.nickname || '目的地',
      address: detail.address || '',
      latitude,
      longitude,
      scale: 16,
    })
  },
  onTapGoTickets() {
    if (!this.data.id) return
    wx.navigateTo({ url: `/subpackages/tickets/info?seller_id=${encodeURIComponent(this.data.id)}` })
  },
})
