<template>
	<view>
		<view class="my-order">

			<view class="item" v-for="(item,index) in list" :key="index" @click="navTo(item)">
				<view class="top">
					<view class="top-left">
						<view class="tit">订单号：{{item.trade_no}}</view>
					</view>
					<view class="top-right">
						<view class="status">{{item.status_text}}</view>
					</view>
				</view>
				<view class="bottom">
					<view class="bottom-right">
						<view class="count-price"
							style="font-weight: bold;font-size: 38upx;margin-bottom: 15upx;display: flex;align-items:center;">
							<text :style="'background-image:url('+item.info_seller.image+')'"
								style="width: 50upx; height: 50upx;border-radius: 10upx;background-size: cover;"></text>
							<text style="display: inline-block;margin-left: 10upx;">{{item.info_seller.nickname}}</text>
						</view>

						<view style="margin: 40rpx 0;">
							<view class="count-price" style="text-align: center;margin: 20upx 0 0;">退款金额：<text
									style="font-size: 30upx;font-weight: bold;font-size: 42upx;">{{item.refund_fee}}</text>
								元</view>
						</view>

						<view class="count-price" v-if="item.info_order_detail && item.info_order_detail.length != 0"
							style="color: #b9b9b9;margin-bottom: 10upx;">
							名字:{{item.info_order_detail.tourist_fullname}}
						</view>
						<view class="count-price" style="color: #b9b9b9;margin-bottom: 10upx;">
							订单总价：{{item.info_order.amount_price}}元</view>
						<view class="count-price" style="color: #b9b9b9;">申请时间：{{item.create_time}}</view>
					</view>
				</view>
			</view>

		</view>
		<!-- 加载loadding -->
		<uni-load-more :status="loadding" v-if="!empty.show"></uni-load-more>
		<!-- end -->
		<!-- 为空页 -->
		<my-empty :empty="empty" v-if="empty.show"></my-empty>
		<!-- end -->
	</view>
</template>

<script>
	import {
		mapState,
		mapMutations
	} from 'vuex';
	export default {
		data() {
			return {
				loadding: 'more',
				loadding_lock: false,
				empty: {
					show: false,
					id: 3
				},
				list: [],
				status: "",
				page: 1,
			};
		},

		onLoad(options) {
			this.init();
		},
		onReachBottom() {
			this.init();
		},

		onShow() {},

		computed: {
			...mapState(['uerInfo']),
		},
		methods: {
			// ...mapMutations(['SetOrderRefundDetail']),
			navTo(val) {
				// this.SetOrderRefundDetail(val)
				uni.navigateTo({
					url: "/pages/user/order_refund_detail?id=" + val.id
				})
			},
			statusName(e, val) {
				switch (e) {
					case 'created':
						return '待支付'
						break;
					case 'paid':
						return '已支付'
						break;
					case 'used':
						return '已使用'
						break;
					case 'refunded':
						return '已退款'
						break;
				}
			},

			init() {
				if (this.loadding == 'no-more') {
					return false;
				};
				this.$api
					.httpRequest(
						`/ticket/getRefundLogList`, {
							page: this.page,
							page_size: 12,
						},
						'GET'
					)
					.then(res => {
						let data = res.data;
						if (data.length == 0) {
							//判断是否有下一页
							this.loadding = 'no-more';
							this.loadding_lock = true;
							return false;
						};
						this.list = [...this.list, ...data];
						if (data.length != 12) {
							this.loadding = 'no-more';
							this.loadding_lock = true;
							return false;
						}
						this.page++;
						this.loadding = 'loading';
					})
			},
		}
	};
</script>

<style lang="scss">
	.my-order {
		width: 100%;
		padding-top: 20upx;

		.item {
			width: calc(95% - 40upx);
			margin: 0 auto 20upx;
			padding: 0 20upx 20upx 20upx;
			background-color: #ffffff;
			border-radius: 20upx;

			.top {
				border-bottom: 1upx solid #f7f7f7;
				padding: 20upx 0;
				width: 100%;
				display: flex;
				justify-content: space-between;

				.top-left {
					width: 70%;
					font-size: 26upx;
				}

				.top-right {
					width: 20%;
					color: #666666;
					text-align: right;
				}
			}

			.bottom {
				padding: 30upx 0;
				width: 100%;
				display: flex;
				justify-content: space-between;

				.bottom-left {
					width: 22%;
					display: flex;
					align-items: center;

					.image {
						width: 100upx;
						height: 100upx;
						background-color: #f7f7f7;
						margin: auto;
						background-size: cover;
						background-position: center center;
						border-radius: 10upx;

					}
				}

				.bottom-right {
					width: 100%;
				}
			}

			.comment {
				width: 100%;
				display: flex;
				justify-content: flex-end;

				.comment-button {
					border-radius: 10upx;
					padding: 15upx 50upx;
					border: 1upx solid #d9d9d9;
				}
			}
		}
	}

	.scroll-x {
		width: 100%;
		white-space: nowrap;
		position: relative;

		& .item {
			padding: 30upx 0;
			text-align: center;
			display: inline-block;

		}

		& .active {
			position: relative;

			&::before {
				position: absolute;
				content: '';
				bottom: 6upx;
				left: 20%;
				height: 4upx;
				width: 60%;
				background-color: $div-bg-color;
			}
		}
	}
</style>