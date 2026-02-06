const config = require('../../../config')
const ui = require('../../../utils/ui')
const urlUtil = require('../../../utils/url')
const systemService = require('../../../services/system')

function isServiceAgreement(title) {
  const t = String(title || '')
  if (!t) return false
  return t.indexOf('服务') >= 0
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),

    title: '',
    contentHtml: '',
    error: null,
  },
  onLoad(options) {
    const title = (options && options.title) || '协议'
    this.setData({ title: String(title) })
    wx.setNavigationBarTitle({ title: String(title) })

    if (!this.data.hasBaseUrl) return
    this.fetchContent({ showLoading: true })
  },
  fetchContent({ showLoading = true } = {}) {
    this.setData({ error: null, contentHtml: '' })
    return systemService
      .fetchSystem({ force: true, showLoading })
      .then((system) => {
        if (!system || typeof system !== 'object') throw new Error('系统配置返回异常')
        const html = isServiceAgreement(this.data.title) ? system.service : system.policy
        if (!html) throw new Error('暂无内容')
        this.setData({ contentHtml: urlUtil.normalizeRichTextHtml(html, this.data.baseUrl) })
      })
      .catch((err) => this.setData({ error: String((err && (err.errMsg || err.message)) || err) }))
  },
  onRetry() {
    this.fetchContent({ showLoading: false })
  },
})

