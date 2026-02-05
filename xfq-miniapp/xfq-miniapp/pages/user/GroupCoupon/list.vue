<template>
	<view class="list-item">
		<my-coupon-list-my :lists="couponList" @click="coupon"></my-coupon-list-my>
		<!-- <my-empty :empty="empty"></my-empty> -->
	</view>
</template>

<script>
import { mapState, mapMutations } from 'vuex';
export default {
	data() {
		return {
			couponList: [],
			empty: {
				id: 2,
				show: false
			},
			id: null
		};
	},
	onLoad(opiton) {
		uni.setNavigationBarTitle({
			title: '消费券-' + opiton.title
		});
		this.id = opiton.id;
		this.init(opiton.id);
	},
	onShow() {
		if (this.is_refresh) {
			this.init(this.id);
			this.setRefresh(false);
		}
	},
	computed: {
		...mapState(['is_refresh'])
	},
	methods: {
		...mapMutations(['setGroupCoupon', 'setRefresh']),
		init(id) {
			this.$api
				.httpRequest(
					`/user/tour_coupon`,
					{
						tid: id
					},
					'POST'
				)
				.then(res => {
					if (res.data.length == 0) {
						this.$set(this.empty, 'show', true);
						return false;
					}
					this.couponList = res.data.map(item => {
						let coupon_price = Number(item.couponIssue.coupon_price);
						let status = 0;
						item.status == 0 ? (status = 5) : (status = 3);
						return {
							title: item.couponIssue.coupon_title,
							price: coupon_price,
							time: item.create_time,
							desc: item.couponClass.title,
							status: status,
							item: item,
							type: "group",
						};
					});
				});
		},
		coupon(item) {
			uni.navigateTo({
				url: `/pages/user/GroupCoupon/my_coupon?id=${item.item.id}`
			});
			// this.setGroupCoupon(item.item);
		}
	}
};
</script>

<style lang="scss">
.list-item {
	width: 95%;
	margin: auto;
	margin-top: 20upx;
}
</style>
