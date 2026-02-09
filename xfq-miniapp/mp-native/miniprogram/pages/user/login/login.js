const request = require('../../../services/request')
const auth = require('../../../services/auth')
const config = require('../../../config')
const ui = require('../../../utils/ui')

const TABBAR_PATH_SET = new Set([
  '/pages/index/index',
  '/pages/merchant/merchant',
  '/pages/tickets/tickets',
  '/pages/user/user',
])

function decodePath(path) {
  const raw = String(path || '')
  if (!raw) return ''
  try {
    return decodeURIComponent(raw)
  } catch (e) {
    return raw
  }
}

function buildUserFromLoginResp(ret) {
  const info = ret && ret.data && ret.data.userinfo
  return {
    token: ret && ret.data && ret.data.token,
    uid: info && info.id,
    name: info && info.name,
    idcard: info && info.idcard,
    mobile: info && info.mobile,
    nickname: info && info.nickname,
    headimgurl: info && info.headimgurl,
    openid: info && info.openid,
    uuid: info && info.uuid,
    no: info && info.no,
  }
}

Page({
  data: {
    redirect: '',
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,
    showAuthSheet: false,
    loginResultText: '',
    error: null,
  },

  onLoad(options) {
    this.setData({ redirect: decodePath(options && options.redirect) })
  },

  onShow() {
    const user = auth.getUser()
    this.setData({ hasLogin: !!(user && user.uid && user.token) })
  },

  onShareAppMessage() {
    return { title: '榆林市旅游消费平台', path: '/pages/index/index' }
  },

  onShareTimeline() {
    return { title: '榆林市旅游消费平台', path: '/pages/index/index' }
  },

  ensureBaseUrl() {
    if (this.data.hasBaseUrl) return true
    ui.showModal({
      title: '提示',
      content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
      showCancel: false,
    })
    return false
  },

  getRedirectTarget() {
    return this.data.redirect || '/pages/index/index'
  },

  jumpAfterLogin() {
    const target = this.getRedirectTarget()
    if (TABBAR_PATH_SET.has(target)) {
      wx.switchTab({ url: target })
      return
    }
    wx.reLaunch({ url: target })
  },

  onTapStart() {
    if (this.data.hasLogin) {
      this.jumpAfterLogin()
      return
    }
    this.setData({ showAuthSheet: true })
  },

  onTapCloseAuthSheet() {
    this.setData({ showAuthSheet: false })
  },

  onTapAgreeAuth() {
    this.setData({ showAuthSheet: false })
    this.onTapLogin()
  },

  onTapAgreement(e) {
    const type = (e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.type) || ''
    if (type === 'service') {
      wx.navigateTo({ url: `/subpackages/content/user/agreement?title=${encodeURIComponent('服务协议')}` })
      return
    }
    if (type === 'privacy') {
      wx.navigateTo({ url: `/subpackages/content/user/agreement?title=${encodeURIComponent('隐私政策')}` })
    }
  },

  onTapLogin() {
    if (!this.ensureBaseUrl()) return
    this.setData({ error: null })

    wx.login({
      success: (res) => {
        const code = res && res.code
        if (!code) {
          this.setData({ error: 'wx.login 失败：未获取到 code' })
          ui.toast('微信登录失败')
          return
        }

        request({ path: '/index/miniwxlogin', data: { code }, showLoading: true })
          .then((ret) => {
            this.setData({ loginResultText: JSON.stringify(ret, null, 2), error: null })

            if (!ret || typeof ret !== 'object') {
              ui.toast('登录返回异常')
              return
            }

            if (ret.code === 0) {
              auth.setUser(buildUserFromLoginResp(ret))
              this.setData({ hasLogin: true })
              ui.toast('登录成功', { icon: 'success' })
              this.jumpAfterLogin()
              return
            }

            if (ret.code === 4444) {
              ui.showModal({
                title: '提示',
                content: '当前账号未注册，需要先补全信息',
                showCancel: false,
              })
              return
            }

            ui.toast(ret.msg || '登录失败')
          })
          .catch((err) => {
            const msg = String((err && (err.errMsg || err.message)) || err)
            this.setData({ error: msg })
            ui.toast('登录失败')
          })
      },
      fail: (err) => {
        const msg = String((err && (err.errMsg || err.message)) || err)
        this.setData({ error: msg })
        ui.toast('微信登录失败')
      },
    })
  },

  onTapClearLogin() {
    auth.clearUser()
    this.setData({ hasLogin: false, loginResultText: '', error: null })
    ui.toast('已清空登录态')
  },
})
