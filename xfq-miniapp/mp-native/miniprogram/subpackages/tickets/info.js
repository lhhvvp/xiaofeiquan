const config = require('../../config')
const ui = require('../../utils/ui')
const urlUtil = require('../../utils/url')
const locationService = require('../../services/location')
const merchantApi = require('../../services/api/merchant')
const ticketsApi = require('../../services/api/tickets')

const DEFAULT_AVATAR_URL =
  'https://thirdwx.qlogo.cn/mmopen/vi_32/POgEwh4mIHO4nibH0KlMECNjjGxQUq24ZEaGT4poC6icRiccVGKSyXwibcPq4BWmiaIGuG1icwxaQX6grC9VemZoJ8rg/132'

function normalizeDetail(detail, baseUrl) {
  if (!detail || typeof detail !== 'object') return null
  return {
    nickname: detail.nickname || '',
    image: urlUtil.normalizeNetworkUrl(detail.image, baseUrl),
    doBusinessTime: detail.do_business_time || '',
    address: detail.address || '',
    mobile: detail.mobile || '',
    commentRate: detail.comment_rate || 0,
    commentNum: detail.comment_num || 0,
    apptOpen: Number(detail.appt_open) === 1,
    latitude: detail.latitude,
    longitude: detail.longitude,
  }
}

function normalizeScenicItem(item, baseUrl) {
  if (!item || typeof item !== 'object') return null
  const distance = Number(item.distance)
  const distanceText = Number.isFinite(distance) ? `${distance.toFixed(2)}km` : ''
  return {
    id: item.id,
    image: urlUtil.normalizeNetworkUrl(item.image, baseUrl),
    nickname: item.nickname || '',
    areaText: item.area_text || '',
    distanceText,
    minPrice: item.min_price || 0,
  }
}

function normalizeCommentItem(item) {
  if (!item || typeof item !== 'object') return null
  const users = item.users && typeof item.users === 'object' ? item.users : {}
  return {
    nickname: users.nickname || '微信用户',
    avatar: users.headimgurl || DEFAULT_AVATAR_URL,
    rate: item.rate || 0,
    content: item.content || '',
    createTime: item.create_time || '',
  }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    sellerId: '',
    latitude: 0,
    longitude: 0,

    detail: null,
    ticketGroups: [],
    scenicList: [],
    commentList: [],

    showTicketModal: false,
    currentTicket: null,

    error: null,
  },
  onLoad(options) {
    const sellerId = (options && (options.seller_id || options.sellerId)) || ''
    this.setData({ sellerId: String(sellerId) })

    this.coordPromise = locationService.getLocation().then((coord) => {
      this.setData({ latitude: coord.latitude, longitude: coord.longitude })
      return coord
    })

    if (this.data.hasBaseUrl) this.refresh()
  },
  onPullDownRefresh() {
    if (!this.data.hasBaseUrl) {
      wx.stopPullDownRefresh()
      return
    }
    this.refresh({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  refresh({ showLoading = true } = {}) {
    if (!this.data.sellerId) {
      ui.toast('缺少 seller_id')
      return Promise.resolve()
    }
    if (!this.data.hasBaseUrl) {
      return ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
    }

    this.setData({
      error: null,
      detail: null,
      ticketGroups: [],
      scenicList: [],
      commentList: [],
      showTicketModal: false,
      currentTicket: null,
    })

    return Promise.resolve(this.coordPromise)
      .then(() =>
        Promise.all([
          this.fetchDetail({ showLoading }),
          this.fetchTicketGroups({ showLoading: false }),
          this.fetchScenicRecommend({ showLoading: false }),
          this.fetchComments({ showLoading: false }),
        ])
      )
      .catch(() => {})
  },
  fetchDetail({ showLoading = true } = {}) {
    return merchantApi
      .getMerchantDetail(
        {
          seller_id: this.data.sellerId,
          latitude: this.data.latitude,
          longitude: this.data.longitude,
        },
        { showLoading }
      )
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          return
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '请求失败')
          return
        }
        const detail = res.data && res.data.detail
        const normalized = normalizeDetail(detail, this.data.baseUrl)
        this.setData({ detail: normalized || null })
        if (normalized && normalized.nickname) {
          wx.setNavigationBarTitle({ title: normalized.nickname })
        }
      })
      .catch((err) => {
        this.setData({ error: String((err && (err.errMsg || err.message)) || err) })
      })
  },
  fetchTicketGroups({ showLoading = true } = {}) {
    return ticketsApi
      .getTicketList({ seller_id: this.data.sellerId }, { showLoading })
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          return
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '请求失败')
          return
        }
        const list = Array.isArray(res.data) ? res.data : []
        this.setData({ ticketGroups: list })
      })
      .catch(() => {
        ui.toast('门票列表加载失败')
      })
  },
  fetchScenicRecommend({ showLoading = true } = {}) {
    return ticketsApi
      .getScenicList({ out_id: this.data.sellerId, page: 1, page_size: 5 }, { showLoading })
      .then((res) => {
        if (!res || typeof res !== 'object') return
        if (res.code !== 0) return
        const list = Array.isArray(res.data) ? res.data : []
        const normalized = list.map((it) => normalizeScenicItem(it, this.data.baseUrl)).filter(Boolean)
        this.setData({ scenicList: normalized })
      })
      .catch(() => {})
  },
  fetchComments({ showLoading = true } = {}) {
    return ticketsApi
      .getCommentList({ mid: this.data.sellerId, page: 1, page_size: 6 }, { showLoading })
      .then((res) => {
        if (!res || typeof res !== 'object') return
        if (res.code !== 0) return
        const list = Array.isArray(res.data) ? res.data : []
        const normalized = list.map(normalizeCommentItem).filter(Boolean)
        this.setData({ commentList: normalized })
      })
      .catch(() => {})
  },
  onTapCall() {
    const mobile = this.data.detail && this.data.detail.mobile
    if (!mobile) {
      ui.toast('暂无电话')
      return
    }
    wx.makePhoneCall({ phoneNumber: String(mobile) })
  },
  onTapNav() {
    const detail = this.data.detail
    if (!detail) return
    const latitude = Number(detail.latitude)
    const longitude = Number(detail.longitude)
    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
      ui.toast('暂无定位信息')
      return
    }
    wx.openLocation({
      name: detail.address || detail.nickname || '目的地',
      address: detail.address || '',
      latitude,
      longitude,
      scale: 16,
    })
  },
  onTapTicketInfo(e) {
    const gIndex = Number(e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.gindex)
    const tIndex = Number(e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.tindex)
    const group = this.data.ticketGroups && this.data.ticketGroups[gIndex]
    const ticket = group && group.ticket_list && group.ticket_list[tIndex]
    if (!ticket) return

    const currentTicket = {
      title: ticket.title || '',
      minPrice: ticket.min_price || 0,
      explainBuyHtml: urlUtil.normalizeRichTextHtml(ticket.explain_buy, this.data.baseUrl),
      explainUseHtml: urlUtil.normalizeRichTextHtml(ticket.explain_use, this.data.baseUrl),
      rightsList: Array.isArray(ticket.rights_list) ? ticket.rights_list : [],
    }

    this.setData({ showTicketModal: true, currentTicket })
  },
  onTapCloseModal() {
    this.setData({ showTicketModal: false, currentTicket: null })
  },
  onTapOrder(e) {
    const id = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({ url: `/subpackages/tickets/order?id=${encodeURIComponent(String(id))}` })
  },
  onTapSubscribe() {
    const sellerId = this.data.sellerId
    const detail = this.data.detail
    if (!sellerId) return
    if (detail && detail.apptOpen === false) {
      ui.toast('当前景区暂不支持预约')
      return
    }
    wx.navigateTo({ url: `/subpackages/user/subscribe/subscribe?seller_id=${encodeURIComponent(String(sellerId))}` })
  },
  onTapMoreComments() {
    const sellerId = this.data.sellerId
    if (!sellerId) return
    wx.navigateTo({ url: `/subpackages/user/comment?mid=${encodeURIComponent(String(sellerId))}` })
  },
  noop() {},
})
