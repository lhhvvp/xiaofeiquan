<template>
	<view class="order-detail">
		<view class="status">{{detail.order_status_text || '-'}}</view>
		<view class="status_text"></view>
		<view class="item">
			<view class="price-box">
				<view class="price-tit">应付金额</view>
				<view class="price">
					{{detail.amount_price || '-'}}
				</view>
			</view>
			<view>
				<view class="product">
					<view class="tit" style="font-size: 34upx;font-weight: bold;">{{detail.seller.nickname || '-'}} -
						{{detail.ticket_info.title || '-'}}
					</view>
					<view class="number" v-if="detail.detail_list">x{{detail.detail_list.length || ''}}
					</view>
				</view>
				<view class="content-box">
					<view class="label">有效期</view>
					<view class="content" style="width: 80%;">{{detail.ticket_info.date || '-'}}（仅限当天使用）</view>
				</view>

				<view class="content-box">
					<view class="label">订单号</view>
					<view class="content" style="width: 70%;">{{detail.trade_no || '-'}}</view>
					<view style="width: 10%;text-align: center;color: #bfbfbf;" @click="copy()">复制</view>
				</view>

			</view>
		</view>

		<view class="item">
			<view class="price-box">
				<view class="price-tit">二维码展示</view>
			</view>
			<view class="qrcode" v-if="detail.order_status == 'used' || detail.order_status == 'paid'">
				<image v-if="detail.order_status == 'used'" src="/static/0002.png"
					style="width: 240upx;height: 240upx;">
				</image>
				<image v-if="detail.order_status == 'paid' && refresh_image " src="/static/0001.png"
					style="width: 240upx;height: 240upx;">
				</image>
				<uqrcode style="width: 100%;display: flex;justify-content: center;align-items: center;"
					v-if="detail.qrcode_str && detail.order_status == 'paid' && qrcodeVal && !refresh_image"
					ref="qrcode1" canvas-id="qrcode1" :start="false" :auto="true" :size="150" :hide="!qrcodeHide"
					:value="qrcodeVal">
				</uqrcode>
				<view class="refresh" @click="init"
					v-if="detail.qrcode_str && detail.order_status == 'paid' && qrcodeVal && !refresh_image">
					<uni-icons type="refreshempty"></uni-icons>
					刷新
				</view>
			</view>
		</view>

		<view class="item" v-if="this.range.length !=0 ">
			<view class="content-box" style="align-items: center;">
				<view class="price-box" style="width: 25%;padding: 0;border:none;border: none;">
					<view class="price-tit">二维码切换</view>
				</view>
				<view class="content" style="width: 75%;">
					<uni-data-select v-model="value" placeholder="券已使用" :localdata="range" @change="change"
						:clear="false" textAlign="right"></uni-data-select>
				</view>
			</view>
		</view>

		<view class="item">
			<view class="price-box">
				<view class="price-tit">游客列表</view>
			</view>
			<view>
				<view class="product" v-for="(item,index) in detail.detail_list" :key="index" style="padding: 20upx 0;">
					<view class="tit">
						<view class="name" style="margin-bottom: 8upx;">
							<text
								style="display: inline-block;margin-right: 24upx;">{{item.tourist_mobile || '-'}}</text>
							{{item.tourist_fullname || '-'}}
						</view>
						<view class="status-name">{{refundProgress(item.refund_progress)}} <text
								style="display: inline-block;margin-left: 10upx;"></text> </view>
					</view>
					<view class="number">
						<view @click.stop="refunded(item.out_trade_no,item.id,true)"
							v-if="item.refund_progress!='completed' && detail.order_status == 'paid' &&  is_status(item) && !is_refunded(item) && item.enter_time == 0">
							<image src="/static/icon/refund-icon.png" style="width: 40upx;height: 40upx;"></image>
							<text>退款</text>
						</view>
						<view @click.stop="unRefunded('order_detail',item.id)"
							v-if="detail.order_status == 'paid' && is_refunded(item)">
							<image src="/static/icon/refund-icon.png" style="width: 40upx;height: 40upx;"></image>
							<text>取消退款</text>
						</view>
						<view @click.stop="qrcodeFun(item)" style="margin-left: 24upx;"
							v-if="detail.order_status == 'paid' && is_status(item) && item.refund_progress!='completed'">
							<image src="/static/icon/qr_1.png" style="width: 40upx;height: 40upx;"></image>
							<text>核销码</text>
						</view>
					</view>
				</view>
			</view>
		</view>
		<view class="item">
			<view class="content-box">
				<view class="label">购买说明</view>
				<view class="content">
					<rich-text :nodes="detail.ticket_info.explain_buy"></rich-text>
				</view>
			</view>
			<view class="content-box">
				<view class="label">使用说明</view>
				<view class="content">
					<rich-text :nodes="detail.ticket_info.explain_use"></rich-text>
				</view>
			</view>
		</view>

		<view class="qrcode-shade" v-if="qrcode.isShow" @click.stop="qrcodeCut">
			<view class="qrcode">
				<view class="name">{{qrcode.val.tourist_fullname}}-{{qrcode.val.tourist_mobile}}</view>
				<uqrcode ref="qrcode2" canvas-id="qrcode2" :start="false" :size="150" :hide="qrcodeHide"
					:value="qrcode.data.UserQrCodeVal">
				</uqrcode>
				<view class="area" v-if="qrcode.data.name">{{qrcode.data.name}}</view>
			</view>
		</view>

		<view class="line" style="height: 120upx;"></view>
		<view class="button-box">
			<view class="buttons" @click="navto()" v-if="detail.order_status == 'used' && !detail.iscomment">评论</view>
			<view class="buttons" @click="refunded()" v-if="detail.order_status=='paid' && !unRefund && RefundAllShow">
				全部退款
			</view>
			<view class="buttons" @click="unRefunded('order',detail.id)" v-if="unRefund">取消退款</view>
			<view class="pay-button" @click="orderpay()" v-if="detail.order_status=='created'">立即购买</view>
		</view>
	</view>
</template>

<script>
	import {
		mapState,
		mapMutations
	} from 'vuex';

	import tuiTimeAxis from "@/components/thorui/tui-time-axis/tui-time-axis.vue"
	import tuiTimeaxisItem from "@/components/thorui/tui-timeaxis-item/tui-timeaxis-item.vue"

	import {
		getLocation
	} from "@/common/common.js"
	export default {
		components: {
			tuiTimeAxis,
			tuiTimeaxisItem
		},
		data() {
			return {
				id: null,
				detail: {},
				use_lat: null,
				use_lng: null,
				qrcode: {
					isShow: false,
					data: null,
					val: null,
				},
				qrcodeVal: null, //订单码的数据
				qrcodeHide: true,
				//选择的
				value: 0,
				range: [],
				unRefund: false, //取消退款显示隐藏
				refresh_image: false, //二维码不可用图片
				RefundAllShow: true, //全部退款按钮的显示隐藏
			};
		},

		onLoad(options) {
			this.id = options.id
		},
		onShow() {
			getLocation().then(res => {
				this.use_lat = res.latitude;
				this.use_lng = res.longitude;
				this.init();
			})
		},

		computed: {
			...mapState(['uerInfo']),
		},
		methods: {
			...mapMutations([]),
			change(e) {
				let {
					use_lat,
					use_lng
				} = this;

				this.qrcodeVal = JSON.stringify({
					qrcode_str: e.qrcode_str,
					be_id: e.id,
					use_lat,
					use_lng,
					type: 'order'
				});

			},
			is_status(vals) {
				let val = vals.rights_list
				if (val.length == 0) {
					if (vals.enter_time != 0) {
						return false;
					}
					return true;
				}
				// == 1 的时候已核销，
				//==0的时候未核销
				let status = false;
				val.forEach(item => {
					if (item.rights_id == this.value) {
						if (item.status == 0) {
							status = true;
						}
					}
				});
				return status
			},
			is_refunded(vals) {
				let val = vals.refund_progress;
				if (val == 'pending_review') {
					return true
				} else {
					return false;
				}
			},
			qrcodeFun(info) {
				const {
					use_lat,
					use_lng
				} = this;
				let qrcode_str = info.qrcode_str; //总码的二维码串

				let id = info.id; //人id

				let UserQrCodeVal = null; //码的数据

				let qrcodeData = {} //二维码对象

				let rights_list = info.rights_list; //多个码的时候

				if (rights_list.length == 0) {
					//单码
					UserQrCodeVal = JSON.stringify({
						qrcode_str: qrcode_str,
						be_id: id,
						use_lat,
						use_lng,
						type: 'order_user',
						id: this.detail.id,
					});
					qrcodeData = {
						UserQrCodeVal,
						name: null
					};
				} else {
					//多码
					let name = "";
					let status = false;
					rights_list.forEach(item => {
						if (item.rights_id == this.value) {
							UserQrCodeVal = JSON.stringify({
								qrcode_str: item.qrcode_str,
								be_id: item.id,
								use_lat,
								use_lng,
								type: 'order_user',
								id: item.detail_id,
							});
							name = item.rights_title;
							status = item.status;
						}
					});
					qrcodeData = {
						UserQrCodeVal,
						name
					};
					if (status) {
						this.$api.msg('该二维码已核销,请选择其他二维码')
						return false
					}
				}


				this.qrcode.isShow = true;
				this.qrcodeHide = !this.qrcodeHide;
				this.qrcode.data = qrcodeData;
				this.qrcode.val = info;
				let timer = setTimeout(() => {
					this.$refs.qrcode2.make();
					clearTimeout(timer);
				}, 200)
			},
			qrcodeCut() {
				this.init();
				this.qrcode.isShow = !this.qrcode.isShow;
				if (!this.qrcode.isShow) {
					this.qrcodeHide = !this.qrcodeHide;
				};
			},
			copy() {
				uni.setClipboardData({
					data: this.detail.trade_no,
					fail(err) {
						console.log(err);
					}
				});
			},
			init() {
				const {
					use_lat,
					use_lng
				} = this;
				this.$api
					.httpRequest(
						`/ticket/getOrderDetail`, {
							order_id: this.id,
						},
						'GET'
					)
					.then(res => {
						this.detail = res.data;
						this.range = res.data.rights_qrcode_list.map((item, index) => {
							let obj = {
								id: item.id,
								text: item.rights_title,
								value: item.rights_id,
								qrcode_str: item.qrcode_str,
								writeoff_num: item.writeoff_num,
								rights_num: item.rights_num,
								disable: item.rights_num == item.writeoff_num ? true :
								false, //核销等于true,未核销等于false
							}
							this.refresh_image = false;
							if (obj.disable) {
								this.refresh_image = true;
							}
							return obj
						});
						let unRefund = 0;
						let completed = 0;
						this.unRefund = false;
						this.RefundAllShow = true;
						res.data.detail_list.forEach((item, key) => {
							if (item.enter_time != 0 || item.refund_progress == 'pending_review') {
								this.RefundAllShow = false;
							}
							if (item.refund_progress == 'pending_review') {
								unRefund++;
								//已退款
							};
							if(item.refund_progress == 'completed'){
								completed++;
							}
							if(item.refund_progress == 'approved'){
								this.RefundAllShow = false;
								this.refresh_image = true;
							}
						});
						if (unRefund != 0) {
							this.unRefund = true;
							if((unRefund+completed) == res.data.detail_list.length){
								this.refresh_image = true;
							}
							//取消退款按钮显示和隐藏
						};

						let Val = this.range.find(item => item.disable === false);
						if (Val) {
							//多游客多权益
							this.qrcodeVal = JSON.stringify({
								qrcode_str: Val.qrcode_str,
								be_id: Val.id,
								use_lat,
								use_lng,
								type: 'order'
							});
							this.value = Val.value;
						} else {
							//多游客单权益
							this.qrcodeVal = JSON.stringify({
								qrcode_str: this.detail.qrcode_str,
								be_id: this.detail.id,
								use_lat,
								use_lng,
								type: 'order'
							});
						}

						if (res.data.order_status == 'paid' && this.qrcodeVal && !this.refresh_image) {
							setTimeout(() => {
								this.$refs.qrcode1.make();
							}, 200)
						}

					})
			},

			navto() {
				uni.navigateTo({
					url: "/pages/user/commentAdd?id=" + this.detail.id
				})
			},

			refunded(id, user_id, single = false) {
				let order_id = null;
				if (id) {
					order_id = id
				} else {
					order_id = this.detail.out_trade_no;
				};
				let url = "/pages/user/refunded?trade_no=" + order_id;

				if (single) {
					url = "/pages/user/refunded?trade_no=" + order_id + '&single=' + true
				}
				uni.showModal({
					title: "提示",
					content: '您确定要退款吗?',
					success: function(res) {
						if (res.confirm) {
							uni.navigateTo({
								url: url
							})
						}
					}
				})
			},
			unRefunded(type, id) {
				// ticket/cancelRefund
				let that = this;
				uni.showModal({
					title: "提示",
					content: '您确定要取消退款吗？',
					success: function(res) {
						if (res.confirm) {
							that.$api.httpRequest(
									`/ticket/cancelRefund`, {
										type,
										id
									},
									'POST'
								)
								.then(res => {
									that.$api.msg(res.msg, 'none');
									setTimeout(() => {
										that.init();
										that.refresh_image = false;
									}, 2500)
								})
						}
					}
				})
			},

			orderpay() {
				this.$api
					.httpRequest(
						`/ticket/orderpay`, {
							uuid: this.uerInfo.uuid,
							openid: this.uerInfo.openid,
							trade_no: this.detail.trade_no,
						},
						'POST'
					)
					.then(res => {
						let that = this;
						if (res.code == 0) {
							let data = res.data;
							uni.requestPayment({
								provider: 'wxpay',
								timeStamp: data.pay.timeStamp,
								nonceStr: data.pay.nonceStr,
								package: data.pay.package,
								signType: data.pay.signType,
								paySign: data.pay.paySign,
								success: function(res) {
									//接口调用成功的回调
									uni.hideLoading();
									uni.redirectTo({
										url: '/pages/user/paySuccess'
									});
								},
								fail: function(err) {
									uni.hideLoading();
									that.$api.msg('支付失败！');
								}
							});
						} else {
							that.$api.msg('支付失败！');
						}
					})
			},
			refundProgress(val) {
				switch (val) {
					case 'pending_review':
						return '退款状态：审核中'
						break;
					case 'refuse':
						return '退款状态：已拒绝'
						break;
					case 'approved':
						return '退款状态：已通过'
						break;
					case 'completed':
						return '退款状态：完成退款'
						break;
					case 'init':
						return '退款状态：未退款'
						break;
					default:
						'状态：正常'
						break;
				}
			}
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
		flex-direction: column;
		align-items: center;
		justify-content: center;

		.refresh {
			padding-top: 30upx;
			color: #86868690;
		}
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
					width: 30%;
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
					color: #c7c7c7;
				}

				.content {
					width: 80%;
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
				margin-bottom: 40upx;
				font-size: 36upx;
			}

			.area {
				margin-top: 40upx;

				view {
					padding-left: 20upx;
					color: #888;
				}
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