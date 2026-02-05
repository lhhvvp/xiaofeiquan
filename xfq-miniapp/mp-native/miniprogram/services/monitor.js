const config = require('../config')

function reportEvent(eventName, payload) {
  if (typeof wx.reportEvent !== 'function') return
  wx.reportEvent(eventName, payload)
}

function init() {
  // placeholder for future monitor initialization (SDK/trace config/etc)
}

function captureError({ type, error }) {
  try {
    // keep console output for dev
    // eslint-disable-next-line no-console
    console.error('[monitor]', type, error)
  } catch (e) {}

  reportEvent(config.monitorEventId, {
    wxdata_perf_monitor_id: type || 'unknown',
    wxdata_perf_monitor_level: 2,
    wxdata_perf_error_code: 1,
    wxdata_perf_error_msg: String(error || ''),
    wxdata_perf_cost_time: 0,
  })
}

function trackRequest({ monitorId, errorCode, errorMsg, costTime }) {
  reportEvent(config.monitorEventId, {
    wxdata_perf_monitor_id: monitorId || 'request',
    wxdata_perf_monitor_level: 1,
    wxdata_perf_error_code: Number(errorCode || 0),
    wxdata_perf_error_msg: String(errorMsg || ''),
    wxdata_perf_cost_time: Number(costTime || 0),
  })
}

module.exports = {
  init,
  reportEvent,
  captureError,
  trackRequest,
}

