<template>
	<view>
		<view class="search">
			<input placeholder="请输入搜索内容" v-model="keyword" @input="keywordInput" placeholder-class="placeholder-input" />
			<view @click="searchButton()">搜索</view>
		</view>
		
		<view class="address-box" v-for="(item, index) in list" :key="index" @click="navto(item.id)">
			<view class="left">
				<view class="title">{{ item.nickname }}</view>
				<view class="address">
					<view class="add">
						<uni-icons type="location-filled"></uni-icons>
						{{ item.address }}
					</view>
				</view>
			</view>
			<view class="right">
				<view class="distance" v-if="!!item.distance">
					<uni-icons type="location"></uni-icons>
					{{ item.distance.toFixed(1) }}km
				</view>
			</view>
		</view>
		<uni-load-more :status="loadding" v-if="!empty.show"></uni-load-more>
		<!-- 为空页 -->
		<my-empty :empty="empty" v-if="empty.show"></my-empty>
		<!-- end -->
	</view>
</template>

<script>
import { getLocation } from "../../common/common.js"
export default {
	data() {
		return {
			list: [],
			page: 0,
			id: 0,
			loadding: 'more',
			loadding_lock: false,
			latitude:0,
			longitude:0,
			empty: {
				show: false,
				id: 3
			},
			keyword:"",
		};
	},
	onLoad(option) {
		this.id = option.id;
		let that = this;
		getLocation().then(
			success => {
				that.latitude = success.latitude;
				that.longitude = success.longitude;
				that.init();
			},
			fail => {
				// 失败
			}
		);
		
		
	},
	onReachBottom() {
		this.init();
	},
	methods: {
		init() {
			if (this.loadding_lock) {
				return false;
			}
			this.$api
				.httpRequest(
					`/coupon/applicableto`,
					{
						id: this.id,
						latitude: this.latitude,
						longitude: this.longitude,
						page: this.page,
						limit: 15,
						keyword:this.keyword,
					},
					'POST'
				)
				.then(res => {
					if (res.code == 0) {
						let data = res.data;
						if (this.page == 0 && data.length == 0) {
							// 如果没有数据则显示为空
							this.$set(this.empty, 'show', true);
							this.loadding_lock = true;
							return false;
						}
						this.list = [...this.list, ...data];

						this.page += 15;
						this.loadding = 'loading';

						if (data.length == 0) {
							//判断是否有下一页
							this.loadding = 'no-more';
							this.loadding_lock = true;
							return false;
						}

						if (data.length != 15) {
							this.loadding = 'no-more';
							this.loadding_lock = true;
							return false;
						}
					} else {
						this.$api.msg(res.msg, 'none');
					}
				});
		},
		distance(lat1, lng1) {
			var that = this;
			let lat2 = that.location.lat;
			let lng2 = that.location.lng;
			let rad1 = (lat1 * Math.PI) / 180.0;
			let rad2 = (lat2 * Math.PI) / 180.0;
			let a = rad1 - rad2;
			let b = (lng1 * Math.PI) / 180.0 - (lng2 * Math.PI) / 180.0;
			let s = 2 * Math.asin(Math.sqrt(Math.pow(Math.sin(a / 2), 2) + Math.cos(rad1) * Math.cos(rad2) * Math.pow(Math.sin(b / 2), 2)));
			s = s * 6378.137;
			s = Math.round(s * 10000) / 10000;
			s = s.toString();
			s = s.substring(0, s.indexOf('.') + 2);
			return s;
		},
		keywordInput(val){
			if(val.detail.value == ''){
				this.$api.msg('请输入关键字!');
			}else{
				this.keyword = val.detail.value;
			}
		},
		searchButton(){
			this.page = 0;
			this.list = [];
			this.loadding = 'more';
			this.loadding_lock = false;
			this.init();
		},
		navto(id) {
			uni.navigateTo({
				url: '/pages/merchant/info/info?id=' + id
			});
		}
	}
};
</script>

<style lang="scss">
page {
	color: #333;
}
.search {
	width: calc(98% - 40upx);
	padding: 0 20upx;
	margin: 0 auto 20upx;
	position: sticky;
	top: 0upx;
	display: flex;
	align-items: center;
	background-color: #fff;
	height: 80px;
	& input {
		width: calc(100% - 60upx);
		padding: 15upx 30upx;
		border-radius: 20upx;
		background-color: #f7f7f7;
	}
	& view {
		position: absolute;
		right: 0;
		padding: 18upx 50upx;
		border-radius: 20upx;
		background: $div-bg-color;
		z-index: 99;
		color: #fff;
		margin-right: 20upx;
	}
}
.address-box {
	background-color: #fff;
	border-bottom: 1px solid #efefef;
	width: calc(100% - 20upx);
	padding: 30upx 0 30upx 20upx;
	display: flex;
	align-items: center;
	& .left {
		width: calc(80% - 10upx);
		padding-right: 10upx;
		& .title {
			font-size: 32upx;
			padding-bottom: 10upx;
			font-weight: bold;
		}
		& .address {
			font-size: 26upx;
			display: flex;
			align-items: center;

			& .add {
				display: inline-block;
				color: #b5b5b5;
				width: 100%;
				overflow: hidden;
				text-overflow: ellipsis;
				display: -webkit-box;
				-webkit-line-clamp: 2;
				-webkit-box-orient: vertical;
			}
		}
	}
	& .right {
		width: 20%;
		& .distance {
			text-align: center;
			display: flex;
			align-items: center;
			color: #b5b5b5;
		}
	}
}
</style>
