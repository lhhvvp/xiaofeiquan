const config = require('../../config')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')
const urlUtil = require('../../utils/url')
const userApi = require('../../services/api/user')
const payApi = require('../../services/api/pay')
const paymentUtil = require('../../utils/payment')

const FALLBACK_ICON = 'https://oss.wlxfq.dianfengcms.com/admins/d2d20480c4d629fc0336b4b639498830.png'

const TABS = [
  { title: '全部', statusParam: '' },
  { title: '已支付', statusParam: 0 },
  { title: '未支付', statusParam: 1 },
  { title: '已退款', statusParam: 2 },
]

function normalizeOrder(item, baseUrl) {
  if (!item || typeof item !== 'object') return null
  const detail = item.detail && typeof item.detail === 'object' ? item.detail : {}
  const paymentStatus = Number(item.payment_status)
  const isRefund = Number(item.is_refund) === 1
  const statusText = isRefund ? '已退款' : paymentStatus === 0 ? '未支付' : paymentStatus === 1 ? '已支付' : '未知'
  const statusClass = isRefund ? 'status--refunded' : paymentStatus === 0 ? 'status--unpaid' : 'status--paid'

  return {
    orderNo: item.order_no || '',
    price: item.origin_price || '',
    title: detail.coupon_title || '',
    couponUuno: detail.coupon_uuno || '',
    icon: urlUtil.normalizeNetworkUrl(detail.coupon_icon, baseUrl) || FALLBACK_ICON,
    statusText,
    statusClass,
    paymentStatus,
    isRefund,
    canPay: !isRefund && paymentStatus === 0,
    canRefund: !isRefund && paymentStatus === 1,
    openid: item.openid,
    issueCouponUserId: item.issue_coupon_user_id,
    raw: item,
  }
}

function inferTabIndexFromQuery(options) {
  const state = Number(options && options.state)
  if (!Number.isFinite(state)) return 0
  if (state === -1) return 0
  if (state === 0) return 1
  if (state === 1) return 2
  if (state === 2) return 3
  return 0
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    tabs: TABS,
    currentIndex: 0,

    list: [],
    page: 1,
    limit: 8,
    requesting: false,
    loadingStatus: 'more',
    loadingLock: false,
    empty: false,
    error: null,

    payingOrderNo: '',

    refundModal: {
      show: false,
      remark: '',
      orderNo: '',
      openid: '',
      issueCouponUserId: null,
      submitting: false,
    },
  },
  onLoad(options) {
    this.setData({ currentIndex: inferTabIndexFromQuery(options) })
  },
  onShow() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    this.setData({ hasLogin })
    if (!hasLogin) return
    if (!this.data.hasBaseUrl) return
    this.refresh({ showLoading: false })
  },
  onPullDownRefresh() {
    this.refresh({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  onReachBottom() {
    this.fetchNextPage()
  },
  ensureLoginOrPrompt() {
    const uid = auth.getUid()
    if (uid) return Promise.resolve(uid)
    return ui
      .showModal({
        title: '提示',
        content: '查看支付订单需要先登录，是否现在去登录？',
        confirmText: '去登录',
        cancelText: '取消',
      })
      .then((res) => {
        if (res && res.confirm) {
          const redirect = '/subpackages/user/pay_order'
          wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
        }
        return null
      })
  },
  onTapTab(e) {
    const index = Number(e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.index)
    if (!Number.isInteger(index) || index < 0 || index >= TABS.length) return
    if (index === this.data.currentIndex) return
    this.setData({ currentIndex: index })
    if (!this.data.hasLogin) return
    this.refresh({ showLoading: false })
  },
  refresh({ showLoading = true } = {}) {
    if (!this.data.hasBaseUrl) {
      ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
      return Promise.resolve()
    }
    return this.ensureLoginOrPrompt().then((uid) => {
      if (!uid) return
      this.setData({
        list: [],
        page: 1,
        requesting: false,
        loadingStatus: 'more',
        loadingLock: false,
        empty: false,
        error: null,
        payingOrderNo: '',
      })
      return this.fetchNextPage({ showLoading })
    })
  },
  fetchNextPage({ showLoading = true } = {}) {
    const uid = auth.getUid()
    if (!uid) return Promise.resolve()
    if (this.data.loadingLock || this.data.requesting) return Promise.resolve()

    const tab = this.data.tabs[this.data.currentIndex] || TABS[0]
    this.setData({ requesting: true, loadingStatus: 'loading' })

    return userApi
      .getCouponOrderList(
        {
          uid,
          status: tab.statusParam,
          limit: this.data.limit,
          page: this.data.page,
        },
        { showLoading }
      )
      .then((res) => {
        if (!res || typeof res !== 'object') {
          this.setData({ loadingStatus: 'error' })
          ui.toast('返回数据异常')
          return
        }
        if (res.code !== 0) {
          this.setData({ loadingStatus: 'error' })
          ui.toast(res.msg || '请求失败')
          return
        }

        const data = res.data && res.data.data
        const list = Array.isArray(data) ? data : []
        const normalized = list.map((it) => normalizeOrder(it, this.data.baseUrl)).filter(Boolean)

        if (this.data.page === 1 && normalized.length === 0) {
          this.setData({
            empty: true,
            loadingStatus: 'no-more',
            loadingLock: true,
          })
          return
        }

        const nextList = (this.data.list || []).concat(normalized)
        const perPage = Number(res.data && res.data.per_page) || this.data.limit
        const noMore = normalized.length === 0 || normalized.length < perPage
        this.setData({
          list: nextList,
          page: this.data.page + 1,
          loadingStatus: noMore ? 'no-more' : 'more',
          loadingLock: noMore,
        })
      })
      .catch((err) => {
        this.setData({ error: String((err && (err.errMsg || err.message)) || err), loadingStatus: 'error' })
      })
      .finally(() => this.setData({ requesting: false }))
  },
  onTapDetail(e) {
    const index = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.index
    const item = this.data.list && this.data.list[index]
    if (!item || !item.orderNo) return
    wx.navigateTo({ url: `/subpackages/user/pay_detail?order_no=${encodeURIComponent(String(item.orderNo))}` })
  },
  onTapPay(e) {
    const index = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.index
    const item = this.data.list && this.data.list[index]
    if (!item || !item.canPay) return
    if (this.data.payingOrderNo) return
    const user = auth.getUser()
    const uid = user && user.uid
    const openid = user && user.openid
    if (!uid || !openid) {
      ui.toast('登录信息缺失（uid/openid）')
      return
    }

    this.setData({ payingOrderNo: item.orderNo })

    const payload = {
      uid,
      openid,
      coupon_uuno: item.couponUuno,
      data: JSON.stringify({ uuno: item.couponUuno, number: 1, price: item.price }),
      type: 'miniapp',
    }

    payApi
      .submit(payload, { showLoading: true })
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          return
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '支付失败')
          return
        }
        return paymentUtil.requestWxPayment(res).then(
          () => {
            ui.toast('支付成功')
            this.refresh({ showLoading: false })
          },
          (err) => {
            const msg = String((err && (err.errMsg || err.message)) || err)
            if (msg.indexOf('cancel') >= 0) {
              ui.toast('已取消支付')
              return
            }
            ui.toast('支付失败，请稍后重试')
          }
        )
      })
      .catch(() => {
        ui.toast('支付失败，请稍后重试')
      })
      .finally(() => {
        if (this.data.payingOrderNo) this.setData({ payingOrderNo: '' })
      })
  },
  onTapOpenRefund(e) {
    const index = e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.index
    const item = this.data.list && this.data.list[index]
    if (!item || !item.canRefund) return

    this.setData({
      refundModal: {
        show: true,
        remark: '',
        orderNo: item.orderNo,
        openid: item.openid || '',
        issueCouponUserId: item.issueCouponUserId || null,
        submitting: false,
      },
    })
  },
  onCloseRefund() {
    this.setData({ refundModal: { ...this.data.refundModal, show: false, remark: '' } })
  },
  onInputRefundRemark(e) {
    const remark = (e && e.detail && e.detail.value) || ''
    this.setData({ refundModal: { ...this.data.refundModal, remark } })
  },
  onSubmitRefund() {
    if (!this.data.refundModal || !this.data.refundModal.show) return
    if (this.data.refundModal.submitting) return

    const user = auth.getUser()
    const uid = user && user.uid
    const openid = this.data.refundModal.openid || (user && user.openid)
    if (!uid || !openid) {
      ui.toast('登录信息缺失（uid/openid）')
      return
    }

    const remark = String(this.data.refundModal.remark || '').trim()
    if (!remark) {
      ui.toast('请输入退款理由')
      return
    }

    this.setData({ refundModal: { ...this.data.refundModal, submitting: true } })

    payApi
      .refund(
        {
          uid,
          openid,
          order_remark: remark,
          order_no: this.data.refundModal.orderNo,
          coupon_issue_user_id: this.data.refundModal.issueCouponUserId,
        },
        { showLoading: true }
      )
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          return
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '提交失败')
          return
        }
        ui.toast(res.msg || '已提交退款')
        this.onCloseRefund()
        this.refresh({ showLoading: false })
      })
      .catch(() => {
        ui.toast('提交失败，请稍后重试')
      })
      .finally(() => {
        this.setData({ refundModal: { ...this.data.refundModal, submitting: false } })
      })
  },
  onRetry() {
    this.refresh({ showLoading: false })
  },
  noop() {},
})
