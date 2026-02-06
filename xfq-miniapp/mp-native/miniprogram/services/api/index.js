const request = require('../request')

function transform({ longitude, latitude } = {}, { showLoading = true } = {}) {
  return request({
    path: '/index/transform',
    method: 'POST',
    data: { longitude, latitude },
    showLoading,
  })
}

function getUserPhoneNumber({ code } = {}, { showLoading = true } = {}) {
  return request({
    path: '/index/getuserphonenumber',
    method: 'POST',
    data: { code },
    showLoading,
  })
}

module.exports = {
  transform,
  getUserPhoneNumber,
}
