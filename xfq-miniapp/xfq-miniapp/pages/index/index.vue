<template>
	<view>
		<my-header></my-header>
		<view class="base-box" :style="'background-image: url(' + slide + ')'" v-if="is_hide">
			<view class="base">
				<view class="item nav">
					<view class="item-nav">
						<my-nav :graphic="true" :navListArray="navList" @current="navClick"></my-nav>
					</view>
					<view class="notice-bar">
						<view class="icons" @click="iconsList"><uni-icons type="sound" size="22"
								color="#a00000"></uni-icons></view>
						<swiper class="swiper" autoplay interval="5000" :circular="true" :vertical="true">
							<swiper-item class="swiper-item" @click="infoClick(item.id)" v-for="(item, index) in news"
								:key="index">{{ item.title }}</swiper-item>
						</swiper>
						<view class="icons"><uni-icons type="right" size="18" color="#a00000"></uni-icons></view>
					</view>
				</view>
				<view class="item" v-for="(item, index) in list" :key="index" v-if="item.list.length != ''">
					<view class="title-box">
						<view class="bg"></view>
						<view class="title">{{ item.title }}</view>
						<view class="bg-reverse"></view>
					</view>
					<view class="coupons-content">
						<view class="coupons-items" v-for="(info, i) in item.list" :key="i"
							@click.stop="couponsInfo(info.id)">
							<view class="coupons-item">
								<view class="left">
									<!-- 旅行团 -->
									<view class="left-cont" v-if="info.cid == 3 || info.cid == 4">
										{{ info.coupon_title }}
									</view>
									<view class="price" v-if="info.cid == 3 || info.cid == 4">{{ info.coupon_price }}
									</view>
									<!-- 旅行团end -->
									<view class="left-cont" v-if="info.cid != 3 && info.cid != 4">{{ item.title }}
									</view>
									<view class="prices" v-if="info.cid != 3 && info.cid != 4">
										<text>{{ info.coupon_title }}</text>
									</view>
								</view>
								<view class="right">
									<!-- <view class="info">*注:仅购买门票使用</view> -->
									<view class="right-but"
										style="background:linear-gradient(134deg, #eec499 0%, #ffe3c6 20%, #fff4ea 50%, #ffe3c6 80%, #eec499 100%);color:#a00000">
										领 取
									</view>
								</view>
							</view>
						</view>
					</view>
					<view class="move" @click="moveTo(item.id, item.title)">查 看 更 多</view>
				</view>

				<view class="item">
					<view class="title-box">
						<view class="bg"></view>
						<view class="title">活动规则</view>
						<view class="bg-reverse"></view>
					</view>
					<view class="content"><rich-text :nodes="system_info"></rich-text></view>
				</view>
				<view class="line-height"></view>
			</view>
		</view>
		<zero-privacy :onNeed='false' :hideTabBar='true'></zero-privacy>
	</view>
</template>

<script>
	import {
		replaceContent
	} from '@/common/common.js';
	import {
		mapState,
		mapMutations
	} from 'vuex';

	export default {
		data() {
			return {
				list: [],
				system_info: '',
				navList: [],
				news: [],
				is_navList: true,
				slide: '',
				is_hide: false
			};
		},
		// #ifdef MP-WEIXIN
		onShareAppMessage(res) {
			//微信小程序分享给朋友
			return {
				title: '榆林市旅游消费平台',
				path: '/pages/index/index'
			};
		},
		onShareTimeline(res) {
			//微信小程序分享朋友圈
			return {
				title: '榆林市旅游消费平台',
				path: '/pages/index/index'
			};
		},
		// #endif
		onLoad() {
			this.system();
		},
		computed: {
			...mapState(['hasLogin', 'uerInfo'])
		},
		onPullDownRefresh() {
			Object.assign(this.$data, this.$options.data())
			this.system();
		},
		methods: {
			init() {

				this.$api
					.httpRequest(
						`/coupon/index`, {
							class_id: 1,
							type: 1,
							tag: 1,
							use_store: 1,
							userid: this.uerInfo.uid
						},
						'POST'
					)
					.then(res => {
						if (res.code == 0) {
							if (this.navList.length != 0) {
								this.is_navList = false;
							}
							res.data.forEach((items, index) => {
								if (this.is_navList) {
									this.navList.push({
										id: items.id,
										title: items.title,
										url: this.$api.urli + items.class_icon
									});

								};

								items.list.map((it, i) => {
									if (it.is_use) {
										res.data[index].list[i].status = 3;
									}
								});
							});
							this.list = res.data;
							this.is_hide = true;
						} else {
							this.$api.msg(res.msg);
						}
					});
			},
			tempApi() {
				this.$api
					.httpRequest(
						`/coupon/tempApi`, {
							class_id: 1,
							type: 1,
							tag: 1,
							use_store: 1,
							userid: 0
						},
						'POST'
					)
					.then(res => {
						if (res.code == 0) {
							if (this.navList.length != 0) {
								this.is_navList = false;
							}
							res.data.forEach((items, index) => {
								if (this.is_navList) {
									this.navList.push({
										id: items.id,
										title: items.title,
										url: this.$api.urli + items.class_icon
									});
								}

								items.list.map((it, i) => {
									if (it.is_use) {
										res.data[index].list[i].status = 3;
									}
								});
							});
							this.list = res.data;
							this.is_hide = true;
						} else {
							this.$api.msg(res.msg);
						}
					});
			},

			statusName(val) {
				let obj = {};
				switch (val) {
					case 1:
						obj.name = '领  取';
						obj.color = '#a00000';
						obj.bgcolor =
							'linear-gradient(134deg, #eec499 0%, #ffe3c6 20%, #fff4ea 50%, #ffe3c6 80%, #eec499 100%);';
						break;
					case 2:
						obj.name = '已结束';
						obj.color = '#ffffff';
						obj.bgcolor = '#6a6a6a';
						break;
					case 3:
						obj.name = '已领取';
						obj.color = '#a00000';
						obj.bgcolor = '#eec499';
						break;
				}
				return obj;
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
			system() {
				this.$api.httpRequest(`/index/system`, {}, 'POST').then(res => {
					if (res.code == 0) {
						if (res.data.slide) {
							this.slide = this.$api.urli + res.data.slide.image;
						};
						uni.stopPullDownRefresh();
						this.system_info = replaceContent(res.data.act_rule);
						this.newsList();
						uni.setStorageSync('system', res.data);
						if (res.data.is_open_api == 1) {
							this.tempApi();
						} else {
							this.init();
						}
					} else {
						this.$api.msg(res.msg, 'none');
					}
				});
			},
			newsList() {
				this.$api.httpRequest(`/index/note_index`, {}, 'POST').then(res => {
					if (res.code == 0) {
						if (res.data != null) {
							let array = [];

							Array.isArray(res.data) ? (array = res.data) : array.push(res.data);

							this.news = [...this.news, ...array];
						}
					} else {
						this.$api.msg(res.msg, 'none');
					}
				});
			},
			moveTo(id, title) {
				uni.navigateTo({
					url: '/pages/list/list?state=' + JSON.stringify({
						id,
						title
					})
				});
			},
			couponsInfo(id) {
				uni.navigateTo({
					url: '/pages/coupon/coupon?id=' + id
				});
			},
			iconsList() {
				uni.navigateTo({
					url: '/pages/news/news'
				});
			},
			infoClick(e) {
				uni.navigateTo({
					url: '/pages/news/info?id=' + e
				});
			},
			navClick(e) {
				let {
					id,
					title
				} = e.item;
				uni.navigateTo({
					url: '/pages/list/list?state=' + JSON.stringify({
						id,
						title
					})
				});
			}
		}
	};
</script>

<style lang="scss" scoped>
	page {
		background-color: $div-bg-color;
		height: 100vh;
	}

	.base-box {
		background-color: $div-bg-color;
		position: relative;
		background-size: 100%;
		background-repeat: no-repeat;
		padding-top: 528rpx;

		& .base {
			width: 90%;
			margin: 0 auto 0;
			overflow: hidden;

			& .item {
				width: calc(100% - 50upx);
				padding: 25upx;
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

				& .title-box {
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

				//end 公共
				& .coupons-content {
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
							background-color: $div-bg-color;
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
							background-image: url('/static/bg.png');
							background-size: 100% 100%;
							background-position: bottom;
							background-repeat: no-repeat;
						}
					}

					& .coupons-item {
						width: 100%;
						margin: auto;
						flex-direction: column;
						background: rgb(255, 216, 187);
						background: linear-gradient(134deg, #ffc285 0%, #ffd8bb 25%, #ffe8d7 50%, #ffd8bb 65%, #ffc285 100%);
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
							background-color: $div-bg-color;
							right: -10upx;
							border-radius: 50%;
							top: calc(50% - 10upx);
						}

						&::after {
							position: absolute;
							content: '';
							width: 20upx;
							height: 20upx;
							left: calc(40% - 10upx);
							background-color: $div-bg-color;
							left: -10upx;
							top: calc(50% - 10upx);
							border-radius: 50%;
						}

						& .left {
							width: 100%;
							display: flex;
							flex-direction: column;
							align-items: center;
							height: auto;

							// height: 150upx;
							& .price {
								color: $div-bg-color;
								word-spacing: 0;
								width: 80%;
								text-align: center;
								border-top: dashed 1upx #ee332b61;
								font-weight: bold;
								font-size: 64upx;
								padding-top: 8upx;
								position: relative;

								&::before {
									content: '￥';
									font-size: 26upx;
								}
							}

							& .prices {
								color: $div-bg-color;
								word-spacing: 0;
								width: 90%;
								height: 80upx;
								text-align: center;
								border-top: dashed 1upx #ee332b61;
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
								width: 90%;
								color: $div-bg-color;
								padding-top: 15upx;
								padding: 15upx 5upx 0;
								margin-bottom: 5upx;
								min-height: 40upx;
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
							color: $div-bg-color;
							height: 140upx;
						}

						& .info {
							color: #ffb5b5;
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

				.move {
					width: 200upx;
					margin: auto;
					color: #fff;
					text-align: center;
					border-radius: 20upx;
					padding: 15upx 20upx;
					background-color: $div-bg-color;
				}
			}

			& .nav {
				width: 100%;
				overflow: hidden;
				padding: 25upx 0upx 0;

				& .item-nav {
					width: 90%;
					padding-bottom: 20upx;
					margin: auto;
				}

				& .notice-bar {
					display: flex;
					width: 100%;
					box-sizing: border-box;
					flex-direction: row;
					align-items: center;
					padding: 7px 12px;
					border-radius: 0 0 10px 10px;
					border-top: dashed 1px #a0000055;
					background-color: rgba(225, 190, 147, 0.37);
					display: flex;
					justify-content: space-between;
					align-content: center;

					& .swiper {
						width: calc(100% - 50px);
						height: 50upx;

						& .swiper-item {
							line-height: 50upx;
							color: $div-bg-color;
							overflow: hidden;
							text-overflow: ellipsis;
							display: -webkit-box;
							-webkit-line-clamp: 1;
							-webkit-box-orient: vertical;
						}
					}

					& .icons {
						width: 22px;
						padding-right: 3px;
					}
				}
			}
		}

		& .line-height {
			height: 20upx;
		}
	}
</style>