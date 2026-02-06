const request = require('../request')

function getNoteList(params = {}, { showLoading = true } = {}) {
  return request({
    path: '/index/note_list',
    method: 'POST',
    data: params,
    showLoading,
  })
}

function getNoteDetail({ id } = {}, { showLoading = true } = {}) {
  return request({
    path: '/index/note_detail',
    method: 'GET',
    data: { id },
    showLoading,
  })
}

module.exports = {
  getNoteList,
  getNoteDetail,
}

