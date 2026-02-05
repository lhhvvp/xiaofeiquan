<template>
	<view class="content">
		<view class="icon">
			<uni-icons type="checkmarkempty" size="54" color="#ffffff"></uni-icons>
		</view>
		<text class="tit">支付成功</text>
		<view class="btn-group">
			<navigator url="/pages/user/my_order?state=" open-type="redirect" class="mix-btn" v-if="order">查看订单</navigator>
			<navigator url="/pages/index/index" open-type="switchTab" class="mix-btn hollow">返回首页</navigator>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				order:true,
			}
		},
		onLoad(opiton) {
			if(opiton.order == 1){
				this.order = false;
			}
		},
		methods: {
			
		}
	}
</script>

<style lang='scss'>
	.content{
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;
		padding-top: 200upx;
	}
	.icon{
		width: 180upx;
		height: 180upx;
		border-radius: 50%;
		background-color: #339f0c;
		display: flex;
		justify-content: center;
		align-items: center;
	}
	.success-icon{
		font-size: 160upx;
		color: $div-bg-color;
		margin-top: 100upx;
	}
	.tit{
		margin-top: 20upx;
		font-size: 38upx;
		color: #303133;
	}
	.btn-group{
		padding-top: 100upx;
	}
	.mix-btn {
		margin-top: 30upx;
		display: flex;
		align-items: center;
		justify-content: center;
		width: 600upx;
		height: 80upx;
		font-size: 24upx;
		color: #fff;
		background-color: $div-bg-color;
		border-radius: 10upx;
		&.hollow{
			background: #fff;
			color: #303133;
			border: 1px solid #ccc;
		}
	}
</style>
