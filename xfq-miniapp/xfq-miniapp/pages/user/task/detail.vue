<template>
	<view class="container container27893">
		<uni-section title="打卡提示" type="line">
			<uni-notice-bar :text="promptText" />
		</uni-section>
		
		<view class="flex flex-wrap diygw-col-24 flex-direction-column flex38-clz">
			<view class="diygw-col-24 text40-clz" v-if="dataList && dataList.length > 0"> 已打卡地点 </view>
			<view class="diygw-col-24 text40-clz" v-else>
				暂未打卡
			</view>
			<view v-if="dataList && dataList.length > 0" class="flex" v-for="item in dataList" :key='item.id'>
				<view class="flex diygw-col-24 items-center flex-nowrap flex54-clz">
					<image :src="item.Seller.image" class="image5-size diygw-image diygw-col-0 image5-clz"
						mode="scaleToFill">
					</image>
					<view class="flex diygw-col-0 flex-direction-column justify-between flex-nowrap flex55-clz">
						<view class="diygw-col-24 text57-clz">{{ item.Seller.nickname }}</view>
						<view class="diygw-col-24 text58-clz">{{ item.create_time }}</view>
					</view>
					<view class="diygw-col-0 text59-clz"> 已打卡 </view>
				</view>
			</view>
		</view>
		<view>
			<view class="flex diygw-col-24 button1-clz">
				<button class="diygw-btn blue radius-sm flex-sub margin-xs button1-button-clz"
					@click="scanCode">打卡</button>
			</view>
			<!-- <view class="flex diygw-col-12 button1-clz">
				<button class="diygw-btn green radius-xs flex-sub margin-xs button1-button-clz"
					@click="receiveGift">领取礼包</button>
			</view> -->
		</view>
	</view>
</template>

<script>
	import {
		getLocation
	} from '@/common/common.js';
	import {
		mapState,
		mapMutations
	} from 'vuex';
	export default {
		data() {
			return {
				promptText: "请确认打卡券是否是需要领取的券",
				//用户全局信息
				userInfo: {},
				//页面传参
				globalOption: {},
				//自定义全局变量
				globalData: {},
				couponId: null,
				latitude: 0,
				longitude: 0,
				dataList: []
			};
		},
		onShow() {},
		onLoad(option) {
			const that = this;
			if (option && option.couponId) {
				that.couponId = option.couponId;
				// 获取用户位置信息
				getLocation(false).then(
					success => {
						that.latitude = success.latitude;
						that.longitude = success.longitude;
						that.init();
					},
				);
			}
			if (option && option.couponTitle) {
				that.promptText = option.couponTitle + "：请确认打卡券是否是需要领取的券";
			}

		},
		computed: {
			...mapState(['hasLogin', 'uerInfo'])
		},
		methods: {
			async init() {
				this.$api.httpRequest(
						`/coupon/getUserCouponRecordList`, {
							userid: this.uerInfo.uid,
							couponId: this.couponId
						},
						'GET'
					)
					.then(res => {
						if (res.code == 0) {
							this.dataList = res.data;
						} else {
							uni.showModal({
								title: '提示',
								content: res.msg,
								showCancel: false
							});
						}

					});
			},
			scanCode() {
				let that = this;
				uni.scanCode({
					success: function(res) {
						console.log(res)
						if (res.scanType === 'QR_CODE') {
							// 获取二维码内容 调后端接口
							that.$api.httpRequest(
									`/user/userClock`, {
										uid: that.uerInfo.uid,
										couponId: that.couponId,
										latitude: that.latitude,
										longitude: that.longitude,
										qrcode_url: res.result
									},
									'POST'
								)
								.then(res => {
									if (res.code == 0) {
										uni.showModal({
											title: '提示',
											content: res.msg,
											showCancel: false
										});
										// 刷新页面
										that.init();
									} else {
										uni.showModal({
											title: '提示',
											content: res.msg,
											showCancel: false
										});
									}
								});
						}
					}
				})
			},
			receiveGift() {

				uni.showModal({
					title: '提示',
					content: '您目前可领取A礼包,礼包只可领取一次，是否立即领取？',
					confirmText: '立即领取',
					cancelText: '暂不领取',
					success: function(res) {
						if (res.confirm) {
							uni.showModal({
								title: '提示',
								content: "领取成功",
								confirmText: '去使用',
								success: function(res) {
									if (res.confirm) {
										uni.navigateTo({
											url: '/pages/user/order?state=1'
										});
									} else if (res.cancel) {
										console.log('用户点击取消');
									}
								}
							});
						} else if (res.cancel) {
							console.log('用户点击取消');
						}

					}
				})
			}
		}
	};
</script>

<style lang="scss" scoped>
	.flex38-clz {
		padding-top: 20rpx;
		border-bottom-left-radius: 16rpx;
		background-size: 100% 100%;
		color: #019dc0;
		padding-left: 20rpx;
		padding-bottom: 20rpx;
		border-top-right-radius: 16rpx;
		margin-right: 20rpx;
		margin-left: 20rpx;
		box-shadow: 0rpx 0rpx 16rpx #d9dcff;
		overflow: hidden;
		width: calc(100% - 20rpx - 20rpx) !important;
		border-top-left-radius: 16rpx;
		margin-top: 20rpx;
		border-bottom-right-radius: 16rpx;
		background-image: linear-gradient(90deg, rgba(252, 253, 255, 0.94) 10%, rgba(238, 243, 255, 0.95) 50%, rgba(252, 253, 255, 0.94) 100%);
		margin-bottom: 20rpx;
		padding-right: 20rpx;
	}

	.text40-clz {
		font-weight: bold;
		font-size: 28rpx !important;
	}

	.flex54-clz {
		padding-top: 10rpx;
		border-bottom-left-radius: 0rpx;
		padding-left: 10rpx;
		padding-bottom: 10rpx;
		border-top-right-radius: 20rpx;
		margin-right: 0rpx;
		margin-left: 0rpx;
		overflow: hidden;
		flex: 1;
		width: calc(100% - 0rpx - 0rpx) !important;
		border-top-left-radius: 0rpx;
		margin-top: 10rpx;
		border-bottom-right-radius: 20rpx;
		background-image: linear-gradient(90deg, rgba(252, 253, 255, 0.94) 0%, #bdf3ff 100%);
		margin-bottom: 10rpx;
		padding-right: 10rpx;
	}

	.image5-clz {
		border-bottom-left-radius: 20rpx;
		overflow: hidden;
		border-top-left-radius: 20rpx;
		border-top-right-radius: 20rpx;
		border-bottom-right-radius: 20rpx;
	}

	.image5-size {
		height: 150rpx !important;
		width: 150rpx !important;
	}

	.flex55-clz {
		padding-top: 10rpx;
		flex: 1;
		padding-left: 20rpx;
		padding-bottom: 10rpx;
		padding-right: 10rpx;
	}

	.text57-clz {
		color: #1d1d1d;
		font-weight: bold;
		font-size: 28rpx !important;
	}

	.text58-clz {
		color: #747474;
	}

	.text59-clz {
		background-color: #01b8df;
		padding-top: 10rpx;
		border-bottom-left-radius: 32rpx;
		overflow: hidden;
		color: #ffffff;
		font-weight: bold;
		padding-left: 40rpx;
		padding-bottom: 10rpx;
		border-top-left-radius: 32rpx;
		border-top-right-radius: 32rpx;
		border-bottom-right-radius: 32rpx;
		padding-right: 40rpx;
	}

	.button1-button-clz {
		margin: 6rpx !important;
	}

	.container27893 {
		padding-left: 0px;
		padding-right: 0px;
	}

	.container27893 {}
</style>