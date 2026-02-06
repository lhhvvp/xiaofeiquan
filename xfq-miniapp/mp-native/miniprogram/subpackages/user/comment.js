const config = require('../../config')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')
const urlUtil = require('../../utils/url')
const ticketsApi = require('../../services/api/tickets')

const DEFAULT_AVATAR = 'https://oss.wlxfq.dianfengcms.com/admins/d2d20480c4d629fc0336b4b639498830.png'

function normalizeComment(item, baseUrl, key) {
  if (!item || typeof item !== 'object') return null
  const users = item.users && typeof item.users === 'object' ? item.users : {}
  const rate = Number(item.rate)
  const safeRate = Number.isFinite(rate) ? rate : 0
  return {
    key: String(key || ''),
    nickname: users.nickname || '微信用户',
    avatar: urlUtil.normalizeNetworkUrl(users.headimgurl, baseUrl) || DEFAULT_AVATAR,
    rate: safeRate,
    rateText: safeRate ? `${safeRate}分` : '',
    content: item.content || '',
    createTime: item.create_time || '',
    raw: item,
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    mid: '',
    isMy: true,
    canShow: false,
    needLoginNotice: false,

    list: [],
    page: 1,
    pageSize: 12,
    requesting: false,
    loadingStatus: 'more',
    loadingLock: false,
    empty: false,
    error: null,
  },
  onLoad(options) {
    const mid = (options && options.mid) || ''
    this.setData({ mid: String(mid).trim() })
    this.setupMode()
  },
  onShow() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    if (hasLogin !== this.data.hasLogin) this.setData({ hasLogin })
    this.setupMode({ refresh: false })
  },
  onPullDownRefresh() {
    this.refresh({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  onReachBottom() {
    this.fetchNextPage({ showLoading: false })
  },
  setupMode({ refresh = true } = {}) {
    const isMy = !this.data.mid
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    const canShow = this.data.hasBaseUrl && (!isMy || hasLogin)
    const needLoginNotice = isMy && this.data.hasBaseUrl && !hasLogin
    this.setData({ isMy, canShow, needLoginNotice })

    if (isMy) wx.setNavigationBarTitle({ title: '我的评价' })
    else wx.setNavigationBarTitle({ title: '评价' })

    if (!canShow) return
    if (refresh) this.refresh({ showLoading: false })
  },
  onTapGoLogin() {
    const url = this.data.mid ? `/subpackages/user/comment?mid=${encodeURIComponent(this.data.mid)}` : '/subpackages/user/comment'
    wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(url)}` })
  },
  refresh({ showLoading = true } = {}) {
    if (!this.data.canShow) return Promise.resolve()
    this.setData({
      list: [],
      page: 1,
      requesting: false,
      loadingStatus: 'more',
      loadingLock: false,
      empty: false,
      error: null,
    })
    return this.fetchNextPage({ showLoading })
  },
  fetchNextPage({ showLoading = true } = {}) {
    if (!this.data.canShow) return Promise.resolve()
    if (this.data.loadingLock || this.data.requesting) return Promise.resolve()

    const uid = auth.getUid()
    const userId = this.data.isMy ? uid : ''

    this.setData({ requesting: true, loadingStatus: 'loading', error: null })

    return ticketsApi
      .getCommentList(
        {
          mid: this.data.mid,
          user_id: userId,
          page: this.data.page,
          page_size: this.data.pageSize,
        },
        { showLoading }
      )
      .then((res) => {
        if (!res || typeof res !== 'object') {
          this.setData({ error: '返回数据异常', loadingStatus: 'error' })
          return
        }
        if (res.code !== 0) {
          this.setData({ error: res.msg || '请求失败', loadingStatus: 'error' })
          return
        }

        const list = Array.isArray(res.data) ? res.data : []
        const normalized = list
          .map((it, index) => normalizeComment(it, this.data.baseUrl, `${this.data.page}-${index}`))
          .filter(Boolean)

        if (this.data.page === 1 && normalized.length === 0) {
          this.setData({ empty: true, loadingStatus: 'no-more', loadingLock: true })
          return
        }

        const nextList = (this.data.list || []).concat(normalized)
        const noMore = normalized.length === 0 || normalized.length < this.data.pageSize
        this.setData({
          list: nextList,
          page: this.data.page + 1,
          loadingStatus: noMore ? 'no-more' : 'more',
          loadingLock: noMore,
        })
      })
      .catch((err) => this.setData({ error: String((err && (err.errMsg || err.message)) || err), loadingStatus: 'error' }))
      .finally(() => this.setData({ requesting: false }))
  },
  onRetry() {
    this.refresh({ showLoading: false })
  },
})
