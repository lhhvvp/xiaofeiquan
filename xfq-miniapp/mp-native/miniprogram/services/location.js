const cache = require('../utils/cache')
const ui = require('../utils/ui')

const COORD_STORAGE_KEY = 'coord'
const COORD_TTL_SECONDS = 30

function normalizeCoord(coord) {
  if (!coord) return null
  if (typeof coord === 'string') {
    try {
      return normalizeCoord(JSON.parse(coord))
    } catch (e) {
      return null
    }
  }
  if (typeof coord !== 'object') return null
  const latitude = Number(coord.latitude)
  const longitude = Number(coord.longitude)
  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return null
  return { latitude, longitude }
}

function getCachedCoord() {
  const fromTtl = cache.getWithTTL(COORD_STORAGE_KEY)
  const normalized = normalizeCoord(fromTtl)
  if (normalized) return normalized

  // fallback: legacy may store plain string/object without TTL
  try {
    return normalizeCoord(wx.getStorageSync(COORD_STORAGE_KEY))
  } catch (e) {
    return null
  }
}

function setCachedCoord(coord) {
  const normalized = normalizeCoord(coord)
  if (!normalized) return
  cache.setWithTTL(COORD_STORAGE_KEY, normalized, COORD_TTL_SECONDS)
}

function openLocationSetting() {
  return new Promise((resolve) => {
    wx.openSetting({
      success(res) {
        resolve(res)
      },
      fail() {
        resolve(null)
      },
    })
  })
}

function getLocation({ cacheFirst = true, promptSetting = true } = {}) {
  if (cacheFirst) {
    const cached = getCachedCoord()
    if (cached) return Promise.resolve(cached)
  }

  return new Promise((resolve) => {
    wx.getLocation({
      type: 'gcj02',
      isHighAccuracy: true,
      success(res) {
        const coord = { latitude: res.latitude, longitude: res.longitude }
        setCachedCoord(coord)
        resolve(coord)
      },
      fail() {
        ui.toast('未获取到定位，请稍后重试')
        const fallback = { latitude: 1, longitude: 1 }
        setCachedCoord(fallback)
        resolve(fallback)

        if (promptSetting) {
          ui
            .showModal({
              title: '定位权限',
              content: '未获取到定位权限，请在设置中开启定位权限后重试。',
              confirmText: '去设置',
              cancelText: '稍后',
            })
            .then((modalRes) => {
              if (modalRes && modalRes.confirm) openLocationSetting()
            })
        }
      },
    })
  })
}

module.exports = {
  getLocation,
  getCachedCoord,
  setCachedCoord,
}

