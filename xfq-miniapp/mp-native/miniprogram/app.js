const monitor = require('./services/monitor')
const config = require('./config')

App({
  globalData: {
    config,
  },
  onLaunch() {
    monitor.init()
  },
  onError(error) {
    monitor.captureError({ type: 'onError', error })
  },
  onUnhandledRejection(event) {
    monitor.captureError({ type: 'unhandledRejection', error: event && event.reason })
  },
})

