Component({
  properties: {
    status: {
      type: String,
      value: '',
    },
    loadingText: {
      type: String,
      value: '加载中...',
    },
    noMoreText: {
      type: String,
      value: '没有更多了',
    },
    errorText: {
      type: String,
      value: '加载失败，点击重试',
    },
  },
  methods: {
    onTap() {
      if (this.data.status === 'error') this.triggerEvent('retry')
    },
  },
})

