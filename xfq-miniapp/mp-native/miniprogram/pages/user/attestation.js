const config = require('../../config')
const auth = require('../../services/auth')
const request = require('../../services/request')
const ui = require('../../utils/ui')

const CODE_TIME_STORAGE_KEY = 'codeTime'

function isValidName(value) {
  return /^[\u4e00-\u9fa5a-zA-Z\s·]{2,30}$/.test(String(value || '').trim())
}

function isValidPhone(value) {
  return /^1\d{10}$/.test(String(value || '').trim())
}

function isValidIdCard(value) {
  return /^[0-9A-Za-z]{15,18}$/.test(String(value || '').trim())
}

function clearTimer(timer) {
  if (timer) clearInterval(timer)
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    uid: 0,
    name: '',
    mobile: '',
    idcard: '',
    mobileCode: '',
    authStatus: 0,
    isMobile: false,
    sendTime: 60,
    sendTitle: '发送验证码',
    sending: false,
    error: '',
  },
  onLoad(options) {
    const uid = auth.getUid() || 0
    this.setData({ uid })

    if (options && options.option) {
      try {
        const option = JSON.parse(options.option)
        this.setData({ authStatus: Number(option && option.auth_status) || 0 })
      } catch (e) {}
    }

    this.restoreSmsCountdown()
    this.loadAuthInfo()
  },
  onUnload() {
    clearTimer(this.codeTimer)
    this.codeTimer = null
  },
  restoreSmsCountdown() {
    let expireAt = 0
    try {
      expireAt = Number(wx.getStorageSync(CODE_TIME_STORAGE_KEY) || 0)
    } catch (e) {}
    const remain = Math.ceil((expireAt - Date.now()) / 1000)
    if (remain > 0) {
      this.startCountdown(remain)
    }
  },
  startCountdown(second = 60) {
    clearTimer(this.codeTimer)
    let time = Number(second) || 60
    if (time <= 0) {
      this.setData({ sendTime: 60, sendTitle: '发送验证码' })
      return
    }
    this.setData({ sendTime: time, sendTitle: `请在${time}秒后重试` })
    this.codeTimer = setInterval(() => {
      time -= 1
      if (time <= 0) {
        clearTimer(this.codeTimer)
        this.codeTimer = null
        this.setData({ sendTime: 60, sendTitle: '重新获取验证码' })
        return
      }
      this.setData({ sendTime: time, sendTitle: `请在${time}秒后重试` })
    }, 1000)
  },
  loadAuthInfo() {
    if (!this.data.hasBaseUrl || !this.data.uid) return
    request({
      path: '/user/auth_info',
      method: 'POST',
      data: { uid: this.data.uid },
      showLoading: false,
    })
      .then((res) => {
        if (!res || typeof res !== 'object' || res.code !== 0 || !res.data) return
        const payload = res.data || {}
        const authStatus = Number(payload.auth_status || this.data.authStatus) || 0
        this.setData({
          name: payload.name || this.data.name,
          idcard: payload.idcard || this.data.idcard,
          mobile: payload.mobile || this.data.mobile,
          authStatus,
          isMobile: !!(payload.mobile || this.data.mobile),
        })
      })
      .catch(() => {})
  },
  onInputName(e) {
    this.setData({ name: (e && e.detail && e.detail.value) || '' })
  },
  onInputIdCard(e) {
    this.setData({ idcard: (e && e.detail && e.detail.value) || '' })
  },
  onInputMobile(e) {
    this.setData({
      mobile: (e && e.detail && e.detail.value) || '',
      isMobile: true,
    })
  },
  onInputMobileCode(e) {
    this.setData({ mobileCode: (e && e.detail && e.detail.value) || '' })
  },
  onGetPhoneNumber(e) {
    const code = e && e.detail && e.detail.code
    if (!code) {
      ui.toast('获取手机号失败')
      return
    }
    request({
      path: '/index/getuserphonenumber',
      method: 'POST',
      data: { code },
      showLoading: true,
    })
      .then((ret) => {
        const phone =
          ret &&
          ret.data &&
          ret.data.phone_info &&
          (ret.data.phone_info.phoneNumber || ret.data.phone_info.purePhoneNumber)
        if (phone) {
          this.setData({ mobile: String(phone), isMobile: true })
          return
        }
        ui.toast('获取手机号失败')
      })
      .catch(() => ui.toast('获取手机号失败'))
  },
  sendCode() {
    if (this.data.sending) return
    if (this.data.sendTime !== 60) return
    if (!isValidPhone(this.data.mobile)) {
      ui.toast('请先输入有效手机号')
      return
    }
    this.setData({ sending: true })
    request({
      path: '/user/smsVerification',
      method: 'POST',
      data: { mobile: this.data.mobile, uid: this.data.uid },
      showLoading: true,
    })
      .then((res) => {
        ui.toast((res && res.msg) || '发送完成')
        const expireAt = Date.now() + 60000
        try {
          wx.setStorageSync(CODE_TIME_STORAGE_KEY, expireAt)
        } catch (e) {}
        this.startCountdown(60)
      })
      .catch(() => ui.toast('发送失败'))
      .finally(() => this.setData({ sending: false }))
  },
  submit() {
    const name = String(this.data.name || '').trim()
    const mobile = String(this.data.mobile || '').trim()
    const idcard = String(this.data.idcard || '').trim()
    const mobileCode = String(this.data.mobileCode || '').trim()

    if (!isValidName(name)) {
      ui.toast('请填写有效姓名')
      return
    }
    if (!isValidPhone(mobile)) {
      ui.toast('请填写有效手机号')
      return
    }
    if (!isValidIdCard(idcard)) {
      ui.toast('请填写有效身份证号')
      return
    }
    if (this.data.isMobile && !mobileCode) {
      ui.toast('请填写短信验证码')
      return
    }

    return request({
      path: '/user/auth_identity',
      method: 'POST',
      data: {
        uid: this.data.uid,
        name,
        idcard,
        mobile,
        tags: this.data.isMobile ? 1 : 0,
        smsCode: mobileCode,
      },
      showLoading: true,
    })
      .then((res) => {
        const code = res && res.code
        const content = (res && res.msg) || '提交完成'
        return ui
          .showModal({
            title: '提示',
            content,
            showCancel: false,
          })
          .then(() => {
            if (code === 0) {
              this.setData({ authStatus: 1 })
              setTimeout(() => wx.navigateBack(), 200)
            }
          })
      })
      .catch((err) => {
        this.setData({ error: String((err && (err.errMsg || err.message)) || err) })
      })
  },
  navBack() {
    wx.navigateBack()
  },
})
