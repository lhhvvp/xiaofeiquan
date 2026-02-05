const config = require('../../../config')
const auth = require('../../../services/auth')
const ui = require('../../../utils/ui')
const userApi = require('../../../services/api/user')

const EDIT_PAYLOAD_KEY = '__nav_tourist_edit'

function safeGetStorage(key) {
  try {
    return wx.getStorageSync(key)
  } catch (e) {
    return null
  }
}

function safeSetStorage(key, value) {
  try {
    wx.setStorageSync(key, value)
  } catch (e) {}
}

function isPhone(val) {
  return /^1\\d{10}$/.test(String(val || '').trim())
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    id: '',
    certTypeOptions: [],
    certTypeIndex: 0,
    certTypeValue: '',
    certTypeText: '',

    fullname: '',
    certId: '',
    mobile: '',

    saving: false,
    error: null,
  },
  onLoad() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    this.setData({ hasLogin })

    const edit = safeGetStorage(EDIT_PAYLOAD_KEY)
    if (edit && typeof edit === 'object') {
      this.setData({
        id: edit.id || '',
        fullname: edit.fullname || '',
        certId: edit.cert_id || '',
        mobile: edit.mobile || '',
        certTypeValue: String(edit.cert_type || ''),
      })
      safeSetStorage(EDIT_PAYLOAD_KEY, null)
      wx.setNavigationBarTitle({ title: '编辑游客' })
    }

    if (this.data.hasBaseUrl && hasLogin) this.fetchCertTypes()
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
    return true
  },
  fetchCertTypes() {
    const uid = auth.getUid()
    if (!uid) return
    userApi
      .getCertTypeList({ userid: uid }, { showLoading: false })
      .then((res) => {
        if (!res || typeof res !== 'object' || res.code !== 0) return
        const data = res.data && typeof res.data === 'object' ? res.data : {}
        const options = Object.keys(data)
          .map((key) => ({ value: String(key), text: String(data[key]) }))
          .sort((a, b) => Number(a.value) - Number(b.value))

        let certTypeValue = this.data.certTypeValue
        if (!certTypeValue && options.length) certTypeValue = options[0].value
        const certTypeIndex = Math.max(
          0,
          options.findIndex((it) => it.value === certTypeValue)
        )
        const certTypeText = options[certTypeIndex] ? options[certTypeIndex].text : ''

        this.setData({ certTypeOptions: options, certTypeValue, certTypeIndex, certTypeText })
      })
      .catch(() => {})
  },
  onPickCertType(e) {
    const index = Number(e && e.detail && e.detail.value) || 0
    const opt = this.data.certTypeOptions && this.data.certTypeOptions[index]
    if (!opt) return
    this.setData({ certTypeIndex: index, certTypeValue: opt.value, certTypeText: opt.text })
  },
  onInputFullname(e) {
    this.setData({ fullname: (e && e.detail && e.detail.value) || '' })
  },
  onInputCertId(e) {
    this.setData({ certId: (e && e.detail && e.detail.value) || '' })
  },
  onInputMobile(e) {
    this.setData({ mobile: (e && e.detail && e.detail.value) || '' })
  },
  onTapSave() {
    if (this.data.saving) return
    if (!this.ensureReady()) return

    const cert_type = String(this.data.certTypeValue || '').trim()
    const fullname = String(this.data.fullname || '').trim()
    const cert_id = String(this.data.certId || '').trim()
    const mobile = String(this.data.mobile || '').trim()
    const id = this.data.id ? String(this.data.id) : ''

    if (!cert_type) {
      ui.toast('请选择证件类型')
      return
    }
    if (!fullname) {
      ui.toast('请输入姓名')
      return
    }
    if (!cert_id) {
      ui.toast('请输入证件号')
      return
    }
    if (!mobile) {
      ui.toast('请输入手机号')
      return
    }
    if (!isPhone(mobile)) {
      ui.toast('手机号格式不正确')
      return
    }

    this.setData({ saving: true, error: null })

    userApi
      .postTourist({ fullname, mobile, cert_type, cert_id, id }, { showLoading: true })
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          return
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '保存失败')
          return
        }
        ui.toast(res.msg || '保存成功')
        setTimeout(() => wx.navigateBack(), 600)
      })
      .catch((err) => this.setData({ error: String((err && (err.errMsg || err.message)) || err) }))
      .finally(() => this.setData({ saving: false }))
  },
})

