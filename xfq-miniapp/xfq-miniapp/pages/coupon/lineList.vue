<template>
	<view>
		<view class="product-items">
			<view class="product-item" v-for="(item, index) in list" :key="index" @click="merchant(item)">
				<view class="product-top" :style="'background-image: url(' + item.images + ');'"></view>
				<view class="product-bottom">
					<view class="tit">{{ item.title }}</view>
					<view class="content">
						<view class="tags">
							<text>{{ item.tags[0] }}</text>
						</view>
						<view class="price-box">
							<view class="price">{{ item.price }}</view>
							<view class="eyes">
								<uni-icons type="eye" style="margin-right: 10upx;"></uni-icons>
								{{ item.access_count }}
							</view>
						</view>
					</view>
				</view>
			</view>
		</view>
		<!-- 加载loadding -->
		<uni-load-more :status="loadding" v-if="!empty.show"></uni-load-more>
		<!-- end -->
		<!-- 为空页 -->
		<my-empty :empty="empty" v-if="empty.show"></my-empty>
		<!-- end -->
	</view>
</template>

<script>
import { prePage } from '@/common/common.js';
export default {
	data() {
		return {
			list: [],
			index: 0,
			latitude: 0,
			longitude: 0,
			page: 1,
			id: null,
			empty:{
				show:false,
				id:2,
			},
			flag:null,
		};
	},
	onReachBottom() {
		this.init();
	},
	// #ifdef MP-WEIXIN
	onShareAppMessage(res) {
		//微信小程序分享给朋友
		return {
			title: '榆林市旅游消费平台',
			path: '/pages/merchant/merchant'
		};
	},
	onShareTimeline(res) {
		//微信小程序分享朋友圈
		return {
			title: '榆林市旅游消费平台',
			path: '/pages/merchant/merchant'
		};
	},
	// #endif
	onLoad(option) {
		this.id = option.id;
		this.flag = option.flag;
		this.init();
	},
	methods: {
		init() {
			const couponId = this.id;
			const flag = this.flag;
			if (this.loadding_lock) {
				//加载锁，防止无限请求接口
				return false;
			}
			this.$api
				.httpRequest(
					`/coupon/line_list`,
					{
						couponId,
						page: this.page,
						limit: 12,
						flag
					},
					'POST'
				)
				.then(res => {
					if (res.code == 0) {
						this.is_onLoad = true;
						let data = res.data;
						if (this.page == 1 && data.length == 0) {
							// 如果没有数据则显示为空
							this.$set(this.empty, 'show', true);
							this.loadding_lock = true;
							return false;
						}
						data.map((item, index) => {
							item.images = this.$api.urli + item.images;
							item.tags = item.tags.split(',');
							return item;
						});

						this.list = [...this.list, ...data];

						if (data.length != 12) {
							//判断是否有下一页
							this.loadding = 'no-more';
							this.loadding_lock = true;
							return false;
						}
						this.page++;
						this.loadding = 'loading';
					}
				});
		},

		merchant(item) {
			uni.navigateTo({
				url: '/pages/coupon/lineInfo?id=' + item.id
			});
		}
	}
};
</script>

<style lang="scss">
.product-items {
	width: 95%;
	margin: auto;
	padding: 20upx;
	display: flex;
	flex-wrap: wrap;
	justify-content: space-between;
	& .product-item {
		width: 48%;
		background-color: #fff;
		box-shadow: 7px 10px 20px #e8e8e8;
		border-radius: 20upx;
		margin-bottom: 20upx;
		overflow: hidden;
		& .product-top {
			width: 100%;
			height: 240upx;
			background-size: cover;
		}
		& .product-bottom {
			width: calc(100% - 40upx);
			padding: 20upx;
			& .tit {
				width: 100%;
				font-size: 30upx;
				font-weight: bold;
				overflow: hidden;
				margin-bottom: 10upx;
				text-overflow: ellipsis;
				display: -webkit-box;
				-webkit-line-clamp: 2;
				-webkit-box-orient: vertical;
			}
			& .tags {
				width: 100%;
				margin-bottom: 10upx;
				& text {
					background-color: $div-bg-color;
					border-radius: 10upx;
					padding: 5upx 10upx;
					display: inline-block;
					margin-right: 10upx;
					font-size: 20upx;
					color: #ffe6e6;
				}
			}
			& .price-box {
				display: flex;
				justify-content: space-between;
				align-items: center;
				& .price {
					position: relative;
					font-size: 36upx;
					font-weight: bold;
					color: $div-bg-color;
					&::before {
						content: '￥';
						font-size: 24upx;
					}
				}
				& .eyes {
					color: #bcbcbc;
				}
			}
		}
	}
}
</style>
