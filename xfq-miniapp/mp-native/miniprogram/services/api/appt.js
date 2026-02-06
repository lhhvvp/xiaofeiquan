const request = require('../request')

function getDatetime({ seller_id } = {}, { showLoading = true } = {}) {
  return request({
    path: '/appt/getDatetime',
    method: 'GET',
    data: { seller_id },
    showLoading,
  })
}

function createAppt(payload = {}, { showLoading = true } = {}) {
  return request({
    path: '/appt/createAppt',
    method: 'POST',
    data: payload,
    showLoading,
  })
}

function getList({ page = 1, page_size = 12, status = '' } = {}, { showLoading = true } = {}) {
  return request({
    path: '/appt/getList',
    method: 'GET',
    data: { page, page_size, status },
    showLoading,
  })
}

function getDetail({ id } = {}, { showLoading = true } = {}) {
  return request({
    path: '/appt/getDetail',
    method: 'GET',
    data: { id },
    showLoading,
  })
}

function cancelAppt({ log_id } = {}, { showLoading = true } = {}) {
  return request({
    path: '/appt/cancelAppt',
    method: 'POST',
    data: { log_id },
    showLoading,
  })
}

function writeOff(payload = {}, { showLoading = true } = {}) {
  return request({
    path: '/appt/writeOff',
    method: 'POST',
    data: payload,
    showLoading,
  })
}

module.exports = {
  getDatetime,
  createAppt,
  getList,
  getDetail,
  cancelAppt,
  writeOff,
}
