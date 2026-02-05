Component({
  properties: {
    message: {
      type: String,
      value: '加载失败',
    },
    retryText: {
      type: String,
      value: '重试',
    },
    showRetry: {
      type: Boolean,
      value: true,
    },
  },
  methods: {
    onRetry() {
      this.triggerEvent('retry')
    },
  },
})

