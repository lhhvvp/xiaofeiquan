function normalizeWxPayParams(payload) {
  if (!payload || typeof payload !== 'object') return null
  const data = payload.data && typeof payload.data === 'object' ? payload.data : payload
  const pay = data.pay && typeof data.pay === 'object' ? data.pay : data
  if (!pay || typeof pay !== 'object') return null

  const timeStamp = pay.timeStamp || pay.timestamp || pay.time_stamp
  const nonceStr = pay.nonceStr || pay.noncestr || pay.nonce_str
  const packageValue = pay.package
  const signType = pay.signType || pay.sign_type || 'MD5'
  const paySign = pay.paySign || pay.paysign || pay.pay_sign

  if (!timeStamp || !nonceStr || !packageValue || !paySign) return null

  return {
    timeStamp: String(timeStamp),
    nonceStr: String(nonceStr),
    package: String(packageValue),
    signType: String(signType || 'MD5'),
    paySign: String(paySign),
  }
}

function requestWxPayment(payloadOrParams) {
  const pay = normalizeWxPayParams(payloadOrParams)
  if (!pay) return Promise.reject(new Error('INVALID_WXPAY_PARAMS'))
  return new Promise((resolve, reject) => {
    wx.requestPayment({
      ...pay,
      success: resolve,
      fail: reject,
    })
  })
}

module.exports = {
  normalizeWxPayParams,
  requestWxPayment,
}

