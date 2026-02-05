const request = require('../request')

function getScenicList(params = {}, { showLoading = true } = {}) {
  return request({ path: '/ticket/getScenicList', method: 'GET', data: params, showLoading })
}

function getTicketList({ seller_id }, { showLoading = true } = {}) {
  return request({ path: '/ticket/getTicketList', method: 'GET', data: { seller_id }, showLoading })
}

function getCommentList({ mid, page = 1, page_size = 6 }, { showLoading = true } = {}) {
  return request({
    path: '/ticket/getCommentList',
    method: 'GET',
    data: { mid, page, page_size },
    showLoading,
  })
}

function getTicketPrice({ ticket_id, channel = 'online' } = {}, { showLoading = true } = {}) {
  return request({
    path: '/ticket/getTicketPirce',
    method: 'GET',
    data: { ticket_id, channel },
    showLoading,
  })
}

function pay(payload = {}, { showLoading = true } = {}) {
  return request({
    path: '/ticket/pay',
    method: 'POST',
    data: payload,
    showLoading,
  })
}

function getOrderList({ page = 1, page_size = 12, status = '' } = {}, { showLoading = true } = {}) {
  return request({
    path: '/ticket/getOrderList',
    method: 'GET',
    data: { page, page_size, status },
    showLoading,
  })
}

function getOrderDetail({ order_id } = {}, { showLoading = true } = {}) {
  return request({
    path: '/ticket/getOrderDetail',
    method: 'GET',
    data: { order_id },
    showLoading,
  })
}

function orderPay({ uuid, openid, trade_no } = {}, { showLoading = true } = {}) {
  return request({
    path: '/ticket/orderpay',
    method: 'POST',
    data: { uuid, openid, trade_no },
    showLoading,
  })
}

function cancelRefund({ type, id } = {}, { showLoading = true } = {}) {
  return request({
    path: '/ticket/cancelRefund',
    method: 'POST',
    data: { type, id },
    showLoading,
  })
}

function refund({ out_trade_no, refund_desc, openid, uuid } = {}, { showLoading = true } = {}) {
  return request({
    path: '/ticket/refund',
    method: 'POST',
    data: { out_trade_no, refund_desc, openid, uuid },
    showLoading,
  })
}

function singleRefund({ out_trade_no, refund_desc, openid, uuid } = {}, { showLoading = true } = {}) {
  return request({
    path: '/ticket/single_refund',
    method: 'POST',
    data: { out_trade_no, refund_desc, openid, uuid },
    showLoading,
  })
}

function getRefundLogList({ page = 1, page_size = 12 } = {}, { showLoading = true } = {}) {
  return request({
    path: '/ticket/getRefundLogList',
    method: 'GET',
    data: { page, page_size },
    showLoading,
  })
}

function getRefundLogDetail({ id } = {}, { showLoading = true } = {}) {
  return request({
    path: '/ticket/getRefundLogDetail',
    method: 'GET',
    data: { id },
    showLoading,
  })
}

module.exports = {
  getScenicList,
  getTicketList,
  getCommentList,
  getTicketPrice,
  pay,
  getOrderList,
  getOrderDetail,
  orderPay,
  cancelRefund,
  refund,
  singleRefund,
  getRefundLogList,
  getRefundLogDetail,
}
