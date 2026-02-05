<template>
	<view class="detail"><my-detail :dataDetail="dataDetail" v-if="dataDetail.typeName !=''"></my-detail></view>
</template>

<script>
import { mapState, mapMutations } from 'vuex';
import { YmdHm } from '@/common/common.js';
export default {
	data() {
		return {
			dataDetail: {}
		};
	},
	onReachBottom() {},
	computed: {
		...mapState(['uerInfo'])
	},
	onLoad(option) {
		const order_no = option.order_no;
		this.init(order_no);
	},
	methods: {
		init(order_no) {
			this.$api
				.httpRequest(
					`/user/coupon_order_detail`,
					{
						uid: this.uerInfo.uid,
						order_no: order_no
					},
					'POST'
				)
				.then(res => {
					if (res.code == 0) {
						const data = res.data;
						this.dataDetail = {
							typeName: '订单详情',
							coupon_icon: data.detail.coupon_icon || 'https://oss.wlxfq.dianfengcms.com/admins/d2d20480c4d629fc0336b4b639498830.png',
							title: data.detail.coupon_title,
							price: data.origin_price,
							number: '1',
							payment_status: data.payment_status,
							orderNumber: data.order_no,
							creationTime: YmdHm(data.create_time),
							payTime: YmdHm(data.update_time)
						};
					}
				});
		}
	}
};
</script>

<style lang="scss">
.detail {
	padding-top: 20upx;
}
</style>
