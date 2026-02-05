const UQRCode = require('../vendor/uqrcode')

function drawToCanvas({ canvasId, text, size = 220, margin = 0, page, foregroundColor, backgroundColor } = {}) {
  if (!canvasId) return Promise.reject(new Error('MISSING_CANVAS_ID'))
  const value = String(text || '')
  if (!value) return Promise.reject(new Error('MISSING_QRCODE_TEXT'))

  const qr = new UQRCode()
  qr.data = value
  qr.size = Number(size) || 220
  qr.margin = Number(margin) || 0
  qr.useDynamicSize = true
  if (foregroundColor) qr.foregroundColor = foregroundColor
  if (backgroundColor) qr.backgroundColor = backgroundColor

  qr.make()

  try {
    qr.canvasContext = wx.createCanvasContext(canvasId, page)
  } catch (e) {
    qr.canvasContext = wx.createCanvasContext(canvasId)
  }

  return qr.drawCanvas()
}

module.exports = {
  drawToCanvas,
}

