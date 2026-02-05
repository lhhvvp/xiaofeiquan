Component({
  properties: {
    text: {
      type: String,
      value: '暂无数据',
    },
    subtext: {
      type: String,
      value: '',
    },
    image: {
      type: String,
      value: '',
    },
    actionText: {
      type: String,
      value: '重试',
    },
    showAction: {
      type: Boolean,
      value: false,
    },
  },
  methods: {
    onAction() {
      this.triggerEvent('action')
    },
  },
})

