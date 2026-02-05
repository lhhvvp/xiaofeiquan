<template>
	<view>
		<view
			class="pull-item"
			:class="item.status == 1 || item.status == 4 || item.status == 5 ? '' : 'end-status'"
			v-for="(item, index) in lists"
			:key="index"
			:style="'background:' + bgColor"
			@click="clickInfo(item)"
		>
			<view class="pull-left">
				<view class="title">
					<text class="pull-title">{{ item.title }}</text>
				</view>
				<view class="time-box">
					<text class="time">{{ item.desc }}</text>
					<text class="time">{{ item.time }}</text>
				</view>
			</view>
			<view class="pull-right">
				<view class="price">
					<text v-if="item.item.issue_coupon_class_id == 3">￥</text>
					{{ item.item.issue_coupon_class_id == 3 ? item.price : '' }}
				</view>
				<view class="pull-status" :class="statusName(item.status,item.type).class">{{ statusName(item.status,item.type).name }}</view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	name: 'my-coupon-list-my',
	props: {
		lists: {
			type: Array,
			default:function(){
				return [];
			}
		},
		bgColor: {
			type: String,
			default: '#ffffff'
		}
	},
	data() {
		return {};
	},
	mounted() {
	},

	methods: {
		statusName(status,type) {
			let obj = {};
			obj.class = '';
			if(type == 'group'){
				obj.name = '未使用';
				switch (status){
					case 3:
						obj.name = '已使用';
						obj.class = 'end';
						break;	
				}; 
				return obj;
			}
			switch (status) {
				case 1:
					obj.name = '未使用';
					break;
				case 2:
					obj.name = '已使用';
					break;
				case 3:
					obj.name = '已过期';
					obj.class = 'end';
					break;
				case 4:
					obj.name = '查看信息';
					break;
					
				case 5:
					obj.name = '未核销';
					break;

			}
			return obj;
		},
		clickInfo(item) {
			this.$emit('click', item);
		}
	}
};
</script>

<style lang="scss" scoped>
.pull-item {
	position: relative;
	margin-bottom: 20upx;
	overflow: hidden;
	&::before {
		position: absolute;
		content: '';
		width: 30upx;
		border-radius: 50%;
		height: 30upx;
		top: -20upx;
		left: calc(70% - 24upx);
		background-color: #f7f7f7;
	}
	&::after {
		position: absolute;
		content: '';
		width: 30upx;
		border-radius: 50%;
		height: 30upx;
		bottom: -20upx;
		left: calc(70% - 24upx);

		background-color: #f7f7f7;
	}
	width: 100%;
	border-radius: 20upx;
	display: flex;
	justify-content: space-between;
	& .pull-left,
	& .pull-right {
		display: flex;
		justify-content: center;
		align-items: center;
	}
	& .pull-left {
		width: 70%;
		padding: 20upx 0 20upx;
		flex-direction: column;
		& .title {
			width: calc(100% - 40upx);
			display: flex;
			padding: 0 20upx;
			color: #333;
			flex-direction: column;
			& .pull-title {
				font-size: 34upx;
				// padding-bottom: 10upx;
				font-weight: bold;
			}
		}
		& .time-box {
			width: 100%;
			padding-top: 30upx;
			& .pull-desc {
				background: rgb(255, 255, 255);
				background: linear-gradient(90deg, rgba(255, 255, 255, 1) 0%, rgba(255, 255, 255, 0) 100%);
				padding: 0 20upx;
				margin-bottom: 10upx;
				color: #666666;
				font-weight: bold;
				font-size: 24upx;
				display: block;
			}
			& .time {
				width: calc(100% - 40upx);
				font-size: 20upx;
				padding: 0 20upx;
				color: #999999;
				text-align: left;
				display: block;
			}
		}
	}
	& .pull-right {
		width: 32%;
		border-left: 1px dashed #ebebeb;
		flex-direction: column;
		& .price {
			width: 100%;
			margin-bottom: 10upx;
			font-size: 60upx;
			color: $div-bg-color;
			font-weight: bold;
			text-align: center;
			& text {
				font-size: 22upx;
				font-weight: 500;
			}
		}
		& .pull-status {
			width: 80%;
			padding: 8upx 0;
			border: 1px solid $div-bg-color;
			border-radius: 50px;
			text-align: center;
			font-size: 24upx;
			font-weight: bold;
			color: $div-bg-color;
		}
	}
}
.end-status {
	.pull-status {
		border: 1px solid #d7d7d7 !important;
		color: #bfbfbf !important;
	}
	.price,
	.pull-title,
	.pull-desc,
	.time {
		color: #bfbfbf !important;
	}
}
</style>
