const config = require('../../config')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')
const systemService = require('../../services/system')
const couponApi = require('../../services/api/coupon')
const urlUtil = require('../../utils/url')

Page({
  data: {
    envVersion: config.envVersion,
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,
    slideUrl: '',
    actRuleHtml: '',
    couponSections: [],
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
  onPullDownRefresh() {
    if (!this.data.hasBaseUrl) {
      wx.stopPullDownRefresh()
      return
    }
    this.refresh({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  refresh({ showLoading = true } = {}) {
    const uid = auth.getUid() || 0
    this.setData({ error: null })

    const fetchSystem = systemService.fetchSystem({ force: true, showLoading })
    const fetchCoupons = couponApi.getIndex({ userid: uid }, { showLoading })

    return Promise.all([fetchSystem, fetchCoupons])
      .then(([system, couponRes]) => {
        const slideUrl =
          system && system.slide && system.slide.image ? urlUtil.normalizeNetworkUrl(system.slide.image, config.baseUrl) : ''
        const actRuleHtml = system && system.act_rule ? urlUtil.normalizeRichTextHtml(system.act_rule, config.baseUrl) : ''

        let sections = []
        if (couponRes && typeof couponRes === 'object' && couponRes.code === 0) {
          sections = Array.isArray(couponRes.data) ? couponRes.data : []
        }

        if ((!sections || sections.length === 0) && (!uid || uid === 0)) {
          return couponApi.getTempApi({ userid: 0 }, { showLoading: false }).then((tmpRes) => {
            let tmpSections = []
            if (tmpRes && typeof tmpRes === 'object' && tmpRes.code === 0) {
              tmpSections = Array.isArray(tmpRes.data) ? tmpRes.data : []
            }
            return { slideUrl, actRuleHtml, sections: tmpSections }
          })
        }

        return { slideUrl, actRuleHtml, sections }
      })
      .then(({ slideUrl, actRuleHtml, sections }) => {
        const normalized = (sections || []).map((sec) => {
          const title = (sec && sec.title) || ''
          const list = Array.isArray(sec && sec.list) ? sec.list : []
          return {
            id: sec && sec.id,
            title,
            iconUrl: urlUtil.normalizeNetworkUrl(sec && sec.class_icon, config.baseUrl),
            list: list.map((c) => ({
              id: c && c.id,
              cid: c && c.cid,
              couponTitle: (c && c.coupon_title) || '',
              couponPrice: c && c.coupon_price,
              isUse: !!(c && c.is_use),
              actionText: c && c.is_use ? '已领取' : '领取',
            })),
          }
        })

        this.setData({
          slideUrl,
          actRuleHtml,
          couponSections: normalized.filter((s) => s && s.list && s.list.length),
          debugText: '',
        })
      })
      .catch((err) => {
        this.setData({ error: String((err && (err.errMsg || err.message)) || err) })
      })
  },
  onRetry() {
    if (!this.data.hasBaseUrl) return
    this.refresh({ showLoading: false })
  },
  onTapCoupon(e) {
    const id = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({ url: `/subpackages/coupon/coupon?id=${encodeURIComponent(String(id))}` })
  },
  onTapGoLogin() {
    wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent('/pages/index/index')}` })
  },
  onTapMyCoupons() {
    wx.navigateTo({ url: '/subpackages/user/order/order' })
  },
  onTapToggleDebug() {
    const next = !this.data.debugOpen
    const debugText = next
      ? JSON.stringify(
          {
            envVersion: this.data.envVersion,
            baseUrl: this.data.baseUrl,
            hasLogin: this.data.hasLogin,
          },
          null,
          2
        )
      : ''
    this.setData({ debugOpen: next, debugText })
  },
  onTapOpenApplicableMerchants() {
    wx.navigateTo({ url: '/subpackages/coupon/list?id=0' })
  },
})
