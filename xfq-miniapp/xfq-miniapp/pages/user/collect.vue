<template>
	<view>
		<!-- <my-banner :bannerList="banner" :isFull="true"></my-banner> -->
		<view class="nav-box" style="position: sticky;" :style="'top:' + (menuButton + 55) + 'px'"><my-nav :navList="nav" @current="current"></my-nav></view>

		<!-- 商家列表 -->
		<my-merchant :lists="list" @click="merchant" v-if="list.length != 0"></my-merchant>
		<!-- end -->

		<!-- 加载loadding -->
		<uni-load-more :status="loadding" v-if="!empty.show"></uni-load-more>
		<!-- end -->
		<!-- 为空页 -->
		<my-empty :empty="empty"></my-empty>
		<!-- end -->
	</view>
</template>

<script>
import { mapState, mapMutations } from 'vuex';
import {getLocation} from "../../common/common.js"
export default {
	data() {
		return {
			index: 0,
			menuButton: 0,
			latitude: 0,
			longitude: 0,
			list: [],
			page: 0,
			loadding: 'more',
			loadding_lock: false,
			empty: {
				show: false,
				id: 3
			}
		};
	},
	onReachBottom() {
		this.init();
	},
	computed: {
		...mapState(['uerInfo'])
	},
	onLoad() {
		let that = this;
		
		getLocation().then(
			success => {
				that.latitude = success.latitude;
				that.longitude = success.longitude;
				that.init();
			},
			fail => {
				// 失败
			}
		);

		let menuButton = uni.getMenuButtonBoundingClientRect();
		this.menuButton = menuButton.top + menuButton.height + 10;
	},
	methods: {
		init() {
			if (this.loadding_lock) {
				//加载锁，防止无限请求接口
				return false;
			};
			if(!this.uerInfo.uid){
				this.$set(this.empty, 'show', true);
			}
			this.$api
				.httpRequest(
					`/user/collection`,
					{
						uid: this.uerInfo.uid,
						page: this.page,
						is_token:true,
						limit: 8,
						latitude: this.latitude,
						longitude: this.longitude
					},
					'POST'
				)
				.then(res => {
					if (res.code == 0) {
						let data = res.data;
						if (this.page == 0 && data.length == 0) {
							// 如果没有数据则显示为空
							this.$set(this.empty, 'show', true);
							this.loadding_lock = true;
							return false;
						}
						data.map((item, index) => {
							item.image = this.$api.urli + item.image;
							item.distance = item.distance.toFixed(2);
							return item;
						});
						this.list = [...this.list, ...data];
						if (data.length != 8) {
							//判断是否有下一页
							this.loadding = 'no-more';
							this.loadding_lock = true;
							return false;
						}
						this.page++;
						this.loadding = 'loading';
					} else {
						this.$set(this.empty, 'show', true);
					}
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
