const config = require('../../config')
const auth = require('../../services/auth')
const ui = require('../../utils/ui')
const urlUtil = require('../../utils/url')
const locationService = require('../../services/location')
const couponApi = require('../../services/api/coupon')

function normalizeDetail(detail, baseUrl) {
  if (!detail || typeof detail !== 'object') return null
  const couponClass = detail.couponClass && typeof detail.couponClass === 'object' ? detail.couponClass : {}
  return {
    id: detail.id,
    cid: detail.cid,
    titleClass: couponClass.title || '',
    couponTitle: detail.coupon_title || '',
    couponPrice: detail.coupon_price || '',
    salePrice: detail.sale_price || 0,
    tips: detail.tips || '',
    tipsExtend: detail.tips_extend || '',
    remainCount: Number(detail.remain_count || 0),
    totalCount: Number(detail.total_count || 0),
    status: detail.status,
    remarkHtml: urlUtil.normalizeRichTextHtml(detail.remark, baseUrl),
    useType: detail.use_type,
    useTypeDescHtml: urlUtil.normalizeRichTextHtml(detail.use_type_desc, baseUrl),
  }
}

function calcProgressPercent(remainCount, totalCount) {
  const remain = Number(remainCount || 0)
  const total = Number(totalCount || 0)
  if (!Number.isFinite(remain) || !Number.isFinite(total) || total <= 0) return 0
  if (remain <= 0) return 100
  const ratio = remain / total
  const percent = (1 - ratio) * 100
  return Math.max(0, Math.min(100, Number(percent.toFixed(2))))
}

function resolveReceiveAction(detail, hasLogin) {
  if (!detail) {
    return { text: '立即领取', disabled: false }
  }
  if (detail.status === 2) {
    return { text: '已结束', disabled: true }
  }
  if (detail.status === 3) {
    return { text: '已领取', disabled: true }
  }
  if (Number(detail.remainCount || 0) <= 0) {
    return { text: '已抢完', disabled: true }
  }
  if (!hasLogin) {
    return { text: '登录后领取', disabled: false }
  }
  return { text: '立即领取', disabled: false }
}

Page({
  data: {
    baseUrl: config.baseUrl,
    hasBaseUrl: !!(config.baseUrl && String(config.baseUrl).trim()),
    hasLogin: false,

    id: '',
    latitude: 0,
    longitude: 0,
    detail: null,
    suitable: null,
    progressPercent: 0,

    receiving: false,
    receiveText: '立即领取',
    receiveDisabled: false,
    error: null,
  },
  onLoad(options) {
    const id = (options && (options.id || options.couponId)) || ''
    this.setData({ id: String(id) })

    this.coordPromise = locationService.getLocation().then((coord) => {
      this.setData({ latitude: coord.latitude, longitude: coord.longitude })
      return coord
    })

    if (this.data.hasBaseUrl) this.refresh()
  },
  onShow() {
    const user = auth.getUser()
    const hasLogin = !!(user && user.token && user.uid)
    this.setData({ hasLogin })
    this.updateReceiveAction(this.data.detail, hasLogin)
  },
  onPullDownRefresh() {
    if (!this.data.hasBaseUrl) {
      wx.stopPullDownRefresh()
      return
    }
    this.refresh({ showLoading: false }).finally(() => wx.stopPullDownRefresh())
  },
  refresh({ showLoading = true } = {}) {
    if (!this.data.id) {
      ui.toast('缺少优惠券 id')
      return Promise.resolve()
    }
    if (!this.data.hasBaseUrl) {
      return ui.showModal({
        title: '提示',
        content: 'baseUrl 未配置：请创建 `miniprogram/config/local.js` 并设置 { baseUrl }',
        showCancel: false,
      })
    }

    const userid = auth.getUid() || 0
    this.setData({ error: null })

    return Promise.resolve(this.coordPromise)
      .then(() => couponApi.getDetail({ couponId: this.data.id, userid }, { showLoading }))
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          return null
        }
        if (res.code !== 0) {
          ui.toast(res.msg || '请求失败')
          return null
        }
        const normalized = normalizeDetail(res.data, this.data.baseUrl)
        this.setData({
          detail: normalized,
          progressPercent: calcProgressPercent(normalized && normalized.remainCount, normalized && normalized.totalCount),
        })
        this.updateReceiveAction(normalized, this.data.hasLogin)
        if (normalized && normalized.couponTitle) {
          wx.setNavigationBarTitle({ title: normalized.couponTitle })
        }
        return normalized
      })
      .then((normalized) => {
        if (!normalized) return
        return couponApi
          .getApplicableMerchantsV2(
            { id: this.data.id, latitude: this.data.latitude, longitude: this.data.longitude },
            { showLoading: false }
          )
          .then((res) => {
            if (!res || typeof res !== 'object' || res.code !== 0) return
            const list = Array.isArray(res.data) ? res.data : []
            if (!list.length) {
              this.setData({ suitable: { nickname: '暂无商家', distanceText: '' } })
              return
            }
            const first = list[0]
            const distance = Number(first.distance)
            this.setData({
              suitable: {
                nickname: first.nickname || '',
                distanceText: Number.isFinite(distance) ? `${distance.toFixed(2)}km` : '',
              },
            })
          })
      })
      .catch((err) => {
        this.setData({ error: String((err && (err.errMsg || err.message)) || err) })
      })
  },
  onTapOpenApplicableMerchants() {
    if (!this.data.id) return
    wx.navigateTo({ url: `/subpackages/coupon/list?id=${encodeURIComponent(String(this.data.id))}` })
  },
  onTapReceive() {
    if (!this.data.detail) return

    if (!this.data.hasLogin) {
      ui
        .showModal({
          title: '提示',
          content: '领取需要先登录，是否现在去登录？',
          confirmText: '去登录',
          cancelText: '取消',
        })
        .then((res) => {
          if (res && res.confirm) {
            const redirect = `/subpackages/coupon/coupon?id=${this.data.id}`
            wx.navigateTo({ url: `/pages/user/login/login?redirect=${encodeURIComponent(redirect)}` })
          }
        })
      return
    }

    const detail = this.data.detail
    if (detail.status === 2) {
      ui.toast('已结束')
      return
    }
    if (detail.status === 3) {
      ui.toast('已领取')
      return
    }
    if (detail.remainCount <= 0) {
      ui.toast('已抢完')
      return
    }
    if (this.data.receiving) return

    this.setData({ receiving: true })

    Promise.resolve(this.coordPromise)
      .then(() =>
        couponApi.receive(
          {
            userid: auth.getUid() || 0,
            couponId: this.data.id,
            latitude: this.data.latitude,
            longitude: this.data.longitude,
          },
          { showLoading: true }
        )
      )
      .then((res) => {
        if (!res || typeof res !== 'object') {
          ui.toast('返回数据异常')
          return
        }
        if (res.code === 0) {
          ui.toast(res.msg || '领取成功')
          this.refresh({ showLoading: false })
          return
        }
        ui.showModal({ title: '提示', content: res.msg || '领取失败', showCancel: false })
      })
      .catch(() => {
        ui.toast('领取失败，请稍后重试')
      })
      .finally(() => this.setData({ receiving: false }))
  },
  updateReceiveAction(detail, hasLogin) {
    const next = resolveReceiveAction(detail, hasLogin)
    this.setData({
      receiveText: next.text,
      receiveDisabled: !!next.disabled,
    })
  },
})
