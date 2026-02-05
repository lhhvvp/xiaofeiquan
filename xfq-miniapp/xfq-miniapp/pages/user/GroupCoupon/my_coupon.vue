<template>
	<view class="coupon-box">
		<view class="coupon">
			<view class="content-coupon">
				<view class="title">{{ groupCoupon.couponIssue.coupon_title }}</view>
				<view class="qrcode">
					<image v-if="groupCoupon.status == 1" src="@/static/001.png"></image>
					<!-- <image v-if="groupCoupon.status == 2" src="@/static/002.png"></image> -->
					<uqrcode v-if="groupCoupon.status == 0" ref="qrcodes" canvas-id="qrcode" :start="false"
						:value="qrcodeVal"></uqrcode>
				</view>
			</view>
		</view>
		<view class="instructions-box">
			<view class="instructions">
				<view class="title">领取时间</view>
				<view class="content">{{ groupCoupon.create_time }}</view>
			</view>

			<view class="instructions">
				<view class="title">使用细则</view>
				<view class="content"><rich-text :nodes="groupCoupon.couponIssue.remark"></rich-text></view>
			</view>

			<view class="instructions" v-if="groupCoupon.status == 1 && sys_sign">
				<view class="title">打卡游客</view>
				<view class="content">
					<view class="item" v-for="(item, index) in groupCoupon.tour_write_off" :key="index">
						<view class="left">
							<view class="tit">
								<uni-icons type="person-filled" size="20" color="#d8d8d8"></uni-icons>
								<text class="icon_tit">{{ item.user.name == '' ? '暂无' : item.user.name }}</text>
							</view>
							<view class="mobile">
								<uni-icons type="phone-filled" size="20" color="#d8d8d8"></uni-icons>
								<text class="icon_tit">{{ item.user.mobile == '' ? '暂无' : item.user.mobile }}</text>
							</view>
						</view>
						<view class="right">
							<view class="clockin bg-a00000" v-if="item.is_clock == 0"
								@click="isClock(item.is_clock, item.id, item.uid)">代打卡</view>
							<view :class="clockName(item.is_clock).class">{{ clockName(item.is_clock).name }}</view>
						</view>
					</view>
				</view>
			</view>
		</view>
		<my-is-clock v-model="spot_name" @submit="submit" v-if="myIsClock" @down="down"></my-is-clock>
	</view>
</template>

<script>
	import {
		mapState,
		mapMutations
	} from 'vuex';
	import {
		getLocation
	} from "../../../common/common.js"
	export default {
		data() {
			return {
				suitable: {},
				qrcodeVal: null,
				timer: null,
				isQrcode: false,
				id: null,
				groupCoupon: {},
				myIsClock: false,
				longitude: null,
				latitude: null,
				address: null,
				spot_name: null, //打卡景点
				user_id: 0, //代打卡人员id
				tour_issue_user_id: 0,
				sys_sign: false //系统控制打卡字段
			};
		},
		onLoad(option) {
			wx.setVisualEffectOnCapture({
				visualEffect: 'hidden'
			});
			this.id = option.id;
			this.init();
			// this.system();
			this.setRefresh(true);
			uni.setScreenBrightness({
				value: 1
			});
		},
		onUnload() {
			clearInterval(this.timer);
		},
		computed: {
			...mapState(['uerInfo'])
		},
		methods: {
			...mapMutations(['setRefresh']),
			system() {
				this.$api.httpRequest(`/index/system`, {}, 'POST').then(res => {
					if (res.code == 0) {
						// 系统字段允许打卡 system true
						if (res.data.is_clock_switch == 1) {
							this.sys_sign = true;
						}
						// 不允许打卡 system false
					} else {
						this.$api.msg(res.msg, 'none');
					}
				});
			},
			init() {
				this.$api
					.httpRequest(
						`/user/tour_coupon_group`, {
							id: this.id //领取id
						},
						'POST'
					)
					.then(res => {
						let that = this;
						if (res.code == 0) {
							this.groupCoupon = res.data;
							res.data.status == 0 && this.qrCode();
						} else {
							this.$api.msg(res.msg, 'none');
							clearInterval(this.timer);
							setTimeout(() => {
								uni.navigateBack();
							}, 2000);
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
				this.$api
					.httpRequest(
						`/user/encryptAES`, {
							id: this.groupCoupon.id,
							salt: this.groupCoupon.enstr_salt
						},
						'POST'
					)
					.then(res => {

						if (res.data.write_off_status == 1) {
							//核销不显示二维码
							this.$set(this.groupCoupon, 'status', 1);
							this.isQrcode = false;
							clearTimeout(this.timer);
						} else {

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
										type: 'groupCoupon'
									});
									this.$refs.qrcodes.make();
								},
								fail => {
									// 失败
								}
							);
						}

					});
			},
			clockName(status) {
				let obj = {};
				switch (status) {
					case 0:
						obj.name = '未打卡';
						obj.class = 'Notclockin';
						break;
					case 1:
						obj.class = 'clockin';
						obj.name = '已打卡';
						break;
				}
				return obj;
			},

			isClock(status, id, user_id) {
				if (status == 0 && this.latitude == null && this.longitude == null && this.address == null && this
					.spot_name == null) {
					// 没打卡获取经纬度
					getLocation(false).then(
						success => {
							this.latitude = success.latitude;
							this.longitude = success.longitude;
							this.$api
								.httpRequest(
									`/index/transform`, {
										longitude: this.longitude,
										latitude: this.latitude
									},
									'POST'
								)
								.then(
									res => {
										this.address = res.data.result.address;
										this.spot_name = res.data.result.formatted_addresses.rough;
										this.user_id = user_id;
										this.tour_issue_user_id = id;
										this.myIsClock = true;
									},
									error => {
										this.$api.msg('位置获取错误，请重试!');
									}
								);
						},
						fail => {
							// 失败
						}
					);
				} else {
					if (status == 1) {
						this.$api.msg('您已经打过卡了', 'none');
						return false;
					}
					this.tour_issue_user_id = id;
					this.user_id = user_id;
					this.myIsClock = true;
				}
			},
			submit(e) {
				let {
					images,
					spot_name,
					desc
				} = e;
				let {
					longitude,
					latitude,
					address
				} = this;
				if (!images) {
					images = 'https://oss.ylbigdata.com/admins/6602b01b17f64ecf18798e4868f394bb.png';
				}
				uni.showLoading({
					mask: true,
					title: '打卡提交中...'
				});
				// 景区
				this.$api
					.httpRequest(
						`/user/clock`, {
							clock_uid: this.user_id,
							tour_issue_user_id: this.tour_issue_user_id,
							spot_name,
							images,
							address,
							longitude,
							latitude,
							dess: desc,
							agency_user_id: uni.getStorageSync('guide')
						},
						'POST'
					)
					.then(res => {
						if (res.code == 0) {
							this.init(); //打完卡更新页面
							setTimeout(() => {
								uni.hideLoading();
								this.myIsClock = false;
							}, 2500);
						}
						this.$api.msg(res.msg, 'none');
					});
			},
			down() {
				this.myIsClock = !this.myIsClock;
			}
		}
	};
</script>

<style lang="scss">
	page {
		min-height: calc(100vh - calc(44px + env(safe-area-inset-top)));
		background: linear-gradient(180deg, rgba(174, 0, 13, 1) 0%, rgba(247, 247, 247, 1) 30%, rgba(247, 247, 247, 1) 100%);
		font-size: #333333;
	}

	.Notclockin {
		width: 50%;
		height: 45upx;
		border-radius: 10upx;
		display: flex;
		align-items: center;
		justify-content: center;
		border: 1px solid #999999;
	}

	.bg-a00000 {
		background-color: $div-bg-color;
		border: none !important;
		color: #fff !important;
	}

	.clockin {
		width: 50%;
		height: 45upx;
		border-radius: 10upx;
		margin-right: 10upx;
		display: flex;
		align-items: center;
		justify-content: center;
		border: 1px solid $div-bg-color;
		color: $div-bg-color;
	}

	.coupon-box {
		width: 100%;
		padding-top: 100upx;

		& .coupon {
			width: 95%;
			margin: auto;
			overflow: hidden;

			& .content-coupon {
				border-radius: 20upx 20upx 0 0;
				width: calc(100% - 40upx);
				margin: auto;
				display: flex;
				align-items: center;
				flex-direction: column;
				padding: 80upx 20upx 20upx;
				background-color: #fff;
				border-bottom: dashed 1upx #dddddd;
				position: relative;

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
					width: 450upx;
					height: 450upx;
					padding: 50upx 0;
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
					width: 20%;
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
				padding: 30upx 20upx 20upx;
				background-color: #fff;
				margin: auto;

				& .item {
					display: flex;
					margin-bottom: 20upx;

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

				& .item:last-child {
					margin-bottom: 0;
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
						width: 77%;
						text-align: right;
						overflow: hidden;
						text-overflow: ellipsis;
						display: -webkit-box;
						-webkit-line-clamp: 1;
						-webkit-box-orient: vertical;
					}

					& .name-icons {
						width: 23%;
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

		& .instructions-box {
			background-color: #f7f7f7;
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

				& .item {
					width: 100%;
					display: flex;
					margin-bottom: 20upx;
					border-bottom: 1px solid #f3f3f3;
					justify-content: space-between;

					& .left {
						width: 70%;
						height: 100upx;

						& .tit,
						& .mobile {
							display: flex;
							align-items: center;
						}

						& .icon_tit {
							margin-left: 10upx;
						}
					}

					& .right {
						width: 30%;
						justify-content: flex-end;
						display: flex;
						align-items: center;
					}
				}
			}
		}
	}
</style>