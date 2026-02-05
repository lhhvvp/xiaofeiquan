<template>
	<view class="base-box" :style="'background-image: url(' + slide + ')'">
		<view class="item">
			<view class="title-box">
				<view class="bg"></view>
				<view class="title">{{ title }}</view>
				<view class="bg-reverse"></view>
			</view>
			<view class="coupons-content">
				<view class="coupons-items" v-for="(info, i) in list" :key="i" @click.stop="couponsInfo(info.id)">
					<view class="coupons-item">
						<view class="left">
							<view class="left-cont" v-if="info.cid == 3 || info.cid == 4">{{ info.coupon_title }}</view>
							<view class="price" v-if="info.cid == 3 || info.cid == 4">{{ info.coupon_price }}</view>

							<view class="left-cont" v-if="info.cid != 3 && info.cid != 4">{{ title }}</view>
							<view class="prices" v-if="info.cid != 3 && info.cid != 4">
								<text>{{ info.coupon_title }}</text>
							</view>
						</view>
						<view class="right">
							<!-- <view class="info">*注:仅购买门票使用</view> -->
							<view class="right-but"
								style="background:linear-gradient(49deg, rgba(229,22,18,1) 0%, rgba(229,22,18,1) 26%, rgba(249, 96, 69, 1.0) 50%, rgba(229,22,18,1) 74%, rgba(229,22,18,1) 100%);color:#ffffff">
								领 取
							</view>
						</view>
					</view>
				</view>
			</view>
			<uni-load-more :status="loadding" v-if="!empty.show"></uni-load-more>
			<my-empty :empty="empty" v-if="empty.show"></my-empty>
		</view>
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
				page: 1,
				loadding: 'more',
				loadding_lock: false,
				empty: {
					show: false,
					id: 3
				},
				list: [],
				state: 0,
				title: '', //
				slide: '',
				opiton: null,

			};
		},
		// #ifdef MP-WEIXIN
		onShareAppMessage(res) {
			//微信小程序分享给朋友;
			return {
				title: '榆林市旅游消费平台',
				path: '/pages/list/list?state=' + JSON.stringify({ id:this.opiton.id, title:this.opiton.title })
			};
		},
		onShareTimeline(res) {
			//微信小程序分享朋友圈
			return {
				title: '榆林市旅游消费平台',
				path: '/pages/list/list?state=' + JSON.stringify({ id:this.opiton.id, title:this.opiton.title })
			};
		},
		// #endif
		onLoad(opiton) {
			
			let state = JSON.parse(opiton.state);;
			this.opiton = state;
			this.title = state.title;
			uni.setNavigationBarTitle({
				title: state.title
			});
			this.state = state.id;
			this.init();
			if (uni.getStorageSync('system').slide == null) {
				this.system();
			} else {
				this.slide = this.$api.urli + uni.getStorageSync('system').slide.image
			}
		},
		onReachBottom() {
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
						`/coupon/list`, {
							cid: this.state,
							userid: this.uerInfo.uid || 0,
							page: this.page,
							limit: 12
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
							data.map((it, i) => {
								if (it.is_use) {
									res.data[i].status = 3;
								}
							});
							this.list = [...this.list, ...data];

							if (data.length != 12) {
								//判断是否有下一页
								this.loadding = 'no-more';
								this.loadding_lock = true;
								return false;
							}

							if (data.length == 0) {
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
			statusName(val) {
				let obj = {};
				switch (val) {
					case 1:
						obj.name = '领  取';
						obj.color = '#fff';
						obj.bgcolor =
							'linear-gradient(49deg, rgba(229,22,18,1) 0%, rgba(229,22,18,1) 26%, rgba(249, 96, 69, 1.0) 50%, rgba(229,22,18,1) 74%, rgba(229,22,18,1) 100%)';
						break;
					case 2:
						obj.name = '已结束';
						obj.color = '#ffffff';
						obj.bgcolor = '#6a6a6a';
						break;
					case 3:
						obj.name = '已领取';
						obj.color = '#fff';
						obj.bgcolor = '#7e0100';
						break;
				}
				return obj;
			},
			current(e) {
				if (this.nav.current === e) {
					return false;
				}
				this.$set(this.nav, 'current', e);
				uni.navigateTo({
					url: `/pages/list/list?state=${e}`
				});
			},
			coupon(e) {
				let id = e.id;
				uni.navigateTo({
					url: `/pages/coupon/coupon?id=${id}`
				});
			},
			receive(couponId, status) {
				if (status == 2) {
					this.$api.msg('优惠券已领完!', 'none');
					return false;
				}
				if (status == 3) {
					this.$api.msg('您已经领取过了!', 'none');
					return false;
				}
				this.$api
					.httpRequest(
						`/coupon/receive`, {
							userid: this.uerInfo.uid,
							couponId
						},
						'POST'
					)
					.then(res => {
						if (res.code == 0) {
							this.$api.msg(res.msg, 'success');
							this.init();
						} else {
							this.$api.msg(res.msg, 'none');
						}
					});
			},
			couponsInfo(id) {
				uni.navigateTo({
					url: '/pages/coupon/coupon?id=' + id
				});
			},
			system() {
				this.$api.httpRequest(`/index/system`, {}, 'POST').then(res => {
					if (res.code == 0) {
						if (res.data.slide) {
							this.slide = this.$api.urli + res.data.slide.image;
						};
						uni.setStorageSync('system', res.data);
						this.slide = this.$api.urli + res.data.slide.image
					} else {
						this.$api.msg(res.msg, 'none');
					}
				});
			},
		}
	};
</script>

<style lang="scss">
	page {
		background-color: $div-bg-color;
	}

	.base-box {
		background-color: $div-bg-color;
		position: relative;
		background-size: 100%;
		background-repeat: no-repeat;
		padding-top: 528upx;
		padding-bottom: 20upx
	}

	.item {
		width: calc(95% - 50upx);
		padding: 25upx;
		margin: auto;
		border-radius: 20upx;
		background-color: #fffce1;
		position: relative;
		margin-bottom: 40upx;
		box-shadow: 4px 4px 4px rgba(0, 0, 0, 0.25);

		&::before {
			content: '';
			width: 40upx;
			height: 40upx;
			display: inline-block;
			border-radius: 50%;
			background-color: $div-bg-color;
			position: absolute;
			left: -20upx;
			top: calc(50% - 20upx);
		}

		&::after {
			content: '';
			width: 40upx;
			height: 40upx;
			display: inline-block;
			border-radius: 50%;
			background-color: $div-bg-color;
			position: absolute;
			right: -20upx;
			top: calc(50% - 20upx);
		}

		.title-box {
			font-size: 42upx;
			// 转变为行内块元素 文字渐变才会生效
			display: flex;
			justify-content: center;
			align-items: center;
			margin-bottom: 40upx;

			& .title {
				font-weight: bold;
				background: linear-gradient(to top, #932027, #bc3839);
				-webkit-background-clip: text;
				color: transparent;
				padding: 0 30upx;
			}

			& .bg {
				width: 100upx;
				height: 50upx;
				background-repeat: no-repeat;
				background-position: center;
				background-size: 100%;
				background-image: url('@/static/title.png');
			}

			& .bg-reverse {
				width: 100upx;
				height: 50upx;
				background-repeat: no-repeat;
				background-position: center;
				background-size: 100%;
				background-image: url('@/static/title.png');
				-webkit-transform: scaleX(-1);
				-moz-transform: scaleX(-1);
				-ms-transform: scaleX(-1);
				-o-transform: scaleX(-1);
				transform: scaleX(-1);
			}
		}
	}

	.coupons-content {
		width: 90%;
		margin: auto;
		display: flex;
		flex-wrap: wrap;
		margin-bottom: 40upx;
		justify-content: space-between;

		& .coupons-items {
			width: calc(45.5% - 40upx);
			overflow: hidden;
			margin-bottom: 16upx;
			display: flex;
			padding: 20upx 20upx 0;
			position: relative;

			&::before {
				content: '';
				position: absolute;
				width: 100%;
				background-color: #fcddbc;
				height: 70%;
				left: 0;
				bottom: 0;
				border-radius: 20px;
			}

			&::after {
				content: '';
				position: absolute;
				width: 100%;
				z-index: 1;
				height: 140upx;
				left: 0;
				bottom: 0;
				background-image: url('/static/bg1.png');
				background-size: 100% 100%;
				background-position: bottom;
				background-repeat: no-repeat;
			}
		}

		& .coupons-item {
			width: 100%;
			margin: auto;
			flex-direction: column;
			background: #e0120f;
			// background: linear-gradient(134deg, #ffc285 0%, #ffd8bb 25%, #ffe8d7 50%, #ffd8bb 65%, #ffc285 100%);
			border-radius: 15upx;
			position: relative;

			&:nth-last-child(1) {
				margin-bottom: 0;
			}

			&:nth-last-child(2) {
				margin-bottom: 0;
			}

			&::before {
				position: absolute;
				content: '';
				width: 20upx;
				height: 20upx;
				background-color: #fcddbc;
				right: -10upx;
				border-radius: 50%;
				top: calc(40% - 10upx);
			}

			&::after {
				position: absolute;
				content: '';
				width: 20upx;
				height: 20upx;
				left: calc(40% - 10upx);
				background-color: #fcddbc;
				left: -10upx;
				top: calc(40% - 10upx);
				border-radius: 50%;
			}

			& .left {
				width: 100%;
				display: flex;
				flex-direction: column;
				align-items: center;
				height: auto;

				& .price {
					color: #fff4e6;
					word-spacing: 0;
					width: 80%;
					text-align: center;
					border-top: dashed 1upx #ffffff61;
					font-weight: bold;
					font-size: 64upx;
					padding-top: 6upx;
					position: relative;

					&::before {
						content: '￥';
						font-size: 26upx;
					}
				}

				& .prices {
					color: #fff4e6;
					word-spacing: 0;
					width: 90%;
					height: 80upx;
					text-align: center;
					border-top: dashed 1upx #fff4e6;
					font-weight: bold;
					font-size: 30upx;
					padding-top: 8upx;
					display: flex;
					justify-content: center;
					align-items: center;

					& text {
						overflow: hidden;
						text-overflow: ellipsis;
						display: -webkit-box;
						-webkit-line-clamp: 2;
						-webkit-box-orient: vertical;
					}
				}

				& .left-cont {
					color: #fff4e6;
					margin-bottom: 5upx;
					min-height: 40upx;
					padding: 15upx 10upx 0;
					line-height: 1.1;
					text-align: center;
					font-size: 26upx;
					overflow: hidden;
					text-overflow: ellipsis;
					display: -webkit-box;
					-webkit-line-clamp: 2;
					-webkit-box-orient: vertical;
				}
			}

			& .right {
				width: 100%;
				display: flex;
				justify-content: center;
				align-items: center;
				flex-direction: column;
				font-weight: bold;
				color: #ee332b;
				height: 140upx;
			}

			& .info {
				color: #ef0000;
				position: relative;
				font-size: 20upx;
				font-weight: 100;
				z-index: 2;
			}

			& .right-but {
				color: #fff;
				width: 80%;
				position: relative;
				z-index: 2;
				text-align: center;
				padding: 10upx 0;
				margin-top: 6upx;
				border-radius: 30upx;
			}
		}
	}
</style>