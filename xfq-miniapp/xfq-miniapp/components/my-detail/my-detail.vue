<template>
	<view class="my-detail">
		<view class="my-detail-header">
			{{dataDetail.typeName || ''}}
		</view>
		<view class="my-detail-body">
			<view class="left">
				<view class="image" :style="'background-image:url('+dataDetail.coupon_icon+')'"></view>
			</view>
			<view class="right">
				<view class="pay-detail-right-title">{{dataDetail.title}}</view>
				<view class="pay-detail-right-price">
					<view class="pay-price">
						<text>实付款：</text>
						￥{{dataDetail.price || 0}}
					</view>
					<view class="pay-number">x{{dataDetail.number}}</view>
				</view>
			</view>
		</view>
		<view class="my-detail-body padding-20">
			<view class="list">
				<view class="list-tlt">支付状态</view>
				<view class="list-content" :style="'color:'+paymentStatus(dataDetail.payment_status).Color">{{paymentStatus(dataDetail.payment_status).Text}}</view>
			</view>
			<view class="list">
				<view class="list-tlt">订单编号</view>
				<view class="list-content">{{dataDetail.orderNumber}}</view>
			</view>
			<view class="list">
				<view class="list-tlt">创建时间</view>
				<view class="list-content">{{dataDetail.creationTime}}</view>
			</view>
			<view class="list">
				<view class="list-tlt">支付时间</view>
				<view class="list-content">{{dataDetail.payTime}}</view>
			</view>
		
		</view>
	</view>
</template>

<script>
	export default {
		name:"my-detail",
		props:{
			dataDetail:{
				type:Object,
				default:function(){
					return{}
				}
			}
		},
		data() {
			return {
				
			};
		},
		methods:{
			paymentStatus(status){
				let obj = {};
				switch (status){
					case 0:
						obj = {
							Color: '#a00000',
							Text: '未支付'
						};
						break;
					case 1:
						obj = {
							Color: '#00aa00',
							Text: '已支付'
						};
						break;
					case 2:
						obj = {
							Color: '#333333',
							Text: '已退款'
						};
						break;
				};
				return obj
			}
		}
	}
</script>

<style lang="scss">
	.my-detail{
		width: 95%;
		margin: auto;
		border-radius: 20upx;
		overflow: hidden;
	}
	.my-detail-header{
		background-color: #fff;
		font-size: 32upx;
		width: calc(100% - 40upx);
		padding: 25upx 20upx 30upx;
		font-weight: bold;
	}
	.my-detail-body{
		width: calc(100% - 40upx);
		background-color: #fff;
		margin-bottom: 20upx;
		padding: 0 20upx 30upx;
		border-radius: 0 0 20upx 20upx;
		display: flex;
		justify-content: space-between;
		& .left{
			overflow: hidden;
			border-radius: 25upx;
			& .image{
				width: 160upx;
				height: 160upx;
				background-repeat: no-repeat;
				background-size: cover;
				background-position: center;
			}
		}
		& .right{
			width: calc(95% - 160upx);
			display: flex;
			flex-direction: column;
			height: 160upx;
			justify-content: space-evenly;
			& .pay-detail-right-title {
				width: 100%;
				font-size: 32upx;
				font-weight: bold;
				overflow: hidden;
				text-overflow: ellipsis;
				display: -webkit-box;
				-webkit-line-clamp: 2;
				-webkit-box-orient: vertical;
			}
			& .pay-detail-right-desc {
				margin: 20upx 0;
				color: #999999;
				overflow: hidden;
				text-overflow: ellipsis;
				display: -webkit-box;
				-webkit-line-clamp: 2;
				-webkit-box-orient: vertical;
			}
			& .pay-detail-right-price {
				width: 95%;
				font-size: 36upx;
				font-weight: bold;
				display: flex;
				align-items: center;
				justify-content: space-between;
				position: relative;
				& .pay-price {
					color: #a00000;
					& text {
						color: #333333;
						font-size: 26upx;
						font-weight: 200;
					}
				}
				& .pay-number {
					font-weight: 200;
					font-size: 26upx;
				}
			}
		}
		& .list{
			display: flex;
			justify-content: space-between;
			padding: 20upx 0;
			width: 100%;
			height: 40upx;
			& .list-content{
				color: #999999;
			}
		}
	}
	.padding-20{
		padding: 0 20upx;
		border-radius: 20upx;
		flex-direction: column;
	}
</style>