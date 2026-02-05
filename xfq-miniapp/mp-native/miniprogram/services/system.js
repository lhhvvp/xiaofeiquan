const cache = require('../utils/cache')
const request = require('./request')

const SYSTEM_STORAGE_KEY = 'system'
const SYSTEM_CACHE_KEY = '__system_cache'
const SYSTEM_TTL_SECONDS = 300

function getSystemFromStorage() {
  try {
    return wx.getStorageSync(SYSTEM_STORAGE_KEY) || null
  } catch (e) {
    return null
  }
}

function setSystemToStorage(system) {
  wx.setStorageSync(SYSTEM_STORAGE_KEY, system || {})
  cache.setWithTTL(SYSTEM_CACHE_KEY, system || {}, SYSTEM_TTL_SECONDS)
}

function getCachedSystem() {
  const cached = cache.getWithTTL(SYSTEM_CACHE_KEY)
  if (cached) return cached
  return getSystemFromStorage()
}

function fetchSystem({ force = false, showLoading = true } = {}) {
  if (!force) {
    const cached = cache.getWithTTL(SYSTEM_CACHE_KEY)
    if (cached) return Promise.resolve(cached)
  }

  return request({ path: '/index/system', method: 'POST', showLoading })
    .then((resp) => {
      if (resp && resp.code === 0 && resp.data) {
        setSystemToStorage(resp.data)
      }
      return resp
    })
    .then((resp) => {
      if (resp && resp.code === 0 && resp.data) return resp.data
      return resp
    })
}

module.exports = {
  getCachedSystem,
  fetchSystem,
  setSystemToStorage,
}

