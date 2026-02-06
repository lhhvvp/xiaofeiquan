function getEnvVersion() {
  try {
    const info = wx.getAccountInfoSync()
    return (info && info.miniProgram && info.miniProgram.envVersion) || 'develop'
  } catch (error) {
    return 'develop'
  }
}

const envVersion = getEnvVersion()

const configByEnvVersion = {
  develop: {
    baseUrl: '',
    monitorEventId: 'wxdata_perf_monitor',
    mock: false,
    mockPayment: false,
  },
  trial: {
    baseUrl: '',
    monitorEventId: 'wxdata_perf_monitor',
    mock: false,
    mockPayment: false,
  },
  release: {
    baseUrl: '',
    monitorEventId: 'wxdata_perf_monitor',
    mock: false,
    mockPayment: false,
  },
}

function loadLocalConfig(env) {
  try {
    // eslint-disable-next-line global-require
    const local = require('./local')
    if (!local || typeof local !== 'object') return {}
    if (local.develop || local.trial || local.release) return local[env] || {}
    return local
  } catch (error) {
    return {}
  }
}

const merged = {
  envVersion,
  ...configByEnvVersion[envVersion],
  ...loadLocalConfig(envVersion),
}

if (merged.mock && (!merged.baseUrl || !String(merged.baseUrl).trim())) {
  merged.baseUrl = 'mock'
}

module.exports = merged
