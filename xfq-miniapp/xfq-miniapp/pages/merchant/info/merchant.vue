<template>
	<view class="merchant">
		<view class="item">
			<view class="title">营业时间</view>
			<view class="content">{{ merchant.do_business_time }}</view>
		</view>
	
		<view class="item">
			<view class="title">商家电话</view>
			<view class="content">{{ merchant.mobile }}</view>
		</view>

		<view class="item">
			<view class="title">联系地址</view>
			<view class="content">{{ merchant.address }}</view>
		</view>
		
		<view class="item">
			<view class="title">商家介绍</view>
			<view class="content">
				<rich-text :nodes="merchant.content"></rich-text>
			</view>
		</view>
	</view>
</template>

<script>
import { mapState, mapMutations } from 'vuex';
export default {
	data() {
		return {};
	},
	computed: {
		...mapState(['merchant'])
	},
	onLoad() {},
	methods: {}
};
</script>

<style lang="scss">
page {
	color: #333;
	padding-top: 20upx;
}
.merchant {
	
	width: calc(95% - 40upx);
	padding: 20upx;
	background-color: #fff;
	border-radius: 20upx;
	margin: auto;
	& .item {
		width: 100%;
		margin-bottom: 50upx;
		padding-top: 30upx;
		& .title {
			font-size: 32upx;
			font-weight: bold;
			margin-bottom: 20upx;
		}
	}
}
</style>
