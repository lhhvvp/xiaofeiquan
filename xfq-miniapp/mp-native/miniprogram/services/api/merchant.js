const request = require('../request')

function getCategories({ showLoading = true } = {}) {
  return request({ path: '/seller/cate', method: 'POST', data: {}, showLoading })
}

function getMerchantList({ class_id, page, limit, latitude, longitude }, { showLoading = true } = {}) {
  return request({
    path: '/seller/list',
    method: 'POST',
    data: {
      class_id,
      page,
      limit,
      latitude,
      longitude,
    },
    showLoading,
  })
}

function searchMerchants({ nickname }, { showLoading = true } = {}) {
  return request({
    path: '/seller/search',
    method: 'POST',
    data: { nickname },
    showLoading,
  })
}

function getMerchantDetail({ seller_id, latitude, longitude }, { showLoading = true } = {}) {
  return request({
    path: '/seller/detail',
    method: 'POST',
    data: { seller_id, latitude, longitude },
    showLoading,
  })
}

module.exports = {
  getCategories,
  getMerchantList,
  searchMerchants,
  getMerchantDetail,
}
