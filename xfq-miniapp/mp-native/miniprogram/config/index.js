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
  },
  trial: {
    baseUrl: '',
    monitorEventId: 'wxdata_perf_monitor',
  },
  release: {
    baseUrl: '',
    monitorEventId: 'wxdata_perf_monitor',
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

module.exports = {
  envVersion,
  ...configByEnvVersion[envVersion],
  ...loadLocalConfig(envVersion),
}
