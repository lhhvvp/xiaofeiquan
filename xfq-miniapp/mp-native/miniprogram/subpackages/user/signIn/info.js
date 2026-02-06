const config = require('../../../config')
const auth = require('../../../services/auth')
const ui = require('../../../utils/ui')
const urlUtil = require('../../../utils/url')
const locationService = require('../../../services/location')
const indexApi = require('../../../services/api/index')
const userApi = require('../../../services/api/user')

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
  if (typeof input !== 'string') return null
  try {
    return JSON.parse(input)
  } catch (e) {
    return null
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    id: '',
    tags: 1,

    latitude: 0,
    longitude: 0,
    address: '',
    spotName: '',
    desc: '',

    images: [],
    maxImages: 3,

    submitting: false,
    error: null,
  },
  onLoad(options) {
    const id = (options && (options.id || options.clock_id)) || ''
    const tags = Number(options && options.tags) || 1
    this.setData({ id: String(id), tags })
    wx.setNavigationBarTitle({ title: '打卡' })
  },
  onShow() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    this.setData({ hasLogin })
    if (hasLogin) this.ensureLocationAndSuggest()
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
    if (!this.data.id) {
      ui.toast('缺少参数 id')
      return false
    }
    return true
  },
  onTapGoLogin() {
    const redirect = `/subpackages/user/signIn/info?id=${encodeURIComponent(String(this.data.id || ''))}&tags=${encodeURIComponent(String(this.data.tags || 1))}`
    wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
  },
  ensureLocationAndSuggest() {
    if (!this.ensureReady()) return Promise.resolve()
    if (this.locationPromise) return this.locationPromise

    this.locationPromise = locationService
      .getLocation()
      .then((coord) => {
        this.setData({ latitude: coord.latitude, longitude: coord.longitude })
        return indexApi
          .transform({ longitude: coord.longitude, latitude: coord.latitude }, { showLoading: false })
          .then((res) => {
            if (!res || typeof res !== 'object') return
            if (res.code !== 0) return
            const result = (res.data && res.data.result) || {}
            const address = result.address || ''
            const rough =
              (result.formatted_addresses && result.formatted_addresses.rough) || result.formatted_addresses || ''
            const spotName = typeof rough === 'string' ? rough : ''
            const updates = {}
            if (!this.data.address && address) updates.address = address
            if (!this.data.spotName && spotName) updates.spotName = spotName
            if (Object.keys(updates).length) this.setData(updates)
          })
      })
      .catch(() => {})

    return this.locationPromise
  },
  onInputSpotName(e) {
    this.setData({ spotName: (e && e.detail && e.detail.value) || '' })
  },
  onInputDesc(e) {
    this.setData({ desc: (e && e.detail && e.detail.value) || '' })
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

    const spotName = String(this.data.spotName || '').trim()
    if (!spotName) {
      ui.toast('请输入打卡地点')
      return
    }

    if (!(this.data.images || []).length) {
      ui.toast('请上传图片')
      return
    }

    if (!config.mock) {
      const hasUploading = (this.data.images || []).some((it) => it && it.uploading)
      if (hasUploading) {
        ui.toast('图片上传中，请稍后')
        return
      }
      const hasFailed = (this.data.images || []).some((it) => it && it.localPath && !it.serverPath)
      if (hasFailed) {
        ui.toast('存在上传失败的图片，请删除后重试')
        return
      }
    }

    const images = (this.data.images || [])
      .map((it) => (config.mock ? it.localPath : it.serverPath))
      .filter(Boolean)
      .join(',')

    const payloadBase = {
      images,
      address: this.data.address || '',
      longitude: this.data.longitude || 0,
      latitude: this.data.latitude || 0,
    }

    const tags = Number(this.data.tags) || 1

    const apiCall =
      tags === 1
        ? userApi.clock(
            {
              clock_uid: uid,
              tour_issue_user_id: this.data.id,
              spot_name: spotName,
              dess: this.data.desc || '',
              ...payloadBase,
            },
            { showLoading: true }
          )
        : userApi.hotelClock(
            {
              id: this.data.id,
              spot_name: spotName,
              descs: this.data.desc || '',
              ...payloadBase,
            },
            { showLoading: true }
          )

    this.setData({ submitting: true, error: null })

    apiCall
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
  onRetry() {
    this.onTapSubmit()
  },
})

