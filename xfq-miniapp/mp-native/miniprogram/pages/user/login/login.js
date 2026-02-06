const request = require('../../../services/request')
const auth = require('../../../services/auth')
const config = require('../../../config')

Page({
  data: {
    redirect: '',
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    loginResultText: '',
    error: null,
  },
  onLoad(options) {
    const redirect = (options && options.redirect) || ''
    this.setData({ redirect })
  },
  onTapLogin() {
    if (!this.data.hasBaseUrl) {
      wx.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
      return
    }
    wx.login({
      success: (res) => {
        const code = res && res.code
        if (!code) {
          this.setData({ error: 'wx.login 失败：未获取到 code' })
          return
        }

        request({ path: '/index/miniwxlogin', data: { code } })
          .then((ret) => {
            this.setData({ loginResultText: JSON.stringify(ret, null, 2), error: null })

            if (!ret || typeof ret !== 'object') {
              wx.showToast({ title: '登录返回异常', icon: 'none' })
              return
            }

            if (ret.code === 0) {
              const info = ret.data && ret.data.userinfo
              const user = {
                token: ret.data && ret.data.token,
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

              auth.setUser(user)

              const target = this.data.redirect || '/pages/index/index'
              wx.reLaunch({ url: target })
              return
            }

            if (ret.code === 4444) {
              wx.showModal({
                title: '提示',
                content: '当前账号未注册，需要先补全信息',
                showCancel: false,
              })
              return
            }

            wx.showToast({ title: ret.msg || '登录失败', icon: 'none' })
          })
          .catch((err) => {
            this.setData({ error: String(err && (err.errMsg || err.message) || err) })
            wx.showToast({ title: '登录失败', icon: 'none' })
          })
      },
      fail: (err) => {
        this.setData({ error: String(err && (err.errMsg || err.message) || err) })
      },
    })
  },
  onTapClearLogin() {
    auth.clearUser()
    wx.showToast({ title: '已清空登录态', icon: 'none' })
  },
})
