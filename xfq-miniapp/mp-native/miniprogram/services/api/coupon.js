const request = require('../request')

function getIndex(
  { class_id = 1, type = 1, tag = 1, use_store = 1, userid = 0 } = {},
  { showLoading = true } = {}
) {
  return request({
    path: '/coupon/index',
    method: 'POST',
    data: { class_id, type, tag, use_store, userid },
    showLoading,
  })
}

function getTempApi(
  { class_id = 1, type = 1, tag = 1, use_store = 1, userid = 0 } = {},
  { showLoading = true } = {}
) {
  return request({
    path: '/coupon/tempApi',
    method: 'POST',
    data: { class_id, type, tag, use_store, userid },
    showLoading,
  })
}

function getDetail({ couponId, userid = 0 } = {}, { showLoading = true } = {}) {
  return request({
    path: '/coupon/detail',
    method: 'POST',
    data: { couponId, userid },
    showLoading,
  })
}

function receive({ userid, couponId, latitude, longitude } = {}, { showLoading = true } = {}) {
  return request({
    path: '/coupon/receive',
    method: 'POST',
    data: { userid, couponId, latitude, longitude },
    showLoading,
  })
}

function getApplicableMerchantsV2({ id, latitude, longitude } = {}, { showLoading = true } = {}) {
  return request({
    path: '/coupon/applicabletoV2',
    method: 'POST',
    data: { id, latitude, longitude },
    showLoading,
  })
}

function getApplicableMerchants({
  id,
  latitude,
  longitude,
  page = 0,
  limit = 15,
  keyword = '',
} = {}) {
  return request({
    path: '/coupon/applicableto',
    method: 'POST',
    data: { id, latitude, longitude, page, limit, keyword },
  })
}

function idToCoupon({ cuid } = {}, { showLoading = true } = {}) {
  return request({
    path: '/coupon/idtocoupon',
    method: 'POST',
    data: { cuid },
    showLoading,
  })
}

function encryptAES({ id, salt, uid } = {}, { showLoading = true } = {}) {
  return request({
    path: '/coupon/encryptAES',
    method: 'POST',
    data: { id, salt, uid },
    showLoading,
  })
}

function writeoff(payload = {}, { showLoading = true } = {}) {
  return request({
    path: '/coupon/writeoff',
    method: 'POST',
    data: payload,
    showLoading,
  })
}

module.exports = {
  getIndex,
  getTempApi,
  getDetail,
  receive,
  getApplicableMerchantsV2,
  getApplicableMerchants,
  idToCoupon,
  encryptAES,
  writeoff,
}
