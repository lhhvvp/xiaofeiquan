<template>
	<view class="coupon-box">
		<view class="coupon">
			<view class="content-coupon">
				<view class="daytime" v-if="info.daytime && info.use_type != 1">{{ info.daytime }}</view>
				<view class="info" v-if="info.couponIssue.cid == 4 || info.couponIssue.cid == 6">
					金额:{{ info.coupon_price }}</view>

				<view class="title">{{ info.coupon_title }}</view>
				<!-- 线下核销券 -->
				<view v-if="info.use_type != 1" class="qrcode">
					<image v-if="!isQrcode && info.status == 1" src="/static/001.png"></image>
					<image v-if="!isQrcode && info.status == 2" src="/static/002.png"></image>
					<uqrcode v-if="isQrcode" ref="qrcode" canvas-id="qrcode" :start="false" :value="qrcodeVal">
					</uqrcode>
				</view>
				<!-- 线上核销券、过期情况 -->
				<view v-if="info.use_type == 1 && info.status == 2" class="qrcode">
					<image src="/static/002.png"></image>
				</view>
				<!-- 领券用户信息 -->
				<view class="user-info">
					<view class="info-name">{{ info.name }}</view>
					<view class="info-id-number">{{ info.idcard }}</view>
				</view>
			</view>
			<view class="items">
				<view class="item" v-if="info.time_end != 0 && info.time_start != 0">
					<view class="item-left"><uni-icons type="shop" color="#ae000d" size="20"></uni-icons></view>
					<view class="item-rigth">{{ info.time }}</view>
				</view>
			</view>
			<view class="info-box">
				<view class="info" @click.stop="navToList()">
					<view class="left">适用于</view>
					<view class="right">
						<view class="name-title">{{ JSON.stringify(suitable) != '{}' ? suitable.address : '暂无' }}</view>
						<view class="name-icons">
							<view v-if="!!suitable.distance">
								{{ JSON.stringify(suitable) != '{}' ? suitable.distance + 'km' : '' }}
							</view>
							<uni-icons type="right"></uni-icons>
						</view>
					</view>
				</view>
			</view>
		</view>

		<!-- 线上核销、收货地址信息 -->
		<view v-if="info.use_type == 1 && info.status != 2" class="instructions">
			<view class="title">收货地址</view>
			<view v-if="!getAddress" class="content" style="display: flex;justify-content: space-between;align-items: center;">
				<view>
					<text>{{ delivery.delivery_user }} {{ delivery.delivery_phone }}</text><br>
					<text>{{ delivery.delivery_address }}</text>
				</view>
				<view style="width: 40%;">
					<button v-if="!submitAddress && send" size="mini" @click="queryLogistics">查看物流</button>
					<button v-if="!submitAddress && !send" size="mini" disabled>待发货</button>
					<button v-if="submitAddress" size="mini" @click="submitAddressFun">提交地址</button>
				</view>
			</view>
			<button v-if="getAddress" @click="chooseAddress">选择收货地址</button>
		</view>
		<view class="instructions">
			<view class="title">领取时间</view>
			<view class="content">{{ info.create_time }}</view>
		</view>
		<view class="instructions">
			<view class="title">使用细则</view>
			<view class="content"><rich-text :nodes="info.remark"></rich-text></view>
		</view>
		<view class="instructions" v-if="info.use_type == 1">
			<view class="title">核销规则</view>
			<view class="content"><rich-text :nodes="info.use_type_desc"></rich-text></view>
		</view>
		<view class="instructions" v-if="info.writeoff != undefined">
			<view class="title">核销信息</view>
			<view class="content">
				<view>核销人：{{ info.writeoff.users.name }}</view>
				<view>核销时间：{{ info.writeoff.create_time }}</view>
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
		dateTime,
		getLocation
	} from '@/common/common.js';
	export default {
		data() {
			return {
				info: {},
				id: 0,
				latitude: 0,
				longitude: 0,
				suitable: {},
				qrcodeVal: null,
				timer: null,
				isQrcode: true, //true核销的码,false//过期和已核销.
				getAddress: true, // 用户是否获取地址
				delivery: {}, // 地址
				submitAddress: false, // 提交地址
				send: false, // 是否发货
				trackingNumber: null
			};
		},
		onLoad(option) {
			const that = this;
			that.id = that.merchant.id;
			that.info = that.merchant.item;
			wx.setVisualEffectOnCapture({
				visualEffect: 'hidden'
			});
			getLocation(false).then(
				success => {
					that.latitude = success.latitude;
					that.longitude = success.longitude;
					that.init();
				},
			);
			that.setRefresh(true);
			setInterval(() => {
				that.daytime();
			}, 1000);
		},
		onUnload() {
			clearInterval(this.timer);
		},

		computed: {
			...mapState(['uerInfo', 'merchant', 'is_refresh'])
		},
		methods: {
			...mapMutations(['setRefresh']),
			navToList() {
				uni.navigateTo({
					url: '/pages/coupon/list?id=' + this.info.couponIssue.id
				});
			},
			applicableto(id) {
				this.$api
					.httpRequest(
						`/coupon/applicableto`, {
							id: id,
							latitude: this.latitude,
							longitude: this.longitude,
							page: 0,
							limit: 1
						},
						'POST'
					)
					.then(res => {
						if (res.code == 0) {
							if (res.data.length == 0) return false;
							this.$set(this.suitable, 'address', res.data[0].nickname);
							if (!!res.data[0].distance) {
								this.$set(this.suitable, 'distance', res.data[0].distance.toFixed(2));
							} else {
								this.suitable.distance = 0;
							}
						} else {
							this.$api.msg(res.msg, 'none');
						}
					});
			},
			init() {
				this.$api
					.httpRequest(
						`/coupon/idtocoupon`, {
							cuid: this.id
						},
						'POST'
					)
					.then(res => {
						if (res.code == 0) {
							this.info.remark = res.data.remark;
							if (res.data.is_permanent == 2) {
								//是否永久有效 0否 1是
								this.info.time = `有效时间：${res.data.coupon_time_start != 0 ? dateTime(res.data.coupon_time_start) : '-'} 至 ${
								res.data.coupon_time_end != 0 ? dateTime(res.data.coupon_time_end) : '-'
							}`;
							} else if (res.data.is_permanent == 1) {
								this.info.time = `有效时间：永久有效`;
							} else {
								this.info.time = `有效时间：${res.data.day}天`;
							}

							this.info.writeoff = res.data.writeoff;
							this.info.use_type = res.data.use_type;
							this.info.use_type_desc = res.data.use_type_desc;
							if (this.info.use_type == 1) {
								// 线上核销不显示二维码
								this.isQrcode = false;
								// 物流信息
								if (res.data.delivery != null && res.data.delivery.delivery_address != '' &&
									res.data.delivery.delivery_user != '' && res.data.delivery.delivery_phone != '' &&
									res.data.delivery.delivery_address != 'undefined' &&
									res.data.delivery.delivery_user != 'undefined' && res.data.delivery.delivery_phone != 'undefined') {
									this.delivery = res.data.delivery;
									this.getAddress = false;
									if (this.delivery.tracking_number != '') {
										this.send = true;
										this.trackingNumber = this.delivery.tracking_number;
									}
								}
							} else if (this.info.status == 0) {
								this.qrCode();
								uni.setScreenBrightness({
									value: 1
								});
							} else {
								this.isQrcode = false;
							}
							this.applicableto(res.data.id);
						} else {
							this.$api.msg(res.msg, 'none');
						}
					});
			},

			qrCode() {
				clearTimeout(this.timer);
				this.$api.httpRequest(`/index/system`, {}, 'POST').then(res => {
					if (res.code == 0) {
						let TimeoutNumber = res.data.is_qrcode_number * 1000 || 260000;
						this.timer = setTimeout(() => {
							this.qrCode();
						}, TimeoutNumber);
					}
				});
				this.$api.httpRequest(
						`/coupon/encryptAES`, {
							id: this.info.id,
							salt: this.info.enstr_salt,
							uid: this.uerInfo.uid
						},
						'POST'
					)
					.then(res => {
						this.info.idcard = res.data.uinfo.idcard;
						this.info.name = res.data.uinfo.name;

						if (res.data.write_off_status == 1) {
							//核销不显示二维码
							this.$set(this.info, 'status', 1);
							this.isQrcode = false;
							clearTimeout(this.timer);
						} else if (this.info.use_type == 1) {
							//线上核销不显示二维码
							clearTimeout(this.timer);
						} else {
							//没核销显示二维码
							let coord = {};
							getLocation(false).then(
								success => {
									coord = {
										latitude: success.latitude,
										longitude: success.longitude
									};
									this.qrcodeVal = JSON.stringify({
										id: res.data.id,
										qrcode: res.data.qrcode_url,
										coord,
										type: 'user'
									});
									this.$refs.qrcode.make();
								},
								fail => {
									// 失败
								}
							);
						}
					});
			},

			daytime() {
				let date = new Date(Date.now());
				let year = date.getFullYear();
				let mon = date.getMonth() + 1;
				let day = date.getDate();
				let hours = date.getHours();
				let minu = date.getMinutes();
				let sec = date.getSeconds();
				let trMon = mon < 10 ? '0' + mon : mon;
				let trDay = day < 10 ? '0' + day : day;
				this.$set(this.info, 'daytime', year + '-' + trMon + '-' + trDay + ' ' + hours + ':' + minu + ':' + sec);
			},
			// 选择地址
			chooseAddress() {
				let that = this;
				uni.chooseAddress({
					success(res) {
						// 将地址传到后台
						that.delivery.delivery_user = res.userName;
						that.delivery.delivery_phone = res.telNumber;
						that.delivery.delivery_address = res.provinceName + res.cityName +
							res.countyName + res.detailInfo;
						that.getAddress = false;
						that.submitAddress = true;
					}
				})
			},
			// 提交地址
			submitAddressFun() {
				this.submitAddress = false;
				this.$api
					.httpRequest(
						`/user/saveDelivery`, {
							coupon_issue_user_id: this.id,
							uid: this.uerInfo.uid,
							delivery_user: this.delivery.delivery_user,
							delivery_phone: this.delivery.delivery_phone,
							delivery_address: this.delivery.delivery_address
						},
						'POST'
					)
					.then(res => {
						uni.showModal({
							title: '提示',
							content: res.msg,
							showCancel: false
						});
					});
			},
			// 查询物流信息
			queryLogistics() {
				// 跳转到物流信息页面
				uni.navigateTo({
					url: '/pages/coupon/logistics?trackingNumber=' + this.trackingNumber +
						'&coupon_issue_user_id=' + this.id
				});
			}
		}
	};
</script>

<style lang="scss">
	page {
		min-height: calc(100vh - calc(44px + env(safe-area-inset-top)));
		font-size: #333333;
	}

	.coupon-box {
		width: 100%;
		background: linear-gradient(180deg, rgba(174, 0, 13, 1) 0%, rgba(247, 247, 247, 1) 30%, rgba(247, 247, 247, 1) 100%);
		padding-top: 100upx;

		& .coupon {
			width: 95%;
			margin: auto;
			overflow: hidden;

			& .info-box {
				border-top: 1upx solid #f7f7f7;
			}

			& .content-coupon {
				border-radius: 10upx 20upx 0 0;
				width: calc(100% - 40upx);
				margin: auto;
				display: flex;
				align-items: center;
				flex-direction: column;
				padding: 50upx 20upx 40upx;
				background-color: #fff;
				border-bottom: dashed 1upx #dddddd;
				position: relative;

				& .user-info {
					width: 100%;
					text-align: center;
					color: #a3a3a3;

					& .info-name {
						font-size: 38rpx;
						color: $div-bg-color;
						line-height: 70rpx;
					}

					& .info-id-number {
						font-size: 35upx;
					}
				}

				&::before,
				&::after {
					content: '';
					position: absolute;
					width: 40upx;
					height: 40upx;
					border-radius: 50%;
					background-color: #f7f7f7;
				}

				&::before {
					left: -20upx;
					bottom: -20upx;
				}

				&::after {
					right: -20upx;
					bottom: -20upx;
				}

				& .qrcode {
					width: 440upx;
					height: 440upx;
					margin: auto;
					display: flex;
					align-items: center;
					justify-content: center;

					& image {
						width: 100%;
						height: 100%;
					}
				}

				& .info {
					color: #fff;
					display: flex;
					height: 60upx;
					line-height: 48upx;
					padding: 0;
					justify-content: center;
					font-size: 24upx;
					position: absolute;
					left: -11upx;
					top: 16upx;
					width: 25%;
					background: none;
					background-image: url('@/static/info.png');
					background-repeat: no-repeat;
					background-size: 100% 100%;
				}

				& .price {
					width: 100%;
					font-size: 45upx;
					font-weight: bold;
					color: $div-bg-color;
					text-align: center;
				}

				& .daytime {
					width: 100%;
					font-size: 45upx;
					text-align: center;
					color: $div-bg-color;
					font-weight: bold;
					padding-bottom: 20upx;
				}

				& .title {
					width: 100%;
					font-size: 35upx;
					text-align: center;
					font-weight: bold;
					padding-bottom: 10upx;
				}

				& .button {
					width: 40%;
					height: 60upx;
					margin-bottom: 10upx;
					box-shadow: none;
				}

				& .progress {
					width: 50%;
					margin: 0 auto 20upx;
				}

				& .area {
					font-size: 22upx;
					color: #828282;
				}
			}

			& .items {
				width: calc(100% - 40upx);
				padding: 0upx 20upx 0upx;
				background-color: #fff;
				margin: auto;

				& .item {
					display: flex;
					padding: 20upx 0;

					& .item-left {
						margin-right: 10upx;
						display: flex;
						align-items: center;
					}

					& .item-rigth {
						color: #5f5f5f;
						display: flex;
						align-items: center;
						font-size: 24upx;
					}
				}
			}

			& .info {
				width: calc(100% - 40upx);
				padding: 0upx 20upx;
				height: 80upx;
				line-height: 80upx;
				background-color: #fff;
				margin: auto;
				display: flex;
				justify-content: space-between;

				& .left {
					width: 20%;
					font-size: 30upx;
					font-weight: bold;
				}

				& .right {
					width: 80%;
					display: flex;
					justify-content: space-between;
					align-items: center;
					color: #a3a3a3;

					& .name-title {
						width: 100%;
						text-align: right;
						overflow: hidden;
						text-overflow: ellipsis;
						display: -webkit-box;
						-webkit-line-clamp: 1;
						-webkit-box-orient: vertical;
					}

					& .name-icons {
						display: flex;
						justify-content: flex-end;
					}
				}
			}

			& .info:last-child {
				padding-top: 0;
				border-radius: 0 0 20upx 20upx;
			}
		}

		& .instructions {
			width: calc(95% - 40upx);
			padding: 20upx;
			border-radius: 20upx;
			margin: auto;
			background-color: #fff;
			margin-top: 20upx;

			& .title {
				font-size: 30upx;
				color: #333333;
				margin-bottom: 20upx;
				font-weight: bold;
			}

			& .content {
				line-height: 45upx;
				font-size: 24upx;
				color: #808080;
			}
		}
	}
</style>