<template>
	<view class="content">
		<text class="success-icon yticon icon-xuanzhong2"></text>
		<text class="tit">支付成功</text>

		<view class="btn-group">
			<view class="mix-btn" @click="navBack">返回</view>
			<navigator url="/pages/index/index" open-type="switchTab" class="mix-btn hollow">返回首页</navigator>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {

			}
		},
		methods: {
			navBack() {
				uni.navigateBack();
			}
		}
	}
</script>

<style lang='scss'>
	.content {
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;
	}

	.success-icon {
		font-size: 160upx;
		color: $div-bg-color;
		margin-top: 100upx;
	}

	.tit {
		font-size: 38upx;
		color: #303133;
	}

	.btn-group {
		padding-top: 100upx;
	}

	.mix-btn {
		margin-top: 30upx;
		display: flex;
		align-items: center;
		justify-content: center;
		width: 600upx;
		height: 80upx;
		font-size: 30upx;
		color: #fff;
		background-color: $div-bg-color;
		border-radius: 10upx;

		&.hollow {
			background: #fff;
			color: #303133;
			border: 1px solid #ccc;
		}
	}

	.icon-xuanzhong2:before {
		content: "\e62f";
	}
</style>