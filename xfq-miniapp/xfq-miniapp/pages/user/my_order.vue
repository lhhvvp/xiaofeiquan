<template>
	<view>
		<view class="nav-box" style="position: sticky;" :style="'top:0px'">
			<scroll-view scroll-x class="scroll-x">
				<view class="item" @click="navClick(index, item)"
					:class="[navList.current == item.status ? 'active' : '']" :style="'width:' + navList.width + '%'"
					v-for="(item, index) in navList.list" :key="index">
					{{ item.title }}
				</view>
			</scroll-view>
		</view>
		<view class="my-order">

			<view class="item" v-for="(item,index) in list" :key="index" @click="navTo(item.id)">
				<view class="top">
					<view class="top-left">
						<view class="tit">{{item.seller.nickname}}</view>
					</view>
					<view class="top-right">
						<view class="status">{{statusName(`${item.order_status}`,item)}}</view>
					</view>
				</view>
				<view class="bottom">
					<view class="bottom-left">
						<view class="image" :style="'background-image:url('+item.seller.image+')'"></view>
					</view>
					<view class="bottom-right">
						<view class="count-price">
							{{item.detail_list[0].ticket_title}}
						</view>
						<view class="number" style="margin: 4upx 0;">张数：{{item.detail_list.length}}</view>
						<view class="count-price">总价：{{item.amount_price}} 元</view>
					</view>
				</view>
				<view class="comment" v-if="item.iscomment == false && item.order_status == 'used'">
					<view class="comment-button" @click="Addcomment(item.id)">评论</view>
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
				navList: {
					current: '',
					width: 25,
					list: [{
							title: '全部',
							status: ""
						}, {
							title: '待支付',
							status: 'created'
						}, {
							title: '已支付',
							status: 'paid'
						}, {
							title: '已使用',
							status: 'used'
						}
						// , {
						// 	title: '已退款',
						// 	status: 'refunded'
						// },
					]
				},
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
			if (options.state != 0) {
				this.$set(this.navList, 'current', String(options.state));
			};
		},
		onReachBottom() {
			this.init();
		},
		onShow() {
			this.list = [];
			this.page = 1;
			this.loadding = 'more';
			this.loadding_lock = false;
			this.empty = {
				show: false,
				id: 3
			};
			this.init();
		},
		computed: {
			...mapState(['uerInfo']),
		},
		methods: {
			...mapMutations([]),
			navTo(id) {
				uni.navigateTo({
					url: "/pages/user/order_detail?id=" + id
				})
			},
			navClick(index, opiton) {
				this.list = [];
				this.page = 1;
				this.loadding = 'more';
				this.loadding_lock = false;
				this.$set(this.navList, 'current', String(opiton.status));
				this.status = opiton.status;
				this.init();
			},
			statusName(e, val) {
				// if(val.iscomment == true){
				// 	e = 'iscomment'
				// }
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
			Addcomment(id) {
				uni.navigateTo({
					url: "/pages/user/commentAdd?id=" + id
				})
			},
			init() {
				if (this.loadding == 'no-more') {
					return false;
				};
				this.$api
					.httpRequest(
						`/ticket/getOrderList`, {
							page: this.page,
							page_size: 12,
							status: this.status,
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
	.nav-box {
		width: 100%;
		margin: auto;
		background-color: #f7f7f7;
		z-index: 9;
	}

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
					font-size: 30upx;
					font-weight: bold;
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
					width: 78%;
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