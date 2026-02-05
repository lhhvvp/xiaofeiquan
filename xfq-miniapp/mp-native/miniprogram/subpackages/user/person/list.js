const config = require('../../../config')
const auth = require('../../../services/auth')
const ui = require('../../../utils/ui')
const userApi = require('../../../services/api/user')

const EDIT_PAYLOAD_KEY = '__nav_tourist_edit'

function safeSetStorage(key, value) {
  try {
    wx.setStorageSync(key, value)
  } catch (e) {}
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    list: [],
    page: 1,
    pageSize: 12,
    loadingStatus: 'more',
    loadingLock: false,
    requesting: false,
    empty: false,
    selectedIds: [],
    error: null,
  },
  onLoad() {
    this.refresh()
  },
  onShow() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    this.setData({ hasLogin })
    if (hasLogin) this.refresh({ showLoading: false })
  },
  onPullDownRefresh() {
    this.refresh({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  onReachBottom() {
    this.fetchNextPage()
  },
  ensureReady() {
    if (!this.data.hasBaseUrl) {
      ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
      return false
    }
    if (!this.data.hasLogin) {
      ui.toast('请先登录')
      return false
    }
    return true
  },
  refresh({ showLoading = true } = {}) {
    if (!this.ensureReady()) return Promise.resolve()
    this.setData({
      list: [],
      page: 1,
      loadingStatus: 'more',
      loadingLock: false,
      requesting: false,
      empty: false,
      selectedIds: [],
      error: null,
    })
    return this.fetchNextPage({ showLoading })
  },
  fetchNextPage({ showLoading = true } = {}) {
    if (!this.ensureReady()) return Promise.resolve()
    if (this.data.loadingLock || this.data.requesting) return Promise.resolve()

    this.setData({ requesting: true, loadingStatus: 'loading' })

    return userApi
      .getTouristList({ page: this.data.page, page_size: this.data.pageSize }, { showLoading })
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          return
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '请求失败')
          return
        }
        const data = res.data && res.data.data
        const list = Array.isArray(data) ? data : []

        if (this.data.page === 1 && list.length === 0) {
          this.setData({ empty: true, loadingLock: true, loadingStatus: 'no-more' })
          return
        }

        const nextList = (this.data.list || []).concat(list)
        const noMore = list.length === 0 || list.length < this.data.pageSize
        this.setData({
          list: nextList,
          page: this.data.page + 1,
          loadingStatus: noMore ? 'no-more' : 'more',
          loadingLock: noMore,
        })
      })
      .catch((err) => this.setData({ error: String((err && (err.errMsg || err.message)) || err) }))
      .finally(() => this.setData({ requesting: false }))
  },
  onCheckboxChange(e) {
    const ids = (e && e.detail && e.detail.value) || []
    this.setData({ selectedIds: Array.isArray(ids) ? ids : [] })
  },
  onTapAdd() {
    if (!this.ensureReady()) return
    safeSetStorage(EDIT_PAYLOAD_KEY, null)
    wx.navigateTo({ url: '/subpackages/user/person/add' })
  },
  onTapEdit(e) {
    if (!this.ensureReady()) return
    const index = Number(e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.index)
    const item = this.data.list && this.data.list[index]
    if (!item) return
    safeSetStorage(EDIT_PAYLOAD_KEY, item)
    wx.navigateTo({ url: '/subpackages/user/person/add' })
  },
  onTapDelete() {
    if (!this.ensureReady()) return
    const ids = this.data.selectedIds || []
    if (!ids.length) {
      ui.toast('请选择要删除的游客')
      return
    }
    ui
      .showModal({
        title: '提示',
        content: `确定删除所选游客（${ids.length}）吗？`,
        confirmText: '删除',
        cancelText: '取消',
      })
      .then((res) => {
        if (!res || !res.confirm) return
        return userApi.delTourist({ ids: ids.join(',') }, { showLoading: true }).then((ret) => {
          if (ret && ret.code === 0) {
            ui.toast(ret.msg || '删除成功')
            this.refresh({ showLoading: false })
            return
          }
          ui.toast((ret && ret.msg) || '删除失败')
        })
      })
      .catch(() => ui.toast('删除失败'))
  },
})

