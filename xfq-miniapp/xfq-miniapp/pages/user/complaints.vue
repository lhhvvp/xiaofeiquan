<template>
	<view class="complaints">
		<view class="title">反馈和意见</view>
		<textarea v-model="content"></textarea>
		<view class="file"><my-file :limit="3" :imageStyles="imageStyles" v-on:updateFile="updateFile"
				v-on:deleteFile="deleteFile"></my-file></view>
		<view class="button-box"><text @click="submit()">提交</text></view>
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
				images: [],
				content: '',
				imagesRequest: [],
				imageStyles: {
					width: 96,
					height: 96,
					border: {
						// 如果为 Boolean 值，可以控制边框显示与否
						color: '#e2e2e2', // 边框颜色
						style: 'solid', // 边框样式
					}
				}
			};
		},
		computed: {
			...mapState(['hasLogin', 'uerInfo'])
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

			submit() {
				let content = this.$api.hasIllegalChar(this.content);
				if (!content) {
					this.$api.msg('请输入内容！');
					return false;
				};
				let images = this.imagesRequest.join(',');
				this.$api
					.httpRequest(
						`/user/feed_back`, {
							uid: this.uerInfo.uid,
							content: content,
							images: images
						},
						'POST'
					)
					.then(res => {
						if (res.code == 0) {
							this.$api.msg('提交成功！', 'success');
							setTimeout(() => {
								uni.navigateBack();
							}, 2500);
						} else {
							this.$api.msg(res.msg, 'success');
						}
					});
			}
		}
	};
</script>

<style lang="scss">
	.complaints {
		width: 100%;
		background-color: #fff;
		margin: auto;
		border-radius: 20upx;

		& textarea {
			width: calc(95% - 40upx);
			margin: auto;
			height: 150upx;
			border-radius: 20upx 20upx 0 0;
			padding: 20upx;
			background-color: #f7f7f7;
		}

		& .file {
			width: calc(95% - 40upx);
			height: 150upx;
			margin: auto;
			padding: 20upx;
			border-radius: 0 0 20upx 20upx;
			background-color: #f7f7f7;
		}

		& .title {
			width: 95%;
			margin: auto;
			font-weight: bold;
			font-size: 30upx;
			padding: 30upx 0;
		}

		& .info {
			width: 95%;
			background-color: #f7f7f7;
			margin: 20upx auto 0;
			border-radius: 20upx;
			overflow: hidden;
			display: flex;
			justify-content: space-between;
			align-items: center;
			position: relative;

			& input {
				width: calc(100% - 120upx);
				height: 80upx;
				padding-left: 120upx;
			}

			& text {
				position: absolute;
				left: 20upx;
			}
		}

		& .button-box {
			width: 100%;
			padding-top: 50upx;
			padding-bottom: 30upx;

			& text {
				width: 80%;
				display: block;
				height: 70upx;
				text-align: center;
				margin: auto;
				line-height: 70upx;
				border-radius: 50upx;
				background-color: $div-bg-color;
				color: #fff;
			}
		}
	}
</style>