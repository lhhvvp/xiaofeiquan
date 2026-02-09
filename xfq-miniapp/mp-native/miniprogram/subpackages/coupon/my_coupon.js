const config = require('../../config')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')
const urlUtil = require('../../utils/url')
const locationService = require('../../services/location')
const systemService = require('../../services/system')
const couponApi = require('../../services/api/coupon')
const qrcodeUtil = require('../../utils/qrcode')

const NAV_PAYLOAD_KEY = '__nav_coupon_issue_user'

function normalizeStatus(status) {
  const s = Number(status)
  if (s === 0) return { status: 0, text: '未使用' }
  if (s === 1) return { status: 1, text: '已核销' }
  if (s === 2) return { status: 2, text: '已过期' }
  return { status: Number.isFinite(s) ? s : 0, text: `状态：${String(status)}` }
}

function getStatusImage(status) {
  const normalized = normalizeStatus(status)
  if (normalized.status === 1) return '/static/icon/001.png'
  if (normalized.status === 2) return '/static/icon/002.png'
  return ''
}

function formatDateTime(input) {
  if (input === null || typeof input === 'undefined') return ''
  if (typeof input === 'number') {
    const date = new Date(input < 1e12 ? input * 1000 : input)
    if (Number.isNaN(date.getTime())) return String(input)
    const y = String(date.getFullYear())
    const m = String(date.getMonth() + 1).padStart(2, '0')
    const d = String(date.getDate()).padStart(2, '0')
    const hh = String(date.getHours()).padStart(2, '0')
    const mm = String(date.getMinutes()).padStart(2, '0')
    const ss = String(date.getSeconds()).padStart(2, '0')
    return `${y}-${m}-${d} ${hh}:${mm}:${ss}`
  }
  const parsed = Date.parse(String(input))
  if (!Number.isNaN(parsed)) return formatDateTime(parsed)
  return String(input)
}

function buildTimeText(data) {
  if (!data || typeof data !== 'object') return ''
  const isPermanent = Number(data.is_permanent)
  if (isPermanent === 1) return '有效时间：永久有效'
  if (isPermanent === 2) {
    const start = Number(data.coupon_time_start) || 0
    const end = Number(data.coupon_time_end) || 0
    const startText = start ? formatDateTime(start) : '-'
    const endText = end ? formatDateTime(end) : '-'
    return `有效时间：${startText} 至 ${endText}`
  }
  if (typeof data.day !== 'undefined') return `有效时间：${data.day}天`
  return ''
}

function safeReadNavPayload() {
  try {
    return wx.getStorageSync(NAV_PAYLOAD_KEY)
  } catch (e) {
    return null
  }
}

function normalizeNavPayload(raw, baseUrl) {
  if (!raw || typeof raw !== 'object') return null
  const status = normalizeStatus(raw.status)
  const issue = raw.couponIssue && typeof raw.couponIssue === 'object' ? raw.couponIssue : null
  return {
    cuid: raw.id,
    couponTitle: raw.coupon_title || '',
    couponPrice: raw.coupon_price || '',
    createTimeText: raw.create_time || '',
    status: status.status,
    statusText: status.text,
    useType: typeof raw.use_type !== 'undefined' ? raw.use_type : null,
    enstrSalt: raw.enstr_salt || '',
    remarkHtml: urlUtil.normalizeRichTextHtml(raw.remark, baseUrl),
    useTypeDescHtml: urlUtil.normalizeRichTextHtml(raw.use_type_desc, baseUrl),
    issueCid: issue ? issue.cid : null,
  }
}

function normalizeWriteoff(writeoff) {
  if (!writeoff || typeof writeoff !== 'object') return null
  const user = writeoff.users && typeof writeoff.users === 'object' ? writeoff.users : null
  return {
    userName: (user && user.name) || '',
    createTime: writeoff.create_time || '',
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    cuid: '',
    couponIssueId: 0,
    latitude: 0,
    longitude: 0,

    info: null,
    suitable: null,
    showQrcode: false,
    qrcodeHint: '',
    qrcodeText: '',
    statusImage: '',
    nowText: '',
    error: null,
  },
  onLoad(options) {
    const cuid = (options && (options.cuid || options.id)) || ''
    this.setData({ cuid: String(cuid) })

    if (wx.setVisualEffectOnCapture) {
      try {
        wx.setVisualEffectOnCapture({ visualEffect: 'hidden' })
      } catch (e) {}
    }

    const navRaw = safeReadNavPayload()
    const navInfo = normalizeNavPayload(navRaw, this.data.baseUrl)
    if (navInfo && String(navInfo.cuid) === String(cuid)) {
      this.setData({ info: navInfo })
      if (navInfo.couponTitle) wx.setNavigationBarTitle({ title: navInfo.couponTitle })
    }

    this.clockTimer = setInterval(() => this.updateNowText(), 1000)
    this.updateNowText()

    this.coordPromise = locationService.getLocation().then((coord) => {
      this.setData({ latitude: coord.latitude, longitude: coord.longitude })
      return coord
    })

    if (this.data.hasBaseUrl) this.refresh()
  },
  onShow() {
    const user = auth.getUser()
    this.setData({ hasLogin: !!(user && user.token && user.uid) })
  },
  onUnload() {
    if (this.clockTimer) clearInterval(this.clockTimer)
    if (this.qrcodeTimer) clearTimeout(this.qrcodeTimer)
  },
  onPullDownRefresh() {
    if (!this.data.hasBaseUrl) {
      wx.stopPullDownRefresh()
      return
    }
    this.refresh({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  updateNowText() {
    this.setData({ nowText: formatDateTime(Date.now()) })
  },
  refresh({ showLoading = true } = {}) {
    if (!this.data.cuid) {
      ui.toast('缺少 cuid')
      return Promise.resolve()
    }
    if (!this.data.hasBaseUrl) {
      return ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
    }

    this.setData({ error: null, qrcodeHint: '', showQrcode: false })

    return Promise.resolve(this.coordPromise)
      .then(() => couponApi.idToCoupon({ cuid: this.data.cuid }, { showLoading }))
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          return null
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '请求失败')
          return null
        }

        const data = res.data && typeof res.data === 'object' ? res.data : {}
        const previous = this.data.info || {}
        const status = normalizeStatus(previous.status)
        const nextInfo = {
          ...previous,
          couponTitle: previous.couponTitle || data.coupon_title || '',
          couponPrice: previous.couponPrice || data.coupon_price || '',
          status: status.status,
          statusText: status.text,
          useType: typeof data.use_type !== 'undefined' ? data.use_type : previous.useType,
          timeText: buildTimeText(data),
          remarkHtml: urlUtil.normalizeRichTextHtml(data.remark, this.data.baseUrl),
          useTypeDescHtml: urlUtil.normalizeRichTextHtml(data.use_type_desc, this.data.baseUrl),
          writeoff: normalizeWriteoff(data.writeoff),
        }

        if (!nextInfo.enstrSalt && data.enstr_salt) nextInfo.enstrSalt = data.enstr_salt

        this.setData({ info: nextInfo, couponIssueId: Number(data.id) || 0 })
        this.setData({ statusImage: getStatusImage(nextInfo.status) })

        if (nextInfo.couponTitle) wx.setNavigationBarTitle({ title: nextInfo.couponTitle })

        return { data, info: nextInfo }
      })
      .then((payload) => {
        if (!payload) return
        const id = Number(payload.data && payload.data.id) || 0
        if (!id) return
        return couponApi
          .getApplicableMerchants(
            { id, latitude: this.data.latitude, longitude: this.data.longitude, page: 0, limit: 1 },
            { showLoading: false }
          )
          .then((res) => {
            if (!res || typeof res !== 'object' || res.code !== 0) return
            const list = Array.isArray(res.data) ? res.data : []
            if (!list.length) {
              this.setData({ suitable: { nickname: '暂无商家', distanceText: '' } })
              return
            }
            const first = list[0]
            const distance = Number(first.distance)
            this.setData({
              suitable: {
                nickname: first.nickname || '',
                distanceText: Number.isFinite(distance) ? `${distance.toFixed(2)}km` : '',
              },
            })
          })
      })
      .then(() => this.maybeRefreshQrCode())
      .catch((err) => {
        this.setData({ error: String((err && (err.errMsg || err.message)) || err) })
      })
  },
  maybeRefreshQrCode() {
    const info = this.data.info
    if (!info) return Promise.resolve()

    const status = normalizeStatus(info.status)
    const useType = Number(info.useType)
    const isOffline = Number.isFinite(useType) ? useType !== 1 : true

    if (!this.data.hasLogin) {
      this.setData({
        showQrcode: false,
        qrcodeHint: '未登录，无法生成核销码',
        statusImage: getStatusImage(status.status),
        info: { ...info, statusText: status.text },
      })
      return Promise.resolve()
    }
    if (!isOffline) {
      this.setData({
        showQrcode: false,
        statusImage: getStatusImage(status.status),
        info: { ...info, statusText: status.text || '线上核销券（无二维码）' },
      })
      return Promise.resolve()
    }
    if (status.status !== 0) {
      this.setData({
        showQrcode: false,
        statusImage: getStatusImage(status.status),
        info: { ...info, statusText: status.text },
      })
      return Promise.resolve()
    }

    this.setData({
      showQrcode: true,
      qrcodeHint: '核销码生成中..',
      statusImage: '',
      info: { ...info, statusText: status.text },
    })

    try {
      wx.setScreenBrightness({ value: 1 })
    } catch (e) {}

    return this.refreshQrCode({ showLoading: false })
  },
  refreshQrCode({ showLoading = false } = {}) {
    if (this.qrcodeTimer) clearTimeout(this.qrcodeTimer)
    const uid = auth.getUid()
    if (!uid) return Promise.resolve()

    const info = this.data.info || {}
    const salt = String(info.enstrSalt || '').trim()
    if (!salt) {
      this.setData({ qrcodeHint: '缺少 salt，无法生成核销码' })
      return Promise.resolve()
    }

    const coord = { latitude: this.data.latitude, longitude: this.data.longitude }

    systemService
      .fetchSystem({ force: true, showLoading: false })
      .then((system) => {
        const seconds = Number(system && system.is_qrcode_number)
        const ms = (Number.isFinite(seconds) && seconds > 0 ? seconds : 260) * 1000
        this.qrcodeTimer = setTimeout(() => this.refreshQrCode({ showLoading: false }), ms)
      })
      .catch(() => {
        this.qrcodeTimer = setTimeout(() => this.refreshQrCode({ showLoading: false }), 260000)
      })

    return couponApi
      .encryptAES({ id: this.data.cuid, salt, uid }, { showLoading })
      .then((res) => {
        if (!res || typeof res !== 'object') {
          this.setData({ qrcodeHint: '生成失败：返回数据异常' })
          return
        }
        if (res.code !== 0) {
          this.setData({ qrcodeHint: res.msg || '生成失败' })
          return
        }

        const data = res.data && typeof res.data === 'object' ? res.data : {}
        const uinfo = data.uinfo && typeof data.uinfo === 'object' ? data.uinfo : null

        const nextInfo = {
          ...info,
          userName: (uinfo && uinfo.name) || info.userName || '',
          userIdcard: (uinfo && uinfo.idcard) || info.userIdcard || '',
        }

        if (Number(data.write_off_status) === 1) {
          const status = normalizeStatus(1)
          this.setData({
            info: { ...nextInfo, status: status.status, statusText: status.text },
            showQrcode: false,
            statusImage: getStatusImage(status.status),
            qrcodeHint: '',
          })
          if (this.qrcodeTimer) clearTimeout(this.qrcodeTimer)
          return
        }

        const qrcodeText = JSON.stringify({ id: data.id, qrcode: data.qrcode_url, coord, type: 'user' })
        this.setData({ info: nextInfo, qrcodeHint: '核销码已更新', qrcodeText, statusImage: '' })
        return this.drawQrCode(qrcodeText)
      })
      .catch(() => {
        this.setData({ qrcodeHint: '生成失败，请稍后重试' })
      })
  },
  waitForNextTick() {
    return new Promise((resolve) => {
      if (typeof wx.nextTick === 'function') {
        wx.nextTick(() => resolve())
        return
      }
      setTimeout(resolve, 30)
    })
  },
  drawQrCode(text) {
    const value = String(text || '').trim()
    if (!value) return Promise.resolve()
    if (!this.data.showQrcode) return Promise.resolve()

    return this.waitForNextTick()
      .then(() =>
        qrcodeUtil.drawToCanvas({
          canvasId: 'qrcode',
          text: value,
          size: 220,
          margin: 0,
          page: this,
        })
      )
      .catch(() => {
        this.setData({ qrcodeHint: '绘制失败，请稍后重试' })
      })
  },
  onTapOpenApplicableMerchants() {
    const id = Number(this.data.couponIssueId) || 0
    if (!id) return
    wx.navigateTo({ url: `/subpackages/coupon/list?id=${encodeURIComponent(String(id))}` })
  },
})
