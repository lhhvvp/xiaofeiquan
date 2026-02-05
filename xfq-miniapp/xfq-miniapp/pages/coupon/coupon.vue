<template>
	<view class="coupon-box">
		<view class="coupon">
			<view class="content-coupon">
				<view class="info">{{ info.titleClass }}</view>
				<view class="title">{{ info.coupon_title }}</view>
				<!-- <view class="price">{{info.coupon_price}}</view> -->
				<view class="buttons" v-if="info.cid != 3 && info.cid != 4"></view>
				<view class="button" v-if="info.cid == 3 || info.cid == 4">{{ info.coupon_price }}</view>
				<view class="progress"><my-progress :percent="number"></my-progress></view>
				<view class="area">适用于：榆林市</view>
			</view>
			<view class="items">
				<view class="item" v-if="info.tips">
					<view class="item-left"><uni-icons type="calendar" color="#ae000d" size="20"></uni-icons></view>
					<view class="item-rigth">{{ info.tips }}</view>
				</view>
				<view class="item" v-if="info.tips_extend">
					<!-- 永久有效 -->
					<view class="item-left"><uni-icons type="shop" color="#ae000d" size="20"></uni-icons></view>
					<view class="item-rigth">{{ info.tips_extend }}</view>
				</view>
				<!-- <view class="item">
					<!-- 发放时间 -->
				<!-- <view class="item-left"><uni-icons type="calendar" color="#ae000d" size="20"></uni-icons></view> -->
				<!-- <view class="item-rigth" v-if="info.limit_time == 0">发放时间：不限时</view> -->
				<!-- <view class="item-rigth" v-if="info.limit_time == 1">发放时间：{{ info.start_time | dateHMS }} - {{ info.end_time | dateHMS }}</view> -->
				<!-- </view> -->
				<!-- <view class="item"> -->
				<!-- 永久有效 -->
				<!-- <view class="item-left"><uni-icons type="shop" color="#ae000d" size="20"></uni-icons></view> -->
				<!-- <view class="item-rigth">有效期：永久</view> -->
				<!-- </view> -->

				<!-- <view class="item" v-if="info.is_permanent == 2"> -->
				<!-- 时间段有效 -->
				<!-- <view class="item-left"><uni-icons type="shop" color="#ae000d" size="20"></uni-icons></view> -->
				<!-- <view class="item-rigth">有效期：{{ info.coupon_time_start }} - {{ info.coupon_time_end }}</view> -->
				<!-- </view> -->
				<!-- <view class="item" v-if="info.is_permanent == 3"> -->
				<!-- 天数有效 -->
				<!-- <view class="item-left"><uni-icons type="shop" color="#ae000d" size="20"></uni-icons></view> -->
				<!-- <view class="item-rigth">有效期：{{ info.day != 0 ? '领取后' + info.day + '天' : '已过期' }}</view> -->
				<!-- </view> -->
			</view>
			<view class="info-items">
				<view class="info-box">
					<view class="info" @click.stop="navToList('/pages/coupon/list?id=' + id)">
						<view class="left">适用于</view>
						<view class="right" v-if="suitable.nickname != undefined">
							<view class="name-title">{{ suitable.nickname }}</view>
							<view class="name-icons">
								<view v-if="!!suitable.distance">{{ suitable.distance }}</view>
								<uni-icons type="right"></uni-icons>
							</view>
						</view>
					</view>
				</view>
				<view class="info-box" v-if="lineListArray != null && lineListArray.id != undefined">
					<view class="info" @click.stop="navToList('/pages/coupon/lineList?flag=1&id=' + id)">
						<view class="left">推荐线路</view>
						<view class="right">
							<view class="name-title">{{ lineListArray.title }}</view>
							<view class="name-icons"><uni-icons type="right"></uni-icons></view>
						</view>
					</view>
				</view>
			</view>
		</view>
		<view class="instructions">
			<view class="title">使用细则</view>
			<view class="content"><rich-text :nodes="info.remark"></rich-text></view>
		</view>
		<view class="instructions" v-if="info.use_type == 1">
			<view class="title">核销规则</view>
			<view class="content"><rich-text :nodes="info.use_type_desc"></rich-text></view>
		</view>
		<view style="height: 120upx;background-color: #f7f7f7;"></view>
		<view class="goods-nav">
			<uni-goods-nav :buttonGroup="buttonGroup" :money="info.sale_price"
				@buttonClick="buttonClick"></uni-goods-nav>
		</view>

		<!-- <uni-transition :duration="500" :show="isTravel">
			<view class="mask-box">
				<view class="mask">
					<view class="mask-tit">
						<text>附近旅行社</text>
						<text @click="isTravel = !isTravel">关闭</text>
					</view>
					<view class="mask-items">
						<view class="mask-item" v-for="(item, index) in travel" :key="index" @click="receive(index)">
							<!-- 领取传经度和纬度 -->
		<!-- <view class="left">
								<view class="tit">{{ item.title }}</view>
								<view class="phone">
									<uni-icons type="phone" size="16"></uni-icons>
									{{ item.phone }}
								</view>
								<view class="address">
									<uni-icons type="location" size="16"></uni-icons>
									{{ item.address }}
								</view>
							</view>
							<view class="right">立即领取</view>
						</view>
					</view>
					<view class="mask-botton" @click="navToList('./lineList')"><text>更多旅行社</text></view>
				</view>
			</view> -->

		<!-- </uni-transition> -->
		<tfgg-verify @result="verifyResult" ref="verifyElement"></tfgg-verify>
		<progress-bar :progress_txt="progress_txt" v-if="progress_txt.is_show"
			@success="progressSuccess"></progress-bar>
	</view>
</template>

<script>
	import {
		mapState,
		mapMutations
	} from 'vuex';
	import {
		dateTime,
		pay,
		getLocation
	} from '@/common/common.js';
	import {
		tfggVerify
	} from '@/components/tfgg-verify/tfgg-verify.vue';
	import {
		progressBar
	} from '@/components/chocolate-progress-bar/chocolate-progress-bar.vue';
	export default {
		components: {
			tfggVerify,
			progressBar
		},
		data() {
			return {
				info: {},
				number: 0,
				id: 0,
				latitude: null,
				longitude: null,
				suitable: {},
				buttonGroup: [{
					text: '立即领取',
					backgroundColor: 'linear-gradient(90deg, #FE6035, #EF1224)',
					color: '#fff',
					code: 2
				}],
				lineListArray: null,
				SliderVerifyButton: {
					option: null
				},
				progress_txt: {
					number: 10,
					is_show: false,
					txt: '排队中...'
				},
				receiveSetTimeout: null,
				coupon_array: [],
				canReceive: false, // 需要做任务的券是否可领
			};
		},
		// #ifdef MP-WEIXIN
		onShareAppMessage(res) {
			//微信小程序分享给朋友
			return {
				title: '榆林市旅游消费平台',
				path: `/pages/coupon/coupon?id=${this.id}`
			};
		},
		onShareTimeline(res) {
			//微信小程序分享朋友圈
			return {
				title: '榆林市旅游消费平台',
				path: `/pages/coupon/coupon?id=${this.id}`
			};
		},
		// #endif
		onLoad(option) {
			clearTimeout(this.receiveSetTimeout);
			let that = this;
			if (uni.getStorageSync('coupon_id')) {
				this.coupon_array = uni.getStorageSync('coupon_id');
			}
			this.id = option.id;

			getLocation().then(
				success => {
					that.latitude = success.latitude;
					that.longitude = success.longitude;
					that.init();
				},
				fail => {
					// 失败
				}
			);
		},
		onUnload() {
			clearTimeout(this.receiveSetTimeout);
		},
		computed: {
			...mapState(['hasLogin', 'uerInfo'])
		},
		methods: {
			init() {
				this.$api
					.httpRequest(
						`/coupon/detail`, {
							couponId: this.id,
							userid: this.uerInfo.uid
						},
						'POST'
					)
					.then(res => {
						if (res.code == 0) {
							//查询缓存里得数据
							if (res.data.is_limit_total != 0 && this.coupon_array) {
								this.coupon_array.forEach(item => {
									if (item == this.id) {
										this.$set(this.buttonGroup[0], 'text', '已领取');
										this.$set(this.buttonGroup[0], 'backgroundColor', '#b1b1b1');
										this.$set(this.buttonGroup[0], 'code', -2);
									}
								});
							}

							if (!!Number(res.data.sale_price)) {
								this.$set(this.buttonGroup[0], 'text', '立即购买');
								this.$set(this.buttonGroup[0], 'code', 1);
							}
							//按钮数据
							// if (!!res.data.used) {
							// 	// res.data.used = null; 没领
							// 	if (res.data.is_limit_total == 0) {
							// 		//优惠券多次领取
							// 		if (Number(res.data.sale_price)) {
							// 			//多次购买
							// 			this.$set(this.buttonGroup[0], 'text', '继续购买');
							// 			this.$set(this.buttonGroup[0], 'code', -1);
							// 		} else {
							// 			//多次领取
							// 			this.$set(this.buttonGroup[0], 'text', '继续领取');
							// 			this.$set(this.buttonGroup[0], 'code', 3);
							// 		}
							// 	} else {
							// 		this.$set(this.buttonGroup[0], 'text', '已领取');
							// 		this.$set(this.buttonGroup[0], 'code', 4);
							// 	}
							// }
							//按钮数据end
							this.canReceive = res.data.can_receive; // 需要做任务的券是否可领
							// 判断是否线上核销，需要完成任务
							if (res.data.use_type == 1) {
								// 判断是否已经领取任务
								// if (this.canReceive) {
								// 	this.$set(this.buttonGroup[0], 'text', '立即领取');
								// } else {
								// 	this.$set(this.buttonGroup[0], 'text', '去打卡');
								// }
								this.buttonGroup = [{
										text: '立即领取',
										backgroundColor: 'linear-gradient(90deg, #FE6035, #EF1224)',
										code: 5
									},
									{
										text: '去打卡',
										backgroundColor: 'linear-gradient(90deg, #FE6035, #EF1224)',
										code: 6
									}
								]
								// this.$set(this.buttonGroup[0], 'text', '立即领取');
								// this.$set(this.buttonGroup[0], 'backgroundColor',
								// 	'linear-gradient(90deg, #FE6035, #EF1224)');
								// this.$set(this.buttonGroup[0], 'code', 5);

								// this.$set(this.buttonGroup[1], 'text', '去打卡');
								// this.$set(this.buttonGroup[1], 'backgroundColor',
								// 	'linear-gradient(90deg, #FE6035, #EF1224)');
								// this.$set(this.buttonGroup[1], 'code', 5);
							}
							// 进度条数据
							if (res.data.remain_count == 0) {
								this.number = 100;
								this.$set(this.buttonGroup[0], 'text', '已抢完');
								this.$set(this.buttonGroup[0], 'backgroundColor', '#b1b1b1');
								this.$set(this.buttonGroup[0], 'code', -2);
							} else {
								let number = (res.data.remain_count / res.data.total_count) * 100;
								number = 100 - number;
								number == 0 ? (this.number = 0.0) : (this.number = number);
								this.number = Number(number.toFixed(2));
							}
							// 进度条数据end

							if (res.data.coupon_time_start != 0 && res.data.coupon_time_end != 0) {
								res.data.coupon_time_start = dateTime(res.data.coupon_time_start);
								res.data.coupon_time_end = dateTime(res.data.coupon_time_end);
							}
							this.info = res.data;
							this.info.titleClass = res.data.couponClass.title;
							this.applicableto();
							if (res.data.cid == 3) {
								this.lineList();
							}
						} else {
							this.$api.msg(res.msg, 'none');
						}
					});
			},
			navToList(url) {
				uni.navigateTo({
					url: url
				});
			},
			receive(id = null) {
				let couponId = null;
				// if (id != null) {
				// 	//旅行社领取
				// 	couponId = id;
				// } else {
				// 正常领取
				couponId = this.info.id;
				if (this.info.status == 2) {
					this.$api.msg('已领完!', 'error');
					return false;
				}
				if (this.info.status == 3) {
					this.$api.msg('您已领取过了', 'error');
					return false;
				}
				// }

				this.$api
					.httpRequest(
						`/coupon/receive`, {
							userid: this.uerInfo.uid,
							couponId: couponId,
							latitude: this.latitude,
							longitude: this.longitude
						},
						'POST'
					)
					.then(res => {
						if (res.code == 0) {
							uni.hideLoading();
							this.$api.msg(res.msg, 'success');
							this.$set(this.buttonGroup[0], 'text', '已领取');
							this.$set(this.buttonGroup[0], 'backgroundColor', '#b1b1b1');
							this.$set(this.buttonGroup[0], 'code', -2);
							this.coupon_array.push(this.id);
							uni.setStorageSync('coupon_id', this.coupon_array);
							setTimeout(() => {
								this.init();
							}, 2500);
						} else if (res.code == 1) {
							uni.showModal({
								title: '提示',
								content: res.msg,
								showCancel: false,
								success(r) {
									if (r.confirm) {
										if (res.msg.indexOf('您还没有认证！暂时无法领取！') == 0) {
											uni.navigateTo({
												url: '/pages/user/attestation'
											});
											return false;
										}
									}
								}
							});
						} else if (res.code == 2) {
							//排队
							this.$set(this.progress_txt, 'is_show', true);
							this.$set(this.progress_txt, 'txt', res.msg);
						} else if (res.code == 998) {
							//计时器
							clearTimeout(this.receiveSetTimeout);
							uni.showLoading({
								title: res.msg
							});
							this.receiveSetTimeout = setTimeout(() => {
								uni.hideLoading();
								this.receive();
							}, 5 * 1000);
						} else {
							uni.showModal({
								title: '提示',
								content: res.msg,
								showCancel: false
							});
						}
					});
			},
			progressSuccess(e) {
				this.$set(this.progress_txt, 'is_show', e.is_show);
				const that = this;
				that.receive();
			},
			applicableto() {
				this.$api
					.httpRequest(
						`/coupon/applicabletoV2`, {
							id: this.id,
							latitude: this.latitude,
							longitude: this.longitude
						},
						'POST'
					)
					.then(res => {
						if (res.code == 0) {
							if (res.data.length != 0) {
								this.$set(this.suitable, 'nickname', `${res.data[0].nickname}`);
								if (!!res.data[0].distance) {
									this.$set(this.suitable, 'distance', `${res.data[0].distance.toFixed(2)}km`);
								} else {
									this.suitable.distance = 0;
								}
							} else {
								this.$set(this.suitable, 'nickname', '暂无商家');
								this.$set(this.suitable, 'distance', '');
							}
						} else {
							this.$api.msg(res.msg, 'none');
						}
					});
			},
			lineList() {
				const couponId = this.id;
				this.$api
					.httpRequest(
						`/coupon/line_list`, {
							couponId,
							flag: 1,
							limit: 1,
							page: 1
						},
						'POST'
					)
					.then(res => {
						if (res.code == 0) {
							this.lineListArray = res.data[0];
						} else {
							this.$api.msg(res.msg, 'none');
						}
					});
			},
			verifyResult() {
				uni.showLoading({
					title: '加载中...'
				});
				this.submitButton();
			},
			// successHandle() {
			// 	this.submitButton();
			// },
			buttonClick(e) {
				console.log(e)
				let that = this;
				this.SliderVerifyButton.option = e;
				if (e.content.code == -2) {
					return false;
				}
				if (e.content.code == 4) {
					this.submitButton();
					return false;
				}
				if (e.content.code == 5) {
					// 判断是否可领取
					if (that.canReceive) {
						// 领取礼包
						uni.showModal({
							title: '提示',
							content: '是否领取' + that.info.coupon_title + ',大礼包只可领取一次，是否立即领取？',
							confirmText: '立即领取',
							cancelText: '暂不领取',
							success: function(res) {
								if (res.confirm) {
									that.receive();
								} else if (res.cancel) {
									console.log('用户点击取消');
								}
							}
						})
					} else {
						// 领取礼包
						uni.showModal({
							title: '提示',
							content: '未达到领取条件！',
							showCancel: false
						})
					}
				}
				if (e.content.code == 6) {
					//跳转到打卡页面
					uni.navigateTo({
						url: '/pages/user/task/detail?couponId=' + that.id + "&couponTitle=" + that.info.coupon_title
					});
				}
				// ajax 允许弹窗的时候在回调中调用，不允许弹窗的时候直接调用
				this.$api.httpRequest(`/index/system`, {}, 'POST').then(res => {
					if (res.code == 0) {
						this.progress_txt.number = res.data.is_queue_number;
						if (res.data.message_code == 1) {
							this.$refs.verifyElement.show();
						} else {
							this.submitButton();
						}
					} else {
						this.$api.msg(res.msg, 'none');
					}
				});
			},
			submitButton() {
				let that = this;
				const e = that.SliderVerifyButton.option;
				if (this.info.is_get == 0) {
					uni.showModal({
						title: '提示',
						content: this.info.tips,
						confirmText: '适用商家',
						success: function(res) {
							if (res.confirm) {
								uni.navigateTo({
									url: '/pages/coupon/list?id=' + that.id
								});
							} else if (res.cancel) {
								console.log('用户点击取消');
							}
						}
					});
					return false;
				}

				// code -1 重新支付, 1 立即支付 ,2立即领取，3已领取重复领取 4已领取

				if ((e.content.code == 1 || e.content.code == -1) && e.index == 0 && e.money != 0) {
					// 购买
					uni.showLoading({
						title: '正在支付...'
					});
					this.CouponPay();
					return false;
				}

				if (e.content.code == 2 && e.index == 0) {
					this.receive();
					return false;
					// 正常领取
				}

				// if (e.content.code == 4 && e.index == 0) {
				// 	//已领取
				// 	uni.navigateTo({
				// 		url: '/pages/user/order?state=0'
				// 	});
				// }
			},
			CouponPay() {
				// this.$api.msg('支付功能正在开发中...请耐心等待!', 'none');
				// return false;
				if (this.info.status == 2) {
					this.$api.msg('优惠券已领完!', 'none');
					return false;
				}
				if (this.info.status == 3) {
					this.$api.msg('您已经领取过了!', 'none');
					return false;
				}

				this.$api
					.httpRequest(
						`/pay/submit`, {
							uid: this.uerInfo.uid,
							openid: this.uerInfo.openid,
							coupon_uuno: this.info.uuno,
							data: JSON.stringify({
								uuno: this.info.uuno,
								number: 1,
								price: this.info.sale_price
							}),
							type: 'miniapp'
						},
						'POST'
					)
					.then(res => {
						pay(res.data.pay)
							.then(success => {
								this.init();
								uni.hideLoading();
								uni.navigateTo({
									url: '/pages/common/paySuccess'
								});
							})
							.catch(err => {
								uni.hideLoading();
								this.$api.msg('支付失败！', 'error');
								this.$set(this.buttonGroup[0], 'text', '继续支付');
								this.$set(this.buttonGroup[0], 'code', -1);
							});
					})
					.catch(err => {
						this.$api.msg('支付异常，请联系管理员', 'none');
						uni.hideLoading();
					});
			}
			// address(lang = 38.29088384, long = 109.74161603) {
			// 	uni.showModal({
			// 		title: '领取成功!',
			// 		content: '是否导航到该商家',
			// 		success(res) {
			// 			if (res.confirm) {
			// 				uni.openLocation({
			// 					latitude: lang,
			// 					longitude: long,
			// 					success: function() {
			// 						console.log('success');
			// 					}
			// 				});
			// 			}
			// 		}
			// 	});
			// }
		}
	};
</script>

<style lang="scss">
	page {
		min-height: calc(100vh - calc(44px + env(safe-area-inset-top)));
		font-size: #333333;
	}

	.coupon-box {
		background: linear-gradient(180deg, rgba(174, 0, 13, 1) 0%, rgba(247, 247, 247, 1) 30%, rgba(247, 247, 247, 1) 100%);
		width: 100%;
		padding-top: 100upx;

		& .coupon {
			width: 95%;
			margin: auto;
			margin-bottom: 20upx;
			overflow: hidden;
			background: #ffffff;
			border-radius: 20upx;
			overflow: hidden;

			& .content-coupon {
				width: calc(100% - 40upx);
				margin: auto;
				display: flex;
				align-items: center;
				flex-direction: column;
				padding: 80upx 20upx 20upx;
				background-color: #fff;
				border-bottom: dashed 1upx #dddddd;
				position: relative;

				&::before,
				&::after {
					content: '';
					position: absolute;
					width: 40upx;
					height: 40upx;
					border-radius: 50%;
					background-color: #f7f7f7;
				}

				&::before {
					left: -20upx;
					bottom: -20upx;
				}

				&::after {
					right: -20upx;
					bottom: -20upx;
				}

				& .info {
					color: #fff;
					display: flex;
					height: 60upx;
					line-height: 48upx;
					padding: 0;
					justify-content: center;
					font-size: 24upx;
					position: absolute;
					left: -11upx;
					top: 16upx;
					width: 25%;
					background: none;
					background-image: url('@/static/info.png');
					background-repeat: no-repeat;
					background-size: 100% 100%;
				}

				& .title {
					width: 100%;
					font-size: 34upx;
					text-align: center;
					font-weight: bold;
					padding-bottom: 15upx;
				}

				& .price {
					width: 100%;
					font-size: 40upx;
					font-weight: bold;
					display: flex;
					padding: 20upx 0;
					color: $div-bg-color;
					align-items: center;
					justify-content: center;
				}

				& .button {
					width: 40%;
					height: 80upx;
					margin: 10upx auto 10upx;
					background: none;
					font-weight: bold;
					font-size: 60upx;
					color: $div-bg-color;
					box-shadow: none;
					align-items: flex-end;

					&:before {
						content: '￥';
						font-size: 26upx;
						margin-bottom: 10upx;
					}
				}

				& .buttons {
					width: 100%;
					height: 104upx;
					background-size: contain;
					background-repeat: no-repeat;
					background-position: center;
					background-image: url(data:image/png;base64,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);
				}

				& .progress {
					width: 50%;
					margin: 0 auto 20upx;
				}

				& .area {
					font-size: 22upx;
					color: #828282;
				}
			}

			& .items {
				width: calc(100% - 40upx);
				padding: 0upx 20upx 0upx;
				background-color: #fff;
				margin: auto;

				& .item {
					display: flex;
					padding: 20upx 0;

					& .item-left {
						margin-right: 10upx;
						display: flex;
						align-items: center;
					}

					& .item-rigth {
						color: #a3a3a3;
						display: flex;
						align-items: center;
						font-size: 24upx;
						line-height: 40upx;
					}
				}
			}

			& .info-items {
				border-radius: 0 0 20upx 20upx;
				overflow: hidden;
			}

			& .info {
				width: calc(100% - 40upx);
				padding: 0upx 20upx;
				height: 80upx;
				line-height: 80upx;
				background-color: #fff;
				margin: auto;
				display: flex;
				justify-content: space-between;

				& .left {
					width: 20%;
					font-size: 30upx;
					font-weight: bold;
				}

				& .right {
					width: 80%;
					display: flex;
					justify-content: space-between;
					align-items: center;
					color: #a3a3a3;

					& .name-title {
						width: 100%;
						text-align: right;
						overflow: hidden;
						text-overflow: ellipsis;
						display: -webkit-box;
						-webkit-line-clamp: 1;
						-webkit-box-orient: vertical;
					}

					& .name-icons {
						padding-left: 20upx;
						display: flex;
						justify-content: flex-end;
					}
				}
			}
		}

		& .instructions {
			width: calc(95% - 40upx);
			padding: 20upx;
			border-radius: 20upx;
			margin: 20upx;
			background-color: #fff;

			& .title {
				font-size: 30upx;
				color: #333333;
				margin-bottom: 0upx;
				font-weight: bold;
			}

			& .content {
				line-height: 45upx;
				font-size: 24upx;

				color: #808080;
			}
		}
	}

	.mask-box {
		width: 100%;
		height: 100%;
		position: fixed;
		left: 0;
		top: 0;
		background-color: rgba(0, 0, 0, 0.6);

		& .mask {
			width: 100%;
			border-radius: 20upx;
			overflow: hidden;
			background-color: #fff;
			height: 70%;
			position: absolute;
			bottom: 0;

			& .mask-tit {
				width: calc(100% - 40upx);
				border-bottom: 1px solid #efefef;
				height: 80upx;
				line-height: 80upx;
				padding: 0 20upx;
				position: absolute;
				top: 0;
				display: flex;
				background-color: #fff;
				justify-content: space-between;

				& text:first-child {
					font-weight: bold;
					font-size: 30upx;
				}

				& text:last-child {
					font-size: 24upx;
					color: #5f5f5f;
				}
			}

			& .mask-items {
				width: 100%;
				height: calc(100% - 220upx);
				position: absolute;
				top: 100upx;

				& .mask-item {
					width: calc(95% - 40upx);
					padding: 20upx;
					margin: auto;
					display: flex;
					justify-content: space-between;
					align-items: center;
					border-radius: 20upx;
					box-shadow: 9.5px 4.3px 10px rgba(0, 0, 0, 0.035), 60px 27px 80px rgba(0, 0, 0, 0.07);
					border: 1px solid #eaeaea78;
					margin-bottom: 20upx;

					& .left {
						width: 80%;

						& .tit {
							font-size: 30upx;
							font-weight: bold;
							margin-bottom: 10upx;
							color: #333333;
						}

						& view {
							color: #808080;
						}
					}

					& .right {
						width: 20%;
						display: flex;
						justify-content: center;
						height: 50upx;
						align-items: center;
						background-color: $div-bg-color;
						border-radius: 20upx;
						color: #fff;
					}
				}
			}

			& .mask-botton {
				width: 100%;
				position: absolute;
				bottom: 0;
				height: 120upx;
				line-height: 120upx;

				& text {
					width: calc(60% - 40upx);
					margin: auto;
					position: absolute;
					padding: 0 20upx;
					height: 80upx;
					line-height: 80upx;
					bottom: 20upx;
					text-align: center;
					left: 20%;
					background-color: $div-bg-color;
					color: #fff;
					border-radius: 50upx;
				}
			}
		}
	}

	/* 下面我们会解释这些 class 是做什么的 */
	.v-enter-active,
	.v-leave-active {
		transition: opacity 2s ease;
	}

	.v-enter-from,
	.v-leave-to {
		opacity: 0;
	}
</style>