const config = require('../../config')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')
const systemService = require('../../services/system')
const couponApi = require('../../services/api/coupon')
const contentApi = require('../../services/api/content')
const urlUtil = require('../../utils/url')

function normalizeNews(resp) {
  if (!resp || typeof resp !== 'object' || resp.code !== 0) return []
  const list = Array.isArray(resp.data) ? resp.data : resp.data ? [resp.data] : []
  return list
    .map((item) => ({
      id: item && item.id,
      title: (item && item.title) || '',
    }))
    .filter((item) => item.id && item.title)
}

function normalizeSectionList(sections = []) {
  return (sections || []).map((sec) => {
    const list = Array.isArray(sec && sec.list) ? sec.list : []
    return {
      id: sec && sec.id,
      title: (sec && sec.title) || '',
      iconUrl: sec && sec.class_icon,
      list: list.map((item) => {
        const cid = Number(item && item.cid)
        const isGroupCoupon = cid === 3 || cid === 4
        return {
          id: item && item.id,
          cid,
          couponTitle: (item && item.coupon_title) || '',
          couponPrice: item && item.coupon_price,
          isUse: !!(item && item.is_use),
          actionText: item && item.is_use ? '已领取' : '领取',
          displayTag: isGroupCoupon ? ((item && item.coupon_title) || '旅行团') : ((sec && sec.title) || '消费券'),
        }
      }),
    }
  })
}

function buildVisibleSections(sectionList, navId) {
  const source = (sectionList || []).filter((item) => item && item.list && item.list.length)
  if (!navId || navId === 'all') return source
  return source.filter((item) => String(item.id) === String(navId))
}

Page({
  data: {
    envVersion: config.envVersion,
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,
    slideUrl: '',
    actRuleHtml: '',
    news: [],
    navList: [],
    selectedNavId: 'all',
    couponSections: [],
    visibleSections: [],
    debugOpen: false,
    debugText: '',
    error: null,
  },

  onLoad() {
    if (this.data.hasBaseUrl) this.refresh()
  },

  onShow() {
    const user = auth.getUser()
    this.setData({ hasLogin: !!(user && user.token && user.uid) })
  },

  onShareAppMessage() {
    return { title: '榆林市旅游消费平台', path: '/pages/index/index' }
  },

  onShareTimeline() {
    return { title: '榆林市旅游消费平台', path: '/pages/index/index' }
  },

  onPullDownRefresh() {
    if (!this.data.hasBaseUrl) {
      wx.stopPullDownRefresh()
      return
    }
    this.refresh({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },

  ensureBaseUrl() {
    if (this.data.hasBaseUrl) return true
    ui.showModal({
      title: '提示',
      content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
      showCancel: false,
    })
    return false
  },

  refresh({ showLoading = true } = {}) {
    const uid = auth.getUid() || 0
    this.setData({ error: null })

    const loadSystem = systemService.fetchSystem({ force: true, showLoading })
    const loadNews = contentApi.getNoteList({}, { showLoading: false })

    return Promise.all([loadSystem, loadNews])
      .then(([systemData, newsResp]) => {
        const slideUrl =
          systemData && systemData.slide && systemData.slide.image
            ? urlUtil.normalizeNetworkUrl(systemData.slide.image, config.baseUrl)
            : ''
        const actRuleHtml =
          systemData && systemData.act_rule ? urlUtil.normalizeRichTextHtml(systemData.act_rule, config.baseUrl) : ''
        const useTempApi = Number(systemData && systemData.is_open_api) === 1
        const news = normalizeNews(newsResp)

        const couponReq = useTempApi
          ? couponApi.getTempApi({ userid: 0 }, { showLoading: false })
          : couponApi.getIndex({ userid: uid }, { showLoading: false })

        return couponReq.then((couponResp) => ({
          slideUrl,
          actRuleHtml,
          news,
          useTempApi,
          couponResp,
          uid,
        }))
      })
      .then(({ slideUrl, actRuleHtml, news, useTempApi, couponResp, uid }) => {
        let sectionSource = []
        if (couponResp && typeof couponResp === 'object' && couponResp.code === 0) {
          sectionSource = Array.isArray(couponResp.data) ? couponResp.data : []
        }

        if (!useTempApi && (!sectionSource || sectionSource.length === 0) && !uid) {
          return couponApi.getTempApi({ userid: 0 }, { showLoading: false }).then((tmpResp) => {
            if (tmpResp && tmpResp.code === 0 && Array.isArray(tmpResp.data)) sectionSource = tmpResp.data
            return { slideUrl, actRuleHtml, news, sectionSource }
          })
        }

        return { slideUrl, actRuleHtml, news, sectionSource }
      })
      .then(({ slideUrl, actRuleHtml, news, sectionSource }) => {
        const normalizedSections = normalizeSectionList(sectionSource)
        const navList = normalizedSections.map((sec) => ({
          id: sec.id,
          idKey: String(sec.id),
          title: sec.title,
          iconUrl: urlUtil.normalizeNetworkUrl(sec.iconUrl, config.baseUrl),
        }))

        const selectedNavId = this.data.selectedNavId === 'all' ? 'all' : String(this.data.selectedNavId)
        const visibleSections = buildVisibleSections(normalizedSections, selectedNavId)

        this.setData({
          slideUrl,
          actRuleHtml,
          news,
          navList,
          couponSections: normalizedSections,
          visibleSections,
          debugText: '',
        })
      })
      .catch((err) => {
        this.setData({
          error: String((err && (err.errMsg || err.message)) || err),
        })
      })
  },

  onRetry() {
    if (!this.data.hasBaseUrl) return
    this.refresh({ showLoading: false })
  },

  onTapGoLogin() {
    wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent('/pages/index/index')}` })
  },

  onTapMyCoupons() {
    wx.navigateTo({ url: '/subpackages/user/order/order' })
  },

  onTapNoticeList() {
    if (!this.ensureBaseUrl()) return
    wx.navigateTo({ url: '/subpackages/content/news/news' })
  },

  onTapNotice(e) {
    const id = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({ url: `/subpackages/content/news/info?id=${encodeURIComponent(String(id))}` })
  },

  onTapNav(e) {
    const id = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.id
    const next = typeof id === 'undefined' || id === null ? 'all' : String(id)
    this.setData({
      selectedNavId: next,
      visibleSections: buildVisibleSections(this.data.couponSections, next),
    })
  },

  onTapSectionMore(e) {
    const id = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.id
    const queryId = id || 0
    wx.navigateTo({
      url: `/subpackages/coupon/list?id=${encodeURIComponent(String(queryId))}`,
    })
  },

  onTapCoupon(e) {
    const id = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({
      url: `/subpackages/coupon/coupon?id=${encodeURIComponent(String(id))}`,
    })
  },

  onTapToggleDebug() {
    const next = !this.data.debugOpen
    const debugText = next
      ? JSON.stringify(
          {
            envVersion: this.data.envVersion,
            baseUrl: this.data.baseUrl,
            hasLogin: this.data.hasLogin,
            newsCount: this.data.news.length,
            sectionCount: this.data.couponSections.length,
          },
          null,
          2
        )
      : ''
    this.setData({ debugOpen: next, debugText })
  },
})
