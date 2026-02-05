<template>
	<view class="comment">
		<view class="items">
			<view class="item" v-for="(item,index) in dataVal" :key="index">
				<view style="display: flex;">
					<view class="left">
						<view class="image"
							:style="'background-image: url('+(item.users.headimgurl || 'https://thirdwx.qlogo.cn/mmopen/vi_32/POgEwh4mIHO4nibH0KlMECNjjGxQUq24ZEaGT4poC6icRiccVGKSyXwibcPq4BWmiaIGuG1icwxaQX6grC9VemZoJ8rg/132')+');'">
						</view>
					</view>
					<view class="right">
						<view class="title">{{item.users.nickname || '微信用户'}}</view>
						<view class="rate">
							<uni-rate :size="18" :value="item.rate" readonly="true"></uni-rate>
						</view>
					</view>
				</view>
				<view style="width: 70%;margin-left: 25%;">
					<view class="content">{{item.content}}</view>
					<view class="date">{{item.create_time}}</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		name: "my-comment",
		props: {
			dataVal: {
				type: Array,
				default: function() {
					return []
				}
			}
		}
	}
</script>

<style lang="scss">
	.comment {
		width: 100%;

		.items {
			width: 100%;
			background-color: #ffffff;

			.item {
				width: 100%;
				padding: 20upx 0;
				border-bottom: 1upx solid #f7f7f7;

				.left {
					width: 25%;
					padding-top: 24upx;
					display: flex;
					justify-content: center;
					align-items: center;

					.image {
						width: 100upx;
						height: 100upx;
						border-radius: 50%;
						background-color: $div-bg-color;
						background-size: cover;
					}
				}

				.right {
					width: 75%;

					.title {
						padding-top: 24upx;
						font-size: 32upx;
						font-weight: bold;
					}
				}

				.content {
					padding-bottom: 10upx;
					line-height: 1.5;
					word-break: break-all;
				}

				.date {
					color: #b6b6b6
				}
			}
		}
	}
</style>