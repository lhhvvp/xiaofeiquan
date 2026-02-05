<template>
	<view class="coupon-cav">
		<view class="mask-box" v-if="is_mask">
			<view class="mask">
				<view class="title">{{ statusName }}</view>
				<view class="status-img">
					<image src="@/static/success.png" v-if="isStatus == 1"></image>
					<image src="@/static/error.png" v-if="isStatus == 0"></image>
				</view>
				<view class="button-status" @click="down">确定</view>
			</view>
		</view>
		<view class="box">
			<view class="title-box">
				<view class="title">商户核销</view>
				<uni-icons type="right" color="#c0c0c0"></uni-icons>
			</view>
			<view class="coupon-box">
				<view class="title">{{ info.coupon_title }}</view>
				<view class="price">{{ info.coupon_price }}</view>
				<view class="type">
					核销人数
					<text
						style="color: #a00000;font-weight: bold;display: inline-block;padding: 0 6upx;">{{ number }}</text>
					人
				</view>
			</view>
			<!-- <view class="button-in">{{ statusName }}</view> -->
			<view class="content-info">
				<view class="time-box">
					<view class="title">优惠券有效期：</view>
					<view class="info">{{ time }}</view>
				</view>

				<view class="time-box">
					<view class="title">旅行团名称：</view>
					<view class="info">{{ tour.name }}</view>
				</view>

				<view class="time-box">
					<view class="title">旅行社名称：</view>
					<view class="info">{{ seller.nickname }}</view>
				</view>

				<view class="time-box">
					<view class="title">旅行团团期：</view>
					<view class="info">{{ tour.term }}</view>
				</view>

				<!-- <view class="time-box" v-if="tour_write_off[0].create_time == null || tour_write_off[0].create_time == '' || tour_write_off[0].create_time == undefined"> -->
				<!-- <view class="title">核销时间：</view> -->
				<!-- <view class="info">{{ tour_write_off[0].create_time }}</view> -->
				<!-- <view class="info">{{ new Date().toLocaleDateString() }}</view> -->

				<!-- </view> -->

				<view class="info-box">
					<view class="title">使用说明：</view>
					<view class="info"><rich-text :nodes="info.remark"></rich-text></view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import {
		mapState,
		mapMutations
	} from 'vuex';
	import {
		dateTime,
		getLocation
	} from '@/common/common.js';
	export default {
		data() {
			return {
				info: {},
				statusName: '请等候',
				mid: 0,
				qrcode_url: null,
				id: null,
				type: null,
				is_mask: true,
				isStatus: null,
				number: 0, //核销人数信息
				time: '', //有效时间
				tour: {}, //旅行团
				tour_write_off: [],
				seller: {}, //旅行社，
				coord: {
					latitude: 0,
					longitude: 0
				},
				latitude: null,
				longitude: null,
			};
		},
		onLoad(option) {
			if (option.qrcode_url == 'undefined' || option.id == 'undefined') {
				this.$api.msg('二维码异常！请重试','none');
				setTimeout(() => {
					uni.navigateBack();
				}, 2000)
				return false;
			}
			this.id = option.id;
			this.mid = option.mid;
			this.qrcode_url = option.qrcode_url;
			this.type = option.type;
			this.coord = JSON.parse(option.coord);
			if (!option.id && !option.type) {
				this.$api.msg('参数错误', 'none');
				setTimeout(() => {
					uni.navigateBack();
				}, 2000);
				return false;
			};
			getLocation(false).then(res => {
				this.latitude = res.latitude;
				this.longitude = res.longitude;
				this.init();
			})
		},
		computed: {
			...mapState(['uerInfo'])
		},
		methods: {
			init(info) {
				this.$api
					.httpRequest(
						`/user/tour_coupon_group`, {
							id: this.id //领取id
						},
						'POST'
					)
					.then(res => {
						let that = this;
						if (res.code == 0) {
							this.info = res.data.couponIssue;
							this.number = res.data.tourist.length;
							this.tour = res.data.tour;
							this.tour_write_off = res.data.tour_write_off;
							this.seller = res.data.seller;
							if (this.info.is_permanent == 1) {
								this.time = `有效期：永久`;
							} else if (this.info.is_permanent == 2) {
								this.time = `有效期：${this.info.coupon_time_start != 0 ? dateTime(this.info.coupon_time_start) : '-'} 至 ${
								this.info.coupon_time_end != 0 ? dateTime(this.info.coupon_time_end) : '-'
							}`;
							} else {
								this.time = `有效期：${this.info.day}天`;
							}
							uni.showLoading({
								title: '核销中',
								success(res) {
									if (res.errMsg === 'showLoading:ok') {
										that.receive();
									}
								}
							});
							// ajax
						} else {
							this.$api.msg(res.msg, 'none');
							setTimeout(() => {
								uni.navigateBack();
							}, 2000);
						}
					});
			},
			receive() {
				if (!this.latitude && !this.longitude) {
					this.$api.msg('定位获取失败，请稍后重试', 'none');
					return false;
				}
				this.$api
					.httpRequest(
						`/user/writeoff_tour`, {
							userid: this.uerInfo.uid, //用户ID
							mid: this.mid, //商户ID
							coupon_issue_user_id: this.id, //消费券领取记录ID
							// use_min_price: this.info.coupon_price, //订单消费金额
							use_min_price: 999999, //订单消费金额
							qrcode_url: this.qrcode_url,
							orderid: 0, //订单ID 暂无
							latitude: this.coord.latitude, //经纬度
							longitude: this.coord.longitude, //经纬度
							vr_latitude: this.latitude,
							vr_longitude: this.longitude
						},
						'POST'
					)
					.then(res => {
						this.statusName = res.msg;
						if (res.code == 0) {
							this.isStatus = 1;
						} else {
							this.isStatus = 0;
						}
						uni.hideLoading();
					});
			},
			down() {
				this.is_mask = !this.is_mask;
			}
		}
	};
</script>

<style lang="scss">
	page {
		background-color: $div-color;

		.color-666 {
			background-color: #666;
			color: #fff;
		}

		& .coupon-cav {
			width: 95%;
			margin: auto;
			padding-top: 150upx;

			& .mask-box {
				width: 100%;
				height: 100%;
				position: fixed;
				left: 0;
				bottom: 0;
				background-color: rgba(0, 0, 0, 0.5);
				z-index: 99;

				& .mask {
					width: 60%;
					overflow: hidden;
					border-radius: 20upx;
					background-color: #fff;
					margin: auto;
					top: 25%;
					left: 20%;
					position: fixed;
					z-index: 100;

					& .title {
						width: 90%;
						padding: 5%;
						text-align: center;
						font-weight: bold;
						font-size: 30rpx;
						border-bottom: 1px solid #f1f1f1;
						margin-bottom: 5upx;
					}

					& .status-img {
						width: 100%;
						height: 355rpx;

						& image {
							width: 100%;
							height: 100%;
						}
					}

					& .title-status {
						width: 100%;
						text-align: center;
						padding-bottom: 10upx;
					}

					& .button-status {
						height: 90upx;
						line-height: 90upx;
						text-align: center;
						width: 100%;
						font-weight: bold;
						font-size: 30upx;
						border-top: 1px solid #f1f1f1;
						background: #f7f7f7;
						color: $div-bg-color;
					}
				}
			}

			& .box {
				width: calc(100% - 40upx);
				padding: 20upx;
				border-radius: 20upx;
				background-color: #ffffff;
				position: relative;

				&::before {
					content: '';
					position: absolute;
					width: 30upx;
					height: 30upx;
					left: -15upx;
					top: calc(50% - 15upx);
					border-radius: 50%;
					background-color: $div-color;
				}

				&::after {
					content: '';
					position: absolute;
					width: 30upx;
					height: 30upx;
					right: -15upx;
					top: calc(50% - 15upx);
					border-radius: 50%;
					background-color: $div-color;
				}
			}

			& .title-box {
				display: flex;
				width: 100%;
				align-items: center;
				justify-content: space-between;

				& .title {
					width: 95%;
					margin: auto;
					height: 80upx;
					border-bottom: 1upx dashed #c7c7c7;
					font-size: 34upx;
					font-weight: bold;
					display: flex;
					align-items: center;
				}
			}

			& .coupon-box {
				width: 100%;
				padding-top: 60upx;
				padding-bottom: 20upx;
				display: flex;
				justify-content: center;
				flex-direction: column;
				text-align: center;

				& .title {
					font-size: 40upx;
					font-weight: bold;
				}

				& .price {
					padding-top: 10upx;
					font-size: 80upx;
					position: relative;
					color: $div-color;
					font-weight: bold;

					&::after {
						content: '元';
						font-size: 20upx;
					}
				}

				& .type {
					color: #333;
					font-size: 34rpx;
					line-height: 110rpx;
				}
			}

			& .button-in {
				margin: auto;
				width: 60%;
				height: 50upx;
				background-color: $div-color;
				display: flex;
				font-size: 24upx;
				align-items: center;
				justify-content: center;
				color: #ffffff;
				border-radius: 20upx;
				padding: 10upx 0;
				margin-bottom: 30upx;
			}

			& .content-info {
				width: 95%;
				margin: auto;
				border-top: 1upx dashed #c7c7c7;

				& .time-box {
					padding: 0 0 26upx;
					display: flex;
					align-items: center;
					justify-content: space-between;

					& .info,
					& .title {
						color: #999999;
					}
				}

				& .time-box:first-child {
					padding-top: 15upx;
				}

				& .time-box:last-child {
					padding-bottom: 10upx;
				}

				& .info-box {
					& .title {
						border-top: 1rpx dashed #c7c7c7;
						font-weight: bold;
						padding: 10upx 0;
						font-size: 28upx;
					}

					& .info {
						color: #999999;
					}
				}
			}
		}
	}
</style>