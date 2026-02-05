const request = require('../request')

function getUserCouponIds({ uid }, { showLoading = true } = {}) {
  return request({
    path: '/user/get_user_coupon_id',
    method: 'POST',
    data: { uid },
    showLoading,
  })
}

function getCouponIssueUserList({ uid, status = '', page = 1, limit = 8 }, { showLoading = true } = {}) {
  return request({
    path: '/user/coupon_issue_user',
    method: 'POST',
    data: { uid, status, page, limit },
    showLoading,
  })
}

function getAuthInfo({ uid } = {}, { showLoading = true } = {}) {
  return request({
    path: '/user/auth_info',
    method: 'POST',
    data: { uid },
    showLoading,
  })
}

function getTouristList({ page = 1, page_size = 999 } = {}, { showLoading = true } = {}) {
  return request({
    path: '/user/getTouristList',
    method: 'GET',
    data: { page, page_size },
    showLoading,
  })
}

function getCertTypeList({ userid } = {}, { showLoading = true } = {}) {
  return request({
    path: '/user/getCertTypeList',
    method: 'POST',
    data: { userid },
    showLoading,
  })
}

function postTourist(payload = {}, { showLoading = true } = {}) {
  return request({
    path: '/user/postTourist',
    method: 'POST',
    data: payload,
    showLoading,
  })
}

function delTourist({ ids } = {}, { showLoading = true } = {}) {
  return request({
    path: '/user/delTourist',
    method: 'POST',
    data: { ids },
    showLoading,
  })
}

function getCouponOrderList({ uid, status = '', limit = 8, page = 1 } = {}, { showLoading = true } = {}) {
  return request({
    path: '/user/coupon_order',
    method: 'POST',
    data: { uid, status, limit, page },
    showLoading,
  })
}

function getCouponOrderDetail({ uid, order_no } = {}, { showLoading = true } = {}) {
  return request({
    path: '/user/coupon_order_detail',
    method: 'POST',
    data: { uid, order_no },
    showLoading,
  })
}

module.exports = {
  getUserCouponIds,
  getCouponIssueUserList,
  getAuthInfo,
  getTouristList,
  getCertTypeList,
  postTourist,
  delTourist,
  getCouponOrderList,
  getCouponOrderDetail,
}
