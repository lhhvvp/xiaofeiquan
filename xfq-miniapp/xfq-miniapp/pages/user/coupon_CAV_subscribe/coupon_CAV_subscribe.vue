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
				<view class="title">预约核销</view>
				<uni-icons type="right" color="#c0c0c0"></uni-icons>
			</view>
			<view class="coupon-box">
				<view class="title">{{ info.seller.nickname }}</view>
				<view class="type">
					预约人数
					<text
						style="color: #a00000;font-weight: bold;display: inline-block;padding: 0 6upx;">{{ info.number }}</text>
					人
				</view>
			</view>
			<!-- <view class="button-in">{{ statusName }}</view> -->
			<view class="content-info">
				<view class="time-box">
					<view class="title">联系人：</view>
					<view class="info">{{ info.fullname }}</view>
				</view>

				<view class="time-box">
					<view class="title">手机号：</view>
					<view class="info">{{ info.phone }}</view>
				</view>

				<view class="time-box">
					<view class="title">身份证号：</view>
					<view class="info">{{ info.idcard }}</view>
				</view>

				<view class="time-box">
					<view class="title">有效期：</view>
					<view class="info">{{ info.start }} - {{info.time_end_text}}</view>
				</view>

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
				qrcode_str: null,
				be_id: null,
				use_lat: null,
				use_lng: null,
				statusName: null,
				is_mask: true,
				isStatus: null,
				statusName: "请等候...",

			};
		},
		onLoad(option) {
			if (!option.data) {
				this.$api.msg('参数错误', 'none');
				setTimeout(() => {
					uni.navigateBack();
				}, 2000);
				return false;
			};
			let data = JSON.parse(option.data);
			this.qrcode_str = decodeURIComponent(data.qrcode_str);
			// this.qrcode_str = data.qrcode_str;
			this.be_id = data.be_id;
			this.use_lat = data.use_lat;
			this.use_lng = data.use_lng;
			this.init();
		},
		computed: {
			...mapState(['uerInfo'])
		},
		methods: {
			init(info) {

				this.$api
					.httpRequest(
						`/appt/getDetail`, {
							id: this.be_id,
						},
						'GET'
					)
					.then(res => {
						this.info = res.data;
						this.receive();
					})
			},
			receive() {
				let {
					qrcode_str,
					be_id,
					use_lat,
					use_lng
				} = this;
				// if (!this.latitude && !this.longitude) {
				// 	this.$api.msg('定位获取失败，请稍后重试', 'none');
				// 	return false;
				// }
				this.$api
					.httpRequest(
						`/appt/writeOff`, {
							qrcode_str,
							be_id,
							use_lat,
							use_lng,
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
				if (this.isStatus == 0) {
					uni.navigateBack();
					//失败以后返回上一页
				}
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