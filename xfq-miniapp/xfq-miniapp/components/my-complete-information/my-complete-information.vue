<template>
	<view v-if="isShow">
		<view class="login-box-coped">
			<view class="login">
				<view class="login-title">注册信息</view>
				<view class="login-item">
					<view class="inputbox">
						<view class="input-title">
							<text class="red">*</text>
							姓名
						</view>
						<input class="input" placeholder="请输入姓名" v-model="name" placeholder-style="font-size:26upx;color:#c9c9c9" />
					</view>
					
					<view class="inputbox">
						<view class="input-title">
							<text class="red">*</text>
							手机号
						</view>
						<view class="mobile">
							<input class="input" placeholder="请输入手机号" v-model="mobile" placeholder-style="font-size:26upx;color:#c9c9c9" />
							<button class="mobile-button" open-type="getPhoneNumber" hover-class="none" @getphonenumber="getphone">获取手机号</button>
						</view>
					</view>

					<view class="inputbox">
						<view class="input-title">
							<text class="red">*</text>
							身份证号
						</view>
						<input class="input" type="idcard" placeholder="请输入身份证号" v-model="IdNumber" placeholder-style="font-size:26upx;color:#c9c9c9" />
					</view>
					<view class="buttom-in" @click="sumbit">完成并获取用户信息</view>
					<view class="cancel" @click="cancel">取消授权</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
import { mapState, mapMutations } from 'vuex';
import { checkIdNumber,checkName,checkPhone } from '@/common/common.js';
export default {
	name: 'my-complete-information',
	props: {
		isShow: {
			default: false,
			type: Boolean
		},
		code: {
			type: String,
			default: ''
		},
		openid:{
			typeof:String,
			default:"",
		},
		is_register:{
			typeof:Boolean,
			default:true,
		}
	},

	data() {
		return {
			name: '',
			mobile: '',
			IdNumber: '',
			getDataPhone: null,
			multiIndex: 0
		};
	},
	mounted() {},
	computed: {
		...mapState(['uerInfo'])
	},
	methods: {
		...mapMutations(['login', 'logout']),
		sumbit() {
			let its = this;
			if (!its.name) {
				this.$api.msg('请填写姓名', 'none');
				return false;
			};
			
			if (!checkName(its.name)) {
				this.$api.msg('请填写有效的姓名', 'none');
				return false;
			}
			if (!checkPhone(its.mobile)) {
				its.$api.msg('请填写手机号', 'none');
				return false;
			}
			if (!its.IdNumber) {
				its.$api.msg('请填写身份证号', 'none');
				return false;
			}
			if (!checkIdNumber(its.IdNumber)) {
				its.$api.msg('请填写正确的身份证号', 'none');
				return false;
			}
			uni.getUserProfile({
				desc: '用于完善会员资料',
				success: function(infoRes) {
					if ('getUserProfile:ok' != infoRes.errMsg) {
						return its.$api.msg('获取用户信息失败！');
					}
					its.$api
						.httpRequest(
							`/user/miniwxregister`,
							{
								name: its.name,
								idcard: its.IdNumber,
								mobile: its.mobile,
								openid: its.openid,
							},
							'POST'
						)
						.then(res => {
							if (res.code == 0) {
								let obj = {
									name: res.data.userinfo.name ? true : false,
									mobile: res.data.userinfo.mobile ? true : false,
									idcard: res.data.userinfo.idcard ? true : false,
									uid: res.data.userinfo.id,
									token: res.data.token
								};
								its.login(obj);
								if(!its.is_register){
									its.$emit('registerFun',true);
									return false;
								}
								setTimeout(() => {
									uni.switchTab({
										url: '/pages/index/index'
									});
								}, 2000);
								its.$api.msg(res.msg, 'success');
							} else {
								
								its.$api.msg(res.msg, 'none');
							}
						});
				}
			});
		},
		cancel() {
			this.$emit('cancel', true);
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
					'/index/getuserphonenumber',
					{
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

<style lang="scss" coped>
button {
	background: none;
	text-align: left;
	font-size: 28upx;
	color: rgba(153, 153, 153, 0.8);
	padding-left: 0;
	padding-right: 0;
	display: flex;
	align-items: center;
}
.red {
	color: $div-bg-color;
}
button:after {
	border: none;
}
.inputbox {
	display: flex;
	align-items: center;
	height: 90upx;
	.mobile{
		display: flex;
		justify-content: space-between;
		align-items: center;
		width: 80%;
		.input {
			width: calc(70% - 20upx);
		}
		.mobile-button{
			background-color: #a00000;
			color: #fff;
			padding: 10upx;
			border-radius: 10upx;
			line-height: 1.5;
		}
	}
}
.inputbox .input-title {
	width: 20%;
}
.input {
	width: 80%;
}
.buttom-in {
	height: 85upx;
	width: 80%;
	font-size: 26upx;
	margin: 30upx auto 0;
	border-radius: 10upx;
	color: #ffffff;
	display: flex;
	align-items: center;
	justify-content: center;
	background-color: $div-bg-color;
	margin-bottom: 20upx;
}
.cancel {
	margin: auto;
	text-align: center;

	color: #b6b6b6;
}
.login-box-coped {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	z-index: 999;
	display: flex;
	justify-content: center;
	align-items: center;
	&:before {
		position: fixed;
		backdrop-filter: blur(5px);
		background-color: rgba(0, 0, 0, 0.6);
		z-index: -1;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		content: '';
	}
	& .login {
		width: 80%;
		height: auto;
		padding: 5% 5% 7%;
		margin: auto;
		border-radius: 20upx;
		background-color: #fff;
		& .login-title {
			width: 100%;
			text-align: center;
			color: #b6b6b6;
			font-size: 26upx;
			height: 80upx;
			line-height: 80upx;
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
	}
}
</style>
