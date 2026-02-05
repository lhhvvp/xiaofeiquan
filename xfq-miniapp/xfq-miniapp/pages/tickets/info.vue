<template>
	<view class="merchant">
		<view class="banner-box">
			<image :src="details.image" :style="'width:' + details.width + 'px;height:' + details.height + 'px;'">
			</image>
		</view>
		<view class="info">
			<view style="width: 75%;">
				<view class="title-box"></view>
				<view class="title">{{details.nickname || ''}}</view>
				<view class="time-move">
					<view class="time">
						<text>营业时间:</text>
						<text>{{details.do_business_time || ''}}</text>
					</view>
				</view>
				<view class="address-box">
					<view class="left">
						<view class="address">
							<uni-icons type="location-filled" size="16" style="margin-right: 5px;" color="#939393"
								@click="addressClick"></uni-icons>
							{{details.address || ''}}
						</view>
					</view>
				</view>
			</view>
			<view style="width: 25%;">
				<view class="score_max">{{details.comment_rate || 0}}<text style="font-size: 28upx;">分</text></view>
				<view class="score">
					<uni-rate :value="details.comment_rate" allow-half="true" size="16" disabled="true"></uni-rate>
				</view>
				<view class="score_min">{{details.comment_num || 0}}条评论</view>
			</view>
		</view>

		<view class="tickets" v-if="details.appt_open ==1">
			<view class="tit">分时预约</view>
			<view class="subscribe">
				<text @click="navToAll('/pages/user/subscribe/subscribe?seller_id='+seller_id)">立即预约</text>
			</view>
		</view>

		<view class="tickets-box" v-for="(item,index) in list">
			<view class="tit">{{item.title}}</view>
			<view class="items">
				<view class="item" @click.stop="infos(ticket)" v-for="(ticket,key) in item.ticket_list">
					<view class="left">
						<view class="left-tit">{{ticket.title}}</view>
						<view class="left-sales">
							<text>购买须知</text>
							<uni-icons type="right" size="14"></uni-icons>
						</view>
					</view>
					<view class="right">
						<view class="price">
							<text style="color: #932027;font-size: 24upx;">￥</text>
							<text style="margin: 0 10upx;color: #932027">{{ticket.min_price || '暂无'}}</text>
							<text style="font-size: 24upx;color: #3e3e3e;" v-if="ticket.min_price">起</text>
						</view>
						<view class="right-button" @click.stop="order(ticket.id)">预约</view>
					</view>
				</view>
			</view>
		</view>

		<view class="tickets">
			<view class="tit">推荐景区</view>
			<my-tickets :lists="ScenicList" @click="tickets" v-if="ScenicList && ScenicList.length != 0"></my-tickets>
		</view>

		<view class="tickets">
			<view class="tit">热门评论({{details.comment_num || 0}})</view>
			<my-comment :dataVal="commentVal" v-if="commentVal.length != 0"></my-comment>
			<view class="move" @click="comment()">查看更多<uni-icons type="right"></uni-icons></view>
		</view>

		<view class="shade" v-if="is_shade">
			<view class="shade-centent">
				<view class="shade-centent-close" @click="is_shade = !is_shade">
					<uni-icons type="closeempty"></uni-icons>
				</view>
				<view class="shade-header">
					<view class="shade-header-left" :style="'background-image: url('+details.image+');'"></view>
					<view class="shade-header-right">
						<view class="shade-header-right-tit">{{details.nickname}}</view>
						<view class="shade-header-right-number">地址：{{details.address}}</view>
						<!-- <view class="shade-header-right-number">已售2000+</view> -->
					</view>
				</view>
				<view style="overflow: auto;height: 900rpx;">
					<view class="shade-items">
						<view class="shade-items-tit">购买须知</view>
						<view class="shade-items-item">
							<rich-text :nodes="info.explain_buy"></rich-text>
						</view>
						<view class="shade-items-tit">使用须知</view>
						<view class="shade-items-item">
							<rich-text :nodes="info.explain_use"></rich-text>
						</view>
						<view class="shade-items-tit" v-if="info.rights_list.length !=0">核销须知</view>
						<view class="shade-items-item" v-if="info.rights_list.length !=0">
							该门票包含
							（<view v-for="(item,index) in info.rights_list" :key="index" style="color: #932027;display: inline-block;">
								<text v-if="index != 0">,</text> {{item.title}} 
							</view>）请到目的地进行核销
						</view>
					</view>

				</view>
			</view>
		</view>

	</view>
</template>

<script>
	import {
		mapState,
		mapMutations
	} from 'vuex';
	import {
		replaceContent,
		getLocation
	} from '@/common/common.js';
	export default {
		data() {
			return {
				info: {}, //弹窗详情
				seller_id: 0,
				is_shade: false, //控制
				list: [], //门票列表
				details: {}, //商户详情
				ScenicList: [], //热门推荐景区
				commentVal: [], //评论
			};
		},

		onLoad(option) {
			this.systemInfo = uni.getSystemInfoSync();
			let that = this;
			this.seller_id = option.seller_id;
			getLocation().then(
				success => {
					that.latitude = success.latitude;
					that.longitude = success.longitude;
					this.init();
					this.detail();
				},
				fail => {
					// 失败
				}
			);
			this.getScenicList();
		},
		// #ifdef MP-WEIXIN
		onShareAppMessage(res) {
			//微信小程序分享给朋友
			return {
				title: '榆林市旅游消费平台',
				path: '/pages/merchant/info/info?id=' + this.seller_id
			};
		},
		onShareTimeline(res) {
			//微信小程序分享朋友圈
			return {
				title: '榆林市旅游消费平台',
				path: '/pages/merchant/info/info?id=' + this.seller_id
			};
		},
		// #endif
		computed: {
			...mapState(['hasLogin', 'uerInfo'])
		},
		methods: {
			...mapMutations(['setMerchant']),
			infos(info) {
				this.is_shade = !this.is_shade;
				this.info = info
			},
			navToAll(url) {
				uni.navigateTo({
					url
				})
			},
			comment() {
				uni.navigateTo({
					url: "/pages/user/comment?mid=" + this.seller_id
				})
			},
			order(id) {
				uni.navigateTo({
					url: '/pages/tickets/order?id=' + id
				})
			},
			getCommentList() {
				this.$api.httpRequest(
						`/ticket/getCommentList`, {
							page: 1,
							page_size: 6,
							mid: this.seller_id,
						},
						'GET'

					)
					.then(res => {
						this.commentVal = res.data
					})
			},
			init() {
				this.$api
					.httpRequest(
						`/ticket/getTicketList`, {
							seller_id: this.seller_id
						},
						'GET'
					).then(res => {
						this.list = res.data;
						this.getCommentList()
					})
			},
			getScenicList() {
				this.$api
					.httpRequest(
						`/ticket/getScenicList`, {
							out_id: this.seller_id,
							page: 1,
							page_size: 5,
						},
						'GET'
					).then(res => {
						this.ScenicList = res.data;
					})
			},
			detail() {
				this.$api
					.httpRequest(`/seller/detail`, {
						seller_id: this.seller_id,
						latitude: this.latitude,
						longitude: this.longitude,
						// uid: this.uerInfo.uid 
					})
					.then(res => {
						this.details = res.data.detail
					})

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
		},
	}
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
		padding: 20upx 30upx 20upx;
		margin-bottom: 20upx;
		background-color: #fff;
		display: flex;
		align-items: center;

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

		}
	}

	.tickets {
		margin-bottom: 20upx;
		background-color: #ffffff;

		.subscribe {
			width: 100%;
			height: 160upx;
			text-align: center;
			display: flex;
			align-items: center;
			justify-content: center;

			text {
				padding: 24upx 50upx;
				background-color: $div-bg-color;
				color: #ffffff;
				border-radius: 20upx;
			}
		}

		.tit {
			font-weight: bold;
			font-size: 30upx;
			padding: 20rpx 30rpx 20rpx;
			border-bottom: 1upx solid #f7f7f7;
		}

		.move {
			padding: 20upx 0;
			border-top: 1upx solid #f7f7f7;
			color: #3e3e3e;
			text-align: center;
		}
	}

	.tickets-box {
		width: calc(95% - 40upx);
		padding: 20upx;
		border-radius: 20upx;
		margin: auto;
		margin-bottom: 20upx;
		background-color: #fff;

		.tit {
			font-weight: bold;
			font-size: 30upx;
			padding-bottom: 20upx;
			border-bottom: 1upx solid #f7f7f7;
		}

		.items {
			width: 100%;

			.item {
				display: flex;
				padding: 30upx 0;
				border-bottom: 1upx solid #f7f7f7;
				align-items: center;

				.left {
					width: 70%;

					&-tit {
						font-size: 30upx;
						margin-bottom: 10upx;
					}

					&-sales {
						font-size: 24upx;
						color: #3e3e3e;
						display: flex;
						align-items: center;
						font-weight: 100;
					}
				}

				.right {
					width: 30%;
					display: flex;
					flex-direction: column;
					align-items: center;

					.price {
						font-size: 40upx;
						font-weight: bold;
					}

					&-button {
						width: 70%;
						margin-top: 10upx;
						text-align: center;
						padding: 10upx;
						color: #fff;
						background-color: #932027;
						border-radius: 20upx;
					}
				}
			}
		}
	}

	.shade {
		width: 100%;
		height: 100%;
		position: fixed;
		background-color: #00000070;
		z-index: 99;
		left: 0;
		top: 0;

		&-centent {
			width: calc(100% - 40upx);
			height: 70%;
			padding: 40upx 20upx;
			position: absolute;
			background-color: #ffffff;
			bottom: 0;
			border-radius: 20upx 20upx 0 0;

			&-close {
				position: absolute;
				right: 20upx;
				top: 20upx;
			}

		}

		&-header {
			display: flex;

			&-left {
				width: 150upx;
				height: 150upx;
				border-radius: 20upx;
				margin-right: 20upx;
				background-size: cover;
				background-position: center center;
			}

			&-right {

				width: calc(90% - 150upx);
				height: 150upx;
				display: flex;
				flex-direction: column;
				justify-content: space-evenly;

				&-tit {
					font-weight: bold;
					font-size: 34upx;
					overflow: hidden;
					text-overflow: ellipsis;
					display: -webkit-box;
					-webkit-line-clamp: 2;
					-webkit-box-orient: vertical;
				}

				&-number {
					color: #6b6b6b;
				}
			}
		}

		&-items {
			width: 100%;
			padding-top: 24upx;

			&-tit {
				font-weight: bold;
				font-size: 30upx;
				margin-bottom: 10upx;
			}

			&-button {
				width: 35%;
				border: 1upx solid #f7f7f7;
				text-align: center;
				padding: 20upx 30upx;
				border-radius: 20upx;
			}

			&-item {
				width: 100%;
				margin-bottom: 40upx;

				&-left-lable {
					width: 15%;
					font-weight: 100;
					color: #3e3e3e;
					display: inline-block;
				}

				&-right-centent {
					margin-left: 10%;
					width: 65%;
					display: inline-block;
				}

				.border {
					border: 1upx solid #932027;
					padding: 4upx;
					text-align: center;
					border-radius: 5upx;
					color: #932027;
					border-radius: 10upx;
				}
			}
		}
	}

	.score_max {
		width: 100%;
		text-align: center;
		font-size: 48upx;
		font-weight: bold;
		color: #ffca3e;
	}

	.score_min {
		width: 100%;
		text-align: center;
		font-size: 28upx;
		color: #847f35;
	}

	.score {
		display: flex;
		justify-content: center;
	}
</style>