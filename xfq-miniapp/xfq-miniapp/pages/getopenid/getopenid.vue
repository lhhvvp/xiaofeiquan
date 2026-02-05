<template>
	<view class="box">
		<view class="logo-box">
			<view class="logo">
				<image src="https://oss.ylbigdata.com/wechat/bg.png"></image>
			</view>
			<text>榆林市文旅电子消费券</text>
		</view>
		
		<view class="button-box">
			<!-- <view class="button" @click="init()">授权</view> -->
			<button class="button" hover-class="none" @click="getphone">授权</button>
			<text>请完成授权以继续使用</text>
		</view>
		<my-complete-information :openid="openid" :isShow="isInfo" @cancel="cancel" :is_register="is_register"
			@registerFun="registerFun"></my-complete-information>
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
				scene: null,
				route: null,
				isInfo: false,
				openid: null,
				is_register: false,
			};
		},
		onLoad(options) {
			var scene = decodeURIComponent(options.scene);
			this.scene = this.GetScene(scene);
		},
		computed: {
			...mapState(['hasLogin', 'uerInfo'])
		},
		methods: {
			...mapMutations(['login', 'logout']),
			async is_login(logoCode) {
				const its = this;
				uni.checkSession({
					success(val) {
						if (val.errMsg == 'checkSession:ok') {
							//获取用户信息
							its.$api
								.httpRequest('/index/miniwxlogin', {
									code: logoCode,
									// encryptedData: infoRes.encryptedData,
									// iv: infoRes.iv,
									// headimgurl: infoRes.userInfo.avatarUrl,
									// nickname: infoRes.userInfo.nickName,
									// mobile: its.getDataPhone,
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
										its.submit(ret.data.userinfo.openid);

									} else if (ret.code == 4444) {
										//未注册的时候
										its.isInfo = true;
										its.openid = ret.data.openid;
										its.is_register = false;
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
					}
				});
			},
			cancel(e) {
				this.isInfo = false;
			},
			registerFun() {
				let openid = this.openid;
				if (openid) {
					this.isInfo = false;
					this.submit(openid);
				}
			},
			submit(openid) {
				uni.hideLoading();
				let scene = this.scene;
				// scene=mid/1*uid/2
				if (scene == null && scene == undefined) {
					this.$api.msg('参数错误，请重试!');
					return;
				}
				let that = this;
				uni.showModal({
					title: '授权信息确认',
					content: `即将绑定商户核销人员,确认信息无误后，单击确定完成绑定！`,
					success: function(res) {
						if (res.confirm) {
							that.getOpenId(openid);
						} else if (res.cancel) {}
					}
				});
			},
			getOpenId(openid_) {
				let scene = this.scene;
				let uuid = scene.uid; //葵花码参数
				let mid = scene.mid; //葵花码参数
				let openid = openid_ || this.uerInfo.openid; //openid
				if (uuid == undefined && mid == undefined) {
					this.$api.msg('参数错误，请重试!');
					setTimeout(() => {
						uni.reLaunch({
							url: '/pages/index/index'
						});
					}, 2000);
					return false;
				}
				//登录状态
				this.$api
					.httpRequest(
						'/seller/bindCheckOpenid', {
							uuid, //核验用户Id
							mid, //商家id
							openid, //openid
							uid: this.uerInfo.uid //用户id
						},
						'POST'
					)
					.then(res => {
						if (res.code == 0) {
							this.$api.msg(res.msg, 'success');
							setTimeout(() => {
								uni.reLaunch({
									url: '/pages/index/index'
								});
							}, 2000);
						} else {
							this.$api.msg(res.msg, 'none');
						}
					});
			},
			GetScene(scene) {
				var obj = {};
				for (var i = 0; i < scene.split('*').length; i++) {
					var arr = scene.split('*')[i].split('/');
					obj[arr[0]] = arr[1];
				}
				return obj;
			},
			getphone(e) {
				let that = this;
				uni.login({
					provider: 'weixin',
					success(res) {
						let logoCode = res.code;
						that.is_login(logoCode);
					}
				});
			},
		}
	};
</script>

<style lang="scss">
	page {
		background-color: #f7f7f7;
		background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAAXNSR0IArs4c6QAAAE5JREFUKFNj3LVrl5Sbm9szBigA8UFMdDFGmAIQjawJ3QC4QnQJdI1ghegmIVsNk2PEpgjmHJg7QWqIV0i01cjBghwkWD2DTTFG8BAb4ADJDGErkDEO0AAAAABJRU5ErkJggg==);
	}

	.box {
		display: flex;
		justify-content: space-around;
		flex-direction: column;
		position: fixed;
		height: 100%;
		width: 100%;

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
			z-index: -1;
		}
	}

	.logo-box {
		width: 100%;
		position: relative;
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;
		padding-top: 150upx;
		padding-bottom: 300upx;

		.logo {
			width: 100%;
			height: 100%;
			position: fixed;
			z-index: -1;
			top: 0;

			& image {
				width: 100%;
				height: 100%;
			}
		}

		& text {
			margin-top: 20upx;
			color: #fff;
			font-size: 40upx;
			font-weight: bold;
			width: 100%;
			text-align: center;
			display: block;
		}
	}

	.button-box {
		text-align: center;

		& text {
			display: block;
			width: 100%;
			text-align: center;
			padding: 40upx 0;
			color: #9a7070;
			font-size: 28upx;
		}

		.button {
			width: 60%;
			margin: auto;
			padding: 0upx;
			border-radius: 20upx;
			background-color: $div-bg-color;
			color: #fff;
			text-align: center;
		}
	}
</style>