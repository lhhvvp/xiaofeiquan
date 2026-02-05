<template>
	<view class="zmm-slider-verify" v-if="isShow" @touchmove.stop.prevent="movestopHandle()">
		<view class="zmm-slider-verify-mask" :style="{ 'background-color': maskColor }"></view>
		<view class="zmm-slider-verify-wrap" :style="{ 'background-color': wrapColor }">
			<view class="zmm-slider-verify-top">
				<text class="zmm-slider-verify-title">{{ title }}</text>
				<text class="zmm-slider-verify-close" @click="closeHandle">关闭</text>
			</view>
			<view class="zmm-slider-verify-tips">
				<text class="zmm-slider-verify-tips-text">{{ tips }}</text>
			</view>
			<view class="zmm-slider-verify-slide-box">
				<image class="zmm-slider-verify-img" v-if="image" :src="image" mode="scaleToFill"></image>
				<image class="zmm-slider-verify-img" v-else src="@/uni_modules/zmm-slider-verify/static/img/Verify.jpg" mode="scaleToFill"></image>
				<view class="zmm-slider-verify-slide-position" :style="{ top: autoTop + 'px' }">
					<view class="zmm-slider-verify-slide" :style="{ height: slideSize }">
						<!-- 用来验证的滑块 -->
						<view class="zmm-slider-verify-block-verify" :style="{ left: autoLeft + 'px', height: slideSize + 'px', width: slideSize + 'px', 'background-color': slideColor }"></view>
						<!-- 操控的滑块 -->
						<view class="zmm-slider-verify-block-copy" :style="{ left: moveLeft + 'px', height: slideSize + 'px', width: slideSize + 'px', 'background-color': slideColor }"></view>
						<!-- 触摸的滑块 -->
						<view class="zmm-slider-verify-block" :style="{ height: slideSize + 'px', width: slideSize + 'px' }" @touchstart="touchstartHandle" @touchmove="touchmoveHandle" @touchend="touchendHandle"></view>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	name: 'zmmSliderVerify',
	emits: ['success', 'error', 'close'],
	props: {
		//标题
		title: {
			type: String,
			default: '滑动校验'
		},
		//提醒
		tips: {
			type: String,
			default: '请将左侧透明滑块拖进白色框内'
		},
		//滑块大小
		slideSize: {
			type: Number,
			default: 50
		},
		//滑块颜色
		slideColor: {
			type: String,
			default: 'rgba(0,0,0,0.4)'
		},
		//遮罩层背景色
		maskColor: {
			type: String,
			default: 'rgba(0,0,0,0.4)'
		},
		verifyImgArray:{
			type:Array,
			default:function(){
				return [];
			},
		},
		// 图片
		verifyImg: {
			type: String,
			default: ''
		},
		//主体背景色
		wrapColor: {
			type: String,
			default: '#ffffff'
		},
		//校验正负差值区间像素
		between: {
			type: Number,
			default: 10
		}
	},
	data() {
		return {
			startPageX: 0, //开始距离
			moveLeft: 0, //滑动距离
			done: false, //是否成功
			autoLeft: 80, //验证滑块随机的像素
			autoTop: 80, //验证滑块随机的top像素
			isShow: false,
			image:null,
		};
	},
	methods: {
		// 拦截其他触摸事件防止nvue下input等元素层级问题
		movestopHandle() {
			return;
		},
		// 随机数
		rMathfloor(min, max) {
			//返回包括最大/小值
			return Math.floor(Math.random() * (max - min + 1)) + min;
		},
		// 初始化
		init() {
			this.moveLeft = 0;
			this.done = false;
			this.autoTop = this.rMathfloor(0, 170 - this.slideSize);
			this.autoLeft = this.rMathfloor(this.slideSize + 20, 300 - this.slideSize);
			const lengths = this.verifyImgArray.length;
			this.image =  this.verifyImgArray[Math.round(Math.random() * lengths)] || null;
		},
		// 显示
		show() {
			this.isShow = true;
			this.init()
		},
		// 关闭
		hide(){
			this.closeHandle()
		},
		//按下
		touchstartHandle(e) {
			if (this.done) {
				return;
			}
			this.startPageX = e.changedTouches[0].pageX;
		},
		// 滑动
		touchmoveHandle(e) {
			// 滑动分两个块来操作不然会有数据抖动
			if (this.done) {
				return;
			}
			var left = e.changedTouches[0].pageX - this.startPageX; //补偿起始位置
			this.moveLeft = left;
		},
		// 滑动离开（最终）
		touchendHandle(e) {
			var endLeft = e.changedTouches[0].pageX;
			var verifyLeft = this.autoLeft + this.startPageX; //补偿起始位置
			var chazhi = verifyLeft - endLeft; //最终差值
			// 判断是否在正负差值区间
			if (chazhi >= 0 - this.between && chazhi <= this.between) {
				this.done = true;
				// 通过会执行成功和关闭
				this.closeHandle()
				this.$emit('success', '验证通过');
				this.$emit('close', '关闭');
			} else {
				this.$emit('error', '验证失败');
				// 失败会执行失败并重新初始化
				this.init();
				uni.showToast({
					title: this.tips,
					icon: 'none'
				});
			}
		},
		// 关闭事件
		closeHandle() {
			this.isShow=false
			this.$emit('close', '关闭');
		}
	}
};
</script>

<style lang="scss" scoped>
.zmm-slider-verify {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 999;
	display: flex;
	flex-direction: row;
	align-items: center;
	justify-content: center;
}
.zmm-slider-verify-mask {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 888;
}
.zmm-slider-verify-wrap {
	position: relative;
	z-index: 999;
	padding: 34rpx;
	border-radius: 24rpx;
	background-color: #ffffff;
	overflow: hidden;
}
.zmm-slider-verify-top {
	display: flex;
	flex-direction: row;
	align-items: center;
}
.zmm-slider-verify-title {
	flex: 1;
	color: #333;
	font-size: 32rpx;
}
.zmm-slider-verify-close {
	font-size: 28rpx;
	color: #333;
}
.zmm-slider-verify-tips {
	margin-top: 12rpx;
	margin-bottom: 12rpx;
}
.zmm-slider-verify-tips-text{
	color: #999;
	font-size: 28rpx;
}
.zmm-slider-verify-slide-box {
	position: relative;
	width: 300px;
	height: 170px;
}
.zmm-slider-verify-img {
	width: 300px;
	height: 170px;
}
.zmm-slider-verify-slide-position {
	position: absolute;
	left: 0;
	top: 0;
	right: 0;
	bottom: 0;
}
.zmm-slider-verify-slide {
	width: 300px;
	height: 50px;
	position: relative;
	overflow: hidden;
}
.zmm-slider-verify-block,
.zmm-slider-verify-block-copy {
	width: 50px;
	height: 50px;
	border-radius: 8rpx;
	position: absolute;
	left: 0px;
	top: 0;
	z-index: 2;
}
.zmm-slider-verify-block-copy {
	z-index: 1;
}
.zmm-slider-verify-block-verify {
	position: absolute;
	left: 0px;
	top: 0;
	border-radius: 8rpx;
	/* #ifndef APP-NVUE */
	box-sizing: border-box;
	/* #endif */
	border: 1px #fff solid;
	box-shadow: 0px 0px 5px rgba(0, 0, 0, 0.4);
}
</style>
