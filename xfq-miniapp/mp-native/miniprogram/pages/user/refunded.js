const config = require('../../config')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')
const ticketsApi = require('../../services/api/tickets')

function isTruthy(val) {
  const v = String(val || '').toLowerCase()
  return v === 'true' || v === '1' || v === 'yes'
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,
    tradeNo: '',
    single: false,
    refundDesc: '',
    submitting: false,
    error: null,
  },
  onLoad(options) {
    const tradeNo = (options && (options.trade_no || options.tradeNo)) || ''
    const single = isTruthy(options && options.single)
    this.setData({ tradeNo: String(tradeNo), single })
  },
  onShow() {
    const user = auth.getUser()
    this.setData({ hasLogin: !!(user && user.token && user.uid) })
  },
  ensureLoginOrPrompt() {
    const uid = auth.getUid()
    if (uid) return Promise.resolve(uid)
    return ui
      .showModal({
        title: '提示',
        content: '申请退款需要先登录，是否现在去登录？',
        confirmText: '去登录',
        cancelText: '取消',
      })
      .then((res) => {
        if (res && res.confirm) {
          const redirect =
            `/pages/user/refunded?trade_no=${encodeURIComponent(String(this.data.tradeNo || ''))}` +
            `&single=${encodeURIComponent(String(this.data.single ? 'true' : 'false'))}`
          wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
        }
        return null
      })
  },
  onInputRefundDesc(e) {
    this.setData({ refundDesc: (e && e.detail && e.detail.value) || '' })
  },
  onTapSubmit() {
    if (this.data.submitting) return
    if (!this.data.hasBaseUrl) return

    this.ensureLoginOrPrompt().then((uid) => {
      if (!uid) return
      const user = auth.getUser()
      const openid = user && user.openid
      const uuid = user && user.uuid
      if (!openid || !uuid) {
        ui.toast('登录信息缺失（openid/uuid）')
        return
      }

      const tradeNo = String(this.data.tradeNo || '').trim()
      const refundDesc = String(this.data.refundDesc || '').trim()
      if (!tradeNo) {
        ui.toast('缺少订单号')
        return
      }
      if (!refundDesc) {
        ui.toast('请输入退款备注')
        return
      }

      this.setData({ submitting: true, error: null })

      const api = this.data.single ? ticketsApi.singleRefund : ticketsApi.refund
      api({ out_trade_no: tradeNo, refund_desc: refundDesc, openid, uuid }, { showLoading: true })
        .then((res) => {
          if (!res || typeof res !== 'object') {
            ui.toast('返回数据异常')
            return
          }
          if (res.code !== 0) {
            ui.toast(res.msg || '提交失败')
            return
          }
          ui.toast(res.msg || '已提交')
          setTimeout(() => wx.navigateBack(), 1200)
        })
        .catch((err) => {
          this.setData({ error: String((err && (err.errMsg || err.message)) || err) })
          ui.toast('提交失败，请稍后重试')
        })
        .finally(() => this.setData({ submitting: false }))
    })
  },
})

