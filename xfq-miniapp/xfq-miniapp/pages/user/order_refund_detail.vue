<template>
	<view class="order-detail">
		<!-- <view class="status">{{detail.info_order_detail[0].refund_progress_text || '-'}}</view> -->
		<view class="status">{{detail.status_text || '-'}}</view>
		<view class="status_text"></view>

		<view class="item" v-if="detail.refuse_desc">
			<view class="price-box">
				<view class="price-tit">拒绝理由</view>
			</view>
			<view>
				<view class="product">
					<view class="tit">{{detail.refuse_desc || '-'}}</view>
				</view>
			</view>
		</view>

		<view class="item">
			<view class="price-box">
				<view class="price-tit">退款金额</view>
				<view class="price">
					{{detail.refund_fee || '-'}}
				</view>
			</view>
			<view>
				<view class="product">
					<view class="tit">景区名称：{{detail.info_seller.nickname || '-'}}</view>
				</view>

				<view class="content-box">
					<view class="label">订单金额</view>
					<view class="content" style="width: 80%;">{{detail.refund_fee}}元</view>
				</view>

				<view class="content-box">
					<view class="label">退款时间</view>
					<view class="content" style="width: 80%;">{{detail.create_time || '-'}}</view>
				</view>

				<view class="content-box">
					<view class="label">订单号</view>
					<view class="content" style="width: 70%;">{{detail.trade_no || '-'}}</view>
					<view style="width: 10%;text-align: center;color: #bfbfbf;" @click="copy()">复制</view>
				</view>
			</view>
		</view>

		<view class="item" v-if="detail.info_order_detail != null">
			<view class="price-box">
				<view class="price-tit">退款门票信息</view>
			</view>
			<view style="padding-bottom: 15upx;border-bottom: 1upx solid #f7f7f7;"
				v-for="(item,index) in detail.info_order_detail">
				<view class="content-box" style="padding: 10upx 0;">
					<view class="label">姓名：</view>
					<view class="content" style="width: 80%;display: flex;justify-content: space-between;">
						<text>{{item.tourist_fullname || '-'}}</text> <text style="color: #bfbfbf;"
							v-if="false">{{item.refund_progress_text}}</text>
					</view>
				</view>
				<view class="content-box" style="padding-bottom: 10upx;">
					<view class="label">手机号：</view>
					<view class="content" style="width: 80%;">{{item.tourist_mobile || '-'}}</view>
				</view>
				<view class="content-box">
					<view class="label">身份证号：</view>
					<view class="content" style="width: 80%;">{{item.tourist_cert_id || '-'}}</view>
				</view>
			</view>

		</view>

		<view class="line" style="height: 120upx;"></view>
		
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
				detail: {},
			};
		},

		onLoad(options) {
			this.id = options.id;
			this.init()
		},
		onShow() {

		},

		computed: {
			...mapState(['uerInfo', 'order_refund_detail']),
		},
		methods: {
			...mapMutations([]),
			init() {
				this.$api
					.httpRequest(
						`/ticket/getRefundLogDetail`, {
							id: this.id,
						},
						'GET'
					)
					.then(res => {
						this.detail = res.data;
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
		padding: 60upx 0;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	;

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
			padding: 60upx 20upx 0;
			margin: auto;
			font-size: 40upx;
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
				padding: 20upx 0;
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

	.qrcode-shade {
		position: fixed;
		width: 100%;
		height: 100%;
		top: 0;
		left: 0;
		z-index: 999;
		display: flex;
		align-items: center;
		justify-content: center;
		background-color: #33333380;

		.qrcode {
			background: linear-gradient(180deg, rgba(255, 248, 249, 1.0) 30%, rgba(247, 247, 247, 1) 70%, rgba(247, 247, 247, 1) 100%);
			width: 90%;
			height: 500upx;
			border-radius: 20upx;
			flex-direction: column;
			margin-bottom: 180upx;

			.name {
				font-weight: bold;
				font-size: 36upx;
				margin-top: 20upx;
			}
		}

		.qrcode-info {
			background: #ffffff;
			width: calc(90% - 100upx);
			padding: 50upx 50upx 50upx 50upx;
			border-radius: 20upx;
		}
	}

	/* 样式 */
	.tui-node {
		height: 20upx;
		width: 20upx;
		border-radius: 50%;
		background-color: #ddd;
		display: flex;
		align-items: center;
		justify-content: center;
		color: #fff;
		flex-shrink: 0;
	}

	.tui-order-title {
		padding-bottom: 12rpx;
		font-size: 32rpx;
		color: #333;
		font-weight: bold;
	}

	.tui-order-desc {
		padding-bottom: 12rpx;
		font-size: 28rpx;
		color: #333;
	}

	.tui-order-time {
		font-size: 24rpx;
		font-weight: bold;
	}

	.tui-gray {
		color: #848484 !important;
	}

	.tui-light-gray {
		color: #888 !important;
	}

	.tui-primary {
		color: #5677fc;
	}
</style>