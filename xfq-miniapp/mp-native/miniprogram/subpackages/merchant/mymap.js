const ui = require('../../utils/ui')
const locationService = require('../../services/location')

function toNumber(val) {
  const n = Number(val)
  return Number.isFinite(n) ? n : null
}

function buildInfo(latitude, longitude) {
  return `当前位置 经度:${longitude},纬度:${latitude}。`
}

function buildMarkers(latitude, longitude) {
  return [
    {
      id: 1,
      latitude,
      longitude,
    },
  ]
}

Page({
  data: {
    lat: 0,
    lng: 0,
    markers: [],
    info: '',
    updating: false,
  },
  onLoad(options) {
    const lat = toNumber(options && options.lat)
    const lng = toNumber(options && options.lng)
    if (lat !== null && lng !== null) {
      this.setLocation({ latitude: lat, longitude: lng })
      return
    }
    this.updateLocation({ cacheFirst: true })
  },
  setLocation(coord) {
    if (!coord) return
    this.setData({
      lat: coord.latitude,
      lng: coord.longitude,
      markers: buildMarkers(coord.latitude, coord.longitude),
      info: buildInfo(coord.latitude, coord.longitude),
    })
  },
  updateLocation({ cacheFirst = false } = {}) {
    if (this.data.updating) return Promise.resolve()
    this.setData({ updating: true })
    return locationService
      .getLocation({ cacheFirst, promptSetting: true })
      .then((coord) => {
        this.setLocation(coord)
        ui.toast('更新成功', { icon: 'success' })
      })
      .finally(() => this.setData({ updating: false }))
  },
  onTapCopy() {
    const info = this.data.info
    if (!info) return
    wx.setClipboardData({ data: String(info) })
  },
  onTapUpdate() {
    this.updateLocation({ cacheFirst: false })
  },
})

