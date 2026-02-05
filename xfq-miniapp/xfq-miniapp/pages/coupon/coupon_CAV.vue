<template>
	<view class="coupon-cav">
		<view class="mask-box" v-if="is_mask">
			<view class="mask">
				<view class="title">{{statusName}}</view>
				<view class="status-img">
					<image src="../../static/success.png" v-if="isStatus == 1"></image>
					<image src="../../static/error.png" v-if="isStatus == 0"></image>
				</view>
				<view class="button-status" @click="down()">确定</view>
			</view>
		</view>

		<view class="box">
			<view class="title-box">
				<view class="title">商户核销</view>
				<uni-icons type="right" color="#c0c0c0"></uni-icons>
			</view>
			<view class="coupon-box">
				<view class="title">{{ info.coupon_title }}</view>
				<view class="price">{{ info.coupon_price }}</view>
				<view class="type">使用类型</view>
			</view>
			<!-- <view class="button-in">{{ statusName }}</view> -->
			<view class="content-info">
				<view class="time-box">
					<view class="title">优惠券有效期</view>
					<view class="info">{{ info.coupon_time_start }}至{{ info.coupon_time_end }}</view>
				</view>
				<view class="info-box">
					<view class="title">使用说明</view>
					<view class="info">暂无说明</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
import { mapState, mapMutations } from 'vuex';
import { getLocation } from "@/common/common.js";
export default {
	data() {
		return {
			info: {},
			statusName: '正在核销请等候...',
			isStatus: null,
			mid: 0,
			qrcode_url: null,
			id: null,
			type: null,
			is_mask:true,
			coord:{
				latitude:0,
				longitude:0,
			},
			latitude:0,
			longitude:0,
		};
	},
	onLoad(option) {
		this.id = option.id;
		this.mid = option.mid;
		this.qrcode_url = option.qrcode_url;
		this.type = option.type;
		this.coord = JSON.parse(option.coord);
		
		if (!option.id && !this.type) {
			this.$api.msg('参数错误');
			setTimeout(() => {
				uni.navigateBack();
			}, 2000);
			return false;
		}
		
		getLocation(false).then(res=>{
			this.latitude = res.latitude
			this.longitude = res.longitude;
			this.init();
		})
		
	},
	computed: {
		...mapState(['uerInfo'])
	},
	methods: {
		init(info) {
			this.$api
				.httpRequest(
					`/coupon/idtocoupon`,
					{
						cuid: this.id //领取id
					},
					'POST'
				)
				.then(res => {
					let that = this;
					if (res.code == 0) {
						
						this.info = res.data;
						uni.showLoading({
							title: '核销中',
							success(res) {
								if (res.errMsg === 'showLoading:ok') {
									that.receive();
								}
							}
						});
						// ajax
					} else {
						this.$api.msg(res.msg, 'none');
						setTimeout(() => {
							uni.navigateBack();
						}, 2000);
					}
				});
		},
		receive() {
			if(!this.latitude && !this.longitude){
				this.$api.msg('定位获取失败，请稍后重试','none');
				return false;
			}
			this.$api
				.httpRequest(
					`/coupon/writeoff`,
					{
						userid: this.uerInfo.uid, //用户ID
						mid: this.mid, //商户ID
						coupon_issue_user_id: this.id, //消费券领取记录ID
						// use_min_price: this.info.coupon_price, //订单消费金额
						use_min_price: 999999, //订单消费金额
						qrcode_url: this.qrcode_url,
						orderid: 0 ,//订单ID 暂无
						latitude:this.coord.latitude,//经纬度
						longitude:this.coord.longitude,//经纬度
						vr_latitude:this.latitude,
						vr_longitude:this.longitude
					},
					'POST'
				)
				.then(res => {
					this.statusName = res.msg;
					if (res.code == 0) {
						this.isStatus = 1;
					} else {
						this.isStatus = 0;
					}
					uni.hideLoading();
				});
		},
		down(){
			this.is_mask = !this.is_mask;
			uni.navigateBack();
		}
	}
};
</script>

<style lang="scss">
page {
	background-color: $div-color;
	.color-666 {
		background-color: #666;
		color: #fff;
	}

	& .coupon-cav {
		width: 95%;
		margin: auto;
		padding-top: 150upx;
		& .mask-box {
			width: 100%;
			height: 100%;
			position: fixed;
			left: 0;
			bottom: 0;
			background-color: rgba(0, 0, 0, .5);
			z-index: 99;
			& .mask {
				width: 60%;
				overflow: hidden;
				border-radius: 20upx;
				background-color: #fff;
				margin: auto;
				top: 25%;
				left: 20%;
				position: fixed;
				z-index: 100;
				
				& .title{
					width: 90%;
					padding:5%;
					text-align: center;
					font-weight: bold;
					font-size: 30rpx;
					border-bottom: 1px solid #f1f1f1;
					margin-bottom: 5upx;
				}
				& .status-img{
					width: 100%;
					height: 355rpx;
					& image{
						width: 100%;
						height: 100%;
					}
				}
				& .title-status{
					width: 100%;
					text-align: center;
					padding-bottom: 10upx;
				}
				& .button-status{
					height: 90upx;
					line-height: 90upx;
					text-align: center;
					width: 100%;
					font-weight: bold;
					font-size: 30upx;
					border-top: 1px solid #f1f1f1;
					background: #f7f7f7;
					color: $div-bg-color;
				}
			}
		}

		& .box {
			width: calc(100% - 40upx);
			padding: 20upx;
			border-radius: 20upx;
			background-color: #ffffff;
			position: relative;
			margin-bottom: 20upx;

			&::before {
				content: '';
				position: absolute;
				width: 30upx;
				height: 30upx;
				left: -15upx;
				top: calc(50% - 15upx);
				border-radius: 50%;
				background-color: $div-color;
			}

			&::after {
				content: '';
				position: absolute;
				width: 30upx;
				height: 30upx;
				right: -15upx;
				top: calc(50% - 15upx);
				border-radius: 50%;
				background-color: $div-color;
			}
		}

		& .title-box {
			display: flex;
			width: 100%;
			align-items: center;
			justify-content: space-between;

			& .title {
				width: 95%;
				margin: auto;
				height: 80upx;
				border-bottom: 1upx dashed #c7c7c7;
				font-size: 34upx;
				font-weight: bold;
				display: flex;
				align-items: center;
			}
		}

		& .coupon-box {
			width: 100%;
			padding-top: 60upx;
			padding-bottom: 20upx;
			display: flex;
			justify-content: center;
			flex-direction: column;
			text-align: center;

			& .status-img {
				width: 100%;
				height: 221upx;
				& image {
					width: 40%;
					height: 100%;
				}
			}
			& .title-status {
				font-size: 30upx;
				font-weight: bold;
			}
			& .title {
				font-size: 40upx;
				font-weight: bold;
			}

			& .price {
				padding-top: 10upx;
				font-size: 80upx;
				position: relative;
				color: $div-color;
				font-weight: bold;

				&::after {
					content: '元';
					font-size: 20upx;
				}
			}

			& .type {
				color: #999999;
			}
		}

		& .button-in {
			margin: auto;
			width: 60%;
			height: 50upx;
			background-color: $div-color;
			display: flex;
			font-size: 24upx;
			align-items: center;
			justify-content: center;
			color: #ffffff;
			border-radius: 20upx;
			padding: 10upx 0;
			margin-bottom: 30upx;
		}

		& .content-info {
			width: 95%;
			margin: auto;
			border-top: 1upx dashed #c7c7c7;

			& .time-box {
				padding: 26upx 0;
				display: flex;
				align-items: center;
				justify-content: space-between;
			}

			& .time-box:last-child {
				padding-bottom: 10upx;
			}

			& .info-box {
				& .title {
					font-weight: bold;
				}

				& .info {
					color: #999999;
				}
			}
		}
	}
}
</style>
