<template>
	<view>
		<view class="mask-box">
			<view class="down" @click="Down">
				<uni-icons type="closeempty"></uni-icons>
				关闭
			</view>
			<view class="mask">
				<view class="title">打卡信息</view>
				<view class="item input">
					<label>打卡地点:</label>
					<input v-model="spot_name" placeholder="请输入打卡地点" />
				</view>

				<view class="item file">
					<view class="tit">打卡留言:</view>
					<view class="files"><textarea v-model="desc" placeholder="请输入打卡留言"></textarea></view>
				</view>

				<view class="item file my-file">
					<view class="tit">上传地点图片:</view>
					<view class="files"><my-file :limit="3" v-on:updateFile="updateFile" v-on:deleteFile="deleteFile"></my-file></view>
				</view>
				<view class="button" @click="submit">提交</view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	name: 'my-is-clock',
	props: {
		value: {
			type: String,
			default: ''
		}
	},
	data() {
		return {
			images: [],
			imagesRequest: [],
			spot_name: this.value,
			mask: false,
			desc: '' //描述
		};
	},
	mounted() {},
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
		submit() {
			if (this.images.length == 0) {
				this.$api.msg('请上传图片', 'none');
			}
			let imagesRequest = this.imagesRequest.join(',');
			this.$emit('submit', { images: imagesRequest, spot_name: this.spot_name, desc: this.desc });
		},
		Down() {
			this.$emit('down', true);
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
				width: 20%;
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
