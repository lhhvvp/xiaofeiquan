<template>
	<view>
		<view class="items" v-if="auth_status != 1">
			<view class="item">
				<text>姓名：</text>
				<input v-model="name" placeholder="请输入姓名" />
			</view>
			<view class="item">
				<text>身份证号：</text>
				<input v-model="idcard" placeholder="请输入身份证号" />
			</view>
			<view class="item">
				<text>手机号：</text>
				<input v-model="mobile" @input="mobileInfo" placeholder="请输入手机号" style="width: 50%;" />
				<view class="wx-phone">
					<button class="mobile-button" open-type="getPhoneNumber" hover-class="none"
						@getphonenumber="getphone" v-if="!is_mobile">一键获取手机号</button>
					<view class="mobile-button" @click="sendCode" v-if="is_mobile">{{sendTitle}}</view>
				</view>
			</view>
			<view class="item" v-if="is_mobile">
				<text>验证码：</text>
				<input v-model="mobile_code" placeholder="请填写验证码" />
			</view>

			<view class="button" @click="submit">实名验证</view>
		</view>
		<view class="items" v-if="auth_status == 1" style="height:calc(  100vh - 160upx);">
			<view class="success-item">
				<view class="success">
					<uni-icons type="checkmarkempty" size="80" color="#ffffff"></uni-icons>
				</view>
				<view class="text">实名验证成功</view>
				<view class="text-next" @click="navto">返回上一页</view>
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
		checkIdNumber,
		checkName,
		checkPhone
	} from '@/common/common.js';
	export default {
		data() {
			return {
				name: null,
				mobile: null,
				idcard: null,
				mobile_code: '',

				auth_status: null,

				is_mobile: false,
				sendTime: 60,
				is_button: true,
				sendTitle: "发送验证码",
			};
		},

		onLoad(options) {
			if(options.option){
				let auth_status = JSON.parse(options.option);
				this.auth_status = auth_status.auth_status;
			}
			this.init();
			let codeTime = uni.getStorageSync('codeTime');
			if (codeTime) {
				if (Date.parse(new Date()) < codeTime) {
					this.sendTime = (Date.parse(new Date()) - codeTime) / 1000;
					this.sendTime = Math.abs(this.sendTime);
					this.sendTimes();
				} else {
					uni.removeStorageSync('codeTime')
				}
			}
		},
		onShow() {},

		computed: {
			...mapState(['uerInfo'])
		},
		methods: {
			...mapMutations([]),
			init() {
				this.$api.httpRequest(
						`/user/auth_info`, {
							uid: this.uerInfo.uid,
						},
						'POST'
					)
					.then(res => {
						this.idcard = res.data.idcard
						// this.mobile = res.data.mobile
						this.name = res.data.name
					})

			},
			mobileInfo(e) {
				this.is_mobile = true;
			},
			submit() {
				let its = this;
				if (!checkName(its.name)) {
					this.$api.msg('请填写有效的姓名', 'none');
					return false;
				}
				if (!its.mobile) {
					its.$api.msg('请填写手机号', 'none');
					return false;
				}
				if (!its.idcard) {
					its.$api.msg('请填写身份证号', 'none');
					return false;
				}
				if (its.is_mobile && !this.mobile_code) {
					its.$api.msg('请填写短信验证码', 'none');
					return false
				}

				its.$api
					.httpRequest(
						`/user/auth_identity`, {
							uid: its.uerInfo.uid,
							name: its.name,
							idcard: its.idcard,
							mobile: its.mobile,
							tags:its.is_mobile ==  true ? 1 : 0,
							smsCode:its.mobile_code,
						},
						'POST'
					)
					.then(res => {
						let code = res.code;
						uni.showModal({
							title: '提示',
							content: res.msg,
							showCancel: false,
							success: function(res) {
								if (res.confirm) {
									if(code == 0){
										uni.navigateBack();	
									}
								}
							}
						});
					});
			},
			navto() {
				uni.navigateBack();
			},
			sendTimes() {
				this.is_button = false;
				let time = setInterval(() => {
					this.sendTime = this.sendTime - 1;
					this.sendTitle = `请在${this.sendTime}秒后重试`;
					if (this.sendTime == 0) {
						clearInterval(time);
						this.sendTime = 60
						this.sendTitle = '重新获取验证码'
					}
				}, 1000)
			},
			sendCode() {
				if (this.sendTime == 60) {
					this.sendTimes();
					this.$api
						.httpRequest(
							'/user/smsVerification', {
								mobile:this.mobile,
								uid:this.uerInfo.uid,
							},
							'POST'
						)
						.then(ret => {
							this.$api.msg(ret.msg, 'none');
							uni.setStorageSync('codeTime', Date.parse(new Date()) + 60000);
						});
				};
			},
			getphone(e) {
				let that = this;
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
							this.mobile = ret.data.phone_info.phoneNumber;
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

	.items {
		background-color: #ffffff;
		width: calc(95% - 40upx);
		padding: 50upx 20upx 50upx;
		margin: 40upx auto 0;
		border-radius: 20upx;
		box-shadow: 2.8px 2.8px 2.2px rgba(0, 0, 0, 0.003), 6.7px 6.7px 5.3px rgba(0, 0, 0, 0.004), 12.5px 12.5px 10px rgba(0, 0, 0, 0.005), 22.3px 22.3px 17.9px rgba(0, 0, 0, 0.006),
			41.8px 41.8px 33.4px rgba(0, 0, 0, 0.007), 100px 100px 80px rgba(0, 0, 0, 0.01);

		.success-item {
			width: 100%;
			height: 500upx;
			display: flex;
			justify-content: center;
			align-items: center;
			flex-direction: column;

			.success {
				width: 150upx;
				height: 150upx;
				border-radius: 50%;
				background-color: #00cf00;
				display: flex;
				justify-content: center;
				align-items: center;
			}

			.text {
				padding-top: 40upx;
				font-size: 36upx;
				font-weight: bold;
			}

			.text-next {
				background-color: $div-bg-color;
				color: #ffffff;
				margin-top: 70upx;
				padding: 20upx 80upx;
				border-radius: 20upx;
			}
		}

		.item {
			padding: 10upx 0;
			border-bottom: 1upx solid #f7f7f7;
			display: flex;

			text {
				width: 20%;
				text-align: center;
				height: 80upx;
				line-height: 80upx;
			}

			input {
				width: 80%;
				height: 80upx;
			}
		}
	}

	.wx-phone {
		width: 30%;
		display: flex;
		align-items: center;
		justify-content: flex-end;
	}

	.mobile-button {
		background-color: #a00000;
		color: #fff;
		padding: 10upx;
		border-radius: 10upx;
		line-height: 1.5;
		font-size: 24upx;
		margin: 0;
	}
</style>