const config = require('../../../config')
const auth = require('../../../services/auth')
const request = require('../../../services/request')
const ui = require('../../../utils/ui')

function normalizeItem(item) {
  if (!item || typeof item !== 'object') return null
  const issue = item.couponIssue && typeof item.couponIssue === 'object' ? item.couponIssue : {}
  const status = Number(item.status || 0)
  return {
    id: item.id || '',
    title: issue.coupon_title || item.coupon_title || '',
    price: issue.coupon_price || item.coupon_price || 0,
    createTime: item.create_time || '',
    status,
    statusText: status === 0 ? '未使用' : '已核销',
    raw: item,
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    tid: '',
    title: '',
    loading: false,
    list: [],
    empty: false,
    error: '',
  },
  onLoad(options) {
    const tid = (options && options.id) || ''
    const title = (options && options.title) || '旅行团'
    this.setData({ tid: String(tid), title: String(title) })
    wx.setNavigationBarTitle({ title: `消费券-${title}` })
    this.fetchList()
  },
  onPullDownRefresh() {
    this.fetchList({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  fetchList({ showLoading = true } = {}) {
    if (!this.data.hasBaseUrl) return Promise.resolve()
    if (this.data.loading) return Promise.resolve()

    const uid = auth.getUid()
    this.setData({ loading: true, error: '', empty: false })

    return request({
      path: '/user/tour_coupon',
      method: 'POST',
      data: { tid: this.data.tid },
      showLoading,
    })
      .then((res) => {
        if (res && res.code === 0 && Array.isArray(res.data)) {
          const list = res.data.map(normalizeItem).filter(Boolean)
          this.setData({ list, empty: list.length === 0 })
          return true
        }
        return false
      })
      .then((ok) => {
        if (ok) return
        return request({
          path: '/user/coupon_issue_user',
          method: 'POST',
          data: { uid, status: '', page: 1, limit: 30 },
          showLoading: false,
        }).then((res) => {
          const rows = (res && res.data && Array.isArray(res.data.data) && res.data.data) || []
          const list = rows.map(normalizeItem).filter(Boolean)
          this.setData({ list, empty: list.length === 0 })
        })
      })
      .catch((err) => this.setData({ error: String((err && (err.errMsg || err.message)) || err), empty: true }))
      .finally(() => this.setData({ loading: false }))
  },
  onTapCoupon(e) {
    const id = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({ url: `/pages/user/GroupCoupon/my_coupon?id=${encodeURIComponent(String(id))}` })
  },
})
