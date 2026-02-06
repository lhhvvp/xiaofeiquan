const config = require('../../config')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')
const ticketsApi = require('../../services/api/tickets')

function toRate(steps) {
  const s = Number(steps)
  if (!Number.isFinite(s) || s <= 0) return 0
  return Math.round(s) / 2
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    orderId: '',
    rateSteps: 0,
    rateText: '0分',
    content: '',

    submitting: false,
    error: null,
  },
  onLoad(options) {
    const id = (options && (options.id || options.order_id || options.orderId || options.orderID)) || ''
    this.setData({ orderId: String(id) })
    if (!this.data.orderId) {
      ui
        .showModal({ title: '提示', content: '参数错误：缺少订单 id', showCancel: false })
        .then(() => wx.navigateBack())
      return
    }
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    this.setData({ hasLogin })
  },
  onShow() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    if (hasLogin !== this.data.hasLogin) this.setData({ hasLogin })
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
    if (!this.data.orderId) return false
    return true
  },
  onTapGoLogin() {
    const redirect = `/subpackages/user/commentAdd?id=${encodeURIComponent(this.data.orderId)}`
    wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
  },
  onChangeRate(e) {
    const steps = Number(e && e.detail && e.detail.value) || 0
    const rate = toRate(steps)
    this.setData({ rateSteps: steps, rateText: `${rate}分` })
  },
  onInputContent(e) {
    this.setData({ content: (e && e.detail && e.detail.value) || '' })
  },
  onTapSubmit() {
    if (this.data.submitting) return
    if (!this.ensureReady()) return

    const rate = toRate(this.data.rateSteps)
    const content = String(this.data.content || '').trim()
    if (!rate) {
      ui.toast('请打分')
      return
    }
    if (!content) {
      ui.toast('请输入评论内容')
      return
    }

    this.setData({ submitting: true, error: null })

    ticketsApi
      .writeComment({ order_id: this.data.orderId, content, rate }, { showLoading: true })
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          return
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '提交失败')
          return
        }
        ui.toast(res.msg || '提交成功', { icon: 'success' })
        setTimeout(() => wx.navigateBack(), 600)
      })
      .catch((err) => this.setData({ error: String((err && (err.errMsg || err.message)) || err) }))
      .finally(() => this.setData({ submitting: false }))
  },
})

