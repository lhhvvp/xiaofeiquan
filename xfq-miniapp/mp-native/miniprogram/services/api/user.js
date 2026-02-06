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

function getUserIndex({ uid } = {}, { showLoading = true } = {}) {
  return request({
    path: '/user/index',
    method: 'POST',
    data: { uid, is_token: true },
    showLoading,
  })
}

function editUser({ id, nickname, headimgurl } = {}, { showLoading = true } = {}) {
  return request({
    path: '/user/edit',
    method: 'POST',
    data: { id, nickname, headimgurl },
    showLoading,
  })
}

function getCollectionList(
  { uid, page = 0, limit = 8, latitude = 0, longitude = 0 } = {},
  { showLoading = true } = {}
) {
  return request({
    path: '/user/collection',
    method: 'POST',
    data: { uid, page, limit, latitude, longitude, is_token: true },
    showLoading,
  })
}

function collectionAction({ action, uid, mid } = {}, { showLoading = true } = {}) {
  return request({
    path: '/user/collection_action',
    method: 'POST',
    data: { action, uid, mid },
    showLoading,
  })
}

function feedBack({ uid, content, images = '' } = {}, { showLoading = true } = {}) {
  return request({
    path: '/user/feed_back',
    method: 'POST',
    data: { uid, content, images },
    showLoading,
  })
}

function getClockList({ uid } = {}, { showLoading = true } = {}) {
  return request({
    path: '/user/clock_list',
    method: 'POST',
    data: { uid },
    showLoading,
  })
}

function clock(payload = {}, { showLoading = true } = {}) {
  return request({
    path: '/user/clock',
    method: 'POST',
    data: payload,
    showLoading,
  })
}

function hotelClock(payload = {}, { showLoading = true } = {}) {
  return request({
    path: '/user/hotel_clock',
    method: 'POST',
    data: payload,
    showLoading,
  })
}

function userClock(payload = {}, { showLoading = true } = {}) {
  return request({
    path: '/user/userClock',
    method: 'POST',
    data: payload,
    showLoading,
  })
}

function tourCouponGroup({ id } = {}, { showLoading = true } = {}) {
  return request({
    path: '/user/tour_coupon_group',
    method: 'POST',
    data: { id },
    showLoading,
  })
}

function writeoffTour(payload = {}, { showLoading = true } = {}) {
  return request({
    path: '/user/writeoff_tour',
    method: 'POST',
    data: payload,
    showLoading,
  })
}

function smsVerification({ mobile, uid } = {}, { showLoading = true } = {}) {
  return request({
    path: '/user/smsVerification',
    method: 'POST',
    data: { mobile, uid },
    showLoading,
  })
}

function authIdentity(payload = {}, { showLoading = true } = {}) {
  return request({
    path: '/user/auth_identity',
    method: 'POST',
    data: payload,
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
  getUserIndex,
  editUser,
  getCollectionList,
  collectionAction,
  feedBack,
  getClockList,
  clock,
  hotelClock,
  userClock,
  tourCouponGroup,
  writeoffTour,
  smsVerification,
  authIdentity,
}
