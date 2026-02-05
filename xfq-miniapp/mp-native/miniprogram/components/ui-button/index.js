Component({
  options: {
    addGlobalClass: true,
  },
  properties: {
    text: {
      type: String,
      value: '',
    },
    type: {
      type: String,
      value: 'default',
    },
    size: {
      type: String,
      value: 'default',
    },
    plain: {
      type: Boolean,
      value: false,
    },
    disabled: {
      type: Boolean,
      value: false,
    },
    loading: {
      type: Boolean,
      value: false,
    },
    block: {
      type: Boolean,
      value: false,
    },
    openType: {
      type: String,
      value: '',
    },
    formType: {
      type: String,
      value: '',
    },
    className: {
      type: String,
      value: '',
    },
  },
  methods: {
    onTap(e) {
      this.triggerEvent('tap', e && e.detail ? e.detail : {})
    },
  },
})

