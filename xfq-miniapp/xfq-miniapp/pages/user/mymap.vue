<template>
	<view>
		<view class="map">
			<map :latitude="lat" :longitude="lng" :markers="markers"></map>
			<view class="info">{{info}}</view>
			<view class="button-map">
				<view @click="copy()" class="copy">复制</view>
				<view @click="updata()">更新位置</view>
			</view>
		</view>
		
	</view>
</template>

<script>
	import {
		mapState,
		mapMutations
	} from 'vuex';
	import {
		getLocation
	} from "@/common/common.js"
	export default {
		data() {
			return {
				lat: null,
				lng: null,
				markers: [],
				info: null,
			};
		},

		onLoad(options) {
			this.lat = options.lat;
			this.lng = options.lng;
			this.markers.push({
				id: 1,
				latitude: options.lat,
				longitude: options.lng,
			});
			this.info = `当前位置 经度:${this.lng},纬度:${this.lat}。`
		},
		onShow() {},

		computed: {
			...mapState(['uerInfo']),
		},
		methods: {
			...mapMutations([]),
			copy() {
				uni.setClipboardData({
					data: this.info,
				});
			},
			updata() {
				getLocation(false).then(res => {
					const {
						latitude,
						longitude
					} = res;
					this.markers = [];
					this.lat = latitude;
					this.lng = longitude;
					this.info = `当前位置 经度:${this.lng},纬度:${this.lat}。`
					this.markers.push({
						id: 1,
						latitude,
						longitude
					})
					this.$api.msg('更新成功！','success');
				})
			}
		}
	};
</script>

<style lang="scss">
	.map {
		width: 100%;
		height: 100vh;

		map {
			width: 100%;
			height: 70vh;
		}
	}
	.info{
		width: calc(100% - 40upx);
		padding: 30rpx 20upx;
		color: #ffffff;
		text-align: center;
		font-size: 30rpx;
		background-color: $div-bg-color;
	}
	.button-map{
		width: 90%;
		display: flex;
		justify-content: space-evenly;
		margin: 50rpx auto 0;
		view{
			width: 40%;
			background-color: $div-bg-color;
			color: #ffffff;
			border-radius: 20upx;
			text-align: center;
			padding: 20rpx;
		}
		.copy{
			background-color: #d4d4d4;
			color: $div-bg-color;
		}
	}
</style>