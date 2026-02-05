Page({
  onTapBack() {
    const pages = getCurrentPages()
    if (pages && pages.length > 1) {
      wx.navigateBack()
      return
    }
    wx.switchTab({ url: '/pages/index/index' })
  },
  onTapHome() {
    wx.switchTab({ url: '/pages/index/index' })
  },
})

