const config = require('../../config')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')
const locationService = require('../../services/location')
const systemService = require('../../services/system')
const userApi = require('../../services/api/user')

const DEFAULT_AVATAR =
  'https://thirdwx.qlogo.cn/mmopen/vi_32/POgEwh4mIHO4nibH0KlMECNjjGxQUq24ZEaGT4poC6icRiccVGKSyXwibcPq4BWmiaIGuG1icwxaQX6grC9VemZoJ8rg/132'

function safeText(value, fallback = '') {
  if (value === null || typeof value === 'undefined') return fallback
  return String(value)
}

function buildDisplayUser(sessionUser = {}, profileUser = {}) {
  const merged = Object.assign({}, sessionUser || {}, profileUser || {})
  const hasLogin = !!(merged && merged.uid && merged.token)
  const nickname = safeText(merged.nickname)
  return {
    uid: Number(merged.uid) || 0,
    name: hasLogin ? safeText(merged.name || nickname, '微信用户') : '暂未登录',
    nickname: nickname && nickname !== '微信用户' ? nickname : '',
    mobile: safeText(merged.mobile),
    headimgurl: safeText(merged.headimgurl, DEFAULT_AVATAR),
    authStatusText: hasLogin ? (Number(merged.auth_status) === 1 ? '（已实名）' : '（未实名）') : '',
    isClock: !!merged.is_clock,
  }
}

Page({
  data: {
    envVersion: config.envVersion,
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,
    displayUser: buildDisplayUser(),
    defaultAvatar: DEFAULT_AVATAR,
    tel: '',
    mid: 0,
  },

  onShow() {
    this.syncSession()
    this.refreshUserPanel({ showLoading: false })
  },

  onShareAppMessage() {
    return {
      title: '榆林市旅游消费平台',
      path: '/pages/user/user',
    }
  },

  onShareTimeline() {
    return {
      title: '榆林市旅游消费平台',
      path: '/pages/user/user',
    }
  },

  syncSession() {
    const sessionUser = auth.getUser()
    const hasLogin = !!(sessionUser && sessionUser.uid && sessionUser.token)
    this.setData({
      hasLogin,
      displayUser: buildDisplayUser(sessionUser),
    })
    return { hasLogin, sessionUser }
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

  ensureLogin({ content, redirect } = {}) {
    const user = auth.getUser()
    const hasLogin = !!(user && user.uid && user.token)
    if (hasLogin) return Promise.resolve(true)

    return ui
      .showModal({
        title: '提示',
        content: content || '该操作需要先登录，是否现在去登录？',
        confirmText: '去登录',
        cancelText: '取消',
      })
      .then((res) => {
        if (res && res.confirm) {
          wx.navigateTo({
            url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect || '/pages/user/user')}`,
          })
        }
        return false
      })
  },

  navigateWithLogin({ path, loginContent } = {}) {
    if (!path) return
    if (!this.ensureBaseUrl()) return
    this.ensureLogin({ content: loginContent, redirect: path }).then((ok) => {
      if (!ok) return
      wx.navigateTo({ url: path })
    })
  },

  refreshUserPanel({ showLoading = false } = {}) {
    const state = this.syncSession()
    if (!this.data.hasBaseUrl) {
      this.setData({ mid: 0, tel: '' })
      return Promise.resolve()
    }

    const uid = auth.getUid()
    if (!uid || !state.hasLogin) {
      this.setData({ mid: 0 })
      return systemService
        .fetchSystem({ showLoading: false })
        .then((system) => this.setData({ tel: safeText(system && system.tel) }))
        .catch(() => {})
    }

    const userReq = userApi.getUserIndex({ uid }, { showLoading })
    const authInfoReq = userApi.getAuthInfo({ uid }, { showLoading: false })
    const systemReq = systemService.fetchSystem({ showLoading: false })

    return Promise.allSettled([userReq, authInfoReq, systemReq]).then((resultList) => {
      const sessionUser = auth.getUser()

      const userResult = resultList[0]
      const authResult = resultList[1]
      const systemResult = resultList[2]

      let profileUser = {}
      let mid = 0

      if (userResult && userResult.status === 'fulfilled') {
        const userResp = userResult.value
        if (userResp && userResp.code === 0 && userResp.data && typeof userResp.data === 'object') {
          profileUser = userResp.data
          if (profileUser.ismv && profileUser.ismv.mid) mid = Number(profileUser.ismv.mid) || 0
        }
      }

      if (!mid && authResult && authResult.status === 'fulfilled') {
        const authResp = authResult.value
        if (authResp && authResp.code === 0 && authResp.data && authResp.data.ismv && authResp.data.ismv.mid) {
          mid = Number(authResp.data.ismv.mid) || 0
        }
      }

      if (systemResult && systemResult.status === 'fulfilled') {
        const systemData = systemResult.value
        this.setData({ tel: safeText(systemData && systemData.tel) })
      }

      const mergedUser = Object.assign({}, sessionUser || {}, profileUser || {})
      if (sessionUser && sessionUser.token) mergedUser.token = sessionUser.token
      auth.setUser(mergedUser)

      this.setData({
        mid,
        hasLogin: !!(mergedUser && mergedUser.uid && mergedUser.token),
        displayUser: buildDisplayUser(mergedUser, profileUser),
      })
    })
  },

  onTapGoLogin() {
    wx.navigateTo({
      url: `/pages/user/login/login?redirect=${encodeURIComponent('/pages/user/user')}`,
    })
  },

  onTapUserHeader() {
    if (this.data.hasLogin) {
      this.onTapProfile()
      return
    }
    this.onTapGoLogin()
  },

  onTapGetUserProfile(e) {
    if (e && typeof e.stopPropagation === 'function') e.stopPropagation()
    if (!this.ensureBaseUrl()) return
    this.ensureLogin({
      content: '更新头像与昵称需要先登录，是否现在去登录？',
      redirect: '/pages/user/user',
    }).then((ok) => {
      if (!ok) return
      wx.getUserProfile({
        desc: '用于完善会员资料',
        success: (infoRes) => {
          if (!infoRes || infoRes.errMsg !== 'getUserProfile:ok') {
            ui.toast('获取用户信息失败')
            return
          }
          const uid = auth.getUid()
          if (!uid) return
          userApi
            .editUser(
              {
                id: uid,
                nickname: safeText(infoRes.userInfo && infoRes.userInfo.nickName),
                headimgurl: safeText(infoRes.userInfo && infoRes.userInfo.avatarUrl),
              },
              { showLoading: true }
            )
            .then((res) => {
              if (res && res.code === 0) {
                ui.toast('更新成功', { icon: 'success' })
                this.refreshUserPanel({ showLoading: false })
                return
              }
              ui.toast((res && res.msg) || '更新失败')
            })
            .catch(() => ui.toast('更新失败'))
        },
      })
    })
  },

  onTapCouponState(e) {
    const state = Number(e && e.currentTarget && e.currentTarget.dataset && e.currentTarget.dataset.state)
    if (![1, 2, 3].includes(state)) return
    const textMap = {
      1: '查看未使用消费券需要先登录，是否现在去登录？',
      2: '查看已使用消费券需要先登录，是否现在去登录？',
      3: '查看已过期消费券需要先登录，是否现在去登录？',
    }
    this.navigateWithLogin({
      path: `/subpackages/user/order/order?state=${encodeURIComponent(String(state))}`,
      loginContent: textMap[state],
    })
  },

  onTapTicketOrders() {
    this.navigateWithLogin({
      path: '/subpackages/user/my_order?state=',
      loginContent: '查看门票订单需要先登录，是否现在去登录？',
    })
  },

  onTapTicketPaidOrders() {
    this.navigateWithLogin({
      path: '/subpackages/user/my_order?state=paid',
      loginContent: '查看已支付订单需要先登录，是否现在去登录？',
    })
  },

  onTapRefundLogs() {
    this.navigateWithLogin({
      path: '/subpackages/user/my_order_refund',
      loginContent: '查看售后记录需要先登录，是否现在去登录？',
    })
  },

  onTapMyCoupons() {
    this.navigateWithLogin({
      path: '/subpackages/user/order/order',
      loginContent: '查看我的券需要先登录，是否现在去登录？',
    })
  },

  onTapPayOrders() {
    this.navigateWithLogin({
      path: '/subpackages/user/pay_order?state=-1',
      loginContent: '查看支付订单需要先登录，是否现在去登录？',
    })
  },

  onTapProfile() {
    this.navigateWithLogin({
      path: '/subpackages/user/set',
      loginContent: '编辑个人资料需要先登录，是否现在去登录？',
    })
  },

  onTapTourists() {
    this.navigateWithLogin({
      path: '/subpackages/user/person/list',
      loginContent: '管理常用游客需要先登录，是否现在去登录？',
    })
  },

  onTapMyAppointments() {
    this.navigateWithLogin({
      path: '/subpackages/user/subscribe/my_list',
      loginContent: '查看我的预约需要先登录，是否现在去登录？',
    })
  },

  onTapCollect() {
    this.navigateWithLogin({
      path: '/subpackages/user/collect',
      loginContent: '查看收藏需要先登录，是否现在去登录？',
    })
  },

  onTapMyComments() {
    this.navigateWithLogin({
      path: '/subpackages/user/comment',
      loginContent: '查看我的评价需要先登录，是否现在去登录？',
    })
  },

  onTapComplaints() {
    this.navigateWithLogin({
      path: '/subpackages/user/complaints',
      loginContent: '提交反馈需要先登录，是否现在去登录？',
    })
  },

  onTapSignInTasks() {
    this.navigateWithLogin({
      path: '/subpackages/user/signIn/signIn',
      loginContent: '查看打卡任务需要先登录，是否现在去登录？',
    })
  },

  onTapCouponClockTask() {
    const path = `/subpackages/user/task/detail?couponId=201&couponTitle=${encodeURIComponent('满 100 减 20')}`
    this.navigateWithLogin({
      path,
      loginContent: '查看打卡券任务需要先登录，是否现在去登录？',
    })
  },

  onTapNews() {
    if (!this.ensureBaseUrl()) return
    wx.navigateTo({ url: '/subpackages/content/news/news' })
  },

  onTapServiceAgreement() {
    if (!this.ensureBaseUrl()) return
    wx.navigateTo({ url: `/subpackages/content/user/agreement?title=${encodeURIComponent('服务协议')}` })
  },

  onTapPrivacyPolicy() {
    if (!this.ensureBaseUrl()) return
    wx.navigateTo({ url: `/subpackages/content/user/agreement?title=${encodeURIComponent('隐私政策')}` })
  },

  onTapMyMap() {
    locationService.getLocation({ cacheFirst: false, promptSetting: true }).then((coord) => {
      wx.navigateTo({
        url: `/subpackages/merchant/mymap?lat=${encodeURIComponent(String(coord.latitude))}&lng=${encodeURIComponent(String(coord.longitude))}`,
      })
    })
  },

  onTapPhoneCall() {
    if (!this.data.tel) {
      ui.toast('暂未配置客服电话')
      return
    }
    wx.makePhoneCall({ phoneNumber: this.data.tel })
  },

  onTapClearCache() {
    ui.showModal({
      title: '提示',
      content: '是否清理缓存并重新登录？',
      confirmText: '清理',
      cancelText: '取消',
    }).then((res) => {
      if (!(res && res.confirm)) return
      try {
        wx.removeStorageSync('uerInfo')
        wx.removeStorageSync('coord')
        wx.removeStorageSync('system')
        wx.clearStorageSync()
      } catch (e) {}
      auth.clearUser()
      wx.reLaunch({ url: '/pages/user/login/login' })
    })
  },

  onTapLogout() {
    if (!this.data.hasLogin) {
      ui.toast('当前未登录')
      return
    }
    ui.showModal({
      title: '提示',
      content: '确定退出当前账号？',
      confirmText: '退出',
      cancelText: '取消',
    }).then((res) => {
      if (!(res && res.confirm)) return
      auth.clearUser()
      this.syncSession()
      this.setData({ mid: 0 })
      ui.toast('已退出登录')
    })
  },

  parseScanResult(rawValue) {
    const raw = String(rawValue || '').trim()
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
    if (!this.ensureBaseUrl()) return
    this.ensureLogin({
      content: '扫码核销需要先登录，是否现在去登录？',
      redirect: '/pages/user/user',
    }).then((ok) => {
      if (!ok) return

      if (!this.data.mid) {
        ui.showModal({
          title: '提示',
          content: '当前账号未绑定核销商户（mid）。请先完成核销人员绑定。',
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
      })
    })
  },

  onTapWriteoffLogs() {
    if (!this.ensureBaseUrl()) return
    this.ensureLogin({
      content: '查看核销记录需要先登录，是否现在去登录？',
      redirect: '/pages/user/user',
    }).then((ok) => {
      if (!ok) return

      if (!this.data.mid) {
        ui.showModal({
          title: '提示',
          content: '当前账号未绑定核销商户（mid）。请先完成核销人员绑定。',
          showCancel: false,
        })
        return
      }

      wx.navigateTo({
        url: `/subpackages/user/order_CAV?mid=${encodeURIComponent(String(this.data.mid))}`,
      })
    })
  },
})
