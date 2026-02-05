<template>
	<view class="container container27893">
		<view class="flex flex-wrap diygw-col-24 flex-direction-column flex2-clz">
			<view v-if="dataList && dataList.length > 0" class="flex" v-for="item in dataList" :key='item.id'>
				<view class="flex flex-wrap diygw-col-24 flex-direction-column justify-between items-start flex9-clz"
					@click="navto('/pages/user/task/detail?taskId=' + item.id)">
					<view class="diygw-col-0 text13-clz"> {{ item.taskName }} </view>
					<view class="diygw-col-0 text14-clz"> {{ item.desc }} </view>
					<view class="flex flex-wrap diygw-col-24 justify-between items-center flex10-clz">
						<view class="diygw-col-0 text15-clz"> {{ item.tips }} </view>
						<view class="diygw-col-0 text16-clz"> {{ item.buttonName }} </view>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				//用户全局信息
				userInfo: {},
				//页面传参
				globalOption: {},
				//自定义全局变量
				globalData: {},
				dataList: [{
					id: "12",
					taskName: "入住酒店、参观景点任务",
					desc: "完成酒店以及景点打卡可领取过年大礼包，过年大礼包内容丰富，内含小米绿豆等杂粮",
					tips: "礼包丰富多样",
					buttonName: "做任务"
				}, {
					id: "14",
					taskName: "观影打卡任务",
					desc: "完成影院打卡",
					tips: "电影周边手办等你拿",
					buttonName: "已完成"
				}]
			};
		},
		onShow() {

		},
		onLoad() {
			console.log();
			this.init();
		},
		methods: {
			async init() {},
			navto(url) {
				uni.navigateTo({
					url: url
				});
			},
		}
	};
</script>

<style lang="scss" scoped>
	.flex2-clz {
		padding-top: 0rpx;
		padding-left: 0rpx;
		padding-bottom: 30rpx;
		padding-right: 0rpx;
	}

	.flex9-clz {
		padding-top: 30rpx;
		border-bottom-left-radius: 16rpx;
		background-size: 100% 100%;
		color: #ffffff;
		padding-left: 20rpx;
		padding-bottom: 30rpx;
		border-top-right-radius: 16rpx;
		margin-right: 20rpx;
		margin-left: 20rpx;
		box-shadow: 0rpx 0rpx 16rpx rgba(0, 0, 0, 0.2);
		overflow: hidden;
		width: calc(100% - 20rpx - 20rpx) !important;
		border-top-left-radius: 16rpx;
		margin-top: 30rpx;
		border-bottom-right-radius: 16rpx;
		background-image: linear-gradient(180deg, rgba(229, 248, 255, 1) 0%, rgba(210, 245, 253, 1) 18%, rgba(189, 243, 255, 1) 50%, rgba(229, 248, 255, 1) 100%);
		margin-bottom: 0rpx;
		padding-right: 20rpx;
	}

	.text13-clz {
		color: #019dc0;
		font-weight: bold;
		font-size: 32rpx !important;
	}

	.text14-clz {
		margin-left: 0rpx;
		color: #43a1a8;
		margin-top: 10rpx;
		margin-bottom: 10rpx;
		margin-right: 0rpx;
	}

	.flex10-clz {
		font-size: 26rpx !important;
	}

	.text15-clz {
		padding-top: 6rpx;
		border-bottom-left-radius: 40rpx;
		overflow: hidden;
		color: #228bf9;
		padding-left: 16rpx;
		padding-bottom: 6rpx;
		border-top-left-radius: 40rpx;
		border-top-right-radius: 0rpx;
		border-bottom-right-radius: 0rpx;
		background-image: linear-gradient(90deg, rgba(96, 222, 250, 0.86) 0%, rgba(255, 255, 255, 0) 100%);
		padding-right: 10rpx;
	}

	.text16-clz {
		background-color: #01b8df;
		padding-top: 10rpx;
		border-bottom-left-radius: 40rpx;
		overflow: hidden;
		color: #ffffff;
		padding-left: 20rpx;
		padding-bottom: 10rpx;
		border-top-left-radius: 40rpx;
		border-top-right-radius: 40rpx;
		border-bottom-right-radius: 40rpx;
		padding-right: 20rpx;
	}

	.container27893 {
		padding-left: 0px;
		padding-right: 0px;
		background-color: #ffffff;
	}

	.container27893 {}
</style>