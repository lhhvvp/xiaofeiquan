const ui = require('../../utils/ui')
const qrcodeUtil = require('../../utils/qrcode')

function normalizeText(input) {
  if (input === null || typeof input === 'undefined') return ''
  return String(input).trim()
}

Page({
  data: {
    title: '',
    text: '',
    used: false,
    error: null,
  },
  onLoad(options) {
    const opts = options && typeof options === 'object' ? options : {}
    const title = normalizeText(opts.title || '')
    const text = normalizeText(opts.text || opts.qrcode || opts.qrcode_str || '')
    const used =
      String(opts.used || opts.isUsed || '') === '1' || String(opts.order_status || opts.status || '') === 'used'

    if (!title) wx.setNavigationBarTitle({ title: '二维码' })
    this.setData({ title, text, used })

    if (!used && !text) this.setData({ error: '缺少二维码内容' })
  },
  onReady() {
    this.draw()
  },
  draw() {
    if (this.data.used) return
    const text = normalizeText(this.data.text)
    if (!text) return
    if (this.data.error) return

    qrcodeUtil
      .drawToCanvas({ canvasId: 'qrcodeCanvas', text, size: 260, margin: 8, page: this })
      .catch((err) => this.setData({ error: String((err && (err.errMsg || err.message)) || err) }))
  },
  onTapCopy() {
    const text = normalizeText(this.data.text)
    if (!text) return
    wx.setClipboardData({
      data: text,
      success: () => ui.toast('已复制'),
      fail: () => ui.toast('复制失败'),
    })
  },
  onRetry() {
    this.setData({ error: null })
    this.draw()
  },
})

