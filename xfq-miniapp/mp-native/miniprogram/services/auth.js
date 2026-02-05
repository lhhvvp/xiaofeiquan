const cache = require('../utils/cache')

const USER_STORAGE_KEY = 'uerInfo'

function getUser() {
  const val = cache.get(USER_STORAGE_KEY)
  if (!val) return {}
  if (typeof val === 'object') return val
  if (typeof val !== 'string') return {}
  try {
    return JSON.parse(val)
  } catch (e) {
    return {}
  }
}

function setUser(user) {
  cache.set(USER_STORAGE_KEY, user || {})
}

function clearUser() {
  cache.remove(USER_STORAGE_KEY)
}

function getToken() {
  const user = getUser()
  return user && user.token
}

function getUid() {
  const user = getUser()
  return user && user.uid
}

function getAuthHeaders() {
  return {
    Token: getToken() || '',
    Userid: getUid() || '',
  }
}

function reLaunchLogin({ redirect } = {}) {
  const url = redirect
    ? `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}`
    : '/pages/user/login/login'
  wx.reLaunch({ url })
}

module.exports = {
  USER_STORAGE_KEY,
  getUser,
  setUser,
  clearUser,
  getToken,
  getUid,
  getAuthHeaders,
  reLaunchLogin,
}

