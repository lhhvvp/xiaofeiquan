<template>
	<view>
		<view class="nav-box" style="position: sticky;" :style="'top:0px'"><my-nav :navList="navList"
				@current="current"></my-nav></view>
		<view class="coupon-list"><my-coupon-list-my :lists="list[index].list"
				v-if="list[index] && list[index].list.length != 0" @click="couponList"></my-coupon-list-my></view>
		<!-- 加载loadding -->
		<uni-load-more :status="list[index].loadding" v-if="!list[index].empty.show"></uni-load-more>
		<!-- end -->

		<!-- 为空页 -->
		<my-empty :empty="list[index].empty" v-if="list[index].empty.show"></my-empty>
		<!-- end -->
	</view>
</template>

<script>
	import {
		mapState,
		mapMutations
	} from 'vuex';
	import {
		padLeftZero,
		dateTime,
		timeToTimestamp
	} from '@/common/common.js';
	export default {
		data() {
			return {
				banner: [{
					images: ''
				}],
				navList: {
					current: 0,
					width: 25,
					list: [{
						title: '全部',
						status: 0
					}, {
						title: '未使用',
						status: 1
					}, {
						title: '已使用',
						status: 2
					}, {
						title: '已过期',
						status: 3
					}]
				},
				list: [],
				index: 0
			};
		},
		onReachBottom() {
			this.init();
		},
		computed: {
			...mapState(['hasLogin', 'uerInfo', 'is_refresh'])
		},
		onLoad(opiton) {
			if (opiton.state != '') {
				this.$set(this.navList, 'current', Number(opiton.state));
				this.index = opiton.state;
			};
			this.draw();
			this.navList.list.forEach((item, index) => {
				this.list.push({
					name: item.title,
					status: item.status,
					list: [],
					page: 1,
					loadding: 'more',
					loadding_lock: false,
					empty: {
						show: false,
						id: 3
					}
				});
			});
			this.init();
		},
		onShow() {
			if (this.is_refresh) {
				this.list = [];
				this.navList.list.forEach((item, index) => {
					this.list.push({
						name: item.title,
						status: item.status,
						list: [],
						page: 1,
						loadding: 'more',
						loadding_lock: false,
						empty: {
							show: false,
							id: 3
						}
					});
				});
				this.init();
				this.setRefresh(false);
			}
		},
		methods: {
			...mapMutations(['setMerchant', 'setRefresh']),
			draw() {
				this.$api
					.httpRequest(
						`/user/get_user_coupon_id`, {
							uid: this.uerInfo.uid,
						},
						'POST'
					)
					.then(res => {
						uni.setStorageSync('coupon_id', res.data);
					})
			},
			init() {
				let index = this.index;
				if (this.list[index].loadding_lock) {
					//加载锁，防止无限请求接口
					return false;
				}
				this.$api
					.httpRequest(
						`/user/coupon_issue_user`, {
							uid: this.uerInfo.uid,
							status: index != 0 ? index - 1 : '',
							page: this.list[index].page,
							limit: 8
						},
						'POST'
					)
					.then(res => {
						if (res.code == 0) {
							let data = res.data.data;
							if (this.list[index].page == 1 && data.length == 0) {
								// 如果没有数据则显示为空
								this.$set(this.list[index].empty, 'show', true);
								this.list[index].loadding_lock = true;
								return false;
							}
							let newdata = data.map((item, index) => {
								const create_time = timeToTimestamp(item.create_time);
								const obj = {};
								obj.id = item.id;
								obj.title = item.coupon_title;
								obj.desc = `领取时间：${dateTime(create_time)}`;
								if (item.couponIssue.is_permanent == 1) {
									//是否永久有效 1永久 2时间段 3天数
									obj.time = `有效时间：永久有效`;
								} else if (item.couponIssue.is_permanent == 2) {
									obj.time = `有效时间：${item.couponIssue.coupon_time_start != 0 ? dateTime(item.couponIssue.coupon_time_start) : '-'} 至 ${
									item.couponIssue.coupon_time_end != 0 ? dateTime(item.couponIssue.coupon_time_end) : '-'
								}`;
								} else {
									obj.time =
										`有效时间：${dateTime(create_time)} - ${dateTime(create_time + 86400 * item.couponIssue.day)}`;
								};
								obj.price = item.coupon_price;
								obj.status = item.status + 1;
								obj.item = item;

								return obj;
							});
							this.list[index].list = [...this.list[index].list, ...newdata];

							if (data.length != res.data.per_page) {
								//判断是否有下一页
								this.list[index].loadding = 'no-more';
								this.list[index].loadding_lock = true;
								return false;
							}
							this.list[index].page++;
							this.list[index].loadding = 'loading';
						}
					});
			},
			current(e) {
				if (this.navList.current === e) {
					return false;
				}
				let index = e.index;
				this.$set(this.navList, 'current', index);
				this.index = e.index;
				this.init();
			},
			merchant(item) {
				uni.navigateTo({
					url: '/pages/merchant/info/info?id=' + item.id
				});
			},
			couponList(item) {
				this.setMerchant(item);
				uni.navigateTo({
					url: '/pages/coupon/my_coupon'
				});
			}
		}
	};
</script>

<style lang="scss">
	.nav-box {
		width: 100%;
		margin: auto;
		background-color: #f7f7f7;
		z-index: 9;
	}

	.coupon-list {
		padding-top: 20upx;
		width: 95%;
		margin: auto;
	}

	.search-box {
		width: calc(100% - 20px);
		display: flex;
		position: sticky;
		top: 0;
		z-index: 99;
		background-color: #fff;
		padding: 10px;
		justify-content: space-between;
		align-items: center;

		& .search {
			// width: calc(95% - 60px);
			position: relative;
			display: flex;
			flex: 1;
			align-items: center;

			& input {
				width: calc(100% - 80upx);
				background: #f7f7f7;
				padding: 5px 10upx 5px 70upx;
				height: 25px;
				border-radius: 20upx;
			}

			& .icons-search {
				position: absolute;
				margin-left: 8upx;
			}
		}

		& .search-qrcode {
			display: flex;
			align-items: center;
			justify-content: space-between;
			margin-left: 20upx;

			& .qrcode {
				margin-right: 5px;
			}

			& .qrcode,
			&.qrcode-log {
				width: 30px;
				display: flex;
				align-items: center;
				justify-content: center;
			}
		}
	}
</style>