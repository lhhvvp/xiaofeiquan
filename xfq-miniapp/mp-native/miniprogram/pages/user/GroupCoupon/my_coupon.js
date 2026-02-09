const config = require('../../../config')
const request = require('../../../services/request')
const ui = require('../../../utils/ui')
const qrcodeUtil = require('../../../utils/qrcode')

function safeName(item) {
  const user = item && item.user && typeof item.user === 'object' ? item.user : {}
  return user.name || '游客'
}

function safeMobile(item) {
  const user = item && item.user && typeof item.user === 'object' ? item.user : {}
  return user.mobile || '-'
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    id: '',
    info: null,
    showQrcode: false,
    qrcodeText: '',
    statusText: '',
    statusImage: '',
    writeoffList: [],
    loading: false,
    error: '',
  },
  onLoad(options) {
    const id = (options && options.id) || ''
    this.setData({ id: String(id) })
    this.fetchDetail()
  },
  onPullDownRefresh() {
    this.fetchDetail({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  fetchDetail({ showLoading = true } = {}) {
    if (!this.data.hasBaseUrl) return Promise.resolve()
    if (!this.data.id) {
      this.setData({ error: '缺少 id' })
      return Promise.resolve()
    }
    this.setData({ loading: true, error: '' })

    return request({
      path: '/user/tour_coupon_group',
      method: 'POST',
      data: { id: this.data.id },
      showLoading,
    })
      .then((res) => {
        if (!res || typeof res !== 'object' || res.code !== 0 || !res.data) {
          ui.toast((res && res.msg) || '查询失败')
          return
        }
        const info = res.data
        const status = Number(info.status || 0)
        const writeoffList = Array.isArray(info.tour_write_off)
          ? info.tour_write_off.map((item) =>
              Object.assign({}, item || {}, {
                displayName: safeName(item),
                displayMobile: safeMobile(item),
              })
            )
          : []
        const statusText = status === 0 ? '未核销' : status === 1 ? '已核销' : '状态未知'
        const statusImage = status === 1 ? '/static/icon/001.png' : status === 2 ? '/static/icon/002.png' : ''
        const qrcodeText = JSON.stringify({
          id: info.id,
          qrcode: info.qrcode_url || '',
          type: 'groupCoupon',
        })
        const showQrcode = status === 0 && !!info.qrcode_url
        this.setData({
          info,
          showQrcode,
          qrcodeText,
          statusText,
          statusImage,
          writeoffList,
        })
        wx.setNavigationBarTitle({ title: (info.couponIssue && info.couponIssue.coupon_title) || '团券详情' })
        if (showQrcode) return this.drawQrCode()
      })
      .catch((err) => this.setData({ error: String((err && (err.errMsg || err.message)) || err) }))
      .finally(() => this.setData({ loading: false }))
  },
  waitForNextTick() {
    return new Promise((resolve) => {
      if (typeof wx.nextTick === 'function') {
        wx.nextTick(resolve)
        return
      }
      setTimeout(resolve, 30)
    })
  },
  drawQrCode() {
    if (!this.data.showQrcode || !this.data.qrcodeText) return Promise.resolve()
    return this.waitForNextTick().then(() =>
      qrcodeUtil.drawToCanvas({
        canvasId: 'groupQrcode',
        text: this.data.qrcodeText,
        size: 220,
        margin: 0,
        page: this,
      })
    )
  },
  onTapPhone(e) {
    const mobile = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.mobile
    if (!mobile || mobile === '-') return
    wx.makePhoneCall({ phoneNumber: String(mobile) })
  },
})
