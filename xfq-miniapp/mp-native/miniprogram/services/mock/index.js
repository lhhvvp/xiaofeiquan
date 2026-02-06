const auth = require('../auth')

function sleep(ms) {
  const n = Number(ms)
  if (!Number.isFinite(n) || n <= 0) return Promise.resolve()
  return new Promise((resolve) => setTimeout(resolve, n))
}

function ok(data, msg = 'ok') {
  return { code: 0, msg, data }
}

function fail(code, msg) {
  return { code, msg: msg || 'mock error', data: null }
}

function createPayParams() {
  const timeStamp = String(Math.floor(Date.now() / 1000))
  return {
    timeStamp,
    nonceStr: 'mock-nonce',
    package: 'prepay_id=mock_prepay',
    signType: 'MD5',
    paySign: 'mock-sign',
  }
}

function createInitialState() {
  const now = Date.now()
  const nowSec = Math.floor(now / 1000)

  const merchants = [
    {
      id: 101,
      image: 'https://oss.wlxfq.dianfengcms.com/admins/d2d20480c4d629fc0336b4b639498830.png',
      nickname: '示例商户 A',
      mobile: '13800138000',
      do_business_time: '09:00-18:00',
      address: '示例路 1 号',
      distance: 0.8,
      latitude: 38.285,
      longitude: 109.734,
      comment_rate: 4.7,
      comment_num: 128,
      appt_open: 0,
    },
    {
      id: 102,
      image: 'https://oss.wlxfq.dianfengcms.com/admins/d2d20480c4d629fc0336b4b639498830.png',
      nickname: '示例商户 B',
      mobile: '13800138001',
      do_business_time: '10:00-20:00',
      address: '示例路 2 号',
      distance: 2.3,
      latitude: 38.289,
      longitude: 109.739,
      comment_rate: 4.3,
      comment_num: 52,
      appt_open: 1,
    },
  ]

  const couponSections = [
    {
      id: 1,
      title: '热门消费券',
      class_icon: '',
      list: [
        { id: 201, cid: 201, coupon_title: '满 100 减 20', coupon_price: 20, is_use: false },
        { id: 202, cid: 202, coupon_title: '满 50 减 10', coupon_price: 10, is_use: false },
      ],
    },
    {
      id: 2,
      title: '新人专区',
      class_icon: '',
      list: [{ id: 203, cid: 203, coupon_title: '新人立减 5 元', coupon_price: 5, is_use: false }],
    },
  ]

  const couponDetailById = {
    201: {
      id: 201,
      cid: 201,
      coupon_title: '满 100 减 20',
      coupon_price: 20,
      sale_price: 0,
      tips: '示例说明：仅用于 mock 自测',
      tips_extend: '',
      remain_count: 88,
      total_count: 100,
      status: 1,
      remark: '<p>mock 备注：这里是图文详情</p>',
      use_type: 2,
      use_type_desc: '<p>线下核销</p>',
    },
    202: {
      id: 202,
      cid: 202,
      coupon_title: '满 50 减 10',
      coupon_price: 10,
      sale_price: 0,
      tips: '示例说明：仅用于 mock 自测',
      tips_extend: '',
      remain_count: 120,
      total_count: 200,
      status: 1,
      remark: '<p>mock 备注：这里是图文详情</p>',
      use_type: 2,
      use_type_desc: '<p>线下核销</p>',
    },
    203: {
      id: 203,
      cid: 203,
      coupon_title: '新人立减 5 元',
      coupon_price: 5,
      sale_price: 0,
      tips: '示例说明：仅用于 mock 自测',
      tips_extend: '',
      remain_count: 999,
      total_count: 999,
      status: 1,
      remark: '<p>mock 备注：这里是图文详情</p>',
      use_type: 2,
      use_type_desc: '<p>线下核销</p>',
    },
  }

  const userCouponIssueList = [
    {
      id: 90001,
      coupon_title: '满 100 减 20',
      coupon_price: 20,
      create_time: nowSec - 3600,
      status: 0,
      use_type: 2,
      enstr_salt: 'mock-salt-1',
      remark: '<p>mock：我的券详情</p>',
      use_type_desc: '<p>线下核销</p>',
      couponIssue: { cid: 201 },
    },
  ]

  const couponClockRecordsByUid = {
    1: {
      201: [],
    },
  }

  let couponClockNextId = 60001

  const tourCouponGroupById = {
    91001: {
      id: 91001,
      status: 0,
      enstr_salt: 'mock-group-salt-1',
      create_time: '2026-02-06 10:00:00',
      qrcode_url: 'MOCK-QR-GROUP-91001',
      couponIssue: {
        coupon_title: '团购旅行团大礼包',
        coupon_price: 0,
        is_permanent: 2,
        coupon_time_start: nowSec - 86400,
        coupon_time_end: nowSec + 86400 * 7,
        day: 7,
        remark: '<p>mock：团购券使用说明</p>',
      },
      tourist: [
        { id: 1, name: '游客 A' },
        { id: 2, name: '游客 B' },
        { id: 3, name: '游客 C' },
      ],
      tour: { name: '示例旅行团 A', term: '2026-02-06' },
      tour_write_off: [],
      seller: { nickname: '示例旅行社 A' },
    },
  }

  const couponOrders = [
    {
      order_no: 'MOCK-PAY-0001',
      origin_price: 20,
      payment_status: 0,
      is_refund: 0,
      openid: 'mock-openid',
      issue_coupon_user_id: 90001,
      create_time: nowSec - 7200,
      update_time: nowSec - 7200,
      detail: {
        coupon_title: '满 100 减 20',
        coupon_uuno: 'UUNO-0001',
        coupon_icon: '',
      },
    },
    {
      order_no: 'MOCK-PAY-0002',
      origin_price: 10,
      payment_status: 1,
      is_refund: 0,
      openid: 'mock-openid',
      issue_coupon_user_id: 90002,
      create_time: nowSec - 86400,
      update_time: nowSec - 86300,
      detail: {
        coupon_title: '满 50 减 10',
        coupon_uuno: 'UUNO-0002',
        coupon_icon: '',
      },
    },
  ]

  const writeoffLogs = [
    { id: 70001, coupon_title: '满 100 减 20', coupon_price: 20, create_time: nowSec - 1800, coupon_issue_id: 201 },
    { id: 70002, coupon_title: '满 50 减 10', coupon_price: 10, create_time: nowSec - 5400, coupon_issue_id: 202 },
  ]

  const writeoffDetailById = {
    70001: {
      id: 70001,
      coupon_title: '满 100 减 20',
      coupon_price: 20,
      create_time: nowSec - 1800,
      coupon_issue_id: 201,
      remark: '<p>mock：核销明细</p>',
    },
    70002: {
      id: 70002,
      coupon_title: '满 50 减 10',
      coupon_price: 10,
      create_time: nowSec - 5400,
      coupon_issue_id: 202,
      remark: '<p>mock：核销明细</p>',
    },
  }

  const scenicList = [
    {
      id: 301,
      image: 'https://oss.wlxfq.dianfengcms.com/admins/d2d20480c4d629fc0336b4b639498830.png',
      nickname: '示例景区 A',
      area_text: '榆阳区',
      distance: 1.2,
      comment_rate: 4.6,
      comment_num: 66,
      min_price: 99,
    },
    {
      id: 302,
      image: 'https://oss.wlxfq.dianfengcms.com/admins/d2d20480c4d629fc0336b4b639498830.png',
      nickname: '示例景区 B',
      area_text: '横山区',
      distance: 5.4,
      comment_rate: 4.2,
      comment_num: 31,
      min_price: 59,
    },
  ]

  const ticketGroupsBySellerId = {
    301: [
      {
        group_title: '门票',
        ticket_list: [
          {
            id: 501,
            title: '成人票',
            min_price: 99,
            explain_buy: '<p>mock：购票说明</p>',
            explain_use: '<p>mock：使用说明</p>',
            rights_list: [{ rights_id: 1, rights_title: '入园' }],
          },
        ],
      },
    ],
    302: [
      {
        group_title: '门票',
        ticket_list: [
          {
            id: 502,
            title: '亲子票',
            min_price: 59,
            explain_buy: '<p>mock：购票说明</p>',
            explain_use: '<p>mock：使用说明</p>',
            rights_list: [{ rights_id: 1, rights_title: '入园' }],
          },
        ],
      },
    ],
  }

  const ticketPriceByTicketId = {
    501: [
      { date: '2026-02-05', price: 99, stock: 20 },
      { date: '2026-02-06', price: 99, stock: 12 },
    ],
    502: [
      { date: '2026-02-05', price: 59, stock: 50 },
      { date: '2026-02-06', price: 59, stock: 26 },
    ],
  }

  const commentBySellerId = {
    301: [
      { users: { nickname: '用户甲', headimgurl: '' }, rate: 5, content: '不错', create_time: '2026-02-01' },
      { users: { nickname: '用户乙', headimgurl: '' }, rate: 4, content: '还行', create_time: '2026-02-02' },
    ],
    302: [{ users: { nickname: '用户丙', headimgurl: '' }, rate: 5, content: '推荐', create_time: '2026-02-03' }],
  }

  const commentByUserId = {
    1: [{ users: { nickname: 'Mock User', headimgurl: '' }, rate: 5, content: 'mock：我的评价', create_time: '2026-02-06' }],
  }

  const ticketOrders = [
    {
      id: 80001,
      seller_id: 301,
      order_status: 'created',
      order_status_text: '待支付',
      iscomment: false,
      amount_price: 99,
      trade_no: 'MOCK-TRADE-80001',
      out_trade_no: 'MOCK-OUT-80001',
      qrcode_str: 'MOCK-QR-ORDER-80001',
      seller: { nickname: '示例景区 A', image: scenicList[0].image },
      ticket_info: {
        title: '成人票',
        date: '2026-02-06',
        explain_buy: '<p>mock：购票说明</p>',
        explain_use: '<p>mock：使用说明</p>',
      },
      rights_qrcode_list: [{ id: 91001, rights_id: 1, rights_title: '入园', qrcode_str: 'MOCK-QR-R-91001', rights_num: 1, writeoff_num: 0 }],
      detail_list: [
        {
          id: 81001,
          tourist_fullname: '张三',
          tourist_mobile: '13800138000',
          tourist_cert_id: '610000199001010000',
          qrcode_str: 'MOCK-QR-DETAIL-81001',
          rights_list: [{ id: 91001, rights_id: 1, rights_title: '入园', status: 0, qrcode_str: 'MOCK-QR-R-91001', detail_id: 81001 }],
          refund_progress: 'init',
          out_trade_no: 'MOCK-OUT-80001',
          enter_time: 0,
        },
      ],
    },
    {
      id: 80002,
      seller_id: 302,
      order_status: 'paid',
      order_status_text: '已支付',
      iscomment: false,
      amount_price: 59,
      trade_no: 'MOCK-TRADE-80002',
      out_trade_no: 'MOCK-OUT-80002',
      qrcode_str: 'MOCK-QR-ORDER-80002',
      seller: { nickname: '示例景区 B', image: scenicList[1].image },
      ticket_info: {
        title: '亲子票',
        date: '2026-02-05',
        explain_buy: '<p>mock：购票说明</p>',
        explain_use: '<p>mock：使用说明</p>',
      },
      rights_qrcode_list: [{ id: 92001, rights_id: 1, rights_title: '入园', qrcode_str: 'MOCK-QR-R-92001', rights_num: 1, writeoff_num: 0 }],
      detail_list: [
        {
          id: 82001,
          tourist_fullname: '李四',
          tourist_mobile: '13800138001',
          tourist_cert_id: '610000199101010000',
          qrcode_str: 'MOCK-QR-DETAIL-82001',
          rights_list: [{ id: 92001, rights_id: 1, rights_title: '入园', status: 0, qrcode_str: 'MOCK-QR-R-92001', detail_id: 82001 }],
          refund_progress: 'init',
          out_trade_no: 'MOCK-OUT-80002',
          enter_time: 0,
        },
      ],
    },
    {
      id: 80003,
      seller_id: 301,
      order_status: 'used',
      order_status_text: '已使用',
      iscomment: false,
      amount_price: 99,
      trade_no: 'MOCK-TRADE-80003',
      out_trade_no: 'MOCK-OUT-80003',
      qrcode_str: 'MOCK-QR-ORDER-80003',
      seller: { nickname: '示例景区 A', image: scenicList[0].image },
      ticket_info: {
        title: '成人票',
        date: '2026-02-06',
        explain_buy: '<p>mock：购票说明</p>',
        explain_use: '<p>mock：使用说明</p>',
      },
      rights_qrcode_list: [{ id: 93001, rights_id: 1, rights_title: '入园', qrcode_str: 'MOCK-QR-R-93001', rights_num: 1, writeoff_num: 1 }],
      detail_list: [
        {
          id: 83001,
          tourist_fullname: '王五',
          tourist_mobile: '13800138002',
          tourist_cert_id: '610000199201010000',
          qrcode_str: 'MOCK-QR-DETAIL-83001',
          rights_list: [{ id: 93001, rights_id: 1, rights_title: '入园', status: 1, qrcode_str: 'MOCK-QR-R-93001', detail_id: 83001 }],
          refund_progress: 'init',
          out_trade_no: 'MOCK-OUT-80003',
          enter_time: nowSec,
        },
      ],
    },
  ]

  const apptScheduleBySellerId = {
    301: {
      number: 5,
      list: {
        '2026-02-06': [
          { id: 70001, time_start_text: '09:00', time_end_text: '10:00', stock: 20 },
          { id: 70002, time_start_text: '10:00', time_end_text: '11:00', stock: 10 },
          { id: 70003, time_start_text: '11:00', time_end_text: '12:00', stock: 6 },
        ],
        '2026-02-07': [
          { id: 70101, time_start_text: '09:00', time_end_text: '10:00', stock: 15 },
          { id: 70102, time_start_text: '10:00', time_end_text: '11:00', stock: 8 },
        ],
      },
    },
    302: {
      number: 5,
      list: {
        '2026-02-06': [
          { id: 71001, time_start_text: '14:00', time_end_text: '15:00', stock: 12 },
          { id: 71002, time_start_text: '15:00', time_end_text: '16:00', stock: 12 },
        ],
        '2026-02-07': [{ id: 71101, time_start_text: '14:00', time_end_text: '15:00', stock: 12 }],
      },
    },
  }

  const apptLogs = [
    {
      id: 90001,
      uid: 1,
      seller_id: 301,
      status: 0,
      status_text: '待核销',
      seller: { nickname: scenicList[0].nickname, image: scenicList[0].image },
      fullname: 'Mock User',
      phone: '13800138000',
      idcard: '610000199001010000',
      number: 1,
      start: '2026-02-06 09:00',
      time_end_text: '10:00',
      qrcode_str: 'MOCK-QR-APPT-90001',
      code: 'APPT-90001',
      tourist_list: [
        {
          tourist_fullname: 'Mock User',
          tourist_mobile: '13800138000',
          tourist_cert_id: '610000199001010000',
          tourist_cert_type: 1,
          refund_progress: 'init',
          out_trade_no: 'MOCK-OUT-APPT-90001',
        },
      ],
    },
  ]

  const apptNextId = 90002

  const ticketOrderDetailDetailById = {
    81001: {
      ticket_title: '成人票',
      tourist_fullname: '张三',
      tourist_mobile: '13800138000',
      explain_buy: '<p>mock：购票说明</p>',
      explain_use: '<p>mock：使用说明</p>',
    },
    82001: {
      ticket_title: '亲子票',
      tourist_fullname: '李四',
      tourist_mobile: '13800138001',
      explain_buy: '<p>mock：购票说明</p>',
      explain_use: '<p>mock：使用说明</p>',
    },
    83001: {
      ticket_title: '成人票',
      tourist_fullname: '王五',
      tourist_mobile: '13800138002',
      explain_buy: '<p>mock：购票说明</p>',
      explain_use: '<p>mock：使用说明</p>',
    },
  }

  const refundLogs = [
    {
      id: 60001,
      trade_no: 'MOCK-TRADE-80002',
      status_text: '审核中',
      refund_fee: 59,
      create_time: '2026-02-05 10:00',
      info_seller: { nickname: '示例景区 B', image: scenicList[1].image },
      info_order: { amount_price: 59 },
    },
  ]

  const refundDetailById = {
    60001: {
      id: 60001,
      trade_no: 'MOCK-TRADE-80002',
      status_text: '审核中',
      refuse_desc: '',
      refund_fee: 59,
      create_time: '2026-02-05 10:00',
      info_seller: { nickname: '示例景区 B', image: scenicList[1].image },
      info_order: { amount_price: 59 },
      info_order_detail: [
        { tourist_fullname: '李四', tourist_mobile: '13800138001', tourist_cert_id: '610000199101010000' },
      ],
    },
  }

  const touristList = [
    { id: 40001, fullname: '张三', mobile: '13800138000', cert_id: '610000199001010000', cert_type: 1 },
    { id: 40002, fullname: '李四', mobile: '13800138001', cert_id: '610000199101010000', cert_type: 1 },
  ]

  const userProfile = {
    nickname: 'Mock User',
    headimgurl: 'https://oss.wlxfq.dianfengcms.com/admins/d2d20480c4d629fc0336b4b639498830.png',
    mobile: '13800138000',
    name: 'Mock User',
    idcard: '610000199001010000',
    auth_status: 0,
  }

  const clockTasksByUserId = {
    1: [
      {
        id: 50001,
        tags: 1,
        coupon_title: '示例打卡券（景区）',
        create_time: nowSec - 86400,
        is_clock: 0,
        tour_name: '示例旅行团 A',
        address: 0,
        clock_time: 0,
        images: '',
      },
      {
        id: 50002,
        tags: 2,
        coupon_title: '示例打卡券（酒店）',
        create_time: nowSec - 172800,
        is_clock: 1,
        tour_name: '示例旅行团 B',
        address: '示例路 1 号',
        clock_time: nowSec - 3600,
        images: userProfile.headimgurl,
      },
    ],
  }

  const collectionMerchantIds = [101]

  const noteList = [
    {
      id: 10001,
      title: '系统公告：mock 自测环境已启用',
      create_time: '2026-02-06',
      hits: 1,
    },
    {
      id: 10002,
      title: '温馨提示：真实 API 可用后请关闭 mock 并回归 M3',
      create_time: '2026-02-06',
      hits: 1,
    },
  ]

  const noteDetailById = {
    10001: {
      id: 10001,
      title: '系统公告：mock 自测环境已启用',
      create_time: '2026-02-06',
      hits: 1,
      content: '<p>当前为 mock 自测环境，用于继续推进原生重构与冒烟验证。</p>',
    },
    10002: {
      id: 10002,
      title: '温馨提示：真实 API 可用后请关闭 mock 并回归 M3',
      create_time: '2026-02-06',
      hits: 1,
      content: '<p>真实后端 API 可用后，请关闭 mock/mockPayment，并按 M3 冒烟清单完整回归。</p>',
    },
  }

  return {
    merchants,
    couponSections,
    couponDetailById,
    userCouponIssueList,
    couponClockRecordsByUid,
    couponClockNextId,
    tourCouponGroupById,
    couponOrders,
    writeoffLogs,
    writeoffDetailById,
    scenicList,
    ticketGroupsBySellerId,
    ticketPriceByTicketId,
    commentBySellerId,
    commentByUserId,
    ticketOrders,
    apptScheduleBySellerId,
    apptLogs,
    apptNextId,
    ticketOrderDetailDetailById,
    refundLogs,
    refundDetailById,
    touristList,
    userProfile,
    clockTasksByUserId,
    collectionMerchantIds,
    noteList,
    noteDetailById,
  }
}

let state = null

function getState() {
  if (!state) state = createInitialState()
  return state
}

function pickPage(items, { offset = 0, limit = 10 } = {}) {
  const list = Array.isArray(items) ? items : []
  const start = Math.max(0, Number(offset) || 0)
  const size = Math.max(0, Number(limit) || 0)
  return list.slice(start, start + size)
}

function getId(input) {
  if (input === null || typeof input === 'undefined') return ''
  return String(input).trim()
}

function asNumber(input, fallback = 0) {
  const n = Number(input)
  return Number.isFinite(n) ? n : fallback
}

function findTicketOrder(orderId) {
  const s = getState()
  const id = String(orderId)
  return (s.ticketOrders || []).find((o) => String(o && o.id) === id) || null
}

function buildTicketOrderList({ status = '' } = {}) {
  const s = getState()
  const list = Array.isArray(s.ticketOrders) ? s.ticketOrders : []
  if (!status) return list
  return list.filter((o) => String(o && o.order_status) === String(status))
}

function buildMerchantDetail(sellerId) {
  const s = getState()
  const id = String(sellerId)
  const found = (s.merchants || []).find((m) => String(m && m.id) === id) || null
  const fallback = s.merchants && s.merchants[0]
  const scenic = (s.scenicList || []).find((it) => String(it && it.id) === id) || null

  const apptOpen =
    s.apptScheduleBySellerId && typeof s.apptScheduleBySellerId === 'object' && s.apptScheduleBySellerId[id] ? 1 : 0

  if (!found && scenic) {
    const coordByScenic = {
      301: { latitude: 38.285, longitude: 109.734 },
      302: { latitude: 38.289, longitude: 109.739 },
    }
    const coord = coordByScenic[id] || {}
    return {
      id: scenic.id || sellerId,
      nickname: scenic.nickname || '',
      image: scenic.image || '',
      do_business_time: '09:00-18:00',
      address: scenic.area_text ? `${scenic.area_text} 示例地址` : '示例地址',
      mobile: '13800138000',
      comment_rate: scenic.comment_rate || 0,
      comment_num: scenic.comment_num || 0,
      appt_open: apptOpen,
      latitude: coord.latitude,
      longitude: coord.longitude,
    }
  }

  const raw = found || fallback || {}
  return {
    id: raw.id || sellerId,
    nickname: raw.nickname || '',
    image: raw.image || '',
    do_business_time: raw.do_business_time || '',
    address: raw.address || '',
    mobile: raw.mobile || '',
    comment_rate: raw.comment_rate || 0,
    comment_num: raw.comment_num || 0,
    appt_open: found ? raw.appt_open || 0 : apptOpen,
    latitude: raw.latitude,
    longitude: raw.longitude,
  }
}

function handleRequest({ path, method, data, header } = {}) {
  const s = getState()
  const p = String(path || '')
  const m = String(method || 'POST').toUpperCase()
  const payload = data && typeof data === 'object' ? data : {}

  const uid = auth.getUid && auth.getUid()
  const user = auth.getUser && auth.getUser()

  // minimal async delay so loading UI is observable
  const delay = 60

  return sleep(delay).then(() => {
    switch (p) {
      case '/index/system':
        return ok({
          slide: { image: '' },
          act_rule: '<p>mock：活动规则</p>',
          is_qrcode_number: 30,
          service: '<p>mock：服务协议</p>',
          policy: '<p>mock：隐私政策</p>',
        })

      case '/index/transform':
        return ok({
          result: {
            address: 'mock 地址',
            formatted_addresses: { rough: 'mock 景点' },
          },
        })

      case '/index/getuserphonenumber':
        return ok({
          phone_info: {
            phoneNumber: (s.userProfile && s.userProfile.mobile) || '13800138000',
          },
        })

      case '/index/miniwxlogin':
        return ok({
          token: 'mock-token',
          userinfo: {
            id: 1,
            name: 'Mock User',
            idcard: '',
            mobile: '',
            openid: 'mock-openid',
            uuid: 'mock-uuid',
            no: 'MOCK-NO',
          },
        })

      case '/index/note_list':
        return ok(s.noteList || [])

      case '/index/note_detail': {
        const id = getId(payload.id)
        const found = (s.noteDetailById && s.noteDetailById[id]) || null
        const fallback = s.noteDetailById && s.noteDetailById[10001]
        return ok(found || fallback || {})
      }

      case '/user/index':
        if (!uid) return fail(110, 'AUTH_REQUIRED')
        return ok({
          ...(s.userProfile || {}),
          ismv: { mid: 10001 },
        })

      case '/user/tour_coupon_group': {
        if (!uid) return fail(110, 'AUTH_REQUIRED')
        const id = getId(payload.id)
        if (!id) return fail(400, 'ID_REQUIRED')
        const table = s.tourCouponGroupById || {}
        const found = table[id] || table[91001] || null
        if (!found) return fail(404, 'NOT_FOUND')
        return ok(found)
      }

      case '/user/writeoff_tour': {
        if (!uid) return fail(110, 'AUTH_REQUIRED')
        const mid = getId(payload.mid)
        const id = getId(payload.coupon_issue_user_id || payload.id)
        const qrcodeUrl = String(payload.qrcode_url || '').trim()
        if (!mid) return fail(400, 'MID_REQUIRED')
        if (!id) return fail(400, 'ID_REQUIRED')
        if (!qrcodeUrl) return fail(400, 'QRCODE_REQUIRED')

        const table = s.tourCouponGroupById || {}
        const found = table[id] || null
        if (!found) return fail(404, 'NOT_FOUND')
        if (String(found.status) !== '0') return fail(400, 'ALREADY_WRITEOFF')
        if (found.qrcode_url && String(found.qrcode_url) !== qrcodeUrl) return fail(400, 'QRCODE_MISMATCH')

        found.status = 1
        if (!Array.isArray(found.tour_write_off)) found.tour_write_off = []
        found.tour_write_off.unshift({
          id: Math.floor(Math.random() * 100000) + 80000,
          uid,
          is_clock: 1,
          user: {
            name: (s.userProfile && s.userProfile.name) || (user && user.name) || 'Mock User',
            mobile: (s.userProfile && s.userProfile.mobile) || (user && user.mobile) || '13800138000',
          },
          create_time: new Date().toISOString().slice(0, 19).replace('T', ' '),
        })

        return ok({ success: true }, '核销成功')
      }

      case '/user/clock_list':
        if (!uid) return fail(110, 'AUTH_REQUIRED')
        return ok((s.clockTasksByUserId && s.clockTasksByUserId[uid]) || [])

      case '/user/clock': {
        if (!uid) return fail(110, 'AUTH_REQUIRED')
        const id = getId(payload.tour_issue_user_id || payload.id)
        const list = (s.clockTasksByUserId && s.clockTasksByUserId[uid]) || []
        const found = list.find((it) => String(it && it.id) === id) || null
        if (!found) return fail(404, 'NOT_FOUND')
        found.is_clock = 1
        found.clock_time = Math.floor(Date.now() / 1000)
        if (payload.address) found.address = payload.address
        if (payload.images) found.images = payload.images
        return ok({ success: true }, '打卡成功')
      }

      case '/user/hotel_clock': {
        if (!uid) return fail(110, 'AUTH_REQUIRED')
        const id = getId(payload.id)
        const list = (s.clockTasksByUserId && s.clockTasksByUserId[uid]) || []
        const found = list.find((it) => String(it && it.id) === id) || null
        if (!found) return fail(404, 'NOT_FOUND')
        found.is_clock = 1
        found.clock_time = Math.floor(Date.now() / 1000)
        if (payload.address) found.address = payload.address
        if (payload.images) found.images = payload.images
        return ok({ success: true }, '打卡成功')
      }

      case '/user/userClock': {
        if (!uid) return fail(110, 'AUTH_REQUIRED')
        const couponId = getId(payload.couponId || payload.coupon_id)
        if (!couponId) return fail(400, 'COUPON_ID_REQUIRED')
        const qrcodeUrl = String(payload.qrcode_url || '').trim()
        if (!qrcodeUrl) return fail(400, 'QRCODE_REQUIRED')

        let sellerId = ''
        try {
          const parsed = JSON.parse(qrcodeUrl)
          if (parsed && typeof parsed === 'object') {
            sellerId = getId(parsed.seller_id || parsed.mid || parsed.id)
          }
        } catch (e) {}
        if (!sellerId) {
          const match = qrcodeUrl.match(/(\d+)/g)
          if (match && match.length) sellerId = String(match[match.length - 1])
        }
        if (!sellerId) sellerId = '101'

        const seller =
          (s.merchants || []).find((m) => String(m && m.id) === String(sellerId)) ||
          (s.scenicList || []).find((it) => String(it && it.id) === String(sellerId)) ||
          (s.merchants && s.merchants[0]) ||
          {}

        if (!s.couponClockRecordsByUid || typeof s.couponClockRecordsByUid !== 'object') s.couponClockRecordsByUid = {}
        if (!s.couponClockRecordsByUid[uid] || typeof s.couponClockRecordsByUid[uid] !== 'object') s.couponClockRecordsByUid[uid] = {}
        if (!Array.isArray(s.couponClockRecordsByUid[uid][couponId])) s.couponClockRecordsByUid[uid][couponId] = []

        const list = s.couponClockRecordsByUid[uid][couponId]
        const has = list.some((it) => String(it && it.Seller && it.Seller.id) === String(seller.id || sellerId))
        if (has) return fail(400, '已打卡')

        const nextId = asNumber(s.couponClockNextId, 60001)
        s.couponClockNextId = nextId + 1

        const now = new Date().toISOString().slice(0, 19).replace('T', ' ')
        list.unshift({
          id: nextId,
          Seller: { id: seller.id || sellerId, nickname: seller.nickname || '', image: seller.image || '' },
          create_time: now,
        })

        return ok({ success: true }, '打卡成功')
      }

      case '/user/edit':
        if (!uid) return fail(110, 'AUTH_REQUIRED')
        if (s.userProfile && typeof s.userProfile === 'object') {
          if (typeof payload.nickname === 'string') s.userProfile.nickname = payload.nickname
          if (typeof payload.headimgurl === 'string') s.userProfile.headimgurl = payload.headimgurl
        }
        return ok({ success: true }, '修改成功')

      case '/user/collection': {
        if (!uid) return fail(110, 'AUTH_REQUIRED')
        const page = asNumber(payload.page, 0)
        const limit = asNumber(payload.limit, 8)
        const offset = page * limit
        const ids = Array.isArray(s.collectionMerchantIds) ? s.collectionMerchantIds : []
        const merchants = ids
          .map((id) => (s.merchants || []).find((m) => String(m && m.id) === String(id)))
          .filter(Boolean)
        return ok(pickPage(merchants, { offset, limit }))
      }

      case '/user/collection_action': {
        if (!uid) return fail(110, 'AUTH_REQUIRED')
        const action = String(payload.action || '').toLowerCase()
        const mid = getId(payload.mid)
        if (!mid) return fail(400, 'MID_REQUIRED')
        if (!Array.isArray(s.collectionMerchantIds)) s.collectionMerchantIds = []
        const has = s.collectionMerchantIds.some((id) => String(id) === mid)
        if (action === 'add' && !has) s.collectionMerchantIds.push(mid)
        if (action === 'del' && has) s.collectionMerchantIds = s.collectionMerchantIds.filter((id) => String(id) !== mid)
        return ok({ success: true }, action === 'del' ? '取消收藏成功' : '收藏成功')
      }

      case '/user/feed_back':
        if (!uid) return fail(110, 'AUTH_REQUIRED')
        return ok({ success: true }, '提交成功')

      case '/user/auth_info':
        if (!uid) return fail(110, 'AUTH_REQUIRED')
        return ok({
          name: (user && user.name) || 'Mock User',
          mobile: (user && user.mobile) || '13800138000',
          idcard: (user && user.idcard) || '610000199001010000',
          auth_status: asNumber(s.userProfile && s.userProfile.auth_status, 0),
          ismv: { mid: 10001 },
        })

      case '/user/smsVerification':
        if (!uid) return fail(110, 'AUTH_REQUIRED')
        return ok({ success: true }, '验证码已发送')

      case '/user/auth_identity': {
        if (!uid) return fail(110, 'AUTH_REQUIRED')
        const name = String(payload.name || '').trim()
        const mobile = String(payload.mobile || '').trim()
        const idcard = String(payload.idcard || '').trim()
        if (!name) return fail(400, 'NAME_REQUIRED')
        if (!mobile) return fail(400, 'MOBILE_REQUIRED')
        if (!idcard) return fail(400, 'IDCARD_REQUIRED')

        if (s.userProfile && typeof s.userProfile === 'object') {
          s.userProfile.name = name
          s.userProfile.mobile = mobile
          s.userProfile.idcard = idcard
          s.userProfile.auth_status = 1
        }

        return ok({ success: true }, '实名验证成功')
      }

      case '/seller/bindCheckOpenid':
        return ok({ success: true }, '绑定成功')

      case '/seller/cate':
        return ok({
          cate: [
            { id: 1, class_name: '餐饮' },
            { id: 2, class_name: '景区' },
          ],
        })

      case '/seller/list': {
        const offset = asNumber(payload.page, 0)
        const limit = asNumber(payload.limit, 8)
        return ok(pickPage(s.merchants, { offset, limit }))
      }

      case '/seller/search': {
        const keyword = String(payload.nickname || '').trim()
        const list = keyword
          ? (s.merchants || []).filter((it) => String(it.nickname || '').indexOf(keyword) >= 0)
          : s.merchants || []
        return ok(list)
      }

      case '/seller/detail': {
        const sellerId = getId(payload.seller_id)
        return ok({ detail: buildMerchantDetail(sellerId) })
      }

      case '/coupon/index':
      case '/coupon/tempApi':
        return ok(s.couponSections)

      case '/coupon/detail': {
        const id = getId(payload.couponId)
        return ok(s.couponDetailById[id] || s.couponDetailById[201] || {})
      }

      case '/coupon/getUserCouponRecordList': {
        if (!uid) return fail(110, 'AUTH_REQUIRED')
        const couponId = getId(payload.couponId || payload.coupon_id)
        if (!couponId) return fail(400, 'COUPON_ID_REQUIRED')
        const table = (s.couponClockRecordsByUid && s.couponClockRecordsByUid[uid]) || {}
        return ok(Array.isArray(table[couponId]) ? table[couponId] : [])
      }

      case '/coupon/receive':
        return ok({ success: true }, '领取成功')

      case '/coupon/applicabletoV2':
      case '/coupon/applicableto': {
        const limit = p === '/coupon/applicabletoV2' ? 5 : asNumber(payload.limit, 15)
        const offset = p === '/coupon/applicabletoV2' ? 0 : asNumber(payload.page, 0)
        const list = pickPage(s.merchants, { offset, limit }).map((m) => ({
          id: m.id,
          nickname: m.nickname,
          address: m.address,
          distance: m.distance,
        }))
        return ok(list)
      }

      case '/user/get_user_coupon_id':
        return ok([201, 202, 203])

      case '/user/coupon_issue_user': {
        const list = Array.isArray(s.userCouponIssueList) ? s.userCouponIssueList : []
        const limit = asNumber(payload.limit, 8)
        return ok({ data: list, per_page: limit })
      }

      case '/coupon/idtocoupon': {
        const cuid = getId(payload.cuid)
        return ok({ coupon_title: '满 100 减 20', coupon_price: 20, use_type: 2, id: cuid })
      }

      case '/coupon/encryptAES': {
        const id = getId(payload.id) || '90001'
        return ok({
          id,
          qrcode_url: `MOCK-QR-COUPON-${id}`,
          write_off_status: 0,
          uinfo: { name: 'Mock User', idcard: '610000199001010000' },
        })
      }

      case '/coupon/writeoff':
        return ok({ success: true }, '核销成功')

      case '/coupon/writeofflog': {
        const offset = Math.max(0, asNumber(payload.page, 1) - 1) * asNumber(payload.limit, 20)
        const limit = asNumber(payload.limit, 20)
        return ok(pickPage(s.writeoffLogs, { offset, limit }))
      }

      case '/coupon/writeoffdetail': {
        const id = getId(payload.id)
        return ok(s.writeoffDetailById[id] || s.writeoffDetailById[70001] || {})
      }

      case '/user/coupon_order': {
        const list = Array.isArray(s.couponOrders) ? s.couponOrders : []
        const limit = asNumber(payload.limit, 8)
        return ok({ data: list, per_page: limit })
      }

      case '/user/coupon_order_detail': {
        const orderNo = getId(payload.order_no)
        const found = (s.couponOrders || []).find((o) => String(o.order_no) === orderNo) || s.couponOrders[0]
        return ok({
          order_no: found.order_no,
          origin_price: found.origin_price,
          payment_status: found.payment_status,
          create_time: found.create_time,
          update_time: found.update_time,
          detail: found.detail,
        })
      }

      case '/pay/submit':
      case '/pay/refund':
        return ok({ pay: createPayParams() }, p === '/pay/refund' ? '退款已提交' : '下单成功')

      case '/appt/getDatetime': {
        const sellerId = getId(payload.seller_id)
        const table = s.apptScheduleBySellerId || {}
        const found = table[sellerId] || table[301] || { number: 1, list: {} }
        return ok({ number: found.number || 1, list: found.list || {} })
      }

      case '/appt/createAppt': {
        if (!uid) return fail(110, 'AUTH_REQUIRED')
        const datetimeId = getId(payload.datetime_id)
        const fullname = String(payload.fullname || '').trim()
        const idcard = String(payload.idcard || '').trim()
        const phone = String(payload.phone || '').trim()
        const number = Math.max(1, asNumber(payload.number, 1))
        if (!datetimeId) return fail(400, 'DATETIME_ID_REQUIRED')
        if (!fullname) return fail(400, 'FULLNAME_REQUIRED')
        if (!idcard) return fail(400, 'IDCARD_REQUIRED')
        if (!phone) return fail(400, 'PHONE_REQUIRED')

        let touristPayload = []
        try {
          touristPayload = JSON.parse(payload.tourist || '[]')
        } catch (e) {
          touristPayload = []
        }
        if (!Array.isArray(touristPayload) || touristPayload.length === 0) return fail(400, 'TOURIST_REQUIRED')

        const scheduleBySeller = s.apptScheduleBySellerId || {}
        let matched = null
        Object.keys(scheduleBySeller).some((sellerKey) => {
          const entry = scheduleBySeller[sellerKey] || {}
          const listMap = entry.list || {}
          return Object.keys(listMap).some((dateKey) => {
            const slots = Array.isArray(listMap[dateKey]) ? listMap[dateKey] : []
            const slot = slots.find((it) => String(it && it.id) === datetimeId) || null
            if (!slot) return false
            matched = { sellerId: sellerKey, date: dateKey, slot }
            return true
          })
        })

        if (!matched) return fail(404, 'DATETIME_NOT_FOUND')
        if (asNumber(matched.slot.stock, 0) < number) return fail(400, 'STOCK_NOT_ENOUGH')

        matched.slot.stock = Math.max(0, asNumber(matched.slot.stock, 0) - number)

        const nextId = asNumber(s.apptNextId, 90001)
        s.apptNextId = nextId + 1

        const scenic = (s.scenicList || []).find((it) => String(it && it.id) === String(matched.sellerId)) || s.scenicList[0] || {}
        const seller = { nickname: scenic.nickname || '', image: scenic.image || '' }

        const touristList = touristPayload
          .map((t) => ({
            tourist_fullname: t && t.fullname,
            tourist_mobile: t && t.mobile,
            tourist_cert_id: t && t.cert_id,
            tourist_cert_type: (t && t.cert_type) || 1,
            refund_progress: 'init',
            out_trade_no: `MOCK-OUT-APPT-${nextId}`,
          }))
          .filter((t) => t.tourist_fullname && t.tourist_mobile && t.tourist_cert_id)

        if (touristList.length !== number) return fail(400, 'TOURIST_NOT_MATCH_NUMBER')

        if (!Array.isArray(s.apptLogs)) s.apptLogs = []
        s.apptLogs.unshift({
          id: nextId,
          uid,
          seller_id: asNumber(matched.sellerId, matched.sellerId),
          status: 0,
          status_text: '待核销',
          seller,
          fullname,
          phone,
          idcard,
          number,
          start: `${matched.date} ${matched.slot.time_start_text || ''}`.trim(),
          time_end_text: matched.slot.time_end_text || '',
          qrcode_str: `MOCK-QR-APPT-${nextId}`,
          code: `APPT-${nextId}`,
          tourist_list: touristList,
        })

        return ok({ id: nextId }, '预约成功')
      }

      case '/appt/getList': {
        if (!uid) return fail(110, 'AUTH_REQUIRED')
        const status = String(payload.status || '')
        const page = asNumber(payload.page, 1)
        const pageSize = asNumber(payload.page_size, 12)
        const offset = Math.max(0, page - 1) * pageSize

        const list = Array.isArray(s.apptLogs) ? s.apptLogs : []
        const mine = list.filter((it) => String(it && it.uid) === String(uid))
        const filtered = status === '' ? mine : mine.filter((it) => String(it && it.status) === status)
        return ok(pickPage(filtered, { offset, limit: pageSize }))
      }

      case '/appt/getDetail': {
        if (!uid) return fail(110, 'AUTH_REQUIRED')
        const id = getId(payload.id)
        const list = Array.isArray(s.apptLogs) ? s.apptLogs : []
        const found = list.find((it) => String(it && it.id) === id) || null
        if (!found) return fail(404, 'NOT_FOUND')
        return ok(found)
      }

      case '/appt/writeOff': {
        if (!uid) return fail(110, 'AUTH_REQUIRED')
        const id = getId(payload.be_id)
        if (!id) return fail(400, 'BE_ID_REQUIRED')

        let qrcodeStr = String(payload.qrcode_str || '').trim()
        try {
          qrcodeStr = decodeURIComponent(qrcodeStr)
        } catch (e) {}
        if (!qrcodeStr) return fail(400, 'QRCODE_REQUIRED')

        const useLat = asNumber(payload.use_lat, 0)
        const useLng = asNumber(payload.use_lng, 0)

        const list = Array.isArray(s.apptLogs) ? s.apptLogs : []
        const found = list.find((it) => String(it && it.id) === id) || null
        if (!found) return fail(404, 'NOT_FOUND')
        if (String(found.status) !== '0') return fail(400, 'CANNOT_WRITEOFF')
        if (String(found.qrcode_str) !== qrcodeStr) return fail(400, 'QRCODE_MISMATCH')

        found.status = 1
        found.status_text = '已核销'
        found.writeoff_time = Math.floor(Date.now() / 1000)
        found.writeoff_lat = useLat
        found.writeoff_lng = useLng

        return ok({ success: true, id: found.id, number: found.number }, '核销成功')
      }

      case '/appt/cancelAppt': {
        if (!uid) return fail(110, 'AUTH_REQUIRED')
        const id = getId(payload.log_id)
        const list = Array.isArray(s.apptLogs) ? s.apptLogs : []
        const found = list.find((it) => String(it && it.id) === id && String(it && it.uid) === String(uid)) || null
        if (!found) return fail(404, 'NOT_FOUND')
        if (String(found.status) !== '0') return fail(400, 'CANNOT_CANCEL')
        found.status = 2
        found.status_text = '已取消'
        return ok({ success: true }, '取消成功')
      }

      case '/ticket/getScenicList': {
        const page = asNumber(payload.page, 1)
        const pageSize = asNumber(payload.page_size, 12)
        const offset = Math.max(0, page - 1) * pageSize
        return ok(pickPage(s.scenicList, { offset, limit: pageSize }))
      }

      case '/ticket/getTicketList': {
        const sellerId = getId(payload.seller_id)
        return ok(s.ticketGroupsBySellerId[sellerId] || s.ticketGroupsBySellerId[301] || [])
      }

      case '/ticket/getCommentList': {
        const mid = getId(payload.mid)
        const userId = getId(payload.user_id)
        const page = asNumber(payload.page, 1)
        const pageSize = asNumber(payload.page_size, 12)
        const offset = Math.max(0, page - 1) * pageSize

        if (userId) {
          if (!uid) return fail(110, 'AUTH_REQUIRED')
          const list = (s.commentByUserId && s.commentByUserId[userId]) || []
          return ok(pickPage(list, { offset, limit: pageSize }))
        }

        const list = (s.commentBySellerId && (s.commentBySellerId[mid] || s.commentBySellerId[301])) || []
        return ok(pickPage(list, { offset, limit: pageSize }))
      }

      case '/ticket/writeComment': {
        if (!uid) return fail(110, 'AUTH_REQUIRED')
        const orderId = getId(payload.order_id || payload.id)
        const content = String(payload.content || '').trim()
        const rate = asNumber(payload.rate, 0)
        if (!orderId) return fail(400, 'ORDER_ID_REQUIRED')
        if (!rate) return fail(400, 'RATE_REQUIRED')
        if (!content) return fail(400, 'CONTENT_REQUIRED')

        const today = new Date().toISOString().slice(0, 10)
        const comment = {
          users: {
            nickname: (s.userProfile && s.userProfile.nickname) || (user && user.name) || 'Mock User',
            headimgurl: (s.userProfile && s.userProfile.headimgurl) || '',
          },
          rate,
          content,
          create_time: today,
        }

        if (!s.commentByUserId || typeof s.commentByUserId !== 'object') s.commentByUserId = {}
        if (!Array.isArray(s.commentByUserId[uid])) s.commentByUserId[uid] = []
        s.commentByUserId[uid].unshift(comment)

        const order = findTicketOrder(orderId)
        const sellerId = order ? getId(order.seller_id) : ''
        if (order && typeof order === 'object') order.iscomment = true

        if (sellerId) {
          if (!s.commentBySellerId || typeof s.commentBySellerId !== 'object') s.commentBySellerId = {}
          if (!Array.isArray(s.commentBySellerId[sellerId])) s.commentBySellerId[sellerId] = []
          s.commentBySellerId[sellerId].unshift(comment)
        }

        return ok({ success: true }, '鎻愪氦鎴愬姛')
      }

      case '/ticket/getTicketPirce': {
        const ticketId = getId(payload.ticket_id)
        return ok(s.ticketPriceByTicketId[ticketId] || s.ticketPriceByTicketId[501] || [])
      }

      case '/ticket/pay':
      case '/ticket/orderpay':
        return ok({ pay: createPayParams() }, '下单成功')

      case '/ticket/getOrderList': {
        const status = String(payload.status || '')
        const list = buildTicketOrderList({ status })
        const page = asNumber(payload.page, 1)
        const pageSize = asNumber(payload.page_size, 12)
        const offset = Math.max(0, page - 1) * pageSize
        return ok(pickPage(list, { offset, limit: pageSize }))
      }

      case '/ticket/getOrderDetail': {
        const orderId = getId(payload.order_id)
        const found = findTicketOrder(orderId) || s.ticketOrders[0]
        return ok(found || {})
      }

      case '/ticket/getOrderDetailDetail': {
        const detailId = getId(payload.order_detail_id)
        return ok(s.ticketOrderDetailDetailById[detailId] || s.ticketOrderDetailDetailById[81001] || {})
      }

      case '/ticket/writeOff': {
        const beId = getId(payload.be_id)
        if (beId === 'ota') return ok({ id: 80002 }, '核销成功')
        return ok({ success: true }, '核销成功')
      }

      case '/ticket/cancelRefund':
      case '/ticket/refund':
      case '/ticket/single_refund':
        return ok({ success: true }, '已提交')

      case '/ticket/getRefundLogList': {
        const page = asNumber(payload.page, 1)
        const pageSize = asNumber(payload.page_size, 12)
        const offset = Math.max(0, page - 1) * pageSize
        return ok(pickPage(s.refundLogs, { offset, limit: pageSize }))
      }

      case '/ticket/getRefundLogDetail': {
        const id = getId(payload.id)
        return ok(s.refundDetailById[id] || s.refundDetailById[60001] || {})
      }

      case '/user/getTouristList': {
        const limit = asNumber(payload.page_size, 999)
        return ok({ data: pickPage(s.touristList, { offset: 0, limit }), per_page: limit })
      }

      case '/user/getCertTypeList':
        return ok({ 1: '身份证', 2: '护照' })

      case '/user/postTourist':
      case '/user/delTourist':
        return ok({ success: true }, '保存成功')

      default:
        if (m === 'OPTIONS') return ok({})
        return Promise.reject(Object.assign(new Error(`MOCK_NOT_IMPLEMENTED: ${p}`), { code: 'MOCK_NOT_IMPLEMENTED' }))
    }
  })
}

module.exports = {
  request: handleRequest,
  reset() {
    state = null
  },
}
