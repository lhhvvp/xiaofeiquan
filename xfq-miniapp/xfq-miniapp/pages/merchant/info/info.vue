<template>
	<view class="merchant">
		<view class="banner-box">
			<image :src="info.image" :style="'width:' + info.width + 'px;height:' + info.height + 'px;'"></image>
			<view class="right" v-if="!!info.distance">
				<text>距离商家</text>
				<text>{{ info.distance.toFixed(2) }}km</text>
			</view>
		</view>
		<view class="info">
			<view class="title-box"></view>
			<view class="title">{{ info.nickname || '' }}</view>
			<view class="time-move">
				<view class="time">
					<text>营业时间:</text>
					<text>{{ info.do_business_time != '' ? info.do_business_time : '-' }}</text>
				</view>
			</view>
			<view class="address-box">
				<view class="left">
					<view class="address">
						<uni-icons type="location-filled" size="16" style="margin-right: 5px;" color="#939393" @click="addressClick"></uni-icons>
						{{ info.address || '' }}
					</view>

					<view class="address">
						<uni-icons type="phone-filled" size="16" style="margin-right: 5px;" color="#939393" @click="telClick"></uni-icons>
						<text style="font-weight: bold;font-size: 30upx;color: #a00000;">{{ info.mobile || '' }}</text>
					</view>
				</view>
			</view>
			<my-readMore :hideLineNum="4" :showHeight="100" v-if="info.content"><rich-text :nodes="info.content" v-if="info.content"></rich-text></my-readMore>

			<view class="my-nav"><my-nav :graphic="true" :navListArray="navList" @current="navClick" :padding="false"></my-nav></view>
		</view>

		<view class="coupon" v-if="is_show">
			<view class="title">优惠券</view>
			<my-coupon-list :lists="couponList" @click="coupon" v-if="is_show" v-on:ToReceive="ToReceive"></my-coupon-list>
		</view>

		<view class="coupon" v-if="is_branch && info.seller_child_node.length != 0">
			<view class="title">
				<view>分支机构</view>
				<view></view>
			</view>
			<view class="list-box">
				<view class="item" v-for="(item, index) in info.seller_child_node" :key="index">
					<view class="right">
						<view class="tit">
							<text>{{ item.nickname }}</text>
							<text @click="SignUpImmediately('', info.id, item.id)">立即报名</text>
						</view>
						<view class="content">
							<view class="tel">
								<uni-icons type="person-filled" color="#a00000" size="14" style="margin-right: 20upx;"></uni-icons>
								<text>{{ item.name }}</text>
							</view>
							<view class="time" @click="telClick(item.mobile)">
								<view>
									<uni-icons type="phone-filled" color="#a00000" size="14" style="margin-right: 20upx;"></uni-icons>
									{{ item.mobile }}
								</view>
								<uni-icons type="forward" size="14"></uni-icons>
							</view>
							<view class="address" @click="addressClick({ address: item.address, latitude: item.latitude, longitude: item.longitude })">
								<view>
									<uni-icons type="location-filled" color="#a00000" size="14" style="margin-right: 20upx;"></uni-icons>
									<text>{{ item.address }}</text>
								</view>
								<uni-icons type="forward" size="14"></uni-icons>
							</view>
						</view>
					</view>
				</view>
			</view>
		</view>

		<view class="coupon" v-if="lineListArray.length != 0 && is_branch">
			<view class="title">
				<view>线路信息</view>
				<view @click="navtoLine('/pages/coupon/lineList?flag=2&id=' + seller_id)">
					查看更多
					<uni-icons type="forward" size="14"></uni-icons>
				</view>
			</view>

			<view class="product-items">
				<view class="product-item" v-for="(item, index) in lineListArray" :key="index" @click="merchant(item)">
					<view class="product-top" :style="'background-image: url(' + item.images + ');'"></view>
					<view class="product-bottom">
						<view class="tit">
							<text>{{ item.title }}</text>
						</view>
						<view class="content">
							<view class="tags">
								<text>{{ item.tags[0] }}</text>
							</view>
							<view class="price-box">
								<view class="price">{{ item.price }}</view>
								<view class="eyes">
									<uni-icons type="eye" style="margin-right: 10upx;"></uni-icons>
									{{ item.access_count }}
								</view>
							</view>
						</view>
					</view>
				</view>
			</view>
		</view>
		<view :class="is_branch ? 'line' : 'line-sm'"></view>
		<view class="bottom" v-if="is_branch">
			<view class="left">
				<view @click="navTo('/pages/index/index', 'index')">
					<uni-icons type="home" size="24"></uni-icons>
					<text>首页</text>
				</view>
				<view @click="navTo('/pages/coupon/lineList', 'line')">
					<uni-icons type="bars" size="24"></uni-icons>
					<text>线路</text>
				</view>
			</view>
			<view class="right"><view class="bottom-button" @click="SignUpImmediately('all', seller_id)">立即报名</view></view>
		</view>
	</view>
</template>

<script>
import { mapState, mapMutations } from 'vuex';
import { getImageInfo, replaceContent, formatDate, dateTime,getLocation } from '@/common/common.js';
export default {
	data() {
		return {
			info: {},
			list: [],
			systemInfo: null,
			couponList: [],
			latitude: 0,
			longitude: 0,
			is_show: false,
			is_branch: false,
			navList: [
				{
					url: '/static/icon/navigation.png',
					type: 'address',
					title: '导航',
					is_button: undefined
				},
				{
					url: '/static/icon/tels.png',
					type: 'tel',
					title: '电话',
					is_button: undefined
				},
				{
					url: '/static/icon/collect.png',
					type: 'collect',
					title: '收藏',
					is_button: undefined
				},
				{
					url: '/static/icon/share-.png',
					title: '分享',
					type: 'share',
					is_button: 'share'
				}
			],
			seller_id: 0,
			lineListArray: []
		};
	},
	
	onLoad(option) {
		this.systemInfo = uni.getSystemInfoSync();
		let that = this;
		this.seller_id = option.id;
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
		
	},
	// #ifdef MP-WEIXIN
	onShareAppMessage(res) {
		//微信小程序分享给朋友
		return {
			title: '榆林市旅游消费平台',
			path: '/pages/merchant/info/info?id='+this.seller_id
		};
	},
	onShareTimeline(res) {
		//微信小程序分享朋友圈
		return {
			title: '榆林市旅游消费平台',
			path: '/pages/merchant/info/info?id='+this.seller_id
		};
	},
	// #endif
	computed: {
		...mapState(['hasLogin', 'uerInfo'])
	},
	methods: {
		...mapMutations(['setMerchant']),
		init() {
			this.$api
				.httpRequest(`/seller/detail`, {
					seller_id: this.seller_id,
					latitude: this.latitude,
					longitude: this.longitude,
					// uid: this.uerInfo.uid 
				})
				.then(res => {
					if (res.code == 0) {
						let data = res.data;
						data.detail.content = replaceContent(data.detail.content);
						this.info = data.detail;
						let datacoupon = data.coupon.map((item, index) => {
							let time,
								desc = null;
							if (item.is_permanent == 1) desc = '有效期：永久';
							if (item.is_permanent == 2) desc = `有效期：${dateTime(item.coupon_time_start)} - ${dateTime(item.coupon_time_end)}`;
							if (item.is_permanent == 3) desc = `有效期：${item.day != 0 ? '领取后' + item.day + '天' : '已过期'}`;

							if (item.limit_time == 0) {
								time = '发放时间：不限时';
							} else {
								time = `发放时间：${dateTime(item.start_time)} - ${dateTime(item.end_time)}`;
							}
							return {
								id: item.id,
								title: item.coupon_title,
								desc: desc,
								time: time,
								price: Number(item.coupon_price),
								status: 1,
								item: item
							};
						});

						if (data.detail.class_id != 3) {
							this.is_show = true;
							this.couponList = datacoupon;
						}
						if (data.detail.class_id == 3) {
							this.is_branch = true;
							this.lineList();
						}
						if (data.detail.image) {
							data.detail.image = this.$api.urli + data.detail.image;
							this.getImageInfo(data.detail.image);
						}
					}
				});
		},
		collection() {
			let action = '';
			let title = this.navList[2].title;

			if (title === '取消收藏') {
				action = 'del';
			}
			if (title === '收藏') {
				action = 'add';
			}
			// coll
			this.$api
				.httpRequest(
					`/user/collection_action`,
					{
						action: action,
						uid: this.uerInfo.uid,
						mid: this.seller_id
					},
					'POST'
				)
				.then(res => {
					if (res.code == 0) {
						this.$api.msg(res.msg, 'none');
						if (action == 'del') {
							this.$set(this.navList, 2, {
								url: '/static/icon/collect.png',
								type: 'collect',
								title: '收藏'
							});
						} else {
							this.$set(this.navList, 2, {
								url: '/static/icon/collect-hover.png',
								type: 'collect',
								title: '取消收藏'
							});
						}
					} else {
						this.$api.msg(res.msg);
					}
				});
		},
		coupon(e) {
			let id = e.id;
			uni.navigateTo({
				url: `/pages/coupon/coupon?id=${id}`
			});
		},
		ToReceive(e) {
			let id = e.id;
			uni.navigateTo({
				url: `/pages/coupon/coupon?id=${id}`
			});
		},
		async getImageInfo(image) {
			let info = await uni.getImageInfo({ src: image });
			let { width, height } = info[1];
			let multiple = this.systemInfo.windowWidth / width;
			this.$set(this.info, 'width', multiple * width);
			this.$set(this.info, 'height', multiple * height);
		},
		addressClick(obj = false) {
			let address,
				longitude,
				latitude = null;
			if (!obj) {
				address = this.info.address;
				longitude = this.info.longitude;
				latitude = this.info.latitude;
			} else {
				longitude = obj.longitude;
				latitude = obj.latitude;
				address = obj.address;
			}
			uni.openLocation({
				name: address,
				longitude: Number(longitude),
				latitude: Number(latitude)
			});
		},
		telClick(mobile = this.info.mobile) {
			uni.makePhoneCall({
				phoneNumber: mobile
			});
		},
		merchant(item) {
			uni.navigateTo({
				url: '/pages/coupon/lineInfo?id=' + item.id
			});
		},
		navClick(info) {
			if (info.item.type == 'address') {
				this.addressClick();
				return false;
			}
			if (info.item.type == 'tel') {
				this.telClick();
				return false;
			}
			if (info.item.type == 'collect') {
				this.collection();
				return false;
			}
		},
		navTo(url, type) {
			if (type == 'index') {
				uni.switchTab({
					url
				});
			}
			if (type == 'line') {
				uni.navigateTo({
					url: url + '?flag=2&id=' + this.seller_id
				});
			}
		},
		lineList() {
			const couponId = this.seller_id;
			this.$api
				.httpRequest(
					`/coupon/line_list`,
					{
						flag: 2,
						couponId,
						limit: 8,
						page: 1
					},
					'POST'
				)
				.then(res => {
					if (res.code == 0) {
						let data = res.data;
						data.map((item, index) => {
							item.images = this.$api.urli + item.images;
							item.tags = item.tags.split(',');
							return item;
						});
						this.lineListArray = data;
					} else {
						this.$api.msg(res.msg, 'none');
					}
				});
		},
		navtoLine(url) {
			uni.navigateTo({
				url: url
			});
		},
		SignUpImmediately(type, mid = 0, mid_sub = 0) {
			let that = this;
			uni.showModal({
				title: '提示',
				content: '你确定要报名吗？',
				success(res) {
					if (res.confirm) {
						let data = {
							// uid,// mid,// mid_sub //机构id
						};

						if (type == 'all') {
							data.uid = that.uerInfo.uid;
							data.mid = mid;
						} else {
							data.uid = that.uerInfo.uid;
							data.mid = mid;
							data.mid_sub = mid_sub;
						}
						that.$api.httpRequest(`/user/addguest`, data, 'POST').then(ref => {
							if (res.code == 0) {
								that.$api.msg(ref.msg, 'success');
							} else {
								that.$api.msg(ref.msg, 'none');
							}
						});
					}
				}
			});
		}
	}
};
</script>

<style lang="scss" scoped>
.banner-box {
	width: 100%;
	position: relative;
	& .right {
		display: flex;
		position: absolute;
		bottom: 26upx;
		right: 20upx;
		flex-direction: column;
		width: 20%;
		text-align: center;
		background: #a00000;
		color: #fff;
		font-size: 22upx;
		line-height: 27upx;
		border-radius: 10px;
		padding: 10upx 0;
		& text:last-child {
			color: #fff4a2;
			font-weight: bold;
		}
	}
	& image {
		width: 100%;
	}
}
.info {
	width: calc(100% - 60upx);
	padding: 20upx 30upx 0;
	margin-bottom: 20upx;
	background-color: #fff;
	& .title {
		font-size: 32upx;
		font-weight: bold;
		margin-bottom: 20upx;
	}
	& .time-move {
		width: 100%;
		display: flex;
		justify-content: space-between;
		padding-bottom: 20upx;
		& .time {
			width: 100%;
			display: flex;
			align-items: center;
			font-size: 22upx;

			& text:first-child {
				display: inline-block;
				background-color: $div-bg-color;
				padding: 8upx 16upx;
				border-radius: 10upx 0 0 10upx;
				color: #fff;
			}
			& text:last-child {
				background-color: #f7f7f7;
				padding: 8upx 16upx;
				border-radius: 0 10upx 10upx 0;
			}
		}
		& .move {
			color: #8b8b8b;
			display: flex;
			font-size: 22upx;
			align-items: center;
		}
	}
	& .my-nav {
		width: 100%;
		border-top: 1px solid #f7f7f7;
		border-bottom: 1px solid #f7f7f7;
	}
	& .address-box {
		width: 100%;
		padding: 20upx 0;
		border-bottom: 1px solid #f7f7f7;
		display: flex;
		align-items: center;
		& .left {
			width: 100%;
			& .address {
				width: 100%;
				padding-bottom: 25upx;
				display: flex;
				color: #939393;
				align-items: center;
				&:last-child {
					padding-bottom: 0upx;
				}
			}
		}
	}
}
.coupon {
	width: calc(100% - 60upx);
	padding: 20upx 30upx 0;
	margin-bottom: 20rpx;
	background-color: #fff;
	& .title {
		font-size: 32upx;
		font-weight: bold;
		border-bottom: 1px solid #e6e6e6;
		padding-bottom: 20upx;
		margin-bottom: 30upx;
		width: 100%;
		display: flex;
		justify-content: space-between;
		align-items: center;
		& view:last-child {
			font-weight: 100;
			height: 40upx;
			line-height: 40upx;
			font-size: 24upx;
			color: #747474;
			display: flex;
			align-items: center;
		}
	}
	& .product-items {
		width: 100%;
		display: flex;
		flex-wrap: wrap;
		justify-content: space-between;
		& .product-item {
			width: 48%;
			background-color: #fff;
			box-shadow: 7px 10px 20px #e8e8e8;
			border-radius: 20upx;
			margin-bottom: 20upx;
			overflow: hidden;
			& .product-top {
				width: 100%;
				height: 240upx;
				background-size: cover;
			}
			& .product-bottom {
				width: calc(100% - 40upx);
				padding: 20upx;
				& .tit {
					width: 100%;
					font-size: 30upx;
					font-weight: bold;
					padding-bottom: 10upx;
					overflow: hidden;
					text-overflow: ellipsis;
					display: -webkit-box;
					-webkit-line-clamp: 2;
					-webkit-box-orient: vertical;
				}
				& .tags {
					width: 100%;
					margin-bottom: 10upx;
					& text {
						background-color: $div-bg-color;
						border-radius: 10upx;
						padding: 5upx 10upx;
						margin-right: 10upx;
						display: inline-block;
						font-size: 20upx;
						color: #ffe6e6;
					}
					& text :last-child {
						margin-right: 0upx;
					}
				}
				& .price-box {
					display: flex;
					justify-content: space-between;
					align-items: center;
					& .price {
						position: relative;
						font-size: 36upx;
						font-weight: bold;
						color: $div-bg-color;
						&::before {
							content: '￥';
							font-size: 24upx;
						}
					}
					& .eyes {
						color: #bcbcbc;
						display: flex;
						align-items: center;
					}
				}
			}
		}
	}
	& .list-box {
		width: 100%;
		& .to-sign-up {
			width: 100%;
			margin-bottom: 50upx;
			padding-bottom: 70upx;
			border-bottom: 1upx solid #ececec;
			position: relative;

			& view {
				width: 50%;
				margin: auto;
				text-align: center;
				font-size: 30upx;
				border-radius: 50upx;
				color: #fff;
				background: rgb(226, 18, 120);
				background: linear-gradient(90deg, rgba(226, 18, 120, 1) 0%, rgba(247, 91, 108, 1) 100%);
				animation-name: buttonBoxshow;
				animation-timing-function: ease-in-out; // 动画执行方式，linear：匀速；ease：先慢再快后慢；ease-in：由慢速开始；ease-out：由慢速结束；ease-in-out：由慢速开始和结束；
				animation-delay: 0s; // 动画延迟时间
				animation-iteration-count: infinite; //  动画播放次数，infinite：一直播放
				animation-duration: 2s; // 动画完成时间
				padding: 20upx 0;
			}
		}

		& .item {
			width: 100%;
			background-color: #ffffff;
			margin-bottom: 20upx;
			padding-bottom: 20upx;
			border-bottom: 1upx solid #ececec;
			display: flex;
			justify-content: space-between;

			& .right {
				width: 100%;
				display: flex;
				justify-content: center;
				flex-direction: column;
				& .tit {
					width: 100%;
					font-weight: bold;
					font-size: 30upx;
					display: flex;
					align-items: center;
					justify-content: space-between;
					& text:first-child {
						width: 85%;
						display: inline-block;
						overflow: hidden;
						display: -webkit-box;
						text-overflow: ellipsis;
						-webkit-line-clamp: 1;
						-webkit-box-orient: vertical;
					}
					& text:last-child {
						width: 15%;
						font-size: 20upx;
						font-weight: 500;
						background-color: $div-bg-color;
						color: #fff;
						border-radius: 20upx;
						padding: 8upx 0;
						text-align: center;
					}
				}
				& .content {
					width: 100%;
					padding-top: 10upx;
					display: flex;
					flex-direction: column;
					color: #747474;
					font-size: 26upx;
					justify-content: flex-end;
					& view {
						margin-bottom: 5upx;
					}
					& .address {
						display: flex;
						justify-content: space-between;
						& view {
							display: -webkit-box;
							overflow: hidden;
							text-overflow: ellipsis;
							-webkit-line-clamp: 1;
							-webkit-box-orient: vertical;
						}
					}
					& .time {
						display: flex;
						justify-content: space-between;
					}
				}
			}
		}
		& .item:last-child {
			margin-bottom: 0px;
		}
	}
}
.coupon-not-padding {
	width: calc(100% - 60upx);
	padding: 0upx 30upx;
	background-color: #fff;
	margin-bottom: 20upx;
}

@keyframes buttonBoxshow {
	0% {
		box-shadow: 0upx 2upx 10upx 0upx #eb4459;
	}
	50% {
		box-shadow: 0upx 9upx 25upx 0upx #eb4459;
	}
	100% {
		box-shadow: 0upx 2upx 10upx 0upx #eb4459;
	}
}
.bottom {
	width: calc(100% - 40upx);
	padding: 0 20upx;
	position: fixed;
	bottom: 0;
	background-color: #fff;
	border-top: 1px solid #f7f7f7;
	height: 100upx;
	display: flex;
	align-items: center;
	& .left {
		width: 38%;
		display: flex;
		margin-left: 1%;
		align-items: center;
		& view {
			width: 130upx;
			text-align: center;
			color: $div-bg-color;
			& text {
				display: block;
			}
		}
	}
	& .right {
		width: 60%;
		& .bottom-button {
			width: 80%;
			margin: auto;
			text-align: center;
			color: #fff;
			padding: 15upx 0;
			border-radius: 30upx;
			background-color: $div-bg-color;
		}
	}
}
.line {
	height: 100upx;
}
.line-sm {
	height: 20upx;
}
</style>
