<template>
	<view class="merchant">
		<view class="banner-box"><image :src="info.images" :style="'width:' + info.width + 'px;height:' + info.height + 'px;'"></image></view>
		<view class="info">
			<view class="title-box"></view>
			<view class="title">{{ info.title }}</view>

			<view class="address-box">
				<view class="left">
					<view class="address">
						<view class="tag" v-for="(item, index) in info.tags">{{ item }}</view>
					</view>
					<view class="address">
						<uni-icons type="info-filled" size="16" style="margin-right: 5px;" color="#939393" @click="addressClick"></uni-icons>
						所属分类：{{ info.lineCategory.name }}
					</view>
				</view>
			</view>
		</view>
		
		<view class="content">
			<view class="tit">行程安排</view>
			<my-readMore :hideLineNum="4" :showHeight="100" v-if="info.content">
				<rich-text :nodes="info.content" v-if="info.content" style="width: 100%;"></rich-text>
			</my-readMore>
		</view>

		<view class="content">
			<view class="tit">费用包含</view>
			{{ info.feeinclude }}
		</view>

		<view class="content">
			<view class="tit">注意事项</view>
			<my-readMore :hideLineNum="4" :showHeight="100" v-if="info.notice">
				<rich-text :nodes="info.notice" v-if="info.notice"></rich-text></my-readMore>
		</view>

		<view class="content" v-if="info.photo.length != 0">
			<view class="tit">相册</view>
			<view class="photo-box">
				<view class="photo" :style="'background-image: url(' + item.image + ');'" v-for="(item, index) in info.photos" @click="GetPhoto(info.photos, index)"></view>
			</view>
		</view>
	</view>
</template>

<script>
import { mapState, mapMutations } from 'vuex';
import { getImageInfo, replaceContent, formatDate, dateTime } from '@/common/common.js';
export default {
	data() {
		return {
			info: {},
			seller_id: 0
		};
	},
	onLoad(option) {
		this.systemInfo = uni.getSystemInfoSync();
		let that = this;
		this.seller_id = option.id;
		this.init();
	},
	computed: {
		...mapState(['hasLogin', 'uerInfo'])
	},
	methods: {
		init() {
			this.$api
				.httpRequest(`/coupon/line_detail`, {
					line_id: this.seller_id
				})
				.then(res => {
					if (res.code == 0) {
						let data = res.data;
						if (data.tags) {
							data.tags = data.tags.split(',');
						}
						if (data.photo) {
							let photo = JSON.parse(data.photo);
							data.photos = [];
							for(let key in photo){
								photo[key].image =  this.$api.urli + photo[key].image;
								data.photos.push(photo[key]);
							};
						}
						data.images = this.$api.urli + data.images;
						this.getImageInfo(data.images);
						this.info = data;
					}
				});
		},
		async getImageInfo(image) {
			let info = await uni.getImageInfo({ src: image });
			let { width, height } = info[1];
			let multiple = this.systemInfo.windowWidth / width;
			this.$set(this.info, 'width', multiple * width);
			this.$set(this.info, 'height', multiple * height);
		},
		GetPhoto(images, index) {
			const imgArray = images.map(item => {
				return item.image;
			});
			uni.previewImage({
				urls: imgArray,
				current: index
			});
		}
	}
};
</script>

<style lang="scss" scoped>
.banner-box {
	width: 100%;
	& image {
		width: 100%;
	}
}
.info {
	width: calc(100% - 60upx);
	padding: 20upx 30upx 0;
	margin-bottom: 20upx;
	background-color: #fff;
	& .title {
		font-size: 32upx;
		font-weight: bold;
	}
	& .time-move {
		width: 100%;
		display: flex;
		justify-content: space-between;
		& .time {
			width: 50%;
			display: flex;
			align-items: center;
			font-size: 22upx;
			& text:first-child {
				display: inline-block;
				background-color: $div-bg-color;
				padding: 8upx 16upx;
				border-radius: 10upx 0 0 10upx;
				color: #fff;
			}
			& text:last-child {
				background-color: #f7f7f7;
				padding: 8upx 16upx;
				border-radius: 0 10upx 10upx 0;
			}
		}
		& .move {
			color: #8b8b8b;
			display: flex;
			font-size: 22upx;
			align-items: center;
		}
	}
	& .my-nav {
		width: 100%;
		border-top: 1px solid #f7f7f7;
		border-bottom: 1px solid #f7f7f7;
	}
	& .address-box {
		width: 100%;
		padding: 20upx 0;
		border-bottom: 1px solid #f7f7f7;
		display: flex;
		align-items: center;
		& .left {
			width: 100%;
			& .address {
				width: 100%;
				padding-bottom: 20upx;
				display: flex;
				color: #939393;
				align-items: center;
				&:last-child {
					padding-bottom: 0upx;
				}
				& .tag {
					display: inline-block;
					background-color: $div-bg-color;
					color: #fff;
					font-size: 20upx;
					margin-right: 10upx;
					margin-bottom: 10upx;
					padding: 5upx 15upx;
					border-radius: 10upx;
				}
				& .tag :last-child {
					margin-right: 0;
				}
			}
		}
	}
}
.content {
	width: calc(100% - 40upx);
	background-color: #fff;
	padding: 20upx;
	margin-bottom: 20upx;
	& .tit {
		width: 100%;
		font-weight: bold;
		font-size: 30upx;
		padding-bottom: 20upx;
		border-bottom: 1px solid #f7f7f7;
	}
	& .photo-box {
		width: 100%;
		& .photo {
			width: 32.3%;
			height: 220upx;
			background-color: #8b8b8b;
			background-size: cover;
			display: inline-block;
			margin-bottom: 5upx;
			border-radius: 10upx;
			overflow: hidden;
			margin-right: 1.5%;
		}
		& .photo:nth-child(3n) {
			margin-right: 0;
		}
	}
}
.coupon {
	width: calc(100% - 60upx);
	padding: 20upx 30upx;
	background-color: #fff;
	margin-bottom: 20upx;
	& .title {
		font-size: 32upx;
		font-weight: bold;
	}
}
.coupon-not-padding {
	width: calc(100% - 60upx);
	padding: 0upx 30upx;
	background-color: #fff;
	margin-bottom: 20upx;
}
</style>
