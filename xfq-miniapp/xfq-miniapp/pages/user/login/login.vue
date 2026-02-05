<template>
	<view>
		<view class="login-box">
			<view class="bg">
				<image class="bg-image" :src="'https://oss.ylbigdata.com/wechat/bg.png'+v"></image>
				<image class="bg-index" :src="'https://oss.ylbigdata.com/wechat/index.png'+v" mode="aspectFit">
				</image>

				<view class="login">
					<view class="login-item">
						<button class="button" @click="loginIndex" v-if="!token">立即登录</button>
						<button class="button" @click="navtoIndex" v-if="token">立即进入</button>
						<!-- <button class="button" open-type="getPhoneNumber" hover-class="none" @getphonenumber="getphone">微信登录</button> -->
					</view>

					<view class="login-info" v-if="false">
						<checkbox-group @change="checkboxChange" style="display: inline-block;">
							<checkbox />
						</checkbox-group>
						登录/注册及同意
						<text @click="agreement('服务协议', 1)">服务协议</text>
						和
						<text @click="agreement('隐私政策', 2)">隐私政策</text>
					</view>
				</view>

				<!-- <image class="bg_logo" :src="'https://oss.ylbigdata.com/wechat/logo.png'+v"></image> -->
				<!-- <image class="bg_title" :src="'https://oss.ylbigdata.com/wechat/title.png'+v" ></image> -->
			</view>

		</view>
		<view class="mask-box" v-if="is_auth">
			<view class="mask">
				<view class="content">
					未注册手机号登录后自动生成账号，请您仔细阅读
					<navigator url="/pages/user/agreement?title=服务协议&type=1">服务协议</navigator>
					和
					<navigator url="/pages/user/agreement?title=隐私政策&type=2">隐私协议</navigator>
					的条款，接受后可开始使用我们的服务
				</view>
				<view class="button-box">
					<button @click="is_auth = !is_auth" hover-class="none">不同意</button>
					<!-- <button open-type="getPhoneNumber" hover-class="none" @getphonenumber="getphone">授权</button> -->
					<button hover-class="none" @click="getphone">同意</button>
				</view>
			</view>
		</view>
		<my-complete-information :openid="openid" :isShow="isInfo" @cancel="cancel"></my-complete-information>
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
				isInfo: false,
				getDataPhone: '', // 组件手机号
				code: null,
				is_auth: false,
				openid: "",
				route: null,
				token: false,
				v: this.$api.imgVersion(),
				confirms: 0,
			};
		},
		computed: {
			...mapState(['hasLogin', 'uerInfo'])
		},
		onLoad(o) {
			if (!!o.route) {
				this.route = o.route;
			}
			if (o.is_moble) {
				this.isInfo = true;
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

		onShow() {
			this.token = !!uni.getStorageSync('uerInfo').token || false;
		},
		methods: {
			...mapMutations(['login', 'logout']),
			checkboxChange(e) {
				this.confirms = e.detail.value.length
			},
			loginIndex() {
				// if(this.confirms == 0){
				// 	this.$api.msg('请勾选服务协议和隐私协议');
				// 	return false
				// }
				this.is_auth = true;

			},
			async is_login(logoCode) {
				const its = this;
				uni.checkSession({
					success(val) {
						if (val.errMsg == 'checkSession:ok') {
							//获取用户信息
							its.$api
								.httpRequest('/index/miniwxlogin', {
									code: logoCode,
									// encryptedData: 'AofGiZRo1TbRzveItEFdAcZ9PTfg8xAKkmxR7zfr7vVv9ZZ/VG9HndybiAXGvL0WsCRcTf89shiOboQbSPLH/sK/iiTkxlElS+MaA4d8cRXU/RhNHQbfIgeT98M30IcbnNeVwW2HxDobYlIW5uoJrcB8oHS6JjI4vwU0cly9MXGxQHWl+xgdNV452BZccDxecC+QWp07cSbGbdDw7nhnQHho+HHjkxILksfSYra1YwS95wYesHAuxLir+4WXiar+WKv/V4OMfcdGsB1nFz+32xGHXdKEXS1ztSNd+nXp6D7NS6s1rQWfOnWn0bvKtIy69CPE4TY0icMBWPrAyiRufqIulWe9O3WD7KrAhpb1AHx5xp+EwkgcPOyrlvjejVSTcFOqdIgPf1BLdvDycogDM0A0YMKjiiEbLClPK7AEwNipI/eqZcB50V3GQH4eyrDNcn5rOwZ/DZEJOCbhc48BxQ==',
									// iv: 'saCBrxchtlUVlYRbwD7RqQ==',
									// headimgurl: infoRes.userInfo.avatarUrl,
									// nickname: infoRes.userInfo.nickName,
									// mobile: its.getDataPhone,	//手机号不传
									// sex: 0
								})
								.then(ret => {
									if (ret.code == 0) {
										let data = {
											token: ret.data.token,
											uid: ret.data.userinfo.id,
											name: ret.data.userinfo.name ? true : false, //姓名
											idcard: ret.data.userinfo.idcard ? true : false, //身份证号
											mobile: ret.data.userinfo.mobile ? true : false, //手机号
											openid: ret.data.userinfo.openid,
											uuid: ret.data.userinfo.uuid,
										};
										its.login(data);
										// if (!data.idcard || !data.name || !data.name) {
										// 	its.isInfo = true;
										// 	return false;
										// }
										uni.switchTab({
											url: '/pages/index/index'
										});

									} else if (ret.code == 4444) {
										//未注册的时候
										its.isInfo = true;

										its.openid = ret.data.openid;

									} else {
										its.$api.msg(ret.msg);
									}
								});
						} else {
							uni.login({
								provider: 'weixin',
								success(res) {
									let logoCode = res.code;
									this.is_login(logoCode);
								}
							});
						}
					},
				});
			},
			getphone(e) {
				let that = this;
				uni.login({
					provider: 'weixin',
					success(res) {
						let logoCode = res.code;
						that.is_login(logoCode);
					},
				});
				return false;
				let code = e.detail.code;
				if (!e.detail.iv) {
					uni.showToast({
						title: '获取手机号失败',
						icon: 'none'
					});
					return;
				}
				//验证code 是否过期
				uni.login({
					provider: 'weixin',
					success(res) {
						let logoCode = res.code;
						that.decryptPhone(code, logoCode);
					}
				});
			},
			decryptPhone(code, logoCode) {
				// 解密手机号
				this.$api
					.httpRequest(
						'/index/getuserphonenumber', {
							code
						},
						'POST'
					)
					.then(ret => {
						// console.log(ret);
						if (ret.data.phone_info) {
							this.getDataPhone = ret.data.phone_info.phoneNumber;
							this.is_login(logoCode);
						}
					});
			},
			agreement(title, type) {
				uni.navigateTo({
					url: `/pages/user/agreement?title=${title}&type=${type}`
				});
			},
			cancel(e) {
				this.isInfo = false;
			},
			navtoIndex() {
				// if(this.confirms == 0){
				// 	this.$api.msg('请勾选服务协议和隐私协议');
				// 	return false
				// }
				uni.switchTab({
					url: "/pages/index/index"
				})
			}
		}
	};
</script>

<style lang="scss" scoped>
	page {}

	.login-box {
		height: 100vh;
		position: relative;
		background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAAXNSR0IArs4c6QAAAE5JREFUKFNj3LVrl5Sbm9szBigA8UFMdDFGmAIQjawJ3QC4QnQJdI1ghegmIVsNk2PEpgjmHJg7QWqIV0i01cjBghwkWD2DTTFG8BAb4ADJDGErkDEO0AAAAABJRU5ErkJggg==);

		&::before {
			content: '';
			position: absolute;
			opacity: 0.3;
			width: 100%;
			height: 100%;
			left: 0%;
			bottom: 0%;
			background-repeat: no-repeat;
			background-size: 50%;
			background-position: top right;
		}

		& .bg {
			position: fixed;
			top: 0;
			height: 100%;
			width: 100%;

			& .bg-image {
				width: 100%;
				height: 100%;
				position: fixed;
			}

			& .bg-index {
				width: 100%;
				height: 73vh;
				position: relative;
				z-index: 99;
			}

			// & .bg_logo {
			// 	top: 50rpx;
			// 	height: 138rpx;
			// }
			// & .bg_title {
			// 	top: 280rpx;
			// 	height: 615rpx;
			// }
		}
	}

	.login {
		width: 90%;
		// height: calc(100% - 5%);
		// background-image: url('@/static/logo.png');
		// background-size: 100%;
		// background-repeat: no-repeat;
		padding: 0 5% 0;
		margin: auto;

		& .login-title {
			width: 100%;
			text-align: center;
			color: #b6b6b6;
			font-size: 26upx;
			height: 40upx;
			line-height: 40upx;
			position: relative;

			&:after {
				position: absolute;
				background-color: #dcdcdc;
				width: 50upx;
				height: 2upx;
				left: 20%;
				top: calc(50% - 2upx);
				content: '';
			}

			&:before {
				position: absolute;
				background-color: #dcdcdc;
				top: calc(50% - 2upx);
				width: 50upx;
				height: 2upx;
				right: 20%;
				content: '';
			}
		}

		& .login-item {
			width: 90%;
			margin: auto;
			position: relative;
			padding-bottom: 65upx;

			// margin-top: 85vh;
			& .button {
				height: 85upx;
				width: 80%;
				font-size: 26upx;
				margin: auto;
				border-radius: 10upx;
				color: #ffffff;
				display: flex;
				align-items: center;
				justify-content: center;
				box-shadow: 0px 8px 11px -1px #4fa737;
				background-color: #4fa737;
				border: 1px solid #4fa737;
			}
		}

		& .login-info {
			width: 100%;
			text-align: center;
			color: #333;
			font-size: 24upx;
			height: 40upx;
			line-height: 40upx;
			position: relative;

			& text {
				display: inline-block;
				padding: 0 10upx;
				color: $div-color;
			}
		}
	}

	.mask-box {
		position: fixed;
		z-index: 99;
		width: 100%;
		height: 100%;
		top: 0;
		left: 0;
		background-color: rgba(0, 0, 0, 0.5);

		& .mask {
			width: 70%;
			padding: 5%;
			background-color: #ffffff;
			border-radius: 20upx;
			position: absolute;
			left: 10%;
			top: calc(50% - 200upx);

			& .content {
				color: #666666;
				line-height: 40upx;

				& navigator {
					display: inline-block;
					padding: 0 20upx;
					font-weight: bold;
					color: $div-bg-color;
				}
			}

			& .button-box {
				padding-top: 30upx;
				width: 100%;
				background: none;
				display: flex;
				justify-content: space-between;

				button {
					width: 40%;
					justify-content: center;
					background-color: $div-bg-color;
					color: #ffffff;
					border-radius: 40upx;
				}

				button:first-child {
					border: 1px solid #b6b6b6;
					color: #b6b6b6;
					background: none;
				}
			}
		}
	}
</style>