<template>
	<view class="list-box">
		<view class="item" v-for="(item, index) in lists" :key="index" @click="click(item)">
			<view class="left" :style="'background-image: url(' + item.image + ');'"></view>
			<view class="right">
				<view class="title">
					<text>{{ item.nickname }}</text>
					<text v-if="!!item.distance">{{ item.distance }}km</text>
					<text v-if="!item.distance"></text>
				</view>
				<view class="content">
					<view class="tel">
						<uni-icons type="phone-filled" color="#a00000" size="14" style="margin-right: 20upx;"></uni-icons>
						<text>{{ item.mobile }}</text>
					</view>
					<view class="time">
						<uni-icons type="notification-filled" color="#a00000" size="14" style="margin-right: 20upx;"></uni-icons>
						营业时间：{{ item.do_business_time }}
					</view>
					<view class="address">
						<uni-icons type="location-filled" color="#a00000" size="14" style="margin-right: 20upx;"></uni-icons>
						<text>{{ item.address }}</text>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	name: 'my-merchant',
	// 商家列表
	props: {
		lists: {
			type: Array,
			default: function() {
				return [];
			}
		}
	},
	data() {
		return {};
	},
	mounted() {},
	methods: {
		click(item) {
			this.$emit('click', item);
		}
	}
};
</script>

<style lang="scss" scoped>
.list-box {
	width: 95%;
	margin: 20upx auto 0;
	& .item {
		width: calc(100% - 40upx);
		background-color: #ffffff;
		border-radius: 20upx;
		margin-bottom: 20upx;
		padding: 20upx;
		display: flex;
		justify-content: space-between;
		& .left {
			width: 200upx;
			height: 165upx;
			border-radius: 20upx;
			overflow: hidden;
			background-position: center;
			background-size: cover;
			background-repeat: no-repeat;
		}
		& .right {
			width: calc(100% - 230upx);
			display: flex;
			justify-content: center;
			flex-direction: column;
			& .title {
				width: 100%;
				font-weight: bold;
				font-size: 30upx;
				display: flex;
				align-items: center;
				justify-content: space-between;
				& text:first-child{
					width: 75%;
					display: inline-block;
					overflow: hidden;
					display: -webkit-box;
					text-overflow: ellipsis;
					-webkit-line-clamp: 1;
					-webkit-box-orient: vertical;
				}
				& text:last-child{
					width: 25%;
					font-size: 20upx;
					font-weight: 100;
					text-align: center;
				}
			}
			& .content {
				width: 100%;
				height: 120upx;
				display: flex;
				flex-direction: column;
				color: #747474;
				font-size: 24upx;
				justify-content: flex-end;
				& view {
					margin-bottom: 5upx;
				}
				& .time{
					display: -webkit-box;
					overflow: hidden;
					text-overflow: ellipsis;
					-webkit-line-clamp: 1;
					-webkit-box-orient: vertical;
				}
				& .address {
					display: -webkit-box;
					overflow: hidden;
					text-overflow: ellipsis;
					-webkit-line-clamp: 1;
					-webkit-box-orient: vertical;
				}
			}
		}
	}
	& .item:last-child {
		margin-bottom: 0px;
	}
}
</style>
