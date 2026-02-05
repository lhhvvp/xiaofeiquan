<template>
	<view class="coupon-box">
		<view class="coupon">
			<view class="content-coupon">
				<!-- <view class="info">券金额:{{ info.coupon_price }}</view> -->

				<view class="title">{{ info.coupon_title }}</view>
				<view class="qrcode"><image src="@/static/001.png"></image></view>
			</view>
			<!-- <view class="items">
				<view class="item">
					<view class="item-left"><uni-icons type="shop" color="#ae000d" size="20"></uni-icons></view>
					<view class="item-rigth">自票券到账起7天(7*24小时)内有效期,最晚核销时间不晚于2022年8月31日23:59:59,逾期作废,不予补偿</view>
				</view>
				<view class="item">
					<view class="item-left"><uni-icons type="vip" color="#ae000d" size="20"></uni-icons></view>
					<view class="item-rigth">62开头银联卡</view>
				</view>
			</view> -->
			<view class="info-box">
				<view class="info" @click.stop="navToList()">
					<view class="left">适用于</view>
					<view class="right" v-if="suitable.address != undefined">
						<view class="name-title">{{ suitable.address }}</view>
						<view class="name-icons">
							<view>{{ suitable.distance }}km</view>
							<uni-icons type="right"></uni-icons>
						</view>
					</view>
				</view>
			</view>

			<!-- <view class="info">
				<view class="left">活动咨询</view>
				<view class="right"><uni-icons type="right"></uni-icons></view>
			</view> -->
		</view>
		<view class="instructions">
			<view class="title">核销时间</view>
			<view class="content">{{ info.create_time }}</view>
		</view>

		<view class="instructions">
			<view class="title">使用细则</view>
			<view class="content"><rich-text :nodes="info.remark"></rich-text></view>
		</view>
	</view>
</template>

<script>
import { mapState, mapMutations } from 'vuex';
import {getLocation} from "../../common/common.js"
export default {
	data() {
		return {
			info: {},
			id: 0,
			latitude: 0,
			longitude: 0,
			coupon_issue_id: 0,
			suitable: {}
		};
	},
	onLoad(option) {
		this.id = option.id;
		this.mid = option.mid;
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
	computed: {
		...mapState(['uerInfo', 'merchant'])
	},
	methods: {
		init() {
			this.$api
				.httpRequest(
					`/coupon/writeoffdetail`,
					{
						userid: this.uerInfo.uid,
						mid: this.mid,
						id: this.id
					},
					'POST'
				)
				.then(res => {
					if (res.code == 0) {
						this.info = res.data;
						this.applicableto(res.data.coupon_issue_id);
						// ajax
					} else {
						this.$api.msg(res.msg, 'none');
						setTimeout(() => {
							uni.navigateBack();
						}, 2000);
					}
				});
		},
		navToList() {
			uni.navigateTo({
				url: '/pages/coupon/list?id=' + this.coupon_issue_id
			});
		},
		applicableto(id) {
			this.coupon_issue_id = id;
			this.$api
				.httpRequest(
					`/coupon/applicableto`,
					{
						id: id,
						latitude: this.latitude,
						longitude: this.longitude,
						page: 0,
						limit: 1
					},
					'POST'
				)
				.then(res => {
					if (res.code == 0) {
						if (res.data.length == 0) return false;
						this.$set(this.suitable, 'address', res.data[0].nickname);
						this.$set(this.suitable, 'distance', res.data[0].distance.toFixed(2));
					} else {
						this.$api.msg(res.msg, 'none');
					}
				});
		}
	}
};
</script>

<style lang="scss">
page {
	min-height: calc(100vh - calc(44px + env(safe-area-inset-top)));
	background: linear-gradient(180deg, rgba(174, 0, 13, 1) 0%, rgba(247, 247, 247, 1) 30%, rgba(247, 247, 247, 1) 100%);
	font-size: #333333;
}
.coupon-box {
	width: 100%;
	padding-top: 100upx;
	& .coupon {
		width: 95%;
		margin: auto;
		overflow: hidden;
		& .content-coupon {
			border-radius: 20upx 20upx 0 0;
			width: calc(100% - 40upx);
			margin: auto;
			display: flex;
			align-items: center;
			flex-direction: column;
			padding: 80upx 20upx 20upx;
			background-color: #fff;
			border-bottom: dashed 1upx #dddddd;
			position: relative;
			&::before,
			&::after {
				content: '';
				position: absolute;
				width: 40upx;
				height: 40upx;
				border-radius: 50%;
				background-color: #f7f7f7;
			}
			&::before {
				left: -20upx;
				bottom: -20upx;
			}
			&::after {
				right: -20upx;
				bottom: -20upx;
			}
			& .qrcode {
				width: 300upx;
				height: 300upx;
				margin: auto;
				& image {
					width: 100%;
					height: 100%;
				}
			}
			& .info {
				color: #fff;
				display: flex;
				height: 60upx;
				line-height: 48upx;
				padding: 0;
				justify-content: center;
				font-size: 24upx;
				position: absolute;
				left: -11upx;
				top: 16upx;
				width: 20%;
				background: none;
				background-image: url('@/static/info.png');
				background-repeat: no-repeat;
				background-size: 100% 100%;
			}
			& .price {
				width: 100%;
				font-size: 45upx;
				font-weight: bold;
				color: $div-bg-color;
				text-align: center;
			}
			& .title {
				width: 100%;
				font-size: 35upx;
				text-align: center;
				font-weight: bold;
				padding-bottom: 10upx;
			}
			& .button {
				width: 40%;
				height: 60upx;
				margin-bottom: 10upx;
				box-shadow: none;
			}
			& .progress {
				width: 50%;
				margin: 0 auto 20upx;
			}
			& .area {
				font-size: 22upx;
				color: #828282;
			}
		}
		& .items {
			width: calc(100% - 40upx);
			padding: 30upx 20upx 20upx;
			background-color: #fff;
			margin: auto;
			& .item {
				display: flex;
				margin-bottom: 20upx;
				& .item-left {
					margin-right: 10upx;
					display: flex;
					align-items: center;
				}
				& .item-rigth {
					color: #5f5f5f;
					display: flex;
					align-items: center;
					font-size: 24upx;
				}
			}
			& .item:last-child {
				margin-bottom: 0;
			}
		}
		& .info {
			width: calc(100% - 40upx);
			padding: 0upx 20upx;
			height: 80upx;
			line-height: 80upx;
			background-color: #fff;
			margin: auto;
			display: flex;
			justify-content: space-between;
			& .left {
				width: 20%;
				font-size: 30upx;
				font-weight: bold;
			}
			& .right {
				width: 80%;
				display: flex;
				justify-content: space-between;
				align-items: center;
				color: #a3a3a3;
				& .name-title {
					width: 100%;
					text-align: right;
					overflow: hidden;
					text-overflow: ellipsis;
					display: -webkit-box;
					-webkit-line-clamp: 1;
					-webkit-box-orient: vertical;
				}
				& .name-icons {
					display: flex;
					justify-content: flex-end;
				}
			}
		}
		& .info:last-child {
			padding-top: 0;
			border-radius: 0 0 20upx 20upx;
		}
	}
	& .instructions {
		width: calc(95% - 40upx);
		padding: 20upx;
		border-radius: 20upx;
		margin: auto;
		background-color: #fff;
		margin-top: 20upx;
		& .title {
			font-size: 30upx;
			color: #333333;
			margin-bottom: 20upx;
			font-weight: bold;
		}
		& .content {
			line-height: 45upx;
			font-size: 24upx;
			color: #808080;
		}
	}
}
</style>
