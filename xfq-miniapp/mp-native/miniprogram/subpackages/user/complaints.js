const config = require('../../config')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')
const urlUtil = require('../../utils/url')
const userApi = require('../../services/api/user')

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

function sanitizeContent(input) {
  const str = String(input || '')
  return str.replace(/[<>]/g, (m) => (m === '<' ? '&lt;' : '&gt;'))
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    content: '',
    images: [],
    maxImages: 3,

    submitting: false,
    error: null,
  },
  onLoad() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    this.setData({ hasLogin })
  },
  onShow() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    if (hasLogin !== this.data.hasLogin) this.setData({ hasLogin })
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
  onTapGoLogin() {
    const redirect = '/subpackages/user/complaints'
    wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
  },
  onInputContent(e) {
    this.setData({ content: (e && e.detail && e.detail.value) || '' })
  },
  onTapAddImage() {
    if (!this.ensureReady()) return
    const remain = Math.max(0, Number(this.data.maxImages) - (this.data.images || []).length)
    if (!remain) return
    wx.chooseImage({
      count: remain,
      sizeType: ['compressed'],
      sourceType: ['album', 'camera'],
      success: (res) => {
        const paths = (res && res.tempFilePaths) || []
        if (!paths.length) return
        const next = (this.data.images || []).slice()
        paths.forEach((p) => {
          if (next.length >= this.data.maxImages) return
          next.push({ localPath: p, previewUrl: p, serverPath: '', uploading: false })
        })
        this.setData({ images: next })
        if (!config.mock) this.uploadPendingImages()
      },
    })
  },
  uploadPendingImages() {
    if (!this.ensureReady()) return
    const baseUrl = String(this.data.baseUrl || '').trim()
    const isHttp = baseUrl.startsWith('http://') || baseUrl.startsWith('https://')
    if (!isHttp) return

    const uid = auth.getUid()
    if (!uid) return

    const list = this.data.images || []
    const needUpload = list
      .map((item, index) => ({ item, index }))
      .filter((x) => x.item && x.item.localPath && !x.item.serverPath && !x.item.uploading)

    if (!needUpload.length) return

    const url = joinUrl(baseUrl, '/upload/index')

    needUpload.forEach(({ item, index }) => {
      this.setData({ [`images[${index}].uploading`]: true })

      wx.uploadFile({
        url,
        filePath: item.localPath,
        name: 'file',
        header: auth.getAuthHeaders(),
        formData: { uid },
        success: (res) => {
          if (!res || res.statusCode !== 200) {
            ui.toast('图片上传失败')
            return
          }
          const body = safeJsonParse(res.data)
          if (!body || typeof body !== 'object') {
            ui.toast('上传返回异常')
            return
          }
          if (body.code !== 0) {
            ui.toast(body.msg || '图片上传失败')
            return
          }
          const rawUrl = body.url || (body.data && body.data.url) || (typeof body.data === 'string' ? body.data : '')
          if (rawUrl) {
            this.setData({
              [`images[${index}].serverPath`]: String(rawUrl),
              [`images[${index}].previewUrl`]: urlUtil.normalizeNetworkUrl(rawUrl, baseUrl) || item.localPath,
            })
          }
        },
        fail: () => ui.toast('图片上传失败'),
        complete: () => this.setData({ [`images[${index}].uploading`]: false }),
      })
    })
  },
  onDeleteImage(e) {
    const index = Number(e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.index)
    const list = (this.data.images || []).slice()
    if (!Number.isInteger(index) || index < 0 || index >= list.length) return
    list.splice(index, 1)
    this.setData({ images: list })
  },
  onPreviewImage(e) {
    const index = Number(e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.index)
    const list = this.data.images || []
    const urls = list.map((it) => it.previewUrl).filter(Boolean)
    if (!urls.length) return
    const current = Number.isInteger(index) && urls[index] ? urls[index] : urls[0]
    wx.previewImage({ current, urls })
  },
  onTapSubmit() {
    if (this.data.submitting) return
    if (!this.ensureReady()) return

    const uid = auth.getUid()
    if (!uid) return

    const content = sanitizeContent(String(this.data.content || '').trim())
    if (!content) {
      ui.toast('请输入内容')
      return
    }

    if (!config.mock) {
      const hasUploading = (this.data.images || []).some((it) => it && it.uploading)
      if (hasUploading) {
        ui.toast('图片上传中，请稍后')
        return
      }
      const hasFailed = (this.data.images || []).some((it) => it && it.localPath && !it.serverPath)
      if (hasFailed && (this.data.images || []).length) {
        ui.toast('存在上传失败的图片，请删除后重试')
        return
      }
    }

    const images = (this.data.images || [])
      .map((it) => (config.mock ? it.localPath : it.serverPath))
      .filter(Boolean)
      .join(',')

    this.setData({ submitting: true, error: null })

    userApi
      .feedBack({ uid, content, images }, { showLoading: true })
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          return
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '提交失败')
          return
        }
        ui.toast(res.msg || '提交成功', { icon: 'success' })
        setTimeout(() => wx.navigateBack(), 600)
      })
      .catch((err) => this.setData({ error: String((err && (err.errMsg || err.message)) || err) }))
      .finally(() => this.setData({ submitting: false }))
  },
})

