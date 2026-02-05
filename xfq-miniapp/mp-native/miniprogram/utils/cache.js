function nowSeconds() {
  return Math.floor(Date.now() / 1000)
}

function set(key, value) {
  wx.setStorageSync(key, value)
}

function get(key) {
  return wx.getStorageSync(key)
}

function remove(key) {
  wx.removeStorageSync(key)
}

// Compatible with legacy uni-app format: JSON.stringify(value) + '|' + expireSeconds
function setWithTTL(key, value, ttlSeconds) {
  const expire = nowSeconds() + Number(ttlSeconds || 0)
  const payload = `${JSON.stringify(value)}|${expire}`
  wx.setStorageSync(key, payload)
}

function getWithTTL(key) {
  const raw = wx.getStorageSync(key)
  if (!raw) return null
  if (typeof raw !== 'string') return raw

  const idx = raw.lastIndexOf('|')
  if (idx <= 0) return raw

  const jsonPart = raw.slice(0, idx)
  const expirePart = raw.slice(idx + 1)
  const expire = Number(expirePart)
  if (!expire || Number.isNaN(expire)) return raw

  if (expire <= nowSeconds()) {
    wx.removeStorageSync(key)
    return null
  }

  try {
    return JSON.parse(jsonPart)
  } catch (error) {
    return null
  }
}

module.exports = {
  get,
  set,
  remove,
  setWithTTL,
  getWithTTL,
}

