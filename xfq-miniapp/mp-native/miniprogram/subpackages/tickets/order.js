const config = require('../../config')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')
const locationService = require('../../services/location')
const ticketsApi = require('../../services/api/tickets')
const userApi = require('../../services/api/user')
const paymentUtil = require('../../utils/payment')

const PAY_RESPONSE_KEY = '__last_ticket_pay_response'

function normalizePriceItem(item) {
  if (!item || typeof item !== 'object') return null
  return {
    date: item.date || '',
    price: Number(item.price || 0),
    stock: Number(item.stock || 0),
  }
}

function clampInt(val, { min = 1, max = 999 } = {}) {
  const n = Number(val)
  if (!Number.isFinite(n)) return min
  const int = Math.floor(n)
  return Math.max(min, Math.min(max, int))
}

function calcTotalPrice(quantity, unitPrice) {
  const q = Number(quantity || 0)
  const p = Number(unitPrice || 0)
  if (!Number.isFinite(q) || !Number.isFinite(p)) return '0'
  const total = q * p
  if (!Number.isFinite(total)) return '0'
  return String(total)
}

function normalizeTouristItem(item) {
  if (!item || typeof item !== 'object') return null
  return {
    id: item.id,
    fullname: item.fullname || '',
    mobile: item.mobile || '',
    cert_id: item.cert_id || '',
    cert_type: item.cert_type,
  }
}

function buildTouristPayload(tourists) {
  const list = Array.isArray(tourists) ? tourists : []
  return list
    .map((t) => ({
      tourist_fullname: t.fullname,
      tourist_cert_type: t.cert_type || 1,
      tourist_cert_id: t.cert_id,
      tourist_mobile: t.mobile,
    }))
    .filter((t) => t.tourist_fullname && t.tourist_cert_id && t.tourist_mobile)
}

function safeSetStorage(key, val) {
  try {
    wx.setStorageSync(key, val)
  } catch (e) {}
}

function safeGetStorage(key) {
  try {
    return wx.getStorageSync(key)
  } catch (e) {
    return null
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    ticketId: '',
    priceList: [],
    dateOptions: [],
    selectedIndex: 0,
    selectedDate: '',
    unitPrice: 0,
    stock: 0,
    maxQuantity: 1,
    quantity: 1,

    contactName: '',
    contactPhone: '',
    contactIdcard: '',
    orderRemark: '',

    touristList: [],
    selectedTourists: [],
    autoTouristHint: '',

    showTouristModal: false,
    touristTempIds: [],
    touristTempCount: 0,

    submitting: false,
    payResultText: '',
    error: null,
  },
  onLoad(options) {
    const ticketId = (options && (options.id || options.ticket_id)) || ''
    this.setData({ ticketId: String(ticketId) })

    this.coordPromise = locationService.getLocation().then((coord) => {
      this.coord = coord
      return coord
    })

    if (this.data.hasBaseUrl) this.refresh()
  },
  onShow() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    this.setData({ hasLogin })
    if (hasLogin) this.ensureUserContext()
  },
  onPullDownRefresh() {
    if (!this.data.hasBaseUrl) {
      wx.stopPullDownRefresh()
      return
    }
    this.refresh({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  refresh({ showLoading = true } = {}) {
    if (!this.data.ticketId) {
      ui.toast('缺少 ticket_id')
      return Promise.resolve()
    }
    if (!this.data.hasBaseUrl) {
      return ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
    }

    this.setData({ error: null })

    return Promise.all([this.fetchPriceList({ showLoading }), this.ensureUserContext({ showLoading: false })]).catch(
      () => {}
    )
  },
  ensureUserContext({ showLoading = true } = {}) {
    if (!this.data.hasLogin) return Promise.resolve()
    return Promise.all([this.fetchContact({ showLoading }), this.fetchTouristList({ showLoading: false })]).catch(
      () => {}
    )
  },
  fetchPriceList({ showLoading = true } = {}) {
    return ticketsApi
      .getTicketPrice({ ticket_id: this.data.ticketId, channel: 'online' }, { showLoading })
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          return
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '请求失败')
          return
        }
        const list = Array.isArray(res.data) ? res.data : []
        const normalized = list.map(normalizePriceItem).filter(Boolean)
        const dateOptions = normalized.map((it) => it.date)
        const first = normalized[0] || { date: '', price: 0, stock: 0 }

        this.setData({
          priceList: normalized,
          dateOptions,
          selectedIndex: 0,
          selectedDate: first.date,
          unitPrice: first.price,
          stock: first.stock,
        })
        this.recalcQuantityBounds()
      })
      .catch((err) => {
        this.setData({ error: String((err && (err.errMsg || err.message)) || err) })
      })
  },
  fetchContact({ showLoading = true } = {}) {
    const uid = auth.getUid()
    if (!uid) return Promise.resolve()
    return userApi
      .getAuthInfo({ uid }, { showLoading })
      .then((res) => {
        if (!res || typeof res !== 'object') return
        if (res.code !== 0) return
        const data = res.data && typeof res.data === 'object' ? res.data : {}
        const name = data.name || ''
        const mobile = data.mobile || ''
        const idcard = data.idcard || ''

        const updates = {}
        if (!this.data.contactName && name) updates.contactName = name
        if (!this.data.contactPhone && mobile) updates.contactPhone = mobile
        if (!this.data.contactIdcard && idcard) updates.contactIdcard = idcard
        if (Object.keys(updates).length) this.setData(updates)

        if (!this.data.selectedTourists.length && name && mobile && idcard) {
          this.setData({
            selectedTourists: [{ id: 'self', fullname: name, mobile, cert_id: idcard, cert_type: 1 }],
            autoTouristHint: '已默认使用本人信息作为游客（如需多人，请选择常用游客）',
          })
          this.recalcQuantityBounds()
        }
      })
      .catch(() => {})
  },
  fetchTouristList({ showLoading = true } = {}) {
    return userApi
      .getTouristList({ page: 1, page_size: 999 }, { showLoading })
      .then((res) => {
        if (!res || typeof res !== 'object') return
        if (res.code !== 0) return
        const data = res.data && res.data.data
        const list = Array.isArray(data) ? data : []
        const normalized = list.map(normalizeTouristItem).filter(Boolean)
        this.setData({ touristList: normalized })
        this.recalcQuantityBounds()
      })
      .catch(() => {})
  },
  recalcQuantityBounds() {
    const stock = clampInt(this.data.stock || 1, { min: 1, max: 999 })
    const touristCount = Array.isArray(this.data.touristList) ? this.data.touristList.length : 0
    const hasSelf = Array.isArray(this.data.selectedTourists)
      ? this.data.selectedTourists.some((t) => String(t.id) === 'self')
      : false
    const maxByTourists = touristCount > 0 ? touristCount : hasSelf ? 1 : 1
    const maxQuantity = Math.max(1, Math.min(stock, maxByTourists))
    const quantity = clampInt(this.data.quantity || 1, { min: 1, max: maxQuantity })

    let selectedTourists = this.data.selectedTourists || []
    if (Array.isArray(selectedTourists) && selectedTourists.length > quantity) {
      selectedTourists = selectedTourists.slice(0, quantity)
    }

    this.setData({ maxQuantity, quantity, selectedTourists, totalPrice: calcTotalPrice(quantity, this.data.unitPrice) })
  },
  onPickDate(e) {
    const index = Number(e && e.detail && e.detail.value) || 0
    const item = (this.data.priceList && this.data.priceList[index]) || null
    if (!item) return
    this.setData({
      selectedIndex: index,
      selectedDate: item.date,
      unitPrice: item.price,
      stock: item.stock,
    })
    this.recalcQuantityBounds()
  },
  onTapMinus() {
    const next = clampInt(this.data.quantity - 1, { min: 1, max: this.data.maxQuantity })
    this.setData({ quantity: next })
    this.recalcQuantityBounds()
  },
  onTapPlus() {
    const next = clampInt(this.data.quantity + 1, { min: 1, max: this.data.maxQuantity })
    this.setData({ quantity: next })
    this.recalcQuantityBounds()
  },
  onInputQuantity(e) {
    const value = e && e.detail && e.detail.value
    const next = clampInt(value, { min: 1, max: this.data.maxQuantity })
    this.setData({ quantity: next })
    this.recalcQuantityBounds()
  },
  onInputContactName(e) {
    this.setData({ contactName: (e && e.detail && e.detail.value) || '' })
  },
  onInputContactPhone(e) {
    this.setData({ contactPhone: (e && e.detail && e.detail.value) || '' })
  },
  onInputRemark(e) {
    this.setData({ orderRemark: (e && e.detail && e.detail.value) || '' })
  },
  ensureLoginOrPrompt() {
    const uid = auth.getUid()
    if (uid) return Promise.resolve(uid)
    const redirect = `/subpackages/tickets/order?id=${encodeURIComponent(String(this.data.ticketId || ''))}`
    return ui
      .showModal({
        title: '提示',
        content: '下单需要先登录，是否现在去登录？',
        confirmText: '去登录',
        cancelText: '取消',
      })
      .then((res) => {
        if (res && res.confirm) {
          wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
        }
        return null
      })
  },
  onTapChooseTourists() {
    this.ensureLoginOrPrompt().then((uid) => {
      if (!uid) return
      this.openTouristModal()
    })
  },
  openTouristModal() {
    const selectedIds = new Set((this.data.selectedTourists || []).map((t) => String(t.id)))
    const selectedCount = selectedIds.size
    const limit = this.data.quantity || 1

    const touristList = (this.data.touristList || []).map((t) => {
      const checked = selectedIds.has(String(t.id))
      const disabled = !checked && selectedCount >= limit
      return { ...t, checked, disabled }
    })

    const touristTempIds = Array.from(selectedIds)

    this.setData({
      touristList,
      showTouristModal: true,
      touristTempIds,
      touristTempCount: touristTempIds.length,
    })
  },
  onTapCloseTouristModal() {
    this.setData({ showTouristModal: false, touristTempIds: [], touristTempCount: 0 })
  },
  onTouristCheckboxChange(e) {
    const raw = (e && e.detail && e.detail.value) || []
    const selected = Array.isArray(raw) ? raw.map(String) : []
    const limit = this.data.quantity || 1
    const trimmed = selected.slice(0, limit)
    const selectedSet = new Set(trimmed)

    const touristList = (this.data.touristList || []).map((t) => {
      const checked = selectedSet.has(String(t.id))
      const disabled = !checked && selectedSet.size >= limit
      return { ...t, checked, disabled }
    })

    this.setData({ touristTempIds: trimmed, touristTempCount: trimmed.length, touristList })
  },
  onTapConfirmTourists() {
    const limit = this.data.quantity || 1
    const ids = this.data.touristTempIds || []
    if (ids.length !== limit) {
      ui.toast(`还需选择 ${Math.max(0, limit - ids.length)} 人`)
      return
    }

    const idSet = new Set(ids.map(String))
    const selectedTourists = (this.data.touristList || []).filter((t) => idSet.has(String(t.id)))
    this.setData({ selectedTourists, showTouristModal: false, autoTouristHint: '' })
    this.recalcQuantityBounds()
  },
  onTapCopyPayResult() {
    const text = this.data.payResultText
    if (!text) return
    wx.setClipboardData({ data: String(text) })
  },
  onTapSubmit() {
    if (this.data.submitting) return
    if (!this.data.hasBaseUrl) return

    this.ensureLoginOrPrompt().then((uid) => {
      if (!uid) return
      const user = auth.getUser()
      const openid = user && user.openid
      const uuid = user && user.uuid
      if (!openid || !uuid) {
        ui.toast('登录信息缺失（openid/uuid）')
        return
      }

      const ticket_date = String(this.data.selectedDate || '').trim()
      if (!ticket_date) {
        ui.toast('请选择日期')
        return
      }

      const contact_man = String(this.data.contactName || '').trim()
      const contact_phone = String(this.data.contactPhone || '').trim()
      if (!contact_man) {
        ui.toast('请完善联系人姓名')
        return
      }
      if (!contact_phone) {
        ui.toast('请完善联系人手机号')
        return
      }

      const number = clampInt(this.data.quantity || 1, { min: 1, max: this.data.maxQuantity })
      const tourists = this.data.selectedTourists || []
      if (!Array.isArray(tourists) || tourists.length !== number) {
        ui.toast('游客人数需与数量一致')
        return
      }

      const tourist = buildTouristPayload(tourists)
      if (tourist.length !== number) {
        ui.toast('游客信息不完整')
        return
      }

      const maxPrice = Number(this.data.unitPrice || 0) * number
      const data = [
        {
          uuno: this.data.ticketId,
          number,
          price: String(maxPrice),
          tourist,
        },
      ]

      const contact = { contact_man, contact_phone }

      this.setData({ submitting: true, error: null })

      Promise.resolve(this.coordPromise)
        .then((coord) => {
          const submit = {
            openid,
            uuid,
            ticket_date,
            data: JSON.stringify(data),
            contact: JSON.stringify(contact),
            create_lat: coord && coord.latitude,
            create_lng: coord && coord.longitude,
            order_remark: this.data.orderRemark || '',
          }
          return ticketsApi.pay(submit, { showLoading: true })
        })
        .then((res) => {
          if (!res || typeof res !== 'object') {
            ui.toast('返回数据异常')
            return
          }
          if (res.code !== 0) {
            ui.showModal({ title: '提示', content: res.msg || '下单失败', showCancel: false })
            return
          }

          safeSetStorage(PAY_RESPONSE_KEY, res.data || res)
          const payResultText = JSON.stringify(res, null, 2)
          this.setData({ payResultText })

          return paymentUtil.requestWxPayment(res).then(
            () => {
              wx.redirectTo({ url: '/pages/user/paySuccess' })
            },
            (err) => {
              const msg = String((err && (err.errMsg || err.message)) || err)
              if (msg.indexOf('cancel') >= 0) {
                ui.toast('已取消支付')
                return
              }
              ui.toast('支付失败，请稍后重试')
            }
          )
        })
        .catch((err) => {
          this.setData({ error: String((err && (err.errMsg || err.message)) || err) })
          ui.toast('下单失败，请稍后重试')
        })
        .finally(() => this.setData({ submitting: false }))
    })
  },
  noop() {},
  onReady() {
    const cached = safeGetStorage(PAY_RESPONSE_KEY)
    if (cached) this.setData({ payResultText: JSON.stringify(cached, null, 2) })
  },
})
