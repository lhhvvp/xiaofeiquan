<template>
	<view>
		<view class="nav-box" style="position: sticky;" :style="'top:0px'"><my-nav :navList="navList" @current="current"></my-nav></view>
		<view class="order">
			<my-order v-if="PayData.data.length !=0" :PayData="PayData" @onDetail="onDetail" @onClick="payButton"></my-order>
		</view>
		<view class="refund" v-if="refundOrder.is_refund" @click="refundOrder.is_refund = !refundOrder.is_refund">
			<view class="refund-content" @click.stop="">
				<view class="close">
					<view class="title">退款理由：</view>
					<uni-icons type="closeempty" size="30" @click="refundOrder.is_refund = !refundOrder.is_refund"></uni-icons>
				</view>
				<textarea placeholder="请输入退款理由" @input="textAreaInput"></textarea>
				<view class="refund-button" @click="submitRefund">提交</view>
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
import { mapState, mapMutations } from 'vuex';
import { pay } from '@/common/common.js';
export default {
	data() {
		return {
			navList: {
				current: 0,
				width: 25,
				list: [{ title: '全部', status: 0 }, { title: '已支付', status: 1 }, { title: '未支付', status: 2 }, { title: '已退款', status: 3 }]
			},
			refundOrder: {
				// 退款订单
				order_no: 0, //退款订单号
				is_refund: false, //退款弹窗
				order_remark: '', //退款理由,
				coupon_issue_user_id: null,
				openid: null
			},
			PayData: {
				type: 1,
				data: []
			},
			state: '',
			page: 1,
			loadding: 'more',
			loadding_lock: false,
			empty: {
				show: false,
				id: 3
			}
		};
	},

	onLoad(option) {
		const state = Number(option.state);
		state == -1 ? (this.state = '') : (this.state = state);
		state == -1 ? this.$set(this.navList, 'current', 0) : false;
		state == 1 ? this.$set(this.navList, 'current', 1) : false;
		state == 2 ? this.$set(this.navList, 'current', 3) : false;

		// this.init();
	},

	onShow() {},

	computed: {
		...mapState(['uerInfo'])
	},
	methods: {
		...mapMutations([]),
		payButton(e) {
			const button = e.button && (e.button = JSON.parse(e.button));
			const order = !!e.order && e.order;
			if (button && order) {
				if (button.type == 'refund') {
					//退款
					this.$set(this.refundOrder, 'openid', order.item.openid);
					this.$set(this.refundOrder, 'is_refund', true);
					this.$set(this.refundOrder, 'order_no', order.item.order_no);
					this.$set(this.refundOrder, 'coupon_issue_user_id', order.item.issue_coupon_user_id);
				}
				if (button.type == 'pay') {
					//支付
					this.$api
						.httpRequest(
							`/pay/submit`,
							{
								uid: this.uerInfo.uid,
								openid: this.uerInfo.openid,
								coupon_uuno: order.item.detail.coupon_uuno,
								data: JSON.stringify({ uuno: order.item.detail.coupon_uuno, number: 1, price: order.item.origin_price }),
								type: 'miniapp'
							},
							'POST'
						)
						.then(res => {
							pay(res.data.pay)
								.then(success => {
									this.$api.msg('支付成功！', 'success');
									setTimeout(() => {
										(this.page = 1), (this.loadding = 'more'), (this.loadding_lock = false), (this.PayData.data = []);
										this.init();
									}, 2500);
								})
								.catch(err => {
									uni.hideLoading();
									this.$api.msg('支付失败！', 'error');
								});
						}).catch(err=>{
							uni.hideLoading();
						});
				}
			}
		},
		textAreaInput(e) {
			this.$set(this.refundOrder, 'order_remark', e.detail.value);
		},
		submitRefund() {
			uni.showLoading({
				title:"退款提交中..."
			})
			const { order_no, order_remark, openid, coupon_issue_user_id } = this.refundOrder;
			this.$api
				.httpRequest(
					`/pay/refund`,
					{
						uid: this.uerInfo.uid,
						openid,
						order_remark,
						order_no,
						coupon_issue_user_id
					},
					'POST'
				)
				.then(res => {
					if (res.code == 0) {
						uni.hideLoading();
						this.$api.msg(res.msg, 'success');
						this.$set(this.refundOrder, 'is_refund', false);
						this.page = 1;
						this.loadding = 'more'; this.loadding_lock = false;this.PayData.data = [];
						this.init();
					}
				});
		},
		onDetail(order_no) {
			uni.navigateTo({
				url: `/pages/user/pay_detail?order_no=${order_no}`
			});
		},
		current(e) {
			this.PayData.data = [];
			this.page = 1;
			this.loadding = 'more';
			this.loadding_lock = false;
			if (this.navList.current === e) {
				return false;
			}
			this.$set(this.navList, 'current', e.index);
			e.index == 0 ? (this.state = '') : (this.state = e.index - 1);
			this.init();
		},
		onReachBottom() {
			this.init();
		},
		init() {
			if (this.loadding_lock) {
				//加载锁，防止无限请求接口
				return false;
			}
			this.$api
				.httpRequest(
					`/user/coupon_order`,
					{
						uid: this.uerInfo.uid,
						status: this.state,
						limit: 8,
						page: this.page
					},
					'POST'
				)
				.then(res => {
					if (res.code == 0) {
						const data = res.data.data;
						if (this.page == 1 && data.length == 0) {
							// 如果没有数据则显示为空
							this.$set(this.empty, 'show', true);
							this.loadding_lock = true;
							return false;
						};
						this.$set(this.empty, 'show', false);
						data.forEach(item => {
							const buttons = this.addButton(item.payment_status,item.is_refund);
							this.PayData.data.push({
								order_id: item.order_no,
								status: item.payment_status,
								payment_status: item.payment_status,
								productName: item.detail.coupon_title,
								is_refund:item.is_refund,
								desc: item.detail.coupon_uuno,
								images: item.detail.coupon_icon || 'https://oss.wlxfq.dianfengcms.com/admins/d2d20480c4d629fc0336b4b639498830.png',
								price: item.origin_price,
								number: 1,
								button: buttons,
								item: item
							});
						});
						if (data.length != res.data.per_page) {
							//判断是否有下一页
							this.loadding = 'no-more';
							this.loadding_lock = true;
							return false;
						}
						this.page++;
						this.loadding = 'loading';
					}
				});
		},

		addButton(payment_status,is_refund) {
			let button = [];
			if(is_refund == 1){
				return button;
			}
			switch (payment_status) {
				case 0:
					button.push({
						name: '立即支付',
						buttonColor: '#a00000',
						color: '#ffffff',
						borderColor: '#a00000',
						type: 'pay'
					});
					break;
				case 1:
					button.push({
						name: '退款',
						buttonColor: '',
						color: '',
						borderColor: '',
						type: 'refund'
					});
					break;
			}

			return button;
		}
	}
};
</script>

<style lang="scss">
.nav-box {
	background-color: #fff;
	z-index: 99;
}
.order {
	padding-top: 20upx;
	width: 95%;
	margin: auto;
}
.refund {
	width: 100%;
	height: 100%;
	background-color: #00000055;
	position: fixed;
	top: 0;
	left: 0;
	& .refund-content {
		width: calc(100% - 40upx);
		padding: 20upx;
		height: 550upx;
		background-color: #fff;
		position: absolute;
		bottom: 0;
		border-radius: 20upx 20upx 0 0;
		& .close {
			display: flex;
			justify-content: space-between;
			align-items: center;
			top: 0;
			margin-bottom: 30upx;
			& .title {
				font-weight: bold;
				font-size: 34upx;
			}
		}
		& textarea {
			width: calc(100% - 40upx);
			padding: 20upx;
			height: 200upx;
			border-radius: 20upx;
			background-color: #f7f7f7;
			margin-bottom: 50upx;
		}
		& .refund-button {
			width: 100%;
			height: 80upx;
			border-radius: 20upx;
			text-align: center;
			line-height: 80upx;
			background-color: $div-bg-color;
			color: #fff;
		}
	}
}
</style>
