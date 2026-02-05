<template>
	<view class="tickets">
		<my-header @search="search"></my-header>
		<!-- <view class="search-box">
			<view class="search">
				<uni-icons class="icons-search" type="search" size="24" color="#c7c7c7"></uni-icons>
				<input placeholder="请输入关键字" @click="search" disabled="true" placeholder-class="placeholder-input" />
			</view>
		</view> -->
		<view class="header" :style="'position: sticky;border-top: 1upx solid #f7f7f7;top:'+menuButton+'px;'">
			<liu-dropdown :menuList="menuList" :dataObj="dataObj" @change="change"></liu-dropdown>
		</view>
		<view class="centent">
			<!-- 商家列表 -->
			<my-tickets :lists="list" @click="tickets" v-if="list && list.length != 0"></my-tickets>
			<!-- end -->

			<!-- 加载loadding -->
			<uni-load-more :status="loadding" v-if="!empty.show"></uni-load-more>
			<!-- end -->
			<!-- 为空页 -->
			<my-empty :empty="empty" v-if="empty.show"></my-empty>
			<!-- end -->
		</view>
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
				menuButton: 0,
				list: [],
				loadding: 'more',
				loadding_lock: false,
				empty: {
					show: false,
					id: 3
				},
				keyWorld: {
					area: "",
					synthesize: "",
				},
				menuList: [{
					id: 1,
					name: '区域',
					isMultiple: false, //是否多选
					showType: 3 //下拉框类型(1、2、3、4)
				}, {
					id: 2,
					name: '智能排序',
					isMultiple: false, //是否多选
					showType: 3 //下拉框类型(1、2、3、4)
				}, ],
				dataObj: {
					itemList1: [{
						id: 0,
						name: '全部',
					}, {
						id: 1,
						name: '榆阳区',
					}, {
						id: 2,
						name: '横山区',
					}, {
						id: 3,
						name: '神木市',
					}, {
						id: 4,
						name: '府谷县',
					}, {
						id: 5,
						name: '靖边县',
					}, {
						id: 6,
						name: '定边县',
					}, {
						id: 7,
						name: '绥德县',
					}, {
						id: 8,
						name: '米脂县',
					}, {
						id: 9,
						name: '佳县',
					}, {
						id: 10,
						name: '吴堡县',
					}, {
						id: 11,
						name: '清涧县',
					}, {
						id: 12,
						name: '子洲县',
					}],
					itemList2: [{
						id: 0,
						name: '智能排序',
					}, {
						id: 'distance',
						name: '距离优先',
					}, {
						id: 'comment',
						name: '好评优先',
					}],
				},
				latitude: null,
				longitude: null,
				page: 1,
			};
		},

		onLoad(options) {
			let menuButton = uni.getMenuButtonBoundingClientRect();
			this.menuButton = menuButton.top + menuButton.height + 10;
			getLocation().then(res => {
				this.latitude = res.latitude
				this.longitude = res.longitude
				this.init();
			})
		},

		onShow() {},
		onReachBottom() {
			this.init()
		},
		computed: {
			...mapState(['uerInfo']),
		},

		methods: {
			...mapMutations([]),
			init() {
				if (this.loadding == 'no-more') {
					return false;
				};
				this.$api
					.httpRequest(
						`/ticket/getScenicList`, {
							area: this.keyWorld.area,
							orderby: this.keyWorld.synthesize,
							page: this.page,
							page_size: 12,
							latitude: this.latitude,
							longitude: this.longitude,
							hasTicket: true,
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
						this.loadding = 'loading';
						if (data.length != 12 && this.page == 1) {
							this.loadding = 'no-more';
							this.loadding_lock = true;
						}
						this.list = [...this.list, ...data];
						this.page++;
					})
			},
			change(e) {
				this.keyWorld.area = e.chooseInfo.itemList1.length != 0 ? e.chooseInfo.itemList1[0].id : ""
				this.keyWorld.synthesize = e.chooseInfo.itemList2.length != 0 ? e.chooseInfo.itemList2[0].id : "";
				this.list = [];
				this.page = 1;
				this.loadding = 'more',
					this.loadding_lock = false,
					this.empty = {
						show: false,
						id: 3
					},
					this.init();
			},
			search(e) {
				uni.navigateTo({
					url: '/pages/search/search'
				});
			},
			tickets(e) {
				uni.navigateTo({
					url: '/pages/tickets/info?seller_id=' + e.id,
				});
			}

		}
	};
</script>

<style lang="scss">
	.header {
		box-shadow:
			12.5px 12.5px 10px rgba(0, 0, 0, 0.002),
			100px 100px 80px rgba(0, 0, 0, 0.07);
		z-index: 1;
	}

	.search-box {
		width: calc(100% - 20px);
		display: flex;
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