const request = require('../request')

function submit(payload = {}, { showLoading = true } = {}) {
  return request({
    path: '/pay/submit',
    method: 'POST',
    data: payload,
    showLoading,
  })
}

function refund(payload = {}, { showLoading = true } = {}) {
  return request({
    path: '/pay/refund',
    method: 'POST',
    data: payload,
    showLoading,
  })
}

module.exports = {
  submit,
  refund,
}

