const config = require('../../config')
const request = require('../../services/request')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')

function parseScene(sceneStr) {
  if (!sceneStr) return {}
  const obj = {}
  String(sceneStr)
    .split('*')
    .filter(Boolean)
    .forEach((part) => {
      const seg = part.split('/')
      if (seg.length >= 2) obj[seg[0]] = seg[1]
    })
  return obj
}

function ensureLogin() {
  const user = auth.getUser()
  if (user && user.openid && user.uid && user.token) return Promise.resolve(user)

  return new Promise((resolve, reject) => {
    wx.login({
      success: (res) => {
        const code = res && res.code
        if (!code) {
          reject(new Error('wx.login 失败：未获取到 code'))
          return
        }

        request({ path: '/index/miniwxlogin', data: { code } })
          .then((ret) => {
            if (!ret || typeof ret !== 'object') throw new Error('登录返回异常')
            if (ret.code === 0) {
              const info = ret.data && ret.data.userinfo
              const nextUser = {
                token: ret.data && ret.data.token,
                uid: info && info.id,
                name: !!(info && info.name),
                idcard: !!(info && info.idcard),
                mobile: !!(info && info.mobile),
                openid: info && info.openid,
                uuid: info && info.uuid,
                no: info && info.no,
              }
              auth.setUser(nextUser)
              resolve(nextUser)
              return
            }
            if (ret.code === 4444) {
              const err = new Error('当前账号未注册，需要先补全信息')
              err.code = 4444
              throw err
            }
            throw new Error(ret.msg || '登录失败')
          })
          .catch(reject)
      },
      fail: reject,
    })
  })
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),

    sceneRaw: '',
    scene: {},
    uuid: '',
    mid: '',
    statusText: '',
    error: null,
  },
  onLoad(options) {
    const raw = options && options.scene ? decodeURIComponent(options.scene) : ''
    const scene = parseScene(raw)
    const uuid = (scene && scene.uid) || ''
    const mid = (scene && scene.mid) || ''
    this.setData({ sceneRaw: raw, scene, uuid: String(uuid), mid: String(mid) })
  },
  onTapAuthorize() {
    if (!this.data.hasBaseUrl) {
      ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
      return
    }
    if (!this.data.uuid && !this.data.mid) {
      ui.showModal({ title: '提示', content: '参数错误：缺少 mid/uid', showCancel: false })
      return
    }

    this.setData({ error: null, statusText: '准备登录...' })

    ensureLogin()
      .then((user) => {
        const openid = user && user.openid
        const uid = user && user.uid
        if (!openid || !uid) throw new Error('登录态异常：缺少 openid/uid')

        return ui
          .showModal({
            title: '授权信息确认',
            content: '即将绑定商户核销人员，确认信息无误后，单击确定完成绑定！',
            confirmText: '确定',
            cancelText: '取消',
          })
          .then((modalRes) => {
            if (!modalRes || !modalRes.confirm) return null
            this.setData({ statusText: '提交绑定...' })
            return request({
              path: '/seller/bindCheckOpenid',
              method: 'POST',
              data: {
                uuid: this.data.uuid,
                mid: this.data.mid,
                openid,
                uid,
              },
              showLoading: true,
            })
          })
      })
      .then((res) => {
        if (!res) return
        this.setData({ statusText: '完成' })
        const msg = (res && res.msg) || '操作完成'
        ui
          .showModal({ title: '提示', content: msg, showCancel: false })
          .then(() => wx.reLaunch({ url: '/pages/index/index' }))
      })
      .catch((err) => {
        const msg = String((err && (err.errMsg || err.message)) || err)
        this.setData({ error: msg, statusText: '' })
        ui.showModal({ title: '失败', content: msg, showCancel: false })
      })
  },
})

