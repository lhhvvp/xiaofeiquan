const config = require('../../../config')
const request = require('../../../services/request')
const locationService = require('../../../services/location')
const ui = require('../../../utils/ui')

const HOTEL_RECORD_KEY = '__group_hotel_user_record'
const DEFAULT_CLOCK_IMAGE = 'https://oss.ylbigdata.com/admins/6602b01b17f64ecf18798e4868f394bb.png'

function safeGetStorage(key) {
  try {
    return wx.getStorageSync(key)
  } catch (e) {
    return null
  }
}

function normalizeClockRow(item) {
  if (!item || typeof item !== 'object') return null
  const user = (item.users && typeof item.users === 'object' && item.users) || {}
  const id = item.id || item.tour_hotel_user_id || item.user_id || ''
  if (!id) return null
  const isClock = Number(item.is_clock || 0)
  return {
    id,
    name: user.name || '',
    mobile: user.mobile || '',
    isClock,
    statusText: isClock === 1 ? '已打卡' : '未打卡',
    raw: item,
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    list: [],
    empty: false,
    showClockForm: false,
    activeId: '',
    activeIndex: -1,
    spotName: '',
    desc: '',
    imageUrl: '',
    submitting: false,
    error: '',
  },
  onLoad(options) {
    const title = (options && options.title) || '酒店'
    wx.setNavigationBarTitle({ title: `打卡游客信息-${title}` })
    this.restoreList(options)
  },
  restoreList(options) {
    const raw = safeGetStorage(HOTEL_RECORD_KEY)
    const sourceList =
      (raw && Array.isArray(raw.tour_hotel_user_record) && raw.tour_hotel_user_record) ||
      (Array.isArray(raw) && raw) ||
      []
    let list = sourceList.map(normalizeClockRow).filter(Boolean)

    if (!list.length && options && options.data) {
      try {
        const parsed = JSON.parse(decodeURIComponent(String(options.data)))
        const parsedList = Array.isArray(parsed) ? parsed : []
        list = parsedList.map(normalizeClockRow).filter(Boolean)
      } catch (e) {}
    }

    this.setData({ list, empty: list.length === 0 })
  },
  onTapPhone(e) {
    const mobile = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.mobile
    if (!mobile) return
    wx.makePhoneCall({ phoneNumber: String(mobile) })
  },
  onTapClock(e) {
    const index = Number(e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.index)
    const item = this.data.list && this.data.list[index]
    if (!item) return
    if (item.isClock === 1) {
      ui.toast('您已经打过卡了')
      return
    }
    this.setData({
      showClockForm: true,
      activeId: String(item.id),
      activeIndex: index,
      spotName: '',
      desc: '',
      imageUrl: '',
      error: '',
    })
  },
  onTapCloseClockForm() {
    if (this.data.submitting) return
    this.setData({
      showClockForm: false,
      activeId: '',
      activeIndex: -1,
      spotName: '',
      desc: '',
      imageUrl: '',
    })
  },
  onInputSpotName(e) {
    this.setData({ spotName: (e && e.detail && e.detail.value) || '' })
  },
  onInputDesc(e) {
    this.setData({ desc: (e && e.detail && e.detail.value) || '' })
  },
  onInputImageUrl(e) {
    this.setData({ imageUrl: (e && e.detail && e.detail.value) || '' })
  },
  submitClock() {
    if (this.data.submitting) return
    if (!this.data.activeId) {
      ui.toast('缺少打卡参数')
      return
    }

    const spotName = String(this.data.spotName || '').trim()
    if (!spotName) {
      ui.toast('请输入打卡地点')
      return
    }

    const imageUrl = String(this.data.imageUrl || '').trim() || DEFAULT_CLOCK_IMAGE
    const desc = String(this.data.desc || '').trim()
    this.setData({ submitting: true, error: '' })

    let longitude = 0
    let latitude = 0
    let address = ''

    return locationService
      .getLocation()
      .catch(() => ({ latitude: 0, longitude: 0 }))
      .then((coord) => {
        latitude = Number(coord && coord.latitude) || 0
        longitude = Number(coord && coord.longitude) || 0
        return request({
          path: '/index/transform',
          method: 'POST',
          data: { longitude, latitude },
          showLoading: false,
        }).catch(() => null)
      })
      .then((resp) => {
        const result = resp && resp.data && resp.data.result
        const rough = result && result.formatted_addresses && result.formatted_addresses.rough
        address = (result && result.address) || rough || spotName
        return request({
          path: '/user/hotel_clock',
          method: 'POST',
          data: {
            id: this.data.activeId,
            images: imageUrl,
            address,
            longitude,
            latitude,
            descs: desc,
            agency_user_id: safeGetStorage('guide') || '',
          },
          showLoading: true,
        })
      })
      .then((res) => {
        if (!res || typeof res !== 'object' || res.code !== 0) {
          ui.toast((res && res.msg) || '打卡失败')
          return
        }
        const nextList = this.data.list.slice()
        if (nextList[this.data.activeIndex]) {
          nextList[this.data.activeIndex].isClock = 1
          nextList[this.data.activeIndex].statusText = '已打卡'
        }
        this.setData({
          list: nextList,
          showClockForm: false,
          activeId: '',
          activeIndex: -1,
          spotName: '',
          desc: '',
          imageUrl: '',
        })
        ui.toast(res.msg || '打卡成功')
      })
      .catch((err) => this.setData({ error: String((err && (err.errMsg || err.message)) || err) }))
      .finally(() => this.setData({ submitting: false }))
  },
})
