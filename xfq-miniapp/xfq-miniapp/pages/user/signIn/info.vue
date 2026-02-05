<template>
	<view>
		<view class="mask-box">
			<view class="mask">
				<view class="title">打卡信息</view>
				<view class="item input">
					<label>打卡地点：</label>
					<input v-model="spot_name" placeholder="请输入打卡地点" />
				</view>

				<view class="item file">
					<view class="tit">打卡留言：</view>
					<view class="files"><textarea v-model="desc" placeholder="请输入打卡留言"></textarea></view>
				</view>

				<view class="item file my-file">
					<view class="tit">上传地点图片：</view>
					<view class="files"><my-file :limit="3" v-on:updateFile="updateFile" v-on:deleteFile="deleteFile"></my-file></view>
				</view>
				<view class="button" @click="submit">提交</view>
			</view>
		</view>
	</view>
</template>

<script>
import {getLocation } from "../../../common/common.js"
export default {
	data() {
		return {
			images: [],
			imagesRequest: [],
			spot_name: null,
			address: null,
			desc: '', //描述,
			latitude: 0,
			longitude: 0,
			tags: 0,
			id: 0,
			is_clock:true,
		};
	},
	onLoad(option) {
		this.id = option.id;
		this.tags = option.tags;
		this.getLatitude();
	},
	methods: {
		updateFile(res) {
			this.images = [];
			this.imagesRequest = [];
			res.forEach((item, index) => {
				this.images.push(item.url);
				this.imagesRequest.push(item.imgPath);
			});
		},
		deleteFile(err) {
			const num1 = this.images.findIndex(v => {
				v.url === err.url;
			});
			this.images.splice(num1, 1);

			const num2 = this.imagesRequest.findIndex(v => {
				v.url === err.url;
			});
			this.imagesRequest.splice(num2, 1);
		},
		getLatitude() {
			getLocation().then(success => {
				this.latitude = success.latitude;
				this.longitude = success.longitude;
				this.$api
					.httpRequest(
						`/index/transform`,
						{
							longitude: this.longitude,
							latitude: this.latitude
						},
						'POST'
					)
					.then(res => {
						if (res.code == 0) {
							if(this.longitude != 1 && this.latitude!=1){
								this.address = res.data.result.address;
								this.spot_name = res.data.result.formatted_addresses.rough;
							}
						} else {
							this.$api.msg(res.msg, 'none');
						}
					});
			});
		},
		submit() {
			if (this.images.length == 0) {
				this.$api.msg('请上传图片', 'none');
				return false;
			}
			if (!this.latitude && !this.longitude) {
				this.$api.msg('正在获取位置请稍后!');
				this.getLatitude();
				return false;
			};
			
			let images = this.imagesRequest.join(',');

			const { spot_name, desc, longitude, latitude, address, tags } = this;
			if (!images) {
				images = 'https://oss.wlxfq.dianfengcms.com/admins/6602b01b17f64ecf18798e4868f394bb.png';
			}
			if(!spot_name){
				this.$api.msg('请输入打卡地点!');
				return false
			}
			uni.showLoading({
				mask: true,
				title: '打卡提交中...'
			});
			if(!this.is_clock){
				return false;
			};
			this.is_clock = false;
			if (tags == 1) {
				// 景区
				this.$api
					.httpRequest(
						`/user/clock`,
						{
							clock_uid: this.uerInfo.uid,
							tour_issue_user_id: this.id,
							spot_name,
							images,
							address,
							longitude,
							latitude,
							dess: desc
						},
						'POST'
					)
					.then(res => {
						this.$api.msg(res.msg, 'none');
						if (res.code == 0) {
							setTimeout(() => {
								// uni.navigateBack();
							}, 1500);
						};
					});
			} else {
				//酒店
				this.$api
					.httpRequest(
						`/user/hotel_clock`,
						{
							id: this.id,
							images,
							address,
							longitude,
							latitude,
							descs: desc
						},
						'POST'
					)
					.then(res => {
						this.$api.msg(res.msg, 'none');
						if (res.code == 0) {
							setTimeout(() => {
								uni.navigateBack();
							}, 1000);
						};
					});
			}
		}
	}
};
</script>

<style lang="scss" scoped>
.mask-box {
	width: 100%;
	height: 100%;
	position: fixed;
	top: 0;
	left: 0;
	background-color: rgba(0, 0, 0, 0.6);
	display: flex;
	align-items: center;
	justify-content: center;
	flex-direction: column;
	& .down {
		width: 90%;
		display: flex;
		align-items: center;
		justify-content: flex-end;
		color: #fff;
		padding-bottom: 20upx;
	}
	& .mask {
		width: calc(85% - 60upx);
		padding: 30upx;
		background-color: #fff;
		border-radius: 20upx;
		& .title {
			padding-bottom: 50upx;
			width: 100%;
			text-align: center;
			font-weight: bold;
			font-size: 36upx;
			color: $div-bg-color;
		}
		& .item.input {
			width: 100%;
			display: flex;
			align-items: center;
			border-bottom: 1px solid #e2e2e2;
			font-size: 28upx;
			height: 80upx;
			& label {
				display: inline-block;
				width: 25%;
				color: #999999;
			}
			& input {
				width: 80%;
				color: #333333;
			}
		}
		& .item.file {
			width: 100%;
			& .tit {
				color: #999999;
				height: 80upx;
				line-height: 80upx;
			}
		}
		& .item.file.my-file {
			margin-bottom: 20upx;
		}

		& .button {
			width: 90%;
			margin: auto;
			color: #fff;
			background-color: $div-bg-color;
		}
	}
}
textarea {
	width: calc(100% - 40upx);
	padding: 20upx;
	height: 200rpx;
	border: 1px solid #e2e2e2;
	background-color: #f9f9f9;
}
</style>
