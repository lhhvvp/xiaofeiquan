<template>
	<view v-if="empty.show" class="empty-box">
		<view class="empty" v-if="empty.id == 1">
			<image src="/static/empty/001.png"></image>
			<text>暂无数据</text>
		</view>
		<view class="empty" v-if="empty.id == 2">
			<image src="/static/empty/002.png"></image>
			<text>暂无数据</text>
		</view>
		<view class="empty" v-if="empty.id == 3">
			<image src="/static/empty/003.png"></image>
			<text>暂无数据</text>
		</view>
	</view>
</template>

<script>
export default {
	name: 'my-empty',
	props: {
		empty: {
			type: Object,
			default: function() {
				return {
					show: false,
					id: 1
				};
			}
		}
	},
	data() {
		return {};
	},
	mounted(){
	}
};
</script>

<style lang="scss">
.empty-box {
	width: 100%;
	padding-top: 100upx;
	padding-bottom: 100upx;
	& .empty {
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;
		& image {
			width: 100px;
			height: 100px;
		}
		& text {
			font-size: 30upx;
			color: #bcbcbc;
		}
	}
}
</style>
