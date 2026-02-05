<template>
	<view>
		<view class="pull-item" v-for="(item, index) in lists" :key="index" :style="'background:' + bgColor" @click="clickInfo(item)">
			<view class="pull-left">
				<view class="title">
					<text class="pull-title">{{ item.title }}</text>
				</view>
				<view class="time-box">
					<text class="pull-desc">{{ item.desc }}</text>
					<text class="time">{{ item.time }}</text>
				</view>
			</view>
			<view class="pull-right">
				<view class="price">
					<text class="text" v-if="item.item.cid == 3">￥</text>
					{{ item.item.cid == 3 ? item.price : '' }}
				</view>
				<!-- <view class="pull-status">领取</view> -->
				<!-- <view class="pull-status">已领取</view> -->
				<view class="pull-status" :class="statusName(item.status).class" @click.stop="ToReceive(item)">{{ statusName(item.status).name }}</view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	name: 'my-coupon-list',
	props: {
		lists: {
			type: Array
		},
		bgColor: {
			type: String,
			default: 'linear-gradient(90deg, #ce3b3b 0%, #ff5959 100%)'
		},
		
	},
	data() {
		return {};
	},
	methods: {
		statusName(status) {
			let obj = {};
			obj.class = '';
			switch (status) {
				case 1:
					obj.name = '查看详情';
					break;
				case 2:
					obj.name = '已领取';
					break;
				case 3:
					obj.name = '已过期';
					obj.class = 'end';
					break;
				case 4:
					obj.name = '查看二维码';
					break;
			}
			return obj;
		},
		clickInfo(item) {
			this.$emit('click', item);
		},
		ToReceive(item) {
			this.$emit('ToReceive', item);
		}
	}
};
</script>

<style lang="scss" scoped>
.pull-item {
	position: relative;
	margin-bottom: 20upx;
	overflow: hidden;
	width: 100%;
	border-radius: 20upx;
	display: flex;
	justify-content: space-between;
	&::before {
		position: absolute;
		content: '';
		width: 24upx;
		border-radius: 50%;
		height: 24upx;
		top: -12upx;
		left: calc(70% - 24upx);

		background-color: #f7f7f7;
	}
	&::after {
		position: absolute;
		content: '';
		width: 24upx;
		border-radius: 50%;
		height: 24upx;
		bottom: -12upx;
		left: calc(70% - 24upx);

		background-color: #f7f7f7;
	}

	& .pull-left,
	& .pull-right {
		display: flex;
		justify-content: center;
		align-items: center;
	}
	& .pull-left {
		width: 70%;
		padding: 25upx 0 25upx;
		flex-direction: column;
		& .title {
			width: calc(100% - 40upx);
			display: flex;
			padding: 0 20upx;
			color: #fff;
			flex-direction: column;
			& .pull-title {
				font-size: 34upx;
				// padding-bottom: 10upx;
				font-weight: bold;
			}
		}
		& .time-box {
			width: 100%;
			padding-top: 10upx;
			& .pull-desc {
				background: rgb(255, 255, 255);
				background: linear-gradient(90deg, rgba(255, 255, 255, 1) 0%, rgba(255, 255, 255, 0) 100%);
				padding: 0 20upx;
				margin-bottom: 10upx;
				color: #df1d2c;
				font-weight: bold;
				font-size: 24upx;
				display: block;
			}
			& .time {
				width: calc(100% - 40upx);
				font-size: 20upx;
				padding: 0 20upx;
				color: #ffd4d8;
				text-align: left;
				display: block;
			}
		}
	}
	& .pull-right {
		width: 32%;
		border-left: 1px dashed #ff8080;
		flex-direction: column;
		& .price {
			width: 100%;
			margin-bottom: 10upx;
			font-size: 60upx;
			color: #fff;
			font-weight: bold;
			text-align: center;
			& .text {
				font-size: 22upx;
				font-weight: 500;
			}
		}
		& .pull-status {
			width: 80%;
			padding: 8upx 0;
			border: 1px solid #fff;
			border-radius: 50px;
			text-align: center;
			font-size: 24upx;
			font-weight: bold;
			color: #fff;
		}
		& .end {
			border: none;
			background-color: #ab3a43;
		}
	}
}
.pull-item:last-child {
	margin-bottom: 0;
}
</style>
