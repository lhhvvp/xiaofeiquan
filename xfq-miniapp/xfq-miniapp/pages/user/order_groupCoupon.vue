<template>
	<view class="order-list">
		<my-coupon-list-my :lists="list" @click="couponList"></my-coupon-list-my>
		<uni-load-more :status="loadding" v-if="!empty.show"></uni-load-more>
		<!-- 为空页 -->
		<my-empty :empty="empty" v-if="empty.show"></my-empty>
		<!-- end -->
	</view>
</template>

<script>
import { mapState, mapMutations } from 'vuex';
export default {
	data() {
		return {
			list: [],
			page: 1,
			loadding: 'more',
			loadding_lock: false,
			empty: {
				show: false,
				id: 3
			},
			mid: 0
		};
	},
	onReachBottom() {
		this.init();
	},
	onLoad(option) {
		this.mid = option.mid;
		this.init();
	},
	computed: {
		...mapState(['hasLogin', 'uerInfo'])
	},
	methods: {
		init() {
			if (this.loadding_lock) {
				//加载锁，防止无限请求接口
				return false;
			}
			this.$api
				.httpRequest(
					`/coupon/tourwriteofflog`,
					{
						page: this.page,
						limit: 20,
						userid: this.uerInfo.uid,
						mid: this.mid //user页面取
					},
					'POST'
				)
				.then(res => {
					if (res.code == 0) {
						let data = res.data;
						if (this.page == 1 && data.length == 0) {
							// 如果没有数据则显示为空
							this.$set(this.empty, 'show', true);
							this.loadding_lock = true;
							return false;
						}
						if (data.length == 0 ) {
							// 如果没有数据则显示为空
							this.loadding_lock = true;
							this.loadding = 'noMore';
							return false;
						}
						let newdata = data.map((item, index) => {
							let time = '';
						
							let obj = {
								id: item.id,
								title: item.coupon_title,
								desc: `核销时间:${item.create_time}`,
								time: time,
								price: item.coupon_price,
								status: 3,
								item:item,
								type:"group",
							};
							return obj;
						});
						this.list = [...this.list, ...newdata];
						this.page++;
						this.loadding = 'loading';
					}
				});
		},
		couponList(item) {
			uni.navigateTo({
				url: `/pages/user/GroupCoupon/my_coupon?id=${item.item.tour_coupon_group_id}`
			});
		}
	}
};
</script>

<style lang="scss">
.order-list {
	width: 95%;
	padding-top: 20upx;
	margin: auto;
}
</style>
