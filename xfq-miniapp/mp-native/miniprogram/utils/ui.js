function toast(title, { icon = 'none', duration = 2500, mask = false } = {}) {
  if (!title) return
  wx.showToast({ title, icon, duration, mask })
}

function showModal({
  title = '提示',
  content = '',
  showCancel = true,
  cancelText = '取消',
  confirmText = '确定',
} = {}) {
  return new Promise((resolve) => {
    wx.showModal({
      title,
      content,
      showCancel,
      cancelText,
      confirmText,
      success(res) {
        resolve(res)
      },
      fail() {
        resolve({ confirm: false, cancel: true })
      },
    })
  })
}

module.exports = {
  toast,
  showModal,
}

