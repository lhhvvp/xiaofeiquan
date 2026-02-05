<template>
	<view>
		<view class="avatar">
			<button :style="'background-image:url(' + headimgurl + ')'" class="avatarButton" open-type="chooseAvatar" @chooseavatar="headimgurlEdit"></button>
			<text>昵称：{{ nickname }}</text>
		</view>
		<view class="items">
			<view class="item">
				<view>昵称</view>
				<input v-model="nickname"  type="nickname" placeholder="请输入昵称" @blur="onblur" />
			</view>

			<!-- <view class="item">
				<view>性别</view>
				<picker @change="SixArrayChange" :value="sex" :range="SixArray">
					<view class="uni-input">{{ SixArray[sex] }}</view>
				</picker>
			</view> -->

			<view class="item disabled">
				<view>真实姓名</view>
				<input v-model="username" disabled placeholder="请输入昵称" />
			</view>
			<view class="item disabled">
				<view>手机号</view>
				<input v-model="mobile" disabled />
			</view>
			<view class="item disabled">
				<view>身份证号</view>
				<input v-model="idcard" disabled placeholder="请输入身份证号" />
			</view>
		</view>
		<view class="button-box"><button class="button" @click="edit">提 交</button></view>
	</view>
</template>

<script>
import { mapState, mapMutations } from 'vuex';
export default {
	data() {
		return {
			info: {},
			// SixArray: ['男', '女'],
			// sex: 0,
			nickname: '',
			headimgurl: 'https://thirdwx.qlogo.cn/mmopen/vi_32/POgEwh4mIHO4nibH0KlMECNjjGxQUq24ZEaGT4poC6icRiccVGKSyXwibcPq4BWmiaIGuG1icwxaQX6grC9VemZoJ8rg/132',
			mobile: '',
			username: '',
			idcard: ''
		};
	},
	onLoad() {
		this.init();
	},
	computed: {
		...mapState(['hasLogin', 'uerInfo'])
	},
	methods: {
		init() {
			this.$api
				.httpRequest(
					`/user/index`,
					{
						uid: this.uerInfo.uid,
						is_token:true,
					},
					'POST'
				)
				.then(res => {
					if (res.code == 0) {
						this.nickname = res.data.nickname;
						this.mobile = res.data.mobile;
						// this.sex = Number(res.data.sex - 1);
						this.headimgurl = res.data.headimgurl;
						this.username = res.data.name;
						this.idcard = res.data.idcard;
					}
				});
		},
		onblur(option){
			this.nickname = option.detail.value
		},
		// SixArrayChange(e) {
		// 	this.sex = e.detail.value;
		// },
		edit() {
			let { nickname, sex, headimgurl } = this;
			sex = sex + 1;
			this.$api
				.httpRequest(
					`/user/edit`,
					{
						id: this.uerInfo.uid,
					
						nickname,
						// sex,
						headimgurl
					},
					'POST'
				)
				.then(res => {
					if (res.code == 0) {
						this.$api.msg(res.msg, 'success');
						setTimeout(() => {
							uni.navigateBack();
						}, 2500);
					} else {
						this.$api.msg(res.msg, 'none');
					}
				});
		},
		headimgurlEdit(e) {
			let that = this;
			const avatars = e.detail.avatarUrl;
			if(!that.uerInfo.token && !that.uerInfo.uid){
				that.headimgurl=avatars
				return false
			}
			uni.uploadFile({
				url: `${that.$api.baseUrl}/upload/index`, //仅为示例，非真实的接口地址
				filePath: avatars,
				name: 'file',
				header:{
					Token: that.uerInfo.token,
					Userid:that.uerInfo.uid,
				},
				formData: {
					uid: that.uerInfo.uid
				},
				success: uploadFileRes => {
					let res = JSON.parse(uploadFileRes.data);
					that.headimgurl = `${that.$api.urli}${res.url}`;
				}
			});
			return false;
			// uni.chooseImage({
			// 	count: 1, //默认9
			// 	sizeType: ['original', 'compressed'], //可以指定是原图还是压缩图，默认二者都有
			// 	sourceType: ['album'], //从相册选择
			// 	success: chooseImageRes => {
			// 		const tempFilePaths = chooseImageRes.tempFilePaths;
			// 		uni.uploadFile({
			// 			url: `${its.$api.urli}/api/upload/index`, //仅为示例，非真实的接口地址
			// 			filePath: tempFilePaths[0],
			// 			name: 'file',
			// 			// formData: {
			// 			// 	uid: its.uerInfo.uid,
			// 			// 	Token: its.uerInfo.token
			// 			// },
			// 			success: uploadFileRes => {
			// 				let res = JSON.parse(uploadFileRes.data);
			// 				its.headimgurl = `${its.$api.urli}${res.url}`;
			// 			}
			// 		});
			// 	}
			// });
		}
	}
};
</script>

<style lang="scss">
.avatar {
	width: 100%;
	height: 280upx;
	background-color: #ffffff;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-direction: column;
	& image {
		width: 130upx;
		height: 130upx;
		border-radius: 50%;
		border: 1px solid #e5e5e5;
	}
	& text {
		display: inline-block;
		padding-top: 20upx;
	}
}
.items {
	margin-top: 20upx;
	width: calc(100% - 60upx);
	padding: 0upx 30upx 0;
	background-color: #ffffff;
	& .item {
		width: 100%;
		padding: 30upx 0;
		border-bottom: 1px solid #f7f7f7;
		display: flex;
		justify-content: space-between;
		& input {
			width: 80%;
			text-align: right;
			font-size: 26upx;
		}

		& picker {
			width: 80%;
			text-align: right;
			& view {
				width: 100%;
			}
		}
		& view {
			width: 20%;
		}
	}
	& .disabled {
		& input {
			color: #bfbfbf;
		}
		& view {
			color: #bfbfbf;
		}
	}
}
.button-box {
	width: 100%;
	position: fixed;
	bottom: 10%;
}
.avatarButton {
	width: 130upx;
	height: 130upx;
	background-size: cover;
	margin: 0;
	padding: 0;
	border-radius: 50%;
	&::after {
		border: none;
	}
}
</style>
