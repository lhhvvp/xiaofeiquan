<template>
	<view class="date">
		<view class="date-items">
			<view class="date-items-item" :class="[index == on ?'on':'']" v-for="(item,index) in datas" :key="index"
				@click="select(index,item)">
				<view class="date">{{item.date}}</view>
				<view class="price"><text>￥</text>{{item.price || '可预约'}}</view>
				
				<view class="data-right" v-if="index == on"><uni-icons type="checkmarkempty"
						color="#ffffff"></uni-icons></view>
			</view>
			<view class="date-items-item" style="width: 16%;" @click="showCalendar = !showCalendar">更多<br />日期</view>
		</view>
		<calendar-select v-model="showCalendar" maxDate="2099-8-20" :altPrice="dataDate"
			@change="changeDate"></calendar-select>
	</view>
</template>

<script>
	export default {
		name: "my-date",
		props: {
			dataDate: {
				type: Array,
				default: function() {
					return []
				}
			}
		},
		data() {
			return {
				showCalendar: false,
				on: 0,
				datas: [],
				
			};
		},
		mounted() {
			this.dataDate.forEach((item, index) => {
				if (index < 3) {
					this.datas.push(item);
				}
			});
		},
		methods: {
			init() {},
			// 获取选中的日期
			changeDate(data) {
				if (!data.price) {
					this.$api.msg('当前时间不能预约','none');
					this.$emit("FormData",{date:null});
					this.showCalendar = false;
					return false;
				};
				this.showCalendar = false;
				// this.from.date = data.date;
				this.datas[2].date = data.result
				this.datas[2].price = data.price
				this.on = 2;
				this.$emit("FormData",{date:data.result,price:data.price})
			},
			select(index,val) {
				this.$emit("FormData",val)
				this.on = index;
			}
		}
	}
</script>

<style lang="scss" scoped>
	.date-items {
		width: 100%;
		display: flex;
		justify-content: space-between;

		&-item {
			height: 120upx;
			border: 1upx solid #f7f7f7;
			width: 25%;
			display: flex;
			justify-content: center;
			align-items: center;
			flex-direction: column;
			border-radius: 10upx;
			position: relative;

			.price {
				color: #932027;
				font-weight: bold;
				padding: 4rpx 0 10rpx;
			}

			.data-right {
				position: absolute;
				right: 0;
				bottom: 0;
				width: 50upx;
				height: 30upx;
				background-color: #932027;
				border-radius: 10upx 0 10upx 0;
				display: flex;
				align-items: center;
				justify-content: center;
			}
		}

		.on {
			border: 1upx solid #932027;
			background-color: #93202705;
		}
	}
</style>