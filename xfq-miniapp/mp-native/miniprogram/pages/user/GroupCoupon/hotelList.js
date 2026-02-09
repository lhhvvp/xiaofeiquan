const config = require('../../../config')
const auth = require('../../../services/auth')
const request = require('../../../services/request')
const ui = require('../../../utils/ui')
const locationService = require('../../../services/location')

const HOTEL_CTX_KEY = '__group_hotel_ctx'
const HOTEL_RECORD_KEY = '__group_hotel_user_record'

function safeGetStorage(key) {
  try {
    return wx.getStorageSync(key)
  } catch (e) {
    return null
  }
}

function safeSetStorage(key, value) {
  try {
    wx.setStorageSync(key, value)
  } catch (e) {}
}

function normalizeItem(item) {
  if (!item || typeof item !== 'object') return null
  return {
    id: item.id || '',
    hotelName: item.hotel_name || '未命名酒店',
    touristCount: item.tourist_numbers || 0,
    createTime: item.create_time || '',
    remark: item.remark || '',
    users: Array.isArray(item.tour_hotel_user_record) ? item.tour_hotel_user_record : [],
    raw: item,
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    tid: '',
    title: '',
    list: [],
    empty: false,
    loading: false,
    error: '',
    showForm: false,
    hotelName: '',
    remark: '',
    submitting: false,
  },
  onLoad(options) {
    const tid = (options && options.tid) || ''
    const title = (options && options.title) || '旅行团'
    this.setData({ tid: String(tid), title: String(title) })
    wx.setNavigationBarTitle({ title: `打卡列表-${title}` })
    this.fetchList()
  },
  onPullDownRefresh() {
    this.fetchList({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  fetchList({ showLoading = true } = {}) {
    if (!this.data.hasBaseUrl) return Promise.resolve()
    this.setData({ loading: true, error: '' })
    return request({
      path: '/user/hotel_tour',
      method: 'POST',
      data: { tid: this.data.tid },
      showLoading,
    })
      .then((res) => {
        if (res && res.code === 0 && Array.isArray(res.data)) {
          const list = res.data.map(normalizeItem).filter(Boolean)
          this.setData({ list, empty: list.length === 0 })
          return
        }
        const ctx = safeGetStorage(HOTEL_CTX_KEY)
        const fallback = (ctx && Array.isArray(ctx.hotels) && ctx.hotels) || []
        const list = fallback.map(normalizeItem).filter(Boolean)
        this.setData({ list, empty: list.length === 0 })
      })
      .catch((err) => this.setData({ error: String((err && (err.errMsg || err.message)) || err), empty: true }))
      .finally(() => this.setData({ loading: false }))
  },
  onTapItem(e) {
    const index = Number(e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.index)
    const item = this.data.list && this.data.list[index]
    if (!item) return
    safeSetStorage(HOTEL_RECORD_KEY, item.raw || item)
    wx.navigateTo({
      url: `/pages/user/GroupCoupon/hotelUserList?title=${encodeURIComponent(String(item.hotelName || '酒店'))}`,
    })
  },
  onTapCreate() {
    const ctx = safeGetStorage(HOTEL_CTX_KEY)
    const status = Number(ctx && ctx.status)
    const tourist = (ctx && Array.isArray(ctx.tourist) && ctx.tourist) || []
    if (status === 5 || tourist.length === 0) {
      ui.toast('当前状态下不可生成酒店打卡任务')
      return
    }
    this.setData({ showForm: true })
  },
  onTapCloseForm() {
    this.setData({ showForm: false, hotelName: '', remark: '' })
  },
  onInputHotelName(e) {
    this.setData({ hotelName: (e && e.detail && e.detail.value) || '' })
  },
  onInputRemark(e) {
    this.setData({ remark: (e && e.detail && e.detail.value) || '' })
  },
  onTapSubmit() {
    if (this.data.submitting) return
    const uid = auth.getUid()
    if (!uid) {
      ui.toast('请先登录')
      return
    }
    const hotelName = String(this.data.hotelName || '').trim()
    const remark = String(this.data.remark || '').trim()
    if (!hotelName) {
      ui.toast('请输入酒店名称')
      return
    }
    if (!remark) {
      ui.toast('请输入备注信息')
      return
    }

    this.setData({ submitting: true })
    return locationService
      .getLocation()
      .catch(() => ({ latitude: 0, longitude: 0 }))
      .then((coord) =>
        request({
          path: '/user/add_sign_record',
          method: 'POST',
          data: {
            uid,
            tid: this.data.tid,
            latitude: coord.latitude || 0,
            longitude: coord.longitude || 0,
            hotel_name: hotelName,
            remark,
          },
          showLoading: true,
        })
      )
      .then((res) => {
        if (!res || typeof res !== 'object' || res.code !== 0) {
          ui.toast((res && res.msg) || '创建失败')
          return
        }
        ui.toast(res.msg || '创建成功')
        this.onTapCloseForm()
        this.fetchList({ showLoading: false })
      })
      .catch(() => ui.toast('创建失败'))
      .finally(() => this.setData({ submitting: false }))
  },
})
