Component({
  properties: {
    nodes: {
      type: null,
      value: '',
    },
  },
  data: {
    safeNodes: '',
  },
  observers: {
    nodes(value) {
      if (Array.isArray(value)) {
        this.setData({ safeNodes: value })
        return
      }
      if (value && typeof value === 'object') {
        this.setData({ safeNodes: value })
        return
      }
      this.setData({ safeNodes: typeof value === 'string' ? value : '' })
    },
  },
})
