/* eslint-disable no-console */
const assert = require('assert')

function createWxStub() {
  const storage = new Map()

  return {
    getAccountInfoSync() {
      return { miniProgram: { envVersion: 'develop' } }
    },
    showLoading() {},
    hideLoading() {},
    showToast() {},
    showModal(opts) {
      if (opts && typeof opts.success === 'function') opts.success({ confirm: false, cancel: true })
    },
    reportEvent() {},

    getStorageSync(key) {
      return storage.get(String(key))
    },
    setStorageSync(key, value) {
      storage.set(String(key), value)
    },
    removeStorageSync(key) {
      storage.delete(String(key))
    },

    requestPayment(opts) {
      if (opts && typeof opts.success === 'function') opts.success({ mock: true })
    },
  }
}

global.wx = createWxStub()

const config = require('../miniprogram/config')
config.mock = true
config.mockPayment = true
if (!config.baseUrl) config.baseUrl = 'mock'

const auth = require('../miniprogram/services/auth')
auth.setUser({
  token: 'mock-token',
  uid: 1,
  openid: 'mock-openid',
  uuid: 'mock-uuid',
})

const request = require('../miniprogram/services/request')
const systemService = require('../miniprogram/services/system')
const couponApi = require('../miniprogram/services/api/coupon')
const contentApi = require('../miniprogram/services/api/content')
const indexApi = require('../miniprogram/services/api/index')
const merchantApi = require('../miniprogram/services/api/merchant')
const ticketsApi = require('../miniprogram/services/api/tickets')
const apptApi = require('../miniprogram/services/api/appt')
const payApi = require('../miniprogram/services/api/pay')
const userApi = require('../miniprogram/services/api/user')
const paymentUtil = require('../miniprogram/utils/payment')

function checkOk(name, res) {
  assert(res && typeof res === 'object', `${name}: response should be object`)
  assert.strictEqual(res.code, 0, `${name}: code should be 0, got ${res.code}`)
  return res
}

async function main() {
  const steps = []
  function step(name, fn) {
    steps.push({ name, fn })
  }

  let myCommentCount = 0
  const smokeCommentContent = 'smoke: 评论'
  let apptDatetimeId = ''
  let apptCreatedId = 0
  let apptWriteoffId = 0
  let apptWriteoffQrcodeStr = ''
  let couponClockCount = 0
  let groupCouponQrcodeUrl = ''

  step('system.fetchSystem', async () => {
    const system = await systemService.fetchSystem({ force: true, showLoading: false })
    assert(system && typeof system === 'object', 'system should be object')
    assert(system && typeof system.service === 'string', 'system.service should be string')
    assert(system && typeof system.policy === 'string', 'system.policy should be string')
  })

  step('index.transform', async () => {
    const res = await indexApi.transform({ longitude: 109.734, latitude: 38.285 }, { showLoading: false })
    const body = checkOk('index.transform', res)
    assert(body.data && body.data.result && body.data.result.address, 'index.transform: result.address should exist')
  })

  step('index.miniwxlogin', async () => {
    const res = await request({ path: '/index/miniwxlogin', method: 'POST', data: { code: 'mock-code' } })
    const body = checkOk('index.miniwxlogin', res)
    assert(body.data && body.data.token, 'token should exist')
    assert(body.data && body.data.userinfo && body.data.userinfo.openid, 'userinfo.openid should exist')
  })

  step('seller.bindCheckOpenid', async () => {
    const res = await request({
      path: '/seller/bindCheckOpenid',
      method: 'POST',
      data: { uuid: 'mock-uuid', mid: '10001', openid: 'mock-openid', uid: 1 },
    })
    checkOk('seller.bindCheckOpenid', res)
  })

  step('merchant.getCategories', async () => checkOk('seller.cate', await merchantApi.getCategories({ showLoading: false })))

  step('merchant.getMerchantList', async () =>
    checkOk(
      'seller.list',
      await merchantApi.getMerchantList(
        { class_id: 1, page: 0, limit: 8, latitude: 0, longitude: 0 },
        { showLoading: false }
      )
    ))

  step('merchant.searchMerchants', async () =>
    checkOk('seller.search', await merchantApi.searchMerchants({ nickname: '示例' }, { showLoading: false })))

  step('merchant.getMerchantDetail', async () =>
    checkOk(
      'seller.detail',
      await merchantApi.getMerchantDetail({ seller_id: 101, latitude: 0, longitude: 0 }, { showLoading: false })
    ))

  step('coupon.getIndex', async () => checkOk('coupon.index', await couponApi.getIndex({ userid: 1 }, { showLoading: false })))

  step('coupon.getTempApi', async () =>
    checkOk('coupon.tempApi', await couponApi.getTempApi({ userid: 0 }, { showLoading: false })))

  step('coupon.getDetail', async () =>
    checkOk('coupon.detail', await couponApi.getDetail({ couponId: 201, userid: 1 }, { showLoading: false })))

  step('coupon.receive', async () =>
    checkOk(
      'coupon.receive',
      await couponApi.receive({ userid: 1, couponId: 201, latitude: 0, longitude: 0 }, { showLoading: false })
    ))

  step('coupon.getApplicableMerchantsV2', async () =>
    checkOk(
      'coupon.applicabletoV2',
      await couponApi.getApplicableMerchantsV2({ id: 201, latitude: 0, longitude: 0 }, { showLoading: false })
    ))

  step('coupon.getApplicableMerchants', async () =>
    checkOk(
      'coupon.applicableto',
      await couponApi.getApplicableMerchants({ id: 201, latitude: 0, longitude: 0, page: 0, limit: 15 }, { showLoading: false })
    ))

  step('coupon.idToCoupon', async () => checkOk('coupon.idtocoupon', await couponApi.idToCoupon({ cuid: 90001 }, { showLoading: false })))

  step('coupon.encryptAES', async () =>
    checkOk('coupon.encryptAES', await couponApi.encryptAES({ id: 90001, salt: 'mock-salt', uid: 1 }, { showLoading: false })))

  step('coupon.writeoff', async () =>
    checkOk('coupon.writeoff', await couponApi.writeoff({ userid: 1, mid: 10001, coupon_issue_user_id: 90001 }, { showLoading: false })))

  step('coupon.writeofflog', async () =>
    checkOk('coupon.writeofflog', await couponApi.getWriteoffLog({ page: 1, limit: 20, userid: 1, mid: 10001 }, { showLoading: false })))

  step('coupon.writeoffdetail', async () =>
    checkOk('coupon.writeoffdetail', await couponApi.getWriteoffDetail({ userid: 1, mid: 10001, id: 70001 }, { showLoading: false })))

  step('user.get_user_coupon_id', async () =>
    checkOk('user.get_user_coupon_id', await userApi.getUserCouponIds({ uid: 1 }, { showLoading: false })))

  step('user.coupon_issue_user', async () =>
    checkOk('user.coupon_issue_user', await userApi.getCouponIssueUserList({ uid: 1, status: '', page: 1, limit: 8 }, { showLoading: false })))

  step('user.auth_info', async () => checkOk('user.auth_info', await userApi.getAuthInfo({ uid: 1 }, { showLoading: false })))

  step('user.index', async () => {
    const res = await userApi.getUserIndex({ uid: 1 }, { showLoading: false })
    const body = checkOk('user.index', res)
    assert(body.data && typeof body.data === 'object', 'user.index: data should be object')
    assert(typeof body.data.nickname === 'string', 'user.index: data.nickname should be string')
  })

  step('user.edit', async () => {
    const res = await userApi.editUser(
      { id: 1, nickname: 'Mock Nickname', headimgurl: 'https://example.com/mock-avatar.png' },
      { showLoading: false }
    )
    checkOk('user.edit', res)
  })

  step('user.index(after_edit)', async () => {
    const res = await userApi.getUserIndex({ uid: 1 }, { showLoading: false })
    const body = checkOk('user.index(after_edit)', res)
    assert(body.data && body.data.nickname === 'Mock Nickname', 'user.index(after_edit): nickname should update')
  })

  step('user.collection', async () => {
    const res = await userApi.getCollectionList({ uid: 1, page: 0, limit: 8, latitude: 0, longitude: 0 }, { showLoading: false })
    const body = checkOk('user.collection', res)
    assert(Array.isArray(body.data), 'user.collection: data should be array')
  })

  step('user.collection_action(add)', async () => {
    const res = await userApi.collectionAction({ action: 'add', uid: 1, mid: 102 }, { showLoading: false })
    checkOk('user.collection_action(add)', res)
  })

  step('user.collection(after_add)', async () => {
    const res = await userApi.getCollectionList({ uid: 1, page: 0, limit: 8, latitude: 0, longitude: 0 }, { showLoading: false })
    const body = checkOk('user.collection(after_add)', res)
    assert(Array.isArray(body.data), 'user.collection(after_add): data should be array')
    assert(body.data.some((it) => String(it && it.id) === '102'), 'user.collection(after_add): should include mid=102')
  })

  step('user.collection_action(del)', async () => {
    const res = await userApi.collectionAction({ action: 'del', uid: 1, mid: 102 }, { showLoading: false })
    checkOk('user.collection_action(del)', res)
  })

  step('user.collection(after_del)', async () => {
    const res = await userApi.getCollectionList({ uid: 1, page: 0, limit: 8, latitude: 0, longitude: 0 }, { showLoading: false })
    const body = checkOk('user.collection(after_del)', res)
    assert(Array.isArray(body.data), 'user.collection(after_del): data should be array')
    assert(!body.data.some((it) => String(it && it.id) === '102'), 'user.collection(after_del): should remove mid=102')
  })

  step('user.feed_back', async () => {
    const res = await userApi.feedBack({ uid: 1, content: 'mock feedback', images: 'a.png,b.png' }, { showLoading: false })
    checkOk('user.feed_back', res)
  })

  step('user.getTouristList', async () =>
    checkOk('user.getTouristList', await userApi.getTouristList({ page: 1, page_size: 999 }, { showLoading: false })))

  step('user.getCertTypeList', async () =>
    checkOk('user.getCertTypeList', await userApi.getCertTypeList({ userid: 1 }, { showLoading: false })))

  step('user.postTourist', async () =>
    checkOk(
      'user.postTourist',
      await userApi.postTourist({ fullname: '张三', mobile: '13800138000', cert_type: 1, cert_id: '610000199001010000' }, { showLoading: false })
    ))

  step('user.delTourist', async () => checkOk('user.delTourist', await userApi.delTourist({ ids: '40001' }, { showLoading: false })))

  step('coupon.getUserCouponRecordList(before)', async () => {
    const res = await couponApi.getUserCouponRecordList({ userid: 1, couponId: 201 }, { showLoading: false })
    const body = checkOk('coupon.getUserCouponRecordList(before)', res)
    assert(Array.isArray(body.data), 'coupon.getUserCouponRecordList(before): data should be array')
    couponClockCount = body.data.length
  })

  step('user.userClock', async () =>
    checkOk(
      'user.userClock',
      await userApi.userClock(
        { uid: 1, couponId: 201, latitude: 0, longitude: 0, qrcode_url: 'MOCK-CLOCK-101' },
        { showLoading: false }
      )
    ))

  step('coupon.getUserCouponRecordList(after)', async () => {
    const res = await couponApi.getUserCouponRecordList({ userid: 1, couponId: 201 }, { showLoading: false })
    const body = checkOk('coupon.getUserCouponRecordList(after)', res)
    assert(Array.isArray(body.data), 'coupon.getUserCouponRecordList(after): data should be array')
    assert(body.data.length === couponClockCount + 1, 'coupon.getUserCouponRecordList(after): length should increase by 1')
    assert(
      body.data.some((it) => String(it && it.Seller && it.Seller.id) === '101'),
      'coupon.getUserCouponRecordList(after): should include Seller.id=101'
    )
  })

  step('user.tour_coupon_group(before)', async () => {
    const res = await userApi.tourCouponGroup({ id: 91001 }, { showLoading: false })
    const body = checkOk('user.tour_coupon_group(before)', res)
    assert(body.data && body.data.couponIssue, 'user.tour_coupon_group(before): couponIssue should exist')
    assert.strictEqual(Number(body.data.status), 0, 'user.tour_coupon_group(before): status should be 0')
    groupCouponQrcodeUrl = body.data.qrcode_url || 'MOCK-QR-GROUP-91001'
    assert(groupCouponQrcodeUrl, 'user.tour_coupon_group(before): qrcode_url should exist')
  })

  step('user.writeoff_tour', async () =>
    checkOk(
      'user.writeoff_tour',
      await userApi.writeoffTour(
        {
          userid: 1,
          mid: 10001,
          coupon_issue_user_id: 91001,
          use_min_price: 999999,
          qrcode_url: groupCouponQrcodeUrl,
          orderid: 0,
          latitude: 0,
          longitude: 0,
          vr_latitude: 0,
          vr_longitude: 0,
        },
        { showLoading: false }
      )
    ))

  step('user.tour_coupon_group(after_writeoff)', async () => {
    const res = await userApi.tourCouponGroup({ id: 91001 }, { showLoading: false })
    const body = checkOk('user.tour_coupon_group(after_writeoff)', res)
    assert.strictEqual(Number(body.data.status), 1, 'user.tour_coupon_group(after_writeoff): status should be 1')
  })

  step('user.clock_list(before)', async () => {
    const res = await userApi.getClockList({ uid: 1 }, { showLoading: false })
    const body = checkOk('user.clock_list(before)', res)
    assert(Array.isArray(body.data), 'user.clock_list: data should be array')
    assert(body.data.some((it) => String(it && it.id) === '50001'), 'user.clock_list: should contain id=50001')
  })

  step('user.clock', async () =>
    checkOk(
      'user.clock',
      await userApi.clock(
        {
          clock_uid: 1,
          tour_issue_user_id: 50001,
          spot_name: 'mock spot',
          images: 'mock.png',
          address: 'mock 地址',
          longitude: 109.734,
          latitude: 38.285,
          dess: 'smoke',
        },
        { showLoading: false }
      )
    ))

  step('user.clock_list(after)', async () => {
    const res = await userApi.getClockList({ uid: 1 }, { showLoading: false })
    const body = checkOk('user.clock_list(after)', res)
    const found = body.data.find((it) => String(it && it.id) === '50001')
    assert(found && Number(found.is_clock) === 1, 'user.clock_list(after): id=50001 should be clocked')
  })

  step('appt.getDatetime', async () => {
    const res = await apptApi.getDatetime({ seller_id: 301 }, { showLoading: false })
    const body = checkOk('appt.getDatetime', res)
    assert(body.data && typeof body.data === 'object', 'appt.getDatetime: data should be object')
    assert(body.data && typeof body.data.number !== 'undefined', 'appt.getDatetime: data.number should exist')
    assert(body.data && typeof body.data.list === 'object', 'appt.getDatetime: data.list should be object')
    const firstDate = body.data && body.data.list && Object.keys(body.data.list)[0]
    const firstSlot = firstDate && Array.isArray(body.data.list[firstDate]) ? body.data.list[firstDate][0] : null
    assert(firstSlot && firstSlot.id, 'appt.getDatetime: should have at least 1 slot with id')
    apptDatetimeId = firstSlot.id
  })

  step('appt.createAppt', async () => {
    const res = await apptApi.createAppt(
      {
        datetime_id: apptDatetimeId,
        fullname: 'Mock User',
        idcard: '610000199001010000',
        phone: '13800138000',
        number: 1,
        lat: 0,
        lng: 0,
        tourist: JSON.stringify([{ fullname: 'Mock User', cert_type: 1, cert_id: '610000199001010000', mobile: '13800138000' }]),
      },
      { showLoading: false }
    )
    const body = checkOk('appt.createAppt', res)
    apptCreatedId = body.data && body.data.id
    assert(apptCreatedId, 'appt.createAppt: data.id should exist')
  })

  step('appt.getList', async () => {
    const res = await apptApi.getList({ page: 1, page_size: 12, status: '' }, { showLoading: false })
    const body = checkOk('appt.getList', res)
    assert(Array.isArray(body.data), 'appt.getList: data should be array')
    assert(body.data.some((it) => String(it && it.id) === String(apptCreatedId)), 'appt.getList: should include new appt')
  })

  step('appt.getDetail', async () => {
    const res = await apptApi.getDetail({ id: apptCreatedId }, { showLoading: false })
    const body = checkOk('appt.getDetail', res)
    assert(body.data && String(body.data.id) === String(apptCreatedId), 'appt.getDetail: id should match')
  })

  step('appt.cancelAppt', async () => {
    const res = await apptApi.cancelAppt({ log_id: apptCreatedId }, { showLoading: false })
    checkOk('appt.cancelAppt', res)
  })

  step('appt.createAppt(writeoff)', async () => {
    const res = await apptApi.createAppt(
      {
        datetime_id: apptDatetimeId,
        fullname: 'Mock User',
        idcard: '610000199001010000',
        phone: '13800138000',
        number: 1,
        lat: 0,
        lng: 0,
        tourist: JSON.stringify([{ fullname: 'Mock User', cert_type: 1, cert_id: '610000199001010000', mobile: '13800138000' }]),
      },
      { showLoading: false }
    )
    const body = checkOk('appt.createAppt(writeoff)', res)
    apptWriteoffId = body.data && body.data.id
    assert(apptWriteoffId, 'appt.createAppt(writeoff): data.id should exist')
  })

  step('appt.getDetail(writeoff)', async () => {
    const res = await apptApi.getDetail({ id: apptWriteoffId }, { showLoading: false })
    const body = checkOk('appt.getDetail(writeoff)', res)
    assert(body.data && String(body.data.id) === String(apptWriteoffId), 'appt.getDetail(writeoff): id should match')
    assert.strictEqual(Number(body.data.status), 0, 'appt.getDetail(writeoff): status should be 0')
    apptWriteoffQrcodeStr = body.data.qrcode_str
    assert(apptWriteoffQrcodeStr, 'appt.getDetail(writeoff): qrcode_str should exist')
  })

  step('appt.writeOff', async () => {
    const res = await apptApi.writeOff(
      { qrcode_str: apptWriteoffQrcodeStr, be_id: apptWriteoffId, use_lat: 0, use_lng: 0 },
      { showLoading: false }
    )
    checkOk('appt.writeOff', res)
  })

  step('appt.getDetail(after_writeoff)', async () => {
    const res = await apptApi.getDetail({ id: apptWriteoffId }, { showLoading: false })
    const body = checkOk('appt.getDetail(after_writeoff)', res)
    assert.strictEqual(Number(body.data.status), 1, 'appt.getDetail(after_writeoff): status should be 1')
  })

  step('user.coupon_order', async () =>
    checkOk('user.coupon_order', await userApi.getCouponOrderList({ uid: 1, status: '', page: 1, limit: 8 }, { showLoading: false })))

  step('user.coupon_order_detail', async () =>
    checkOk('user.coupon_order_detail', await userApi.getCouponOrderDetail({ uid: 1, order_no: 'MOCK-PAY-0001' }, { showLoading: false })))

  step('pay.submit', async () => checkOk('pay.submit', await payApi.submit({ uid: 1, openid: 'mock-openid' }, { showLoading: false })))

  step('pay.refund', async () => checkOk('pay.refund', await payApi.refund({ uid: 1, openid: 'mock-openid' }, { showLoading: false })))

  step('ticket.getScenicList', async () =>
    checkOk('ticket.getScenicList', await ticketsApi.getScenicList({ page: 1, page_size: 12 }, { showLoading: false })))

  step('ticket.getTicketList', async () =>
    checkOk('ticket.getTicketList', await ticketsApi.getTicketList({ seller_id: 301 }, { showLoading: false })))

  step('ticket.getCommentList(by_mid)', async () =>
    checkOk('ticket.getCommentList(by_mid)', await ticketsApi.getCommentList({ mid: 301, page: 1, page_size: 6 }, { showLoading: false })))

  step('ticket.getCommentList(by_user_before)', async () => {
    const res = await ticketsApi.getCommentList({ user_id: 1, page: 1, page_size: 12 }, { showLoading: false })
    const body = checkOk('ticket.getCommentList(by_user_before)', res)
    assert(Array.isArray(body.data), 'ticket.getCommentList(by_user_before): data should be array')
    myCommentCount = body.data.length
  })

  step('ticket.getOrderDetail(used_before)', async () => {
    const res = await ticketsApi.getOrderDetail({ order_id: 80003 }, { showLoading: false })
    const body = checkOk('ticket.getOrderDetail(used_before)', res)
    assert(body.data && body.data.order_status === 'used', 'ticket.getOrderDetail(used_before): order_status should be used')
    assert.strictEqual(body.data.iscomment, false, 'ticket.getOrderDetail(used_before): iscomment should be false')
  })

  step('ticket.writeComment', async () => {
    const res = await ticketsApi.writeComment({ order_id: 80003, content: smokeCommentContent, rate: 4.5 }, { showLoading: false })
    checkOk('ticket.writeComment', res)
  })

  step('ticket.getOrderDetail(used_after)', async () => {
    const res = await ticketsApi.getOrderDetail({ order_id: 80003 }, { showLoading: false })
    const body = checkOk('ticket.getOrderDetail(used_after)', res)
    assert.strictEqual(body.data.iscomment, true, 'ticket.getOrderDetail(used_after): iscomment should be true')
  })

  step('ticket.getCommentList(by_user_after)', async () => {
    const res = await ticketsApi.getCommentList({ user_id: 1, page: 1, page_size: 12 }, { showLoading: false })
    const body = checkOk('ticket.getCommentList(by_user_after)', res)
    assert(Array.isArray(body.data), 'ticket.getCommentList(by_user_after): data should be array')
    assert(body.data.length === myCommentCount + 1, `ticket.getCommentList(by_user_after): expected ${myCommentCount + 1}, got ${body.data.length}`)
    assert(
      body.data.some((it) => it && it.content === smokeCommentContent),
      'ticket.getCommentList(by_user_after): should contain new comment'
    )
  })

  step('ticket.getCommentList(by_mid_after)', async () => {
    const res = await ticketsApi.getCommentList({ mid: 301, page: 1, page_size: 6 }, { showLoading: false })
    const body = checkOk('ticket.getCommentList(by_mid_after)', res)
    assert(Array.isArray(body.data), 'ticket.getCommentList(by_mid_after): data should be array')
    assert(
      body.data.some((it) => it && it.content === smokeCommentContent),
      'ticket.getCommentList(by_mid_after): should contain new comment'
    )
  })

  step('ticket.getTicketPrice', async () =>
    checkOk('ticket.getTicketPirce', await ticketsApi.getTicketPrice({ ticket_id: 501, channel: 'online' }, { showLoading: false })))

  step('ticket.pay', async () => checkOk('ticket.pay', await ticketsApi.pay({ ticket_id: 501 }, { showLoading: false })))

  step('ticket.getOrderList', async () =>
    checkOk('ticket.getOrderList', await ticketsApi.getOrderList({ page: 1, page_size: 12, status: '' }, { showLoading: false })))

  step('ticket.getOrderDetail', async () =>
    checkOk('ticket.getOrderDetail', await ticketsApi.getOrderDetail({ order_id: 80001 }, { showLoading: false })))

  step('ticket.orderPay', async () =>
    checkOk('ticket.orderpay', await ticketsApi.orderPay({ uuid: 'mock-uuid', openid: 'mock-openid', trade_no: 'MOCK-TRADE-80001' }, { showLoading: false })))

  step('ticket.writeOff', async () =>
    checkOk('ticket.writeOff', await ticketsApi.writeOff({ qrcode_str: 'MOCK-QR', be_id: 80001, use_lat: 0, use_lng: 0 }, { showLoading: false })))

  step('ticket.getOrderDetailDetail', async () =>
    checkOk('ticket.getOrderDetailDetail', await ticketsApi.getOrderDetailDetail({ order_detail_id: 81001 }, { showLoading: false })))

  step('ticket.cancelRefund', async () =>
    checkOk('ticket.cancelRefund', await ticketsApi.cancelRefund({ type: 'order_detail', id: 81001 }, { showLoading: false })))

  step('ticket.refund', async () =>
    checkOk('ticket.refund', await ticketsApi.refund({ out_trade_no: 'MOCK-OUT-80002', refund_desc: 'test', openid: 'mock-openid', uuid: 'mock-uuid' }, { showLoading: false })))

  step('ticket.single_refund', async () =>
    checkOk('ticket.single_refund', await ticketsApi.singleRefund({ out_trade_no: 'MOCK-OUT-80002', refund_desc: 'test', openid: 'mock-openid', uuid: 'mock-uuid' }, { showLoading: false })))

  step('ticket.getRefundLogList', async () =>
    checkOk('ticket.getRefundLogList', await ticketsApi.getRefundLogList({ page: 1, page_size: 12 }, { showLoading: false })))

  step('ticket.getRefundLogDetail', async () =>
    checkOk('ticket.getRefundLogDetail', await ticketsApi.getRefundLogDetail({ id: 60001 }, { showLoading: false })))

  step('content.getNoteList', async () => {
    const res = await contentApi.getNoteList({}, { showLoading: false })
    const body = checkOk('content.getNoteList', res)
    assert(Array.isArray(body.data), 'content.getNoteList: data should be array')
  })

  step('content.getNoteDetail', async () => {
    const res = await contentApi.getNoteDetail({ id: 10001 }, { showLoading: false })
    const body = checkOk('content.getNoteDetail', res)
    assert(body.data && body.data.id, 'content.getNoteDetail: data.id should exist')
    assert(typeof body.data.content === 'string', 'content.getNoteDetail: data.content should be string')
  })

  step('payment.requestWxPayment(mockPayment)', async () => {
    const res = await paymentUtil.requestWxPayment({ data: { pay: { timeStamp: '1', nonceStr: 'n', package: 'p', paySign: 's' } } })
    assert(res && res.mockPayment === true, 'mockPayment should resolve with {mockPayment:true}')
  })

  const started = Date.now()
  for (const item of steps) {
    const t = Date.now()
    await item.fn()
    console.log(`OK  ${item.name}  +${Date.now() - t}ms`)
  }
  console.log(`\nALL OK (${steps.length} steps) in ${Date.now() - started}ms`)
}

main().catch((err) => {
  console.error('FAILED:', err)
  process.exitCode = 1
})
