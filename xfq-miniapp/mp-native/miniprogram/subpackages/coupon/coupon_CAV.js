const config = require('../../config')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')
const locationService = require('../../services/location')
const couponApi = require('../../services/api/coupon')

function safeParseJson(val) {
  if (!val) return null
  const raw = String(val).trim()
  if (!raw) return null
  try {
    return JSON.parse(raw)
  } catch (e) {}
  try {
    return JSON.parse(decodeURIComponent(raw))
  } catch (e) {}
  return null
}

function normalizeInfo(data) {
  if (!data || typeof data !== 'object') return null
  const useType = Number(data.use_type)
  return {
    couponTitle: data.coupon_title || '',
    couponPrice: data.coupon_price || '',
    useType,
    useTypeText: useType === 1 ? '线上' : '线下',
    timeText: '',
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    id: '',
    mid: 0,
    qrcodeUrl: '',
    coord: null,
    vrLatitude: 0,
    vrLongitude: 0,

    info: null,
    showMask: false,
    statusTitle: '核销中',
    statusMsg: '正在核销，请稍候..',
    error: null,
  },
  onLoad(options) {
    const id = (options && options.id) || ''
    const mid = Number(options && options.mid) || 0
    let qrcodeUrl = (options && (options.qrcode_url || options.qrcodeUrl)) || ''
    try {
      qrcodeUrl = decodeURIComponent(String(qrcodeUrl))
    } catch (e) {
      qrcodeUrl = String(qrcodeUrl)
    }
    const coord = safeParseJson(options && options.coord)

    this.setData({ id: String(id), mid, qrcodeUrl: String(qrcodeUrl), coord })

    if (!this.data.id || !this.data.mid || !this.data.qrcodeUrl) {
      ui
        .showModal({ title: '提示', content: '参数错误（缺少 id/mid/qrcode_url）', showCancel: false })
        .then(() => wx.navigateBack())
      return
    }

    this.coordPromise = locationService.getLocation().then((c) => {
      this.setData({ vrLatitude: c.latitude, vrLongitude: c.longitude })
      return c
    })

    if (this.data.hasBaseUrl) this.init()
  },
  onShow() {
    const user = auth.getUser()
    this.setData({ hasLogin: !!(user && user.token && user.uid) })
  },
  init() {
    const uid = auth.getUid()
    if (!uid) {
      ui.toast('请先登录')
      auth.reLaunchLogin({ redirect: '/pages/user/user' })
      return
    }

    this.setData({ error: null, showMask: false })

    couponApi
      .idToCoupon({ cuid: this.data.id }, { showLoading: true })
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          return null
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '请求失败')
          return null
        }
        const info = normalizeInfo(res.data)
        this.setData({ info })
        return info
      })
      .then((info) => {
        if (!info) return
        this.setData({ showMask: true, statusTitle: '核销中', statusMsg: '正在核销，请稍候..' })
        return this.writeoff()
      })
      .catch((err) => this.setData({ error: String((err && (err.errMsg || err.message)) || err) }))
  },
  writeoff() {
    const uid = auth.getUid()
    const coord = this.data.coord || {}
    const latitude = Number(coord.latitude)
    const longitude = Number(coord.longitude)

    return Promise.resolve(this.coordPromise)
      .then(() =>
        couponApi.writeoff(
          {
            userid: uid,
            mid: this.data.mid,
            coupon_issue_user_id: this.data.id,
            use_min_price: 999999,
            qrcode_url: this.data.qrcodeUrl,
            orderid: 0,
            latitude: Number.isFinite(latitude) ? latitude : 0,
            longitude: Number.isFinite(longitude) ? longitude : 0,
            vr_latitude: this.data.vrLatitude,
            vr_longitude: this.data.vrLongitude,
          },
          { showLoading: true }
        )
      )
      .then((res) => {
        if (!res || typeof res !== 'object') {
          this.setData({ statusTitle: '核销失败', statusMsg: '返回数据异常' })
          return
        }
        if (res.code === 0) {
          this.setData({ statusTitle: '核销成功', statusMsg: res.msg || '核销成功' })
          return
        }
        this.setData({ statusTitle: '核销失败', statusMsg: res.msg || '核销失败' })
      })
      .catch(() => this.setData({ statusTitle: '核销失败', statusMsg: '核销失败，请稍后重试' }))
  },
  onTapDone() {
    wx.navigateBack()
  },
})
