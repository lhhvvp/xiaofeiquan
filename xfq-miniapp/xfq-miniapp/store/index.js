import Vue from 'vue'
import Vuex from 'vuex'
import {
	httpRequest
} from '../httpRequest.js'
Vue.use(Vuex)
const store = new Vuex.Store({
	state: { //存放状态
		merchant: {}, //商家详情,
		groupCoupon: {}, //旅游行团，优惠券信息,
		touristInfo:{},//旅行团游客信息,
		hotelList:{},//酒店打卡信息；
		hotelUserList:[],//酒店用户打卡信息,
		indexInfo:{
			data:[],
			time:null,
		},//首页信息
		uerInfo: uni.getStorageSync('uerInfo') ? uni.getStorageSync('uerInfo') : {},
		is_refresh:false,
		order_refund_detail:{}//退款列表详情
	},
	mutations: {
		SetOrderRefundDetail(state, payload){
			state.order_refund_detail = payload;
		},
		setIndexData(state, payload){
			state.indexInfo.data = payload;
			let seconds = 300; //5分钟刷新
			let nowTime = Date.parse(new Date()) / 1000;
			let expire = nowTime + Number(seconds);
			state.indexInfo.time = expire;
		},

		setMerchant(state, payload) {
			state.merchant = payload
		},
		setTouristInfo(state, payload) {
			state.touristInfo = payload
		},
		setHotelList(state, payload) {
			state.hotelList = payload
		},
		setHotelUserList(state, payload){
			state.hotelUserList = payload
		},
		setGroupCoupon(state, payload){
			state.groupCoupon = payload;
		},
		login(state, payload) {
			state.uerInfo.token = payload.token
			state.uerInfo.uid = payload.uid
			state.uerInfo.openid = payload.openid,
			state.uerInfo.uuid = payload.uuid,
			state.uerInfo.no = payload.no,
			uni.setStorageSync('uerInfo', payload);
		},
		//小程序返回上一页刷新配合onShow();
		setRefresh(state, payload) {
			state.is_refresh = payload;
		}
	}
})
export default store
