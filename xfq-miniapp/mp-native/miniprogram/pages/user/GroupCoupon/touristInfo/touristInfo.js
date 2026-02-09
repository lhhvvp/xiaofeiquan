const TOURIST_KEY = '__group_tourists'

function safeGetStorage(key) {
  try {
    return wx.getStorageSync(key)
  } catch (e) {
    return null
  }
}

Page({
  data: {
    list: [],
    empty: false,
  },
  onLoad(options) {
    const title = (options && options.title) || '旅行团'
    wx.setNavigationBarTitle({ title: `游客信息-${title}` })

    let list = []
    const raw = safeGetStorage(TOURIST_KEY)
    if (Array.isArray(raw)) list = raw
    if (!list.length && options && options.data) {
      try {
        const parsed = JSON.parse(decodeURIComponent(String(options.data)))
        if (Array.isArray(parsed)) list = parsed
      } catch (e) {}
    }
    this.setData({ list, empty: list.length === 0 })
  },
  onTapPhone(e) {
    const phone = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.phone
    if (!phone) return
    wx.makePhoneCall({ phoneNumber: String(phone) })
  },
})
