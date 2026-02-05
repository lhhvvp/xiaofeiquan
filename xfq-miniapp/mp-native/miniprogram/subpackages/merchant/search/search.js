const config = require('../../../config')
const ui = require('../../../utils/ui')
const urlUtil = require('../../../utils/url')
const merchantApi = require('../../../services/api/merchant')

const HISTORY_KEY = 'SearchHistory'

function readHistory() {
  try {
    const val = wx.getStorageSync(HISTORY_KEY)
    if (Array.isArray(val)) return val.filter(Boolean).map(String)
    return []
  } catch (e) {
    return []
  }
}

function writeHistory(history) {
  try {
    wx.setStorageSync(HISTORY_KEY, history || [])
  } catch (e) {}
}

function normalizeMerchant(item, baseUrl) {
  if (!item || typeof item !== 'object') return null
  const distance = Number(item.distance)
  const distanceText = Number.isFinite(distance) ? `${distance.toFixed(2)}km` : ''
  return {
    id: item.id,
    image: urlUtil.normalizeNetworkUrl(item.image, baseUrl),
    nickname: item.nickname || '',
    mobile: item.mobile || '',
    doBusinessTime: item.do_business_time || '',
    address: item.address || '',
    distanceText,
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),

    keyword: '',
    history: [],
    showHistory: true,

    list: [],
    empty: false,
    requesting: false,
    error: null,
  },
  onLoad(options) {
    const keyword = (options && options.keyword) || ''
    this.setData({ history: readHistory() })

    if (keyword) {
      this.setData({ keyword: String(keyword) })
      this.onTapSearch()
    }
  },
  onInputKeyword(e) {
    const keyword = (e && e.detail && e.detail.value) || ''
    const trimmed = String(keyword)
    this.setData({ keyword: trimmed })
    if (!trimmed) {
      this.setData({ showHistory: true, list: [], empty: false })
    }
  },
  onTapSearch() {
    const keyword = String(this.data.keyword || '').trim()
    if (!keyword) {
      ui.toast('请输入关键字')
      return
    }
    if (!this.data.hasBaseUrl) {
      ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
      return
    }
    if (this.data.requesting) return

    const history = [keyword].concat(this.data.history || [])
    const deduped = Array.from(new Set(history)).slice(0, 20)
    writeHistory(deduped)

    this.setData({
      history: deduped,
      showHistory: false,
      list: [],
      empty: false,
      requesting: true,
      error: null,
    })

    merchantApi
      .searchMerchants({ nickname: keyword }, { showLoading: true })
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          return
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '请求失败')
          return
        }

        const raw = res.data
        const list = Array.isArray(raw) ? raw : Array.isArray(raw && raw.data) ? raw.data : []
        const normalized = list.map((it) => normalizeMerchant(it, this.data.baseUrl)).filter(Boolean)
        this.setData({ list: normalized, empty: normalized.length === 0 })
      })
      .catch((err) => {
        this.setData({ error: String((err && (err.errMsg || err.message)) || err) })
        ui.toast('请求失败，请稍后重试')
      })
      .finally(() => {
        this.setData({ requesting: false })
      })
  },
  onTapHistory(e) {
    const keyword = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.keyword
    if (!keyword) return
    this.setData({ keyword: String(keyword) })
    this.onTapSearch()
  },
  onTapClearHistory() {
    ui
      .showModal({
        title: '提示',
        content: '是否清空历史记录？',
        confirmText: '清空',
        cancelText: '取消',
      })
      .then((res) => {
        if (!res || !res.confirm) return
        writeHistory([])
        this.setData({ history: [] })
      })
  },
  onTapMerchant(e) {
    const id = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({ url: `/subpackages/merchant/info/info?id=${encodeURIComponent(String(id))}` })
  },
})

