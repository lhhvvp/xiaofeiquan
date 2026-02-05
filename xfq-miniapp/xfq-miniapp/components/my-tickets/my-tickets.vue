<template>
	<view class="list-box">
		<view class="item" v-for="(item, index) in lists" :key="index" @click="click(item)">
			<view class="left" :style="'background-image: url(' + item.image + ');'"></view>
			<view class="right">
				<view class="title">
					<text>{{ item.nickname }}</text>
				</view>
				<view class="content">
					<view class="info" style="padding-top: 10upx;">{{item.area_text}}<text style="margin: 0 14upx;">|</text> 距我 {{item.distance}}km</view>
					<view class="info" style="display: flex;">
						<view>
							<uni-rate :readonly="true" size="16" :value="item.comment_rate"></uni-rate>
						</view>
						<text style="margin: 0 14upx;">|</text> {{item.comment_num}}人评价</view>
					<view class="money">门票<text style="color: #932027;font-weight: bold;margin-left: 10upx;">￥</text>
						<text style="font-size: 36upx;font-weight: bold;color: #932027;margin-right: 10upx;">{{item.min_price || 0}}</text> 起
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		name: 'my-tickets',
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
					overflow : hidden;
					text-overflow: ellipsis;
					display: -webkit-box;
					-webkit-line-clamp: 1;
					-webkit-box-orient: vertical;
				}
				& .content {
					width: 100%;
					height: 140upx;
					display: flex;
					flex-direction: column;
					color: #747474;
					font-size: 24upx;
					justify-content: flex-end;
					& view {
						margin-bottom: 5upx;
					}
				}
			}
		}

		& .item:last-child {
			margin-bottom: 0px;
		}
	}
</style>