<template>
	<view class="group-coupon">
		<view class="item" v-for="(item, index) in info" :key="index" :class="item.tour.status == 5 ? 'not-item' : ''">
			<view class="title-box">
				<view class="left">
					<view class="tag"></view>
					<view class="title">{{ item.tour.name }}</view>
				</view>
				<view class="right"><uni-icons type="right" size="22" color="#bababa"></uni-icons></view>
			</view>
			<view class="desc-box" @click.stop="navto(item.tour.id,item.tour.name)">
				<view class="desc-item">创建时间：{{ item.create_time }}</view>
				<view class="desc-item">总票数：{{ item.tour.spot_ids.split(',').length }}张</view>
				<view class="desc-item">计调人:{{ item.tour.planner }}</view>
				<view class="desc-item">联系方式:{{ item.tour.mobile }}</view>
			</view>
			<view class="bottom-box">
				<view class="botton color-86b897" @click.stop="navto(item.tour.id,item.tour.name)"><text style="font-weight: bold;">消费券</text></view>
				<view class="botton color-ffb47b" @click.stop="tourist(item.tourist,item.tour.name)"><text>游客信息</text></view>
				<view class="botton color-a00000" @click.stop="PunchCard(item)"><text>酒店打卡</text></view>
				
			</view>
		</view>

		
	</view>
</template>

<script>
import { mapState, mapMutations } from 'vuex';
export default {
	data() {
		return {
			info: [],	
		};
	},
	onLoad() {
		this.init();
	},
	computed: {
		...mapState(['uerInfo'])
	},
	methods: {
		...mapMutations(['setTouristInfo','setHotelList']),
		init() {
			this.$api
				.httpRequest(
					`/user/guide_tour`,
					{
						uid: this.uerInfo.uid
					},
					'POST'
				)
				.then(res => {
					if (res.data != ' ') {
						let data = res.data.sort(this.SortUp);
						data.forEach(item => {
							if (item.tour != null) {
								this.info.push(item);
							}
						});
					}
				});
		},
		SortUp(x, y) {
			return x.tour.status - y.tour.status;
		},
		navto(id,title) {
			uni.navigateTo({
				url: `/pages/user/GroupCoupon/list?id=${id}&title=${title}`
			});
		},
		tourist(item,title) {
			this.setTouristInfo(item);
			uni.navigateTo({
				url: `./touristInfo/touristInfo?title=${title}`
			});
		},
		PunchCard(item) {
			this.setHotelList(item);
			uni.navigateTo({
				url: `hotelList?tid=${item.tid}`
			});
		},
		
		
	
	}
};
</script>

<style lang="scss">
.not-item {
	color: #bfbfbf !important;
}
.not-item .desc-box .desc-item {
	color: #bfbfbf !important;
}

.not-item .botton {
	color: #bfbfbf !important;
	border: 2upx solid #bfbfbf !important;
}


.group-coupon {
	width: 100%;
	margin-top: 20upx;
	& .item {
		width: calc(95% - 40upx);
		padding: 20upx;
		border-radius: 20upx;
		margin: 0 auto 20upx;
		background-color: #fff;
		box-shadow: 2.8px 2.8px 2.2px rgba(0, 0, 0, 0.003), 6.7px 6.7px 5.3px rgba(0, 0, 0, 0.004), 12.5px 12.5px 10px rgba(0, 0, 0, 0.005),
			22.3px 22.3px 17.9px rgba(0, 0, 0, 0.006), 41.8px 41.8px 33.4px rgba(0, 0, 0, 0.007), 100px 100px 80px rgba(0, 0, 0, 0.01);
		& .title-box {
			width: 100%;
			display: flex;
			align-items: center;
			justify-content: space-between;
			& .tag {
			}
			& .title {
				font-weight: bold;
				font-size: 32upx;
				overflow: hidden;
				text-overflow: ellipsis;
				display: -webkit-box;
				word-break: break-all;
				-webkit-line-clamp: 1;
				-webkit-box-orient: vertical;
			}
		}
		& .desc-box {
			width: 100%;
			padding: 20upx 0 20upx;
			font-size: 22upx;
			& .desc-item {
				color: #666666;
				margin-bottom: 10upx;
			}
			& .info {
				display: flex;
				color: $div-bg-color;
			}
			& .desc-item:last-child {
				margin-bottom: 0;
			}
		}
		& .bottom-box {
			width: 100%;
			border-top: 1px solid #f7f7f7;
			display: flex;
			padding-top: 20upx;
			& .botton {
				width: calc(33% - 42px);
				margin-right: 7%;
				text-align: center;
				padding: 10upx 20upx;
				border-radius: 10upx;
				border: 1px solid;
				font-size: 24upx;
				font-weight: bold;
			}
			& .botton:last-child {
				margin-right: 0;
			}
		}
	}
}
textarea {
	width: calc(100% - 40upx);
	padding: 20upx;
	height: 200rpx;
	border: 1px solid #e2e2e2;
	background-color: #f9f9f9;
}
.color-86b897{
	color: #86b897;
	border-color: #86b897;
}
.color-ffb47b{
	color: #ffb47b;
	border-color: #ffb47b;
}
.color-a00000{
	color: $div-bg-color;
	border-color: $div-bg-color;
}
</style>
