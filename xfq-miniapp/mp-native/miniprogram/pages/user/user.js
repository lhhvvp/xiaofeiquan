const config = require('../../config')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')
const userApi = require('../../services/api/user')

function formatUser(user) {
  if (!user || typeof user !== 'object') return {}
  const safe = {}
  ;['uid', 'openid', 'uuid', 'no', 'name', 'idcard', 'mobile'].forEach((k) => {
    if (typeof user[k] !== 'undefined') safe[k] = user[k]
  })
  safe.hasToken = !!user.token
  return safe
}

Page({
  data: {
    envVersion: config.envVersion,
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    userText: '',
    mid: 0,
  },
  onShow() {
    const user = auth.getUser()
    this.setData({ userText: JSON.stringify(formatUser(user), null, 2) })
    this.refreshMerchantRole({ showLoading: false })
  },
  onTapGoLogin() {
    wx.navigateTo({
      url: `/pages/user/login/login?redirect=${encodeURIComponent('/pages/user/user')}`,
    })
  },
  onTapLogout() {
    auth.clearUser()
    this.onShow()
    wx.showToast({ title: '已退出登录', icon: 'none' })
  },
  onTapMyCoupons() {
    if (!this.data.hasBaseUrl) {
      ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
      return
    }
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    if (!hasLogin) {
      ui
        .showModal({
          title: '提示',
          content: '查看我的券需要先登录，是否现在去登录？',
          confirmText: '去登录',
          cancelText: '取消',
        })
        .then((res) => {
          if (res && res.confirm) {
            const redirect = '/subpackages/user/order/order'
            wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
          }
        })
      return
    }
    wx.navigateTo({ url: '/subpackages/user/order/order' })
  },
  onTapTourists() {
    if (!this.data.hasBaseUrl) {
      ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
      return
    }
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    if (!hasLogin) {
      ui
        .showModal({
          title: '提示',
          content: '管理常用游客需要先登录，是否现在去登录？',
          confirmText: '去登录',
          cancelText: '取消',
        })
        .then((res) => {
          if (res && res.confirm) {
            const redirect = '/subpackages/user/person/list'
            wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
          }
        })
      return
    }
    wx.navigateTo({ url: '/subpackages/user/person/list' })
  },
  onTapTicketOrders() {
    if (!this.data.hasBaseUrl) {
      ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
      return
    }
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    if (!hasLogin) {
      ui
        .showModal({
          title: '提示',
          content: '查看门票订单需要先登录，是否现在去登录？',
          confirmText: '去登录',
          cancelText: '取消',
        })
        .then((res) => {
          if (res && res.confirm) {
            const redirect = '/subpackages/user/my_order?state='
            wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
          }
        })
      return
    }
    wx.navigateTo({ url: '/subpackages/user/my_order?state=' })
  },
  onTapPayOrders() {
    if (!this.data.hasBaseUrl) {
      ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
      return
    }
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    if (!hasLogin) {
      ui
        .showModal({
          title: '提示',
          content: '查看支付订单需要先登录，是否现在去登录？',
          confirmText: '去登录',
          cancelText: '取消',
        })
        .then((res) => {
          if (res && res.confirm) {
            const redirect = '/subpackages/user/pay_order?state=-1'
            wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
          }
        })
      return
    }
    wx.navigateTo({ url: '/subpackages/user/pay_order?state=-1' })
  },
  onTapRefundLogs() {
    if (!this.data.hasBaseUrl) {
      ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
      return
    }
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    if (!hasLogin) {
      ui
        .showModal({
          title: '提示',
          content: '查看售后记录需要先登录，是否现在去登录？',
          confirmText: '去登录',
          cancelText: '取消',
        })
        .then((res) => {
          if (res && res.confirm) {
            const redirect = '/subpackages/user/my_order_refund'
            wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
          }
        })
      return
    }
    wx.navigateTo({ url: '/subpackages/user/my_order_refund' })
  },
  refreshMerchantRole({ showLoading = false } = {}) {
    if (!this.data.hasBaseUrl) return Promise.resolve()
    const uid = auth.getUid()
    if (!uid) {
      this.setData({ mid: 0 })
      return Promise.resolve()
    }
    return userApi
      .getAuthInfo({ uid }, { showLoading })
      .then((res) => {
        if (!res || typeof res !== 'object' || res.code !== 0) {
          this.setData({ mid: 0 })
          return
        }
        const ismv = res.data && res.data.ismv
        const mid = ismv && ismv.mid
        this.setData({ mid: Number(mid) || 0 })
      })
      .catch(() => this.setData({ mid: 0 }))
  },
  parseScanResult(str) {
    const raw = String(str || '').trim()
    if (!raw) return null
    try {
      return JSON.parse(raw)
    } catch (e) {}
    try {
      return JSON.parse(decodeURIComponent(raw))
    } catch (e) {}
    return null
  },
  onTapScanWriteoff() {
    if (!this.data.hasBaseUrl) {
      ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
      return
    }
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    if (!hasLogin) {
      ui
        .showModal({
          title: '提示',
          content: '扫码核销需要先登录，是否现在去登录？',
          confirmText: '去登录',
          cancelText: '取消',
        })
        .then((res) => {
          if (res && res.confirm) {
            wx.navigateTo({
              url: `/pages/user/login/login?redirect=${encodeURIComponent('/pages/user/user')}`,
            })
          }
        })
      return
    }
    if (!this.data.mid) {
      ui.showModal({
        title: '提示',
        content: '当前账号未绑定核销商户（mid）。请先通过绑定链接完成核销人员绑定。',
        showCancel: false,
      })
      return
    }

    wx.scanCode({
      success: (res) => {
        const data = this.parseScanResult(res && res.result)
        if (!data) {
          ui.toast('二维码数据异常')
          return
        }

        if (data.coord && typeof data.coord === 'string') {
          try {
            data.coord = JSON.parse(data.coord)
          } catch (e) {}
        }

        if (data.type !== 'user') {
          ui.toast('暂不支持该二维码类型')
          return
        }

        const coord = data.coord ? JSON.stringify(data.coord) : ''
        const url =
          `/subpackages/coupon/coupon_CAV?id=${encodeURIComponent(String(data.id || ''))}` +
          `&mid=${encodeURIComponent(String(this.data.mid))}` +
          `&qrcode_url=${encodeURIComponent(String(data.qrcode || ''))}` +
          `&coord=${encodeURIComponent(coord)}`
        wx.navigateTo({ url })
      },
      fail: () => {},
    })
  },
})
