const config = require('../../../config')
const ui = require('../../../utils/ui')
const contentApi = require('../../../services/api/content')

function normalizeItem(item) {
  if (!item || typeof item !== 'object') return null
  return {
    id: item.id,
    title: item.title || '',
    createTime: item.create_time || '',
    hits: item.hits || 0,
    raw: item,
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),

    list: [],
    requesting: false,
    loadingStatus: 'more',
    empty: false,
    error: null,
  },
  onLoad() {
    if (!this.data.hasBaseUrl) return
    this.refresh({ showLoading: false })
  },
  onPullDownRefresh() {
    this.refresh({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  refresh({ showLoading = true } = {}) {
    if (!this.data.hasBaseUrl) {
      ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
      return Promise.resolve()
    }
    if (this.data.requesting) return Promise.resolve()

    this.setData({
      list: [],
      requesting: false,
      loadingStatus: 'loading',
      empty: false,
      error: null,
    })

    return this.fetchList({ showLoading })
  },
  fetchList({ showLoading = true } = {}) {
    if (this.data.requesting) return Promise.resolve()
    this.setData({ requesting: true, loadingStatus: 'loading', error: null })

    return contentApi
      .getNoteList({}, { showLoading })
      .then((res) => {
        if (!res || typeof res !== 'object') {
          this.setData({ error: '返回数据异常', loadingStatus: 'error' })
          return
        }
        if (res.code !== 0) {
          const msg = res.msg || '请求失败'
          this.setData({ error: msg, loadingStatus: 'error' })
          return
        }
        const list = Array.isArray(res.data) ? res.data : []
        const normalized = list.map(normalizeItem).filter(Boolean)
        this.setData({
          list: normalized,
          empty: normalized.length === 0,
          loadingStatus: 'no-more',
        })
      })
      .catch((err) => {
        this.setData({ error: String((err && (err.errMsg || err.message)) || err), loadingStatus: 'error' })
      })
      .finally(() => this.setData({ requesting: false }))
  },
  onTapItem(e) {
    const id = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({ url: `/subpackages/content/news/info?id=${encodeURIComponent(String(id))}` })
  },
  onRetry() {
    this.refresh({ showLoading: false })
  },
})

