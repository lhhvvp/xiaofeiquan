<template>
	<view :class="[!isFull ? 'not-full' : 'full']" :style="'height:' + height + 'rpx;'">
		<swiper
			class="carousel"
			:interval="interval"
			:autoplay="autoplay"
			:circular="circular"
			:indicator-color="indicatorColor"
			:indicator-dots="indicatorDots"
			@change="swiperChange"
		>
			<swiper-item v-for="(item, index) in bannerList" :key="index" class="carousel-item" @click="navToPage(item, index)">
				<image :src="item.images" v-if="item != '' && item.images != ''" />
				<image src="../../static/empty/banner.png" v-if="item == '' || item.images == ''" />
			</swiper-item>
		</swiper>
	</view>
</template>

<script>
export default {
	name: 'my-banner',
	props: {
		bannerList: {
			type: Array
		},
		width: {
			type: Number,
			default: 100
		},
		height: {
			type: Number,
			default: 350
		},
		indicatorDots: {
			//是否显示面板指示点
			type: Boolean,
			default: true
		},
		circular: {
			//是否采用衔接滑动，即播放到末尾后重新回到开头
			type: Boolean,
			default: true
		},
		indicatorColor: {
			//面板指示点颜色
			type: String,
			default: '#fff'
		},
		isFull: {
			//是否全屏
			type: Boolean,
			default: false
		},
		autoplay: {
			//是否自动播放
			type: Boolean,
			default: true
		},
		interval: {
			//播放间隔
			type: Number,
			default: 3000
		}
	},
	data() {
		return {};
	},
	methods: {
		navToPage(item, index) {
			this.$emit('ClickInfo', { item, index });
		},
		swiperChange(e) {
			// console.log(e);
		}
	}
};
</script>

<style lang="scss">
.full {
	width: 100%;
	height: 350upx;
}
.not-full {
	width: 95%;
	margin: auto;
	overflow: hidden;
	height: 320upx;
	image {
		border-radius: 16upx;
	}
}
.carousel {
	width: 100%;
	height: 100%;
	position: relative;
	&::before {
		width: 100%;
		position: absolute;
		bottom: -1px;
		height: 10px;
		z-index: 10;
		background-size: 100%;
		content: '';
		background-image: url(@/static/o.png);
	}
}
.carousel-item {
	width: 100%;
	height: 100%;
	overflow: hidden;
	position: relative;
	
}
.carousel-item image {
	position: absolute;
	width: 100%;
	height: 100%;
}
</style>
