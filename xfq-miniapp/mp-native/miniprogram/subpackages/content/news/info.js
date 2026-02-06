const config = require('../../../config')
const ui = require('../../../utils/ui')
const urlUtil = require('../../../utils/url')
const contentApi = require('../../../services/api/content')

function normalizeDetail(data, baseUrl) {
  if (!data || typeof data !== 'object') return null
  const content = data.content || ''
  return {
    id: data.id,
    title: data.title || '',
    createTime: data.create_time || '',
    hits: data.hits || 0,
    contentHtml: urlUtil.normalizeRichTextHtml(content, baseUrl),
    raw: data,
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),

    id: '',
    detail: null,
    error: null,
  },
  onLoad(options) {
    const id = (options && options.id) || ''
    this.setData({ id: String(id) })
    if (!this.data.id) {
      ui.showModal({ title: '提示', content: '参数错误：缺少 id', showCancel: false }).then(() => wx.navigateBack())
      return
    }
    if (!this.data.hasBaseUrl) return
    this.fetchDetail({ showLoading: true })
  },
  fetchDetail({ showLoading = true } = {}) {
    if (!this.data.id) return Promise.resolve()
    this.setData({ error: null })
    return contentApi
      .getNoteDetail({ id: this.data.id }, { showLoading })
      .then((res) => {
        if (!res || typeof res !== 'object') {
          this.setData({ error: '返回数据异常' })
          return
        }
        if (res.code !== 0) {
          this.setData({ error: res.msg || '请求失败' })
          return
        }
        const normalized = normalizeDetail(res.data, this.data.baseUrl)
        if (!normalized) {
          this.setData({ error: '返回数据异常' })
          return
        }
        this.setData({ detail: normalized })
        if (normalized.title) wx.setNavigationBarTitle({ title: normalized.title })
      })
      .catch((err) => this.setData({ error: String((err && (err.errMsg || err.message)) || err) }))
  },
  onRetry() {
    this.fetchDetail({ showLoading: false })
  },
})

