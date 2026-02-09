const config = require('../../config')
const auth = require('../../services/auth')
const request = require('../../services/request')
const ui = require('../../utils/ui')

function normalizeRow(item) {
  if (!item || typeof item !== 'object') return null
  return {
    time: item.time || item.ftime || item.accept_time || '',
    content: item.context || item.status || item.desc || '-',
    location: item.location || item.area || '',
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    trackingNumber: '',
    couponIssueUserId: '',
    company: '未知',
    list: [],
    empty: false,
    loading: false,
    error: '',
  },
  onLoad(options) {
    const trackingNumber =
      (options && (options.trackingNumber || options.tracking_number || options.no || options.number)) || ''
    const couponIssueUserId = (options && (options.coupon_issue_user_id || options.couponIssueUserId)) || ''
    this.setData({
      trackingNumber: String(trackingNumber),
      couponIssueUserId: String(couponIssueUserId),
    })
    if (trackingNumber) {
      this.fetchLogistics()
    } else {
      this.setData({ empty: true, error: '缺少物流单号' })
    }
  },
  onPullDownRefresh() {
    this.fetchLogistics({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  fetchLogistics({ showLoading = true } = {}) {
    if (!this.data.hasBaseUrl) return Promise.resolve()
    if (!this.data.trackingNumber) return Promise.resolve()
    this.setData({ loading: true, error: '', empty: false })
    return request({
      path: '/user/getLogisticsInformation',
      method: 'GET',
      data: {
        uid: auth.getUid() || 0,
        coupon_issue_user_id: this.data.couponIssueUserId,
        tracking_number: this.data.trackingNumber,
      },
      showLoading,
    })
      .then((res) => {
        if (!res || typeof res !== 'object' || res.code !== 0) {
          const msg = (res && res.msg) || '查询失败'
          this.setData({ empty: true, error: msg, list: [] })
          ui.showModal({ title: '提示', content: msg, showCancel: false })
          return
        }
        const data = (res && res.data) || {}
        const rows = (Array.isArray(data.data) && data.data) || []
        const list = rows.map(normalizeRow).filter(Boolean)
        this.setData({
          company: data.exp_name || data.expName || '未知',
          list,
          empty: list.length === 0,
        })
      })
      .catch((err) =>
        this.setData({
          empty: true,
          error: String((err && (err.errMsg || err.message)) || err),
          list: [],
        })
      )
      .finally(() => this.setData({ loading: false }))
  },
})
