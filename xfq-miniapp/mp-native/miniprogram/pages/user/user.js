const config = require('../../config')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')
const locationService = require('../../services/location')
const userApi = require('../../services/api/user')

function formatUser(user) {
  if (!user || typeof user !== 'object') return {}
  const safe = {}
  ;['uid', 'openid', 'uuid', 'no', 'name', 'nickname', 'headimgurl', 'idcard', 'mobile'].forEach((k) => {
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
  onTapProfile() {
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
          content: '编辑个人资料需要先登录，是否现在去登录？',
          confirmText: '去登录',
          cancelText: '取消',
        })
        .then((res) => {
          if (res && res.confirm) {
            const redirect = '/subpackages/user/set'
            wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
          }
        })
      return
    }
    wx.navigateTo({ url: '/subpackages/user/set' })
  },
  onTapCollect() {
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
          content: '查看收藏需要先登录，是否现在去登录？',
          confirmText: '去登录',
          cancelText: '取消',
        })
        .then((res) => {
          if (res && res.confirm) {
            const redirect = '/subpackages/user/collect'
            wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
          }
        })
      return
    }
    wx.navigateTo({ url: '/subpackages/user/collect' })
  },
  onTapComplaints() {
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
          content: '提交反馈需要先登录，是否现在去登录？',
          confirmText: '去登录',
          cancelText: '取消',
        })
        .then((res) => {
          if (res && res.confirm) {
            const redirect = '/subpackages/user/complaints'
            wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
          }
        })
      return
    }
    wx.navigateTo({ url: '/subpackages/user/complaints' })
  },
  onTapMyComments() {
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
          content: '查看我的评价需要先登录，是否现在去登录？',
          confirmText: '去登录',
          cancelText: '取消',
        })
        .then((res) => {
          if (res && res.confirm) {
            const redirect = '/subpackages/user/comment'
            wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
          }
        })
      return
    }
    wx.navigateTo({ url: '/subpackages/user/comment' })
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
  onTapMyAppointments() {
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
          content: '查看我的预约需要先登录，是否现在去登录？',
          confirmText: '去登录',
          cancelText: '取消',
        })
        .then((res) => {
          if (res && res.confirm) {
            const redirect = '/subpackages/user/subscribe/my_list'
            wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
          }
        })
      return
    }
    wx.navigateTo({ url: '/subpackages/user/subscribe/my_list' })
  },
  onTapSignInTasks() {
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
          content: '查看打卡任务需要先登录，是否现在去登录？',
          confirmText: '去登录',
          cancelText: '取消',
        })
        .then((res) => {
          if (res && res.confirm) {
            const redirect = '/subpackages/user/signIn/signIn'
            wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
          }
        })
      return
    }
    wx.navigateTo({ url: '/subpackages/user/signIn/signIn' })
  },
  onTapCouponClockTask() {
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
          content: '查看打卡券任务需要先登录，是否现在去登录？',
          confirmText: '去登录',
          cancelText: '取消',
        })
        .then((res) => {
          if (res && res.confirm) {
            const redirect = `/subpackages/user/task/detail?couponId=201&couponTitle=${encodeURIComponent('满 100 减 20')}`
            wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
          }
        })
      return
    }
    wx.navigateTo({
      url: `/subpackages/user/task/detail?couponId=201&couponTitle=${encodeURIComponent('满 100 减 20')}`,
    })
  },
  onTapMyMap() {
    locationService.getLocation({ cacheFirst: false, promptSetting: true }).then((coord) => {
      wx.navigateTo({
        url: `/subpackages/merchant/mymap?lat=${encodeURIComponent(String(coord.latitude))}&lng=${encodeURIComponent(String(coord.longitude))}`,
      })
    })
  },
  onTapNews() {
    if (!this.data.hasBaseUrl) {
      ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
      return
    }
    wx.navigateTo({ url: '/subpackages/content/news/news' })
  },
  onTapServiceAgreement() {
    if (!this.data.hasBaseUrl) {
      ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
      return
    }
    wx.navigateTo({ url: `/subpackages/content/user/agreement?title=${encodeURIComponent('服务协议')}` })
  },
  onTapPrivacyPolicy() {
    if (!this.data.hasBaseUrl) {
      ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
      return
    }
    wx.navigateTo({ url: `/subpackages/content/user/agreement?title=${encodeURIComponent('隐私政策')}` })
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

        const type = String(data.type || '')

        if (type === 'user') {
          const coord = data.coord ? JSON.stringify(data.coord) : ''
          const url =
            `/subpackages/coupon/coupon_CAV?id=${encodeURIComponent(String(data.id || ''))}` +
            `&mid=${encodeURIComponent(String(this.data.mid))}` +
            `&qrcode_url=${encodeURIComponent(String(data.qrcode || ''))}` +
            `&coord=${encodeURIComponent(coord)}`
          wx.navigateTo({ url })
          return
        }

        if (type === 'order') {
          if (!data.qrcode_str || typeof data.be_id === 'undefined' || data.be_id === null) {
            ui.toast('二维码数据异常')
            return
          }
          wx.navigateTo({
            url: `/subpackages/user/coupon_CAV_order/coupon_CAV_order?data=${encodeURIComponent(JSON.stringify(data))}`,
          })
          return
        }

        if (type === 'order_user') {
          if (!data.qrcode_str || typeof data.be_id === 'undefined' || data.be_id === null || !data.id) {
            ui.toast('二维码数据异常')
            return
          }
          wx.navigateTo({
            url: `/subpackages/user/coupon_CAV_order/coupon_CAV_user?data=${encodeURIComponent(JSON.stringify(data))}`,
          })
          return
        }

        if (type === 'subscribe') {
          if (!data.qrcode_str || typeof data.be_id === 'undefined' || data.be_id === null) {
            ui.toast('二维码数据异常')
            return
          }
          wx.navigateTo({
            url: `/subpackages/user/coupon_CAV_subscribe/coupon_CAV_subscribe?data=${encodeURIComponent(JSON.stringify(data))}`,
          })
          return
        }

        if (type === 'groupCoupon') {
          if (!data.qrcode || !data.id) {
            ui.toast('二维码数据异常')
            return
          }
          const coord = data.coord ? encodeURIComponent(JSON.stringify(data.coord)) : ''
          const url =
            `/subpackages/coupon/coupon_CAV_Group/coupon_CAV_Group` +
            `?id=${encodeURIComponent(String(data.id))}` +
            `&mid=${encodeURIComponent(String(this.data.mid))}` +
            `&qrcode_url=${encodeURIComponent(String(data.qrcode))}` +
            `&coord=${coord}` +
            `&type=${encodeURIComponent(type)}`
          wx.navigateTo({ url })
          return
        }

        ui.toast('暂不支持该二维码类型')
      },
      fail: () => {},
    })
  },
  onTapWriteoffLogs() {
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
          content: '查看核销记录需要先登录，是否现在去登录？',
          confirmText: '去登录',
          cancelText: '取消',
        })
        .then((res) => {
          if (res && res.confirm) {
            const redirect = `/subpackages/user/order_CAV?mid=${encodeURIComponent(String(this.data.mid || ''))}`
            wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
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

    wx.navigateTo({ url: `/subpackages/user/order_CAV?mid=${encodeURIComponent(String(this.data.mid))}` })
  },
})
