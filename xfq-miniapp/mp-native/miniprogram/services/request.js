const config = require('../config')
const auth = require('./auth')
const monitor = require('./monitor')

let loadingCount = 0

function createBaseUrlNotConfiguredError() {
  const error = new Error(
    'baseUrl 未配置：请创建 `xfq-miniapp/mp-native/miniprogram/config/local.js` 并设置 { baseUrl }'
  )
  error.code = 'BASE_URL_NOT_CONFIGURED'
  return error
}

function joinUrl(baseUrl, path) {
  const base = String(baseUrl || '')
  const suffix = String(path || '')
  if (!suffix) return base
  if (!base) return suffix
  if (base.endsWith('/') && suffix.startsWith('/')) return base.slice(0, -1) + suffix
  if (!base.endsWith('/') && !suffix.startsWith('/')) return `${base}/${suffix}`
  return base + suffix
}

function showLoading(title) {
  loadingCount += 1
  if (loadingCount === 1) {
    wx.showLoading({ title: title || '加载中...', mask: true })
  }
}

function hideLoading() {
  if (loadingCount <= 0) return
  loadingCount -= 1
  if (loadingCount === 0) wx.hideLoading()
}

function ensureNetwork() {
  return new Promise((resolve, reject) => {
    wx.getNetworkType({
      success(res) {
        if (res.networkType === 'none') {
          wx.showToast({ title: '当前无网络连接，请联网后重试', icon: 'none' })
          reject(new Error('NO_NETWORK'))
          return
        }
        resolve()
      },
      fail() {
        resolve()
      },
    })
  })
}

function shouldForceLogin(body) {
  if (!body) return false
  const code = body.code
  const msg = body.msg
  const forceCodes = new Set([110, 111, 112, 113, 114])
  if (forceCodes.has(code)) return true
  if (typeof msg === 'string' && msg.indexOf('用户信息异常') === 0) return true
  return false
}

function request({
  path,
  url,
  data = {},
  method = 'POST',
  headers = {},
  timeout = 15000,
  showLoading: shouldShowLoading = true,
  loadingTitle = '加载中...',
} = {}) {
  let didHideLoading = false

  function hideLoadingOnce() {
    if (!shouldShowLoading || didHideLoading) return
    didHideLoading = true
    hideLoading()
  }

  if (!url && (!config.baseUrl || !String(config.baseUrl).trim())) {
    return Promise.reject(createBaseUrlNotConfiguredError())
  }

  const requestUrl = url || joinUrl(String(config.baseUrl).trim(), path || '')
  if (!/^https?:\/\//.test(requestUrl)) {
    const error = new Error(`请求 URL 非法：${requestUrl}`)
    error.code = 'INVALID_REQUEST_URL'
    return Promise.reject(error)
  }

  const header = {
    'content-type': 'application/x-www-form-urlencoded',
    ...auth.getAuthHeaders(),
    ...headers,
  }

  const start = Date.now()

  if (shouldShowLoading) showLoading(loadingTitle)

  return ensureNetwork()
    .then(
      () =>
        new Promise((resolve, reject) => {
          wx.request({
            url: requestUrl,
            method,
            data,
            header,
            timeout,
            dataType: 'json',
            success(res) {
              const costTime = Date.now() - start
              const statusCode = res.statusCode
              if (statusCode !== 200) {
                monitor.trackRequest({
                  monitorId: requestUrl,
                  errorCode: statusCode,
                  errorMsg: JSON.stringify({ statusCode, url: requestUrl }),
                  costTime,
                })
                reject(new Error(`HTTP_${statusCode}`))
                return
              }

              const body = res.data
              if (shouldForceLogin(body)) {
                wx.showModal({
                  title: '提示',
                  content: '您还未登录/登录已过期，请重新登录',
                  showCancel: true,
                  cancelText: '取消',
                  confirmText: '重新登录',
                  success(modalRes) {
                    if (modalRes.confirm) {
                      auth.clearUser()
                      auth.reLaunchLogin({ redirect: '/pages/index/index' })
                    }
                  },
                })
                reject(new Error('AUTH_REQUIRED'))
                return
              }

              resolve(body)
            },
            fail(err) {
              const costTime = Date.now() - start
              monitor.trackRequest({
                monitorId: requestUrl,
                errorCode: 1,
                errorMsg: String((err && (err.errMsg || err.message)) || err),
                costTime,
              })
              reject(err)
            },
            complete() {
              hideLoadingOnce()
            },
          })
        })
    )
    .catch((err) => {
      hideLoadingOnce()
      throw err
    })
}

module.exports = request
