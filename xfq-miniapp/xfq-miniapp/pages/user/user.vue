<template>
	<view class="user-box">
		<view class="bg" :style="'padding-top:' + menuButton.top + 'px'">
			<view class="user-info" @click="login(info)">
				<view class="avatar">
					<image :src="
							info.headimgurl ||
								'https://thirdwx.qlogo.cn/mmopen/vi_32/POgEwh4mIHO4nibH0KlMECNjjGxQUq24ZEaGT4poC6icRiccVGKSyXwibcPq4BWmiaIGuG1icwxaQX6grC9VemZoJ8rg/132'
						" style="width: 100%;height: 100%;"></image>
				</view>
				<view class="user-name">
					<view style="display: flex;justify-content: center;align-items: flex-start;flex-direction: column;">
						<view>
							<text>{{ info.name || '暂未登录' }}</text>
							<text v-if="info.nickname && info.nickname != '微信用户'"
								style="display: inline-block;margin-left: 10upx;">( {{ info.nickname || '' }} )</text>
						</view>
						<view class="mobile">
							<text>{{ info.mobile }}</text>
							<text>{{ info.auth_status == 1 ? '（已实名）':'（未实名）'}}</text>
						</view>
					</view>
					<text class="mobile-text" @click.stop="getUserInfo" v-if="avatar_boolen">点击获取微信资料</text>
					<uni-icons class="mobile-text" style="background: none;box-shadow: none" type="gear" color="#ffffff"
						size="34" v-if="!avatar_boolen"></uni-icons>
				</view>
			</view>
		</view>
		<!-- 领取记录和设置 -->
		<view class="log" :style="'top:' + (menuButton.top + 90) + 'px'">
			<view class="item" @click="navto('/pages/user/order?state=1')">
				<text class="yticon icon-shouye"></text>
				<text>未使用</text>
			</view>
			<view class="item" @click="navto('/pages/user/order?state=2')">
				<text class="yticon icon-shouhoutuikuan"></text>
				<text>已使用</text>
			</view>
			<view class="item" @click="navto('/pages/user/order?state=3')">
				<text class="yticon icon-yishouhuo"></text>
				<text>已过期</text>
			</view>
		</view>

		<view class="meunt">
			<view class="title">门票订单</view>
			<view class="items">
				<view class="item" @click="navto('/pages/user/my_order?state=')">
					<view class="item-image">
						<image src="@/static/icon/order.png"></image>
					</view>
					<view class="item-title">全部</view>
				</view>

				<view class="item" @click="navto('/pages/user/my_order?state=paid')">
					<view class="item-image">
						<image src="@/static/icon/payment.png"></image>
					</view>
					<view class="item-title">已支付</view>
				</view>

				<view class="item" @click="navto('/pages/user/my_order_refund')">
					<view class="item-image">
						<image src="@/static/icon/refund.png"></image>
					</view>
					<view class="item-title">售后</view>
				</view>

			</view>
		</view>

		<view class="meunt yulin" style="position: relative;" v-if="false && info.credit_score && info.credit_rating">
			<view class="items">
				<view class="item" style="width: 35%;padding: 0;border-right: 1upx solid #ef8381;">
					<text class="th-number">{{info.credit_score}}</text>
					<text class="th-name">桃花分</text>
				</view>
				<view class="item" style="width: 65%;padding: 0;">
					<view class="th-live">信用等级：<text>{{info.credit_rating}}</text></view>
					<view class="th-time">更新时间：{{info.update_credit}}</view>
					<view class="th-button" @click="yulin()">点击更新</view>
				</view>
			</view>
		</view>

		<view class="meunt" v-if="is_mid">
			<view class="title">商户管理</view>
			<view class="items">
				<view class="item" @click="scancode()">
					<view class="item-image">
						<image src="@/static/icon/qrcode.png"></image>
					</view>
					<view class="item-title">扫码核销</view>
				</view>
				<view class="item" @click="navto('/pages/user/order_CAV?mid=' + mid)">
					<view class="item-image">
						<image src="@/static/icon/list.png"></image>
					</view>
					<view class="item-title">核销记录</view>
				</view>

				<view class="item" @click="navto('/pages/user/order_groupCoupon?mid=' + mid)">
					<view class="item-image">
						<image src="@/static/icon/groupCoupon.png"></image>
					</view>
					<view class="item-title">团核销记录</view>
				</view>
				<view class="item" @click="address()">
					<view class="item-image">
						<image src="@/static/icon/address.png"></image>
					</view>
					<view class="item-title">获取定位</view>
				</view>
			</view>
		</view>

		<view class="meunt-box">
			<view class="items">
				<view class="item" @click="navto('/pages/user/signIn/signIn')" v-if="info.is_clock">
					<view class="item-title">
						<view class="item-image">
							<image src="@/static/icon/dakai.png"></image>
						</view>
						<view class="tit">打卡任务</view>
					</view>
					<view class="item-rigth"><uni-icons type="right"></uni-icons></view>
				</view>

				<view class="item" v-if="info.guide" @click="navto('/pages/user/GroupCoupon/GroupCoupon')">
					<view class="item-title">
						<view class="item-image">
							<image src="@/static/icon/coupon.png"></image>
						</view>
						<view class="tit">我的旅行团</view>
					</view>
					<view class="item-rigth"><uni-icons type="right"></uni-icons></view>
				</view>
				<view class="item" @click="navto('/pages/user/person/list')" v-if="isUserInfo">
					<view class="item-title">
						<view class="item-image">
							<image src="@/static/icon/yk.png"></image>
						</view>
						<view class="tit">常用游客</view>
					</view>
					<view class="item-rigth"><uni-icons type="right"></uni-icons></view>
				</view>
				<view class="item" @click="navto('/pages/user/subscribe/my_list')" v-if="isUserInfo">
					<view class="item-title">
						<view class="item-image">
							<image src="@/static/icon/time.png"></image>
						</view>
						<view class="tit">我的预约</view>
					</view>
					<view class="item-rigth"><uni-icons type="right"></uni-icons></view>
				</view>
				<view class="item" @click="navto('/pages/user/collect')" v-if="isUserInfo">
					<view class="item-title">
						<view class="item-image">
							<image src="@/static/icon/sc.png"></image>
						</view>
						<view class="tit">我的收藏</view>
					</view>
					<view class="item-rigth"><uni-icons type="right"></uni-icons></view>
				</view>

				<view class="item" @click="navto('/pages/user/comment')" v-if="isUserInfo">
					<view class="item-title">
						<view class="item-image">
							<image src="@/static/icon/message.png"></image>
						</view>
						<view class="tit">我的评论</view>
					</view>
					<view class="item-rigth"><uni-icons type="right"></uni-icons></view>
				</view>
				<!-- <view class="item" @click="navto('/pages/user/task/index',info)" v-if="isUserInfo">
					<view class="item-title">
						<view class="item-image">
							<image src="@/static/icon/jy.png"></image>
						</view>
						<view class="tit">我的任务</view>
					</view>
					<view class="item-rigth"><uni-icons type="right"></uni-icons></view>
				</view> -->
				<view class="item" @click="tels()">
					<view class="item-title">
						<view class="item-image">
							<image src="@/static/icon/tel.png"></image>
						</view>
						<view class="tit">电话咨询</view>
					</view>
					<view class="item-rigth"><uni-icons type="right"></uni-icons></view>
				</view>
				<view class="item">
					<button class="item-title" open-type="contact" show-message-card="true">
						<view class="item-image">
							<image src="@/static/icon/kf.png"></image>
						</view>
						<view class="tit">在线客服</view>
					</button>
					<view class="item-rigth"><uni-icons type="right"></uni-icons></view>
				</view>
				<view class="item" @click="navtos('/pages/user/attestation?option='+JSON.stringify(info))"
					v-if="isUserInfo">
					<view class="item-title">
						<view class="item-image">
							<image src="@/static/icon/jy.png"></image>
						</view>
						<view class="tit">实名认证</view>
					</view>
					<view class="item-rigth"><uni-icons type="right"></uni-icons></view>
				</view>
				<view class="item">
					<button class="item-title" open-type="share">
						<view class="item-image">
							<image src="@/static/icon/share.png"></image>
						</view>
						<view class="tit">分享给好友</view>
					</button>
					<view class="item-rigth"><uni-icons type="right"></uni-icons></view>
				</view>

				<view class="item" @click="navto('/pages/user/complaints',info)" v-if="isUserInfo">
					<view class="item-title">
						<view class="item-image">
							<image src="@/static/icon/jy.png"></image>
						</view>
						<view class="tit">投诉和建议</view>
					</view>
					<view class="item-rigth"><uni-icons type="right"></uni-icons></view>
				</view>

				<view class="item" @click="navto('/pages/user/set')">
					<view class="item-title">
						<view class="item-image">
							<image src="@/static/icon/set.png"></image>
						</view>
						<view class="tit">设置</view>
					</view>
					<view class="item-rigth"><uni-icons type="right"></uni-icons></view>
				</view>

				<view class="item" @click="clearCache">
					<view class="item-title">
						<view class="item-image">
							<image src="@/static/icon/cache.png"></image>
						</view>
						<view class="tit">清理缓存</view>
					</view>
					<view class="item-rigth"><uni-icons type="right"></uni-icons></view>
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
		YmdHm,
		getLocation
	} from "@/common/common.js"
	export default {
		data() {
			return {
				menuButton: {},
				info: {},
				mid: 0,
				is_mid: false,
				is_lock: true,
				is_onLoad: false,
				avatar_boolen: false,
				isUserInfo: false,
			};
		},
		// #ifdef MP-WEIXIN
		onShareAppMessage(res) {
			//微信小程序分享给朋友
			return {
				title: '榆林市旅游消费平台',
				path: '/pages/user/user'
			};
		},
		onShareTimeline(res) {
			//微信小程序分享朋友圈
			return {
				title: '榆林市旅游消费平台',
				path: '/pages/user/user'
			};
		},
		// #endif
		onLoad() {
			// this.init();
			this.system(uni.getStorageSync('system'));
			this.menuButton = uni.getMenuButtonBoundingClientRect();
			this.menuButton.top = this.menuButton.height + this.menuButton.top + 20;
			let that = this;
			uni.getSystemInfo({
				success: function(res) {
					let version = res.SDKVersion;
					version = version.replace(/\./g, '');
					version = version.split("");
					version = `${version[0]}${version[1]}${version[2]}`;
					if (parseInt(version) < 227) {
						that.avatar_boolen = true;
					}
				}
			});
		},
		onShow() {
			this.init();
		},
		computed: {
			...mapState(['uerInfo'])
		},
		methods: {
			yulin() {
				this.$api
					.httpRequest(
						`/test/rsyncTaohuaSign`, {
							uid: this.uerInfo.uid
						},
						'POST'
					).then(res => {
						if (res.code == 0) {
							this.$set(this.info, 'credit_score', res.data.credit_score);
							this.$set(this.info, 'credit_rating', res.data.credit_rating);
							if (!!res.data.update_credit) {
								this.$set(this.info, 'update_credit', YmdHm(res.data.update_credit));
							}
							this.$api.msg('更新成功', 'success');
						}
					})
			},
			init() {
				this.$api
					.httpRequest(
						`/user/index`, {
							uid: this.uerInfo.uid,
							is_token: true,
						},
						'POST'
					)
					.then(res => {
						if (res.code == 0) {
							this.info = res.data;
							uni.setStorageSync('guide', res.data.guide);
							this.is_lock = false;
							this.isUserInfo = true;
							if (!!this.info.update_credit) {
								this.info.update_credit = YmdHm(this.info.update_credit);
							}
							if (res.data.ismv) {
								this.mid = res.data.ismv.mid;
								this.is_mid = true;
								this.is_onLoad = true;
							} else {
								this.is_mid = false;
							}
						}
					});
			},
			navto(url) {
				uni.navigateTo({
					url: url
				});
			},
			navtos(url) {
				// this.info.auth_status == 1
				uni.navigateTo({
					url: url
				});
			},
			scancode() {
				let that = this;
				uni.scanCode({
					success: function(res) {
						// ajax
						if (res.scanType === 'QR_CODE') {
							let objVal = that.isJsonString(res.result),	//先转对象
								data = null;
							if (!objVal) {
								//转对象失败以后在转编码格式。
								let text = decodeURIComponent(res.result);
								
								if (!that.isJsonString(text)) {
									//在转对象失败以后提示
									// 废码;
									that.$api.msg('数据异常,请重试！', 'none');
								} else {
									//成功以后
									data = JSON.parse(text);
								}
							} else {
								//成功以后
								data = objVal;
							};
							if (data.coord) {
								data.coord = JSON.stringify(data.coord);
							};
							
							if (data.type == 'user') {
								uni.navigateTo({
									// 消费券散客核销
									url: '/pages/coupon/coupon_CAV?id=' + data.id + '&mid=' + that
										.mid + '&qrcode_url=' + data.qrcode + '&type=' + data.type +
										'&coord=' + data.coord
								});
							} else if (data.type == 'order') {
								//购买票核销总
								data.qrcode_str = encodeURIComponent(data.qrcode_str);
								uni.navigateTo({
									// 领取记录的主键id
									url: '/pages/user/coupon_CAV_order/coupon_CAV_order?data=' + JSON
										.stringify(data),
								});

							} else if (data.type == 'order_user') {
								//购买票核销单用户
								data.qrcode_str = encodeURIComponent(data.qrcode_str);
								uni.navigateTo({
									// 领取记录的主键id
									url: `/pages/user/coupon_CAV_order/coupon_CAV_user?data=${JSON.stringify(data)}`,
								});

							} else if (data.type == 'subscribe') {
								//购买票核销
								data.qrcode_str = encodeURIComponent(data.qrcode_str);
								uni.navigateTo({
									// 领取记录的主键id
									url: '/pages/user/coupon_CAV_subscribe/coupon_CAV_subscribe?data=' +
										JSON
										.stringify(data),
								});

							} else {
								uni.navigateTo({
									// 领取记录的主键id
									url: '/pages/coupon/coupon_CAV_Group/coupon_CAV_Group?id=' +
										data.id +
										'&mid=' +
										that.mid +
										'&qrcode_url=' +
										data.qrcode +
										'&type=' +
										data.type +
										'&coord=' +
										data.coord
								});
							}
						}
						// 公共方法
					}
				});
			},
			tels() {
				uni.makePhoneCall({
					phoneNumber: this.tel
				});
			},
			isJsonString(str) {
				//字符串转对象
				try {
					return JSON.parse(str);
				} catch (e) {
					return false;
				}
			},
			system(system) {
				if (system != '') {
					this.tel = system.tel;
					return false;
				}
				this.$api.httpRequest(`/index/system`, {}, 'POST').then(res => {
					if (res.code == 0) {
						uni.setStorageSync('system', res.data);
						this.tel = res.data.tel;
					} else {
						this.$api.msg(res.msg, 'none');
					}
				});
			},
			getUserInfo() {
				let its = this;
				uni.getUserProfile({
					desc: '用于完善会员资料',
					success: function(infoRes) {
						if ('getUserProfile:ok' != infoRes.errMsg) {
							return its.$api.msg('获取用户信息失败！');
						}
						let headimgurl = infoRes.userInfo.avatarUrl;
						let nickname = infoRes.userInfo.nickName;
						its.$api
							.httpRequest(
								`/user/edit`, {
									id: its.uerInfo.uid,
									nickname,
									headimgurl
								},
								'POST'
							)
							.then(res => {
								if (res.code == 0) {
									its.$api.msg(res.msg, 'success');
									setTimeout(() => {
										its.init();
									}, 2500);
								} else {
									its.$api.msg(res.msg, 'none');
								}
							});
					}
				});
			},
			address() {
				let that = this;
				uni.showModal({
					title: '提示',
					content: '请确定您当前位置在售票口或核销口',
					success(res) {
						if (res.confirm) {
							that.getAddress();
						}
					}

				})
			},
			getAddress() {
				getLocation().then(res => {
					let latitude = res.latitude
					let longitude = res.longitude;
					uni.navigateTo({
						url: `/pages/user/mymap?lat=${latitude}&lng=${longitude}`,
					});
					return false;
					uni.showModal({
						title: '您当前的位置',
						content: `经度为${longitude},纬度为${latitude}`,
						cancelText: "复制",
						success(res) {
							if (res.confirm) {} else if (res.cancel) {
								uni.setClipboardData({
									data: `您当前的位置经度为${longitude},纬度为${latitude}`,
								});
							}
						}
					})
				})
			},
			login(info) {
				let url = '';
				if (!info.name) {
					url = '/pages/user/login/login';
				} else {
					url = '/pages/user/set';

				};
				uni.navigateTo({
					url: url,
				})
			},
			clearCache() {
				let that = this;
				uni.showModal({
					title: '提示',
					content: '是否清理缓存',
					success(res) {
						if (res.confirm) {
							try {
								uni.showLoading({
									title: '清理中...'
								});
								setTimeout(() => {
									uni.removeStorageSync('uerInfo');
									uni.removeStorageSync('coord');
									uni.removeStorageSync('system');
									uni.clearStorage();
									uni.hideLoading();
									uni.reLaunch({
										url: '/pages/user/login/login'
									});
								}, 2500);
							} catch (e) {
								console.log(e);
							}
						}
					}
				});
			}
		}
	};
</script>

<style lang="scss">
	.user-box {
		position: relative;
	}

	.meunt-box {
		width: calc(100% - 40upx);
		padding: 0 20upx;
		background-color: #fff;
		border-radius: 20upx;

		& .item {
			width: 100%;
			display: flex;
			height: 100upx;
			font-size: 28upx;
			border-bottom: 1px solid #f7f7f7;

			&:last-child {
				border: 0;
			}

			& button {
				background: none;
				padding: 0;
				display: flex;
				align-items: center;
				font-size: 28upx;
				color: #333;

				&::after {
					border: none;
				}
			}

			align-items: center;
			justify-content: space-between;

			& .item-title {
				width: 90%;
				display: flex;
				align-items: center;

				& .item-image {
					width: 45upx;
					height: 45upx;
					margin-right: 20upx;

					& image {
						width: 100%;
						height: 100%;
					}
				}
			}

			& .item-rigth {
				width: 10%;
				text-align: right;
			}
		}
	}

	.bg {
		background-color: $div-bg-color;
		background-image: url(@/static/bg2.png);
		background-size: 100%;
		margin-bottom: 130upx;
		background-repeat: no-repeat;

		& .user-info {
			width: 90%;
			padding-bottom: 100upx;
			margin: auto;
			display: flex;
			align-items: center;

			& .avatar {
				width: 120upx;
				height: 120upx;
				border-radius: 50%;
				background-color: #fff;
				margin-right: 20upx;
				overflow: hidden;
			}

			& .user-name {
				color: #fff;
				font-size: 28upx;
				width: calc(100% - 120upx);
				display: flex;
				align-items: center;
				justify-content: space-between;

				& .mobile {
					font-size: 20upx;
					display: flex;
					align-items: center;

					&-text {
						background-color: #a00000;
						display: inline-block;
						font-size: 20rpx;
						border-radius: 5px;
						padding: 10upx 20upx;
						color: #ffbebe;
						box-shadow: 2px 2px 5px #690000;
					}
				}

				& text {
					height: 50upx;
					line-height: 50upx;
					font-size: 28upx;
				}
			}
		}
	}

	.log {
		width: 95%;
		left: 2.5%;
		position: absolute;
		margin: auto;
		display: flex;
		justify-content: space-between;
		border-radius: 20upx;
		overflow: hidden;

		& .item {
			width: 33.333%;
			display: flex;
			padding: 20upx 0;
			flex-direction: column;
			justify-content: center;
			align-items: center;
			border-right: 1px solid #f7f7f7;
			height: 120upx;
			background-color: #fff;

			& text {
				text-align: center;
			}

			& image {
				width: 80upx;
				height: 80upx;
				border-radius: 20upx;
				background-color: antiquewhite;
				margin-bottom: 10upx;
			}
		}

		& .item:last-child {
			border: none;
		}
	}

	.yulin {
		height: 120upx;
		overflow: hidden;
		background: rgb(254, 243, 244);
		background: linear-gradient(137deg, rgba(254, 243, 244, 1) 0%, rgba(247, 209, 206, 1) 100%);
		border: 1px solid $div-bg-color;
		display: flex;
		align-items: center;
		color: #333333;

	}

	.meunt {
		width: calc(95% - 40upx);
		margin: 0 auto 20upx;
		border-radius: 20upx;
		padding: 20upx;
		background-color: #fff;

		& .title {
			border-bottom: 1px solid #f3f3f3;
			padding: 12upx 0;
			padding-bottom: 20upx;
			padding-left: 20upx;
			font-weight: bold;
			font-size: 28upx;
			position: relative;

			&:before {
				content: '';
				background-color: $div-bg-color;
				position: absolute;
				width: 8upx;
				left: 0;
				top: 12upx;
				border-radius: 20upx;
				height: 50%;
			}
		}

		& .items {
			width: 100%;
			display: flex;

			& .item {
				width: 25%;
				display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;
				padding-top: 30upx;

				& .item-image {
					width: 80upx;
					height: 80upx;

					& image {
						width: 80upx;
						height: 80upx;
					}
				}

				& .item-title {
					height: 60upx;
					line-height: 60upx;
				}

				& .th-number,
				{
				display: block;
				height: 70upx;
				width: 95%;
				line-height: 70upx;
				text-align: center;
			}

			& .th-number {
				font-size: 60upx;
				color: #c24239;
				font-weight: bold;
			}

			& .th-name {
				width: 95%;
				display: block;
				color: #c24239;
				text-align: center;
			}

			& .th-live,
			.th-time {
				text-align: left;
				width: 80%;
				height: 50upx;
				line-height: 50upx;
			}

			& .th-live text {
				color: $div-bg-color;
				display: inline-block;
				padding-right: 6upx;
				font-size: 32upx;
				font-weight: bold;
			}

			& .th-button {
				position: absolute;
				color: #fff;
				padding: 6upx 16upx;
				border-radius: 0 0 0 10upx;
				right: 0;
				top: 0;
				background-color: $div-bg-color;
			}
		}
	}
	}
</style>