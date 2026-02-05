Page({
  data: {
    showOrder: true,
  },
  onLoad(options) {
    const hideOrder = String(options && options.order) === '1'
    this.setData({ showOrder: !hideOrder })
  },
  onTapViewOrders() {
    wx.redirectTo({ url: '/subpackages/user/my_order?state=' })
  },
  onTapHome() {
    wx.switchTab({ url: '/pages/index/index' })
  },
})

