const config = require('../../../config')
const auth = require('../../../services/auth')
const ui = require('../../../utils/ui')
const locationService = require('../../../services/location')
const apptApi = require('../../../services/api/appt')
const userApi = require('../../../services/api/user')

function clampInt(val, { min = 1, max = 999 } = {}) {
  const n = Number(val)
  if (!Number.isFinite(n)) return min
  const int = Math.floor(n)
  return Math.max(min, Math.min(max, int))
}

function normalizeSlot(item) {
  if (!item || typeof item !== 'object') return null
  const stock = Number(item.stock || 0)
  return {
    id: item.id,
    timeStartText: item.time_start_text || '',
    timeEndText: item.time_end_text || '',
    stock: Number.isFinite(stock) ? stock : 0,
    raw: item,
  }
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
      fullname: t.fullname,
      cert_type: t.cert_type || 1,
      cert_id: t.cert_id,
      mobile: t.mobile,
    }))
    .filter((t) => t.fullname && t.cert_id && t.mobile)
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    sellerId: '',
    latitude: 0,
    longitude: 0,

    maxNumber: 1,
    scheduleMap: {},
    dateOptions: [],
    selectedDateIndex: 0,
    selectedDate: '',

    slotList: [],
    selectedSlotIndex: -1,
    selectedSlotId: '',
    selectedSlotStock: 0,

    maxQuantity: 1,
    quantity: 1,

    fullname: '',
    idcard: '',
    phone: '',

    touristList: [],
    selectedTourists: [],
    autoTouristHint: '',

    showTouristModal: false,
    touristTempIds: [],
    touristTempCount: 0,

    submitting: false,
    error: null,
  },
  onLoad(options) {
    const sellerId = (options && (options.seller_id || options.sellerId)) || ''
    this.setData({ sellerId: String(sellerId) })
    wx.setNavigationBarTitle({ title: '立即预约' })

    this.coordPromise = locationService.getLocation().then((coord) => {
      this.setData({ latitude: coord.latitude, longitude: coord.longitude })
      return coord
    })

    if (this.data.hasBaseUrl) this.refreshSchedule()
  },
  onShow() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    this.setData({ hasLogin })
    if (hasLogin) this.ensureUserContext({ showLoading: false })
  },
  onPullDownRefresh() {
    this.refresh({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  refresh({ showLoading = true } = {}) {
    if (!this.data.hasBaseUrl) {
      wx.stopPullDownRefresh()
      return Promise.resolve()
    }
    return Promise.all([this.refreshSchedule({ showLoading }), this.ensureUserContext({ showLoading: false })]).catch(
      () => {}
    )
  },
  refreshSchedule({ showLoading = true } = {}) {
    if (!this.data.sellerId) {
      ui.toast('缺少 seller_id')
      return Promise.resolve()
    }
    if (!this.data.hasBaseUrl) return Promise.resolve()

    this.setData({ error: null })

    return apptApi
      .getDatetime({ seller_id: this.data.sellerId }, { showLoading })
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          return
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '请求失败')
          return
        }
        const data = res.data && typeof res.data === 'object' ? res.data : {}
        const scheduleMap = (data.list && typeof data.list === 'object' ? data.list : {}) || {}
        const dateOptions = Object.keys(scheduleMap)
        const maxNumber = clampInt(data.number || 1, { min: 1, max: 999 })

        if (!dateOptions.length) {
          this.setData({
            maxNumber,
            scheduleMap: {},
            dateOptions: [],
            selectedDateIndex: 0,
            selectedDate: '',
            slotList: [],
            selectedSlotIndex: -1,
            selectedSlotId: '',
            selectedSlotStock: 0,
          })
          this.recalcQuantityBounds()
          return
        }

        let selectedDateIndex = 0
        const today = new Date().toISOString().slice(0, 10)
        const todayIndex = dateOptions.indexOf(today)
        if (todayIndex >= 0) selectedDateIndex = todayIndex

        const selectedDate = dateOptions[selectedDateIndex] || dateOptions[0]
        const slots = Array.isArray(scheduleMap[selectedDate]) ? scheduleMap[selectedDate] : []
        const slotList = slots.map(normalizeSlot).filter(Boolean)

        this.setData({
          maxNumber,
          scheduleMap,
          dateOptions,
          selectedDateIndex,
          selectedDate,
          slotList,
          selectedSlotIndex: -1,
          selectedSlotId: '',
          selectedSlotStock: 0,
        })
        this.recalcQuantityBounds()
      })
      .catch((err) => this.setData({ error: String((err && (err.errMsg || err.message)) || err) }))
  },
  ensureUserContext({ showLoading = true } = {}) {
    if (!this.data.hasLogin) return Promise.resolve()
    if (!this.data.hasBaseUrl) return Promise.resolve()
    return Promise.all([this.fetchContact({ showLoading }), this.fetchTouristList({ showLoading: false })]).catch(
      () => {}
    )
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
        if (!this.data.fullname && name) updates.fullname = name
        if (!this.data.phone && mobile) updates.phone = mobile
        if (!this.data.idcard && idcard) updates.idcard = idcard
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
    const maxBySlotStock = clampInt(this.data.selectedSlotStock || 1, { min: 1, max: 999 })
    const touristCount = Array.isArray(this.data.touristList) ? this.data.touristList.length : 0
    const hasSelf = Array.isArray(this.data.selectedTourists)
      ? this.data.selectedTourists.some((t) => String(t.id) === 'self')
      : false
    const maxByTourists = touristCount > 0 ? touristCount : hasSelf ? 1 : 1
    const maxByBackend = clampInt(this.data.maxNumber || 1, { min: 1, max: 999 })

    const maxQuantity = Math.max(1, Math.min(maxBySlotStock, maxByTourists, maxByBackend))
    const quantity = clampInt(this.data.quantity || 1, { min: 1, max: maxQuantity })

    let selectedTourists = this.data.selectedTourists || []
    if (Array.isArray(selectedTourists) && selectedTourists.length > quantity) {
      selectedTourists = selectedTourists.slice(0, quantity)
    }

    this.setData({ maxQuantity, quantity, selectedTourists })
  },
  onPickDate(e) {
    const index = Number(e && e.detail && e.detail.value) || 0
    const dateOptions = this.data.dateOptions || []
    const selectedDate = dateOptions[index]
    if (!selectedDate) return
    const scheduleMap = this.data.scheduleMap || {}
    const slots = Array.isArray(scheduleMap[selectedDate]) ? scheduleMap[selectedDate] : []
    const slotList = slots.map(normalizeSlot).filter(Boolean)
    this.setData({
      selectedDateIndex: index,
      selectedDate,
      slotList,
      selectedSlotIndex: -1,
      selectedSlotId: '',
      selectedSlotStock: 0,
    })
    this.recalcQuantityBounds()
  },
  onTapSlot(e) {
    const index = Number(e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.index)
    const slot = this.data.slotList && this.data.slotList[index]
    if (!slot) return
    this.setData({
      selectedSlotIndex: index,
      selectedSlotId: String(slot.id || ''),
      selectedSlotStock: Number(slot.stock || 0),
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
  onInputFullname(e) {
    this.setData({ fullname: (e && e.detail && e.detail.value) || '' })
  },
  onInputPhone(e) {
    this.setData({ phone: (e && e.detail && e.detail.value) || '' })
  },
  onInputIdcard(e) {
    this.setData({ idcard: (e && e.detail && e.detail.value) || '' })
  },
  ensureLoginOrPrompt() {
    const uid = auth.getUid()
    if (uid) return Promise.resolve(uid)
    const redirect = `/subpackages/user/subscribe/subscribe?seller_id=${encodeURIComponent(String(this.data.sellerId || ''))}`
    return ui
      .showModal({
        title: '提示',
        content: '预约需要先登录，是否现在去登录？',
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

    const touristTempIds = Array.from(selectedIds).filter((id) => id !== 'self')

    this.setData({
      showTouristModal: true,
      touristList,
      touristTempIds,
      touristTempCount: touristTempIds.length,
    })
  },
  onTapCloseTouristModal() {
    this.setData({ showTouristModal: false })
  },
  onTouristCheckboxChange(e) {
    const ids = (e && e.detail && e.detail.value) || []
    const limit = this.data.quantity || 1
    const trimmed = Array.isArray(ids) ? ids.slice(0, limit) : []

    const selectedSet = new Set(trimmed.map(String))
    const selectedCount = selectedSet.size
    const touristList = (this.data.touristList || []).map((t) => {
      const checked = selectedSet.has(String(t.id))
      const disabled = !checked && selectedCount >= limit
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
  onTapSubmit() {
    if (this.data.submitting) return
    if (!this.data.hasBaseUrl) return

    this.ensureLoginOrPrompt().then((uid) => {
      if (!uid) return

      const datetime_id = String(this.data.selectedSlotId || '').trim()
      if (!datetime_id) {
        ui.toast('请选择时间段')
        return
      }

      const fullname = String(this.data.fullname || '').trim()
      const idcard = String(this.data.idcard || '').trim()
      const phone = String(this.data.phone || '').trim()
      if (!fullname) {
        ui.toast('请输入姓名')
        return
      }
      if (!idcard) {
        ui.toast('请输入身份证号')
        return
      }
      if (!phone) {
        ui.toast('请输入手机号')
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

      this.setData({ submitting: true, error: null })

      Promise.resolve(this.coordPromise)
        .then((coord) =>
          apptApi.createAppt(
            {
              datetime_id,
              fullname,
              idcard,
              phone,
              number,
              lat: coord && coord.latitude,
              lng: coord && coord.longitude,
              tourist: JSON.stringify(tourist),
            },
            { showLoading: true }
          )
        )
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
          setTimeout(() => wx.navigateTo({ url: '/subpackages/user/subscribe/my_list' }), 300)
        })
        .catch((err) => this.setData({ error: String((err && (err.errMsg || err.message)) || err) }))
        .finally(() => this.setData({ submitting: false }))
    })
  },
  noop() {},
})

