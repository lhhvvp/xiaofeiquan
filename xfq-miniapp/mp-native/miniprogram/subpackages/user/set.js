const config = require('../../config')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')
const urlUtil = require('../../utils/url')
const userApi = require('../../services/api/user')

const DEFAULT_AVATAR = 'https://oss.wlxfq.dianfengcms.com/admins/d2d20480c4d629fc0336b4b639498830.png'

function joinUrl(baseUrl, path) {
  const base = String(baseUrl || '')
  const suffix = String(path || '')
  if (!suffix) return base
  if (!base) return suffix
  if (base.endsWith('/') && suffix.startsWith('/')) return base.slice(0, -1) + suffix
  if (!base.endsWith('/') && !suffix.startsWith('/')) return `${base}/${suffix}`
  return base + suffix
}

function safeJsonParse(input) {
  if (!input) return null
  if (typeof input === 'object') return input
  const raw = String(input || '')
  try {
    return JSON.parse(raw)
  } catch (e) {
    return null
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    avatarUrl: DEFAULT_AVATAR,
    nickname: '',
    username: '',
    mobile: '',
    idcard: '',

    uploading: false,
    saving: false,
    error: null,
  },
  onLoad() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    this.setData({ hasLogin })
    if (this.data.hasBaseUrl && hasLogin) this.fetchProfile({ showLoading: true })
  },
  onShow() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    if (hasLogin !== this.data.hasLogin) this.setData({ hasLogin })
    if (this.data.hasBaseUrl && hasLogin) this.fetchProfile({ showLoading: false })
  },
  ensureReady() {
    if (!this.data.hasBaseUrl) {
      ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
      return false
    }
    if (!this.data.hasLogin) {
      ui.toast('请先登录')
      return false
    }
    return true
  },
  fetchProfile({ showLoading = true } = {}) {
    if (!this.ensureReady()) return Promise.resolve()
    const uid = auth.getUid()
    if (!uid) return Promise.resolve()

    this.setData({ error: null })
    return userApi
      .getUserIndex({ uid }, { showLoading })
      .then((res) => {
        if (!res || typeof res !== 'object') {
          this.setData({ error: '返回数据异常' })
          return
        }
        if (res.code !== 0) {
          this.setData({ error: res.msg || '请求失败' })
          return
        }
        const data = res.data && typeof res.data === 'object' ? res.data : {}
        this.setData({
          nickname: data.nickname || '',
          mobile: data.mobile || '',
          username: data.name || '',
          idcard: data.idcard || '',
          avatarUrl: urlUtil.normalizeNetworkUrl(data.headimgurl, this.data.baseUrl) || this.data.avatarUrl,
        })
      })
      .catch((err) => this.setData({ error: String((err && (err.errMsg || err.message)) || err) }))
  },
  onTapGoLogin() {
    const redirect = '/subpackages/user/set'
    wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
  },
  onInputNickname(e) {
    this.setData({ nickname: (e && e.detail && e.detail.value) || '' })
  },
  onChooseAvatar(e) {
    const avatarUrl = e && e.detail && e.detail.avatarUrl
    if (!avatarUrl) return

    if (config.mock) {
      this.setData({ avatarUrl })
      return
    }

    if (!this.ensureReady()) return

    this.uploadAvatar(avatarUrl)
  },
  uploadAvatar(filePath) {
    if (this.data.uploading) return
    const baseUrl = String(this.data.baseUrl || '').trim()
    const isHttp = baseUrl.startsWith('http://') || baseUrl.startsWith('https://')
    if (!this.data.hasBaseUrl || !isHttp) {
      ui.toast('baseUrl 非法，无法上传')
      this.setData({ avatarUrl: filePath })
      return
    }

    const uid = auth.getUid()
    if (!uid) {
      ui.toast('请先登录')
      return
    }

    const url = joinUrl(baseUrl, '/upload/index')

    this.setData({ uploading: true, error: null, avatarUrl: filePath })
    wx.showLoading({ title: '上传中..', mask: true })

    wx.uploadFile({
      url,
      filePath,
      name: 'file',
      header: auth.getAuthHeaders(),
      formData: { uid },
      success: (res) => {
        if (!res || res.statusCode !== 200) {
          ui.toast('上传失败')
          return
        }
        const body = safeJsonParse(res.data)
        if (!body || typeof body !== 'object') {
          ui.toast('上传返回异常')
          return
        }
        if (body.code !== 0) {
          ui.toast(body.msg || '上传失败')
          return
        }
        const rawUrl = body.url || (body.data && body.data.url) || (typeof body.data === 'string' ? body.data : '')
        if (rawUrl) {
          this.setData({ avatarUrl: urlUtil.normalizeNetworkUrl(rawUrl, this.data.baseUrl) })
        }
        ui.toast(body.msg || '上传成功', { icon: 'success' })
      },
      fail: (err) => {
        this.setData({ error: String((err && (err.errMsg || err.message)) || err) })
        ui.toast('上传失败')
      },
      complete: () => {
        wx.hideLoading()
        this.setData({ uploading: false })
      },
    })
  },
  onTapSave() {
    if (this.data.saving) return
    if (!this.ensureReady()) return

    const uid = auth.getUid()
    if (!uid) return

    const nickname = String(this.data.nickname || '').trim()
    if (!nickname) {
      ui.toast('请输入昵称')
      return
    }

    const headimgurl = this.data.avatarUrl

    this.setData({ saving: true, error: null })

    userApi
      .editUser({ id: uid, nickname, headimgurl }, { showLoading: true })
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          return
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '提交失败')
          return
        }

        const user = auth.getUser()
        auth.setUser({ ...(user || {}), nickname, headimgurl })

        ui.toast(res.msg || '提交成功', { icon: 'success' })
        setTimeout(() => wx.navigateBack(), 600)
      })
      .catch((err) => this.setData({ error: String((err && (err.errMsg || err.message)) || err) }))
      .finally(() => this.setData({ saving: false }))
  },
  onRetry() {
    this.fetchProfile({ showLoading: false })
  },
})
