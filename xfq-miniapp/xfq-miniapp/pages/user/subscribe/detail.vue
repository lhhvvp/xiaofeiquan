<template>
	<view class="order-detail">
		<view class="status">{{detail.status_text || '-'}}</view>
		<view class="status_text"></view>
		<view class="item">
			<view class="price-box">
				<view class="price-tit">预约详情</view>
				<!-- 	<view class="price">
					{{detail.fullname || '-'}}
				</view> -->
			</view>
			<view>
				<view class="product">
					<view class="tit">预约人数</view>
					<view class="number">x{{detail.number || ''}}</text>
					</view>
				</view>

				<view class="content-box">
					<view class="label">预约人</view>
					<view class="content">{{detail.fullname || ''}}</view>
				</view>

				<view class="content-box">
					<view class="label">手机号</view>
					<view class="content">{{detail.phone || ''}}</view>
				</view>
				
				<view class="qrcode" v-if="detail.status == 0">
					<!-- <image v-if="!isQrcode && info.status == 1" src="/static/001.png"></image>
					<image v-if="!isQrcode && info.status == 2" src="/static/002.png"></image> -->
					<uqrcode v-if="detail.qrcode_str" ref="qrcode" canvas-id="qrcode" :start="false" :size="150" :value="codeVal">
					</uqrcode>
				</view>

				<view class="content-box">
					<view class="label">预约编号</view>
					<view class="content" style="width: 70%;">{{detail.code || '-'}}</view>
					<view style="width: 10%;text-align: center;color: #bfbfbf;" @click="copy()">复制</view>
				</view>
				
				<view class="content-box">
					<view class="label">有效期</view>
					<view class="content">{{detail.start || '-'}} - {{detail.time_end_text || ''}}（仅限当天使用）</view>
				</view>
			</view>
		</view>
		
		<view class="item">
			<view class="price-box">
				<view class="price-tit">游客列表</view>
			</view>
			<view>
				<view class="product" v-for="(item,index) in detail.tourist_list" :key="index" style="padding: 20upx 0;">
					<view class="tit">
						<view class="name" style="margin-bottom: 8upx;">
							<text
								style="display: inline-block;margin-right: 24upx;">{{item.tourist_mobile || '-'}}</text>
							{{item.tourist_fullname || '-'}}
						</view>
						<view class="status-name">身份证号：{{item.tourist_cert_id}}<text
								style="display: inline-block;margin-left: 10upx;"></text> </view>
					</view>
					<view class="number" v-if="item.refund_progress != 'approved'">
						<view @click.stop="refunded(item.out_trade_no,true)"
							v-if="detail.order_status == 'paid' && is_status(item)">
							<image src="/static/icon/refund-icon.png" style="width: 40upx;height: 40upx;"></image>
							<text>退款</text>
						</view>
						<view @click.stop="qrcodeFun(item)" style="margin-left: 24upx;"
							v-if="detail.order_status == 'paid' && item.refund_progress == 'init' && is_status(item)">
							<image src="/static/icon/qr_1.png" style="width: 40upx;height: 40upx;"></image>
							<text>核销码</text>
						</view>
					</view>
				</view>
			</view>
		</view>

		<!-- <view class="item">
			<view class="price-box">
				<view class="price-tit">游客列表</view>
			</view>
			<view>
				<view class="product" v-for="(item,index) in detail.detail_list"
					@click="qrcodeFun(item,item.qrcode_str,item.id)">
					<view class="tit">{{item.tourist_fullname || '-'}} - {{item.tourist_mobile || '-'}}</view>
					<view class="number">
						<image src="/static/icon/qr_1.png" style="width: 40upx;height: 40upx;"></image>
					</view>
				</view>
			</view>
		</view> -->

		<view class="line" style="height: 120upx;"></view>
		<view class="button-box" v-if="detail.status == 0">
			<view class="buttons" @click="cancel()">取消预约</view>
		</view>
	</view>
</template>

<script>
	import {
		mapState,
		mapMutations
	} from 'vuex';
	import {
		getLocation
	} from "@/common/common.js"
	export default {
		data() {
			return {
				id: null,
				detail: {},
				use_lat: null,
				use_lng: null,
				codeVal:null,
				
			};
		},

		onLoad(options) {
			this.id = options.id
			getLocation().then(res => {
				this.use_lat = res.latitude;
				this.use_lng = res.longitude;
				this.init();
			})
		},

		onShow() {},

		computed: {
			...mapState(['uerInfo']),
		},
		methods: {
			...mapMutations([]),
			copy() {
				uni.setClipboardData({
					data: this.detail.code,
				});
			},
			init() {
				this.$api
					.httpRequest(
						`/appt/getDetail`, {
							id: this.id,
						},
						'GET'
					)
					.then(res => {
						this.detail = res.data;
						if (res.data.status == 0) {
							this.codeVal = JSON.stringify({
								qrcode_str: res.data.qrcode_str,
								be_type: 'all',
								be_id: res.data.id,
								use_lat :this.use_lat,
								use_lng :this.use_lng,
								type: 'subscribe'
							});
							setTimeout(() => {
								this.$refs.qrcode.make();
							}, 1500)
						}

					})
			},
			navto() {
				uni.navigateTo({
					url: "/pages/user/commentAdd?order_id=" + this.detail.id
				})
			},

			cancel() {
				this.$api
					.httpRequest(
						`/appt/cancelAppt`, {
							log_id: this.detail.id,
						},
						'POST'
					)
					.then(res => {
						let that = this;
						if (res.code == 0) {
							that.$api.msg(res.msg, 'success');
							setTimeout(() => {
								uni.navigateBack()
							}, 2500)
						} else {
							that.$api.msg(res.msg);
						}
					})
			},
			
		}
	};
</script>

<style lang="scss">
	page {
		font-size: 28upx;
	}

	.qrcode {
		width: 100%;
		padding: 80upx 0;
		display: flex;
		align-items: center;
		justify-content: center;
	};

	.is-user-qr-code {
		width: 100%;
		height: 100vh;
		display: flex;
		align-items: center;
		justify-content: center;
		position: fixed;
		left: 0;
		top: 0;
		z-index: 999;
		background-color: #33333380;
	}

	.order-detail {
		width: 100%;
		background: linear-gradient(180deg, rgba(174, 0, 13, 1) 0%, rgba(247, 247, 247, 1) 30%, rgba(247, 247, 247, 1) 100%);
		.status {
			width: calc(95% - 20upx);
			padding: 50upx 20upx 0;
			margin: auto;
			font-size: 38upx;
			font-weight: bold;
			color: #ffffff;
		}

		.status_text {
			width: calc(95% - 20upx);
			padding: 6upx 20upx 0;
			margin: auto;
			font-size: 24upx;
			color: #868686;
			margin-bottom: 30upx;
		}

		.item {
			background-color: #ffffff;
			width: calc(95% - 40upx);
			margin: auto;
			border-radius: 20upx;
			padding: 20upx;
			margin-bottom: 20upx;

			.price-box {
				padding: 0 20upx 20upx 0;
				width: 100%;
				display: flex;
				justify-content: space-between;
				font-size: 34upx;
				font-weight: bold;
				border-bottom: 1upx solid #f7f7f7;
			}

			.product {
				padding: 20upx 0;
				width: 100%;
				display: flex;
				align-items: center;
				justify-content: space-between;

				.tit {
					font-size: 30upx;
				}
			}

			.content-box {
				padding: 0 0 20upx 0;
				width: 100%;
				display: flex;
				justify-content: space-between;

				.label {
					width: 20%;
					color: #868686;
				}

				.content {
					width: 80%;
				}
			}

		}

		.button-box {
			width: calc(100% - 40upx);
			height: 120upx;
			padding: 0 20upx;
			background-color: #ffffff;
			position: fixed;
			bottom: 0;
			display: flex;
			align-items: center;
			justify-content: flex-end;

			.pay-button {
				background-color: $div-bg-color;
				padding: 16upx 80upx;
				color: #ffffff;
				border-radius: 20upx;
				margin-left: 20upx;
			}

			.buttons {
				border: 1upx solid #adadad;
				padding: 16upx 70upx;
				color: #333333;
				border-radius: 20upx;
				margin-left: 20upx;
			}

			.buttons:last-child {
				margin-left: 0;
			}
		}
	}
	.item {
		background-color: #ffffff;
		width: calc(95% - 40upx);
		margin: auto;
		border-radius: 20upx;
		padding: 20upx;
		margin-bottom: 20upx;
	
		.price-box {
			padding: 0 20upx 20upx 0;
			width: 100%;
			display: flex;
			justify-content: space-between;
			font-size: 34upx;
			font-weight: bold;
			border-bottom: 1upx solid #f7f7f7;
	
			.price {
				color: $div-bg-color;
	
				&:before {
					content: "￥";
					color: $div-bg-color;
					font-size: 20upx;
				}
			}
		}
	
		.product {
			padding: 30upx 0;
			width: 100%;
			display: flex;
			align-items: center;
			justify-content: space-between;
	
			.tit {
				font-size: 30upx;
				display: flex;
				flex-direction: column;
	
				.status-name,
				.refund-name {
					font-size: 24upx;
					color: #c7c7c7;
				}
			}
	
			.number {
				width: 18%;
				display: flex;
				justify-content: flex-end;
	
				view {
					display: flex;
					flex-direction: column;
					justify-content: center;
					align-items: center;
	
					image {
						padding-bottom: 6rpx;
					}
				}
	
				text {
					font-size: 20upx;
					color: #d2d2d2;
				}
			}
		}
	
		.content-box {
			padding: 0 0 20upx 0;
			width: 100%;
			display: flex;
			justify-content: space-between;
	
			.label {
				width: 20%;
				color: #868686;
			}
	
			.content {
				width: 70%;
			}
		}
	
		.content-box:last-child {
			padding: 0;
		}
	
	}
</style>