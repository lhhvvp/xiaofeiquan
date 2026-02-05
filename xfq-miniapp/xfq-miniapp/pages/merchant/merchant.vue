<template>
	<view>
		<my-header @search="search"></my-header>
		<view class="search-box" :style="'top:' + menuButton + 'px'">
			<view class="search">
				<uni-icons class="icons-search" type="search" size="24" color="#c7c7c7"></uni-icons>
				<input placeholder="请输入关键字" @click="searchClick" disabled="true" placeholder-class="placeholder-input" />
			</view>
		</view>

		<!-- <my-banner :bannerList="banner" :isFull="true"></my-banner> -->

		<view class="nav-box" style="position: sticky;" :style="'top:' + (menuButton + 55) + 'px'"><my-nav :navList="nav" @current="current"></my-nav></view>

		<!-- 商家列表 -->
		<my-merchant :lists="list[index].list" @click="merchant" v-if="list[index] && list[index].list.length != 0"></my-merchant>
		<!-- end -->

		<!-- 加载loadding -->
		<uni-load-more :status="list[index].loadding" v-if="!list[index].empty.show"></uni-load-more>
		<!-- end -->
		<!-- 为空页 -->
		<my-empty :empty="list[index].empty" v-if="list[index].empty.show"></my-empty>
		<!-- end -->
	</view>
</template>

<script>
import { getLocation } from '../../common/common.js';
export default {
	data() {
		return {
			banner: [
				{
					images: ''
				}
			],
			nav: {
				current: 0,
				width: 20,
				list: []
			},
			list: [],
			index: 0,
			menuButton: 0,
			latitude: 0,
			longitude: 0,
			is_onLoad: false,
			class_id: 1
		};
	},
	onReachBottom() {
		this.init(this.class_id);
	},
	// onTabItemTap(e) {
	// 	//关闭锁!
	// 	if(this.nav.list != 0){
	// 		this.nav.current = 0;
	// 		this.list = [];
	// 		this.navList();
	// 	}
	// },
	// #ifdef MP-WEIXIN
	onShareAppMessage(res) {
		//微信小程序分享给朋友
		return {
			title: '榆林市旅游消费平台',
			path: '/pages/merchant/merchant'
		};
	},
	onShareTimeline(res) {
		//微信小程序分享朋友圈
		return {
			title: '榆林市旅游消费平台',
			path: '/pages/merchant/merchant'
		};
	},
	// #endif
	onLoad() {
		
		getLocation().then(
			success => {
				this.latitude = success.latitude;
				this.longitude = success.longitude;
				this.banners();
			},
			fail => {
				// 失败
			}
		);
		let menuButton = uni.getMenuButtonBoundingClientRect();
		this.menuButton = menuButton.top + menuButton.height + 10;
	},
	onPullDownRefresh() {
		Object.assign(this.$data, this.$options.data());
		let menuButton = uni.getMenuButtonBoundingClientRect();
		this.menuButton = menuButton.top + menuButton.height + 10;
		getLocation().then(
			success => {
				this.latitude = success.latitude;
				this.longitude = success.longitude;
				this.banners();
			},
			fail => {
				// 失败
			}
		);
	},
	methods: {
		init(class_id = 1) {
			let index = this.index;
			if (this.list[index].loadding_lock) {
				//加载锁，防止无限请求接口
				return false;
			}
			this.$api
				.httpRequest(
					`/seller/list`,
					{
						class_id: class_id,
						page: this.list[index].page,
						limit: 8,
						latitude: this.latitude,
						longitude: this.longitude
					},
					'POST'
				)
				.then(res => {
					if (res.code == 0) {
						this.is_onLoad = true;
						let data = res.data;
						if (this.list[index].page == 0 && data.length == 0) {
							// 如果没有数据则显示为空
							this.$set(this.list[index].empty, 'show', true);
							this.list[index].loadding_lock = true;
							return false;
						}
						data.map((item, index) => {
							item.image = this.$api.urli + item.image;
							if(!!item.distance){
								item.distance = item.distance.toFixed(2);
							}
							return item;
						});

						this.list[index].list = [...this.list[index].list, ...data];

						if (data.length != 8) {
							//判断是否有下一页
							this.list[index].loadding = 'no-more';
							this.list[index].loadding_lock = true;
							return false;
						}
						this.list[index].page += 8;
						this.list[index].loadding = 'loading';
					}
				});
		},
		banners() {
			// 分类和轮播
			let that = this;
			this.$api.httpRequest(`/seller/cate`, {}, 'POST').then(res => {
				if (res.code == 0) {
					let data = res.data;
					this.banner = data.slide.map((item, index) => {
						let image = `${this.$api.urli}${item.image}`;
						return {
							images: image,
							url: item.url
						};
					});

					this.nav.list = data.cate.map((item, index) => {
						return {
							title: item.class_name,
							status: item.id
						};
					});
					this.navList();
					uni.stopPullDownRefresh();
				}
			});
		},
		navList() {
			this.nav.list.forEach((item, index) => {
				this.list.push({
					name: item.title,
					status: item.status,
					list: [],
					page: 0,
					loadding: 'more',
					loadding_lock: false,
					empty: {
						show: false,
						id: 3
					}
				});
			});
			this.init(this.nav.list[0].status);
		},
		search(e) {
			uni.navigateTo({
				url: '/pages/search/search'
			});
		},
		current(e) {
			this.class_id = e.item.status;
			if (this.nav.current === e) {
				return false;
			}
			this.$set(this.nav, 'current', e.index);
			this.index = e.index;
			this.init(e.item.status);
		},
		searchClick() {
			uni.navigateTo({
				url: '/pages/search/search'
			});
		},
		merchant(item) {
			uni.navigateTo({
				url: '/pages/merchant/info/info?id=' + item.id
			});
		}
	}
};
</script>

<style lang="scss">
.nav-box {
	background-color: #f7f7f7;
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
