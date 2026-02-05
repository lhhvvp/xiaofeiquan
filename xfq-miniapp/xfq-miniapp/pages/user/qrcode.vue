<template>
	<view class="box">
		<view class="item">
			<image v-if="detail.order_status == 'used'" src="/static/001.png" style="width: 200upx;height: 200upx;">
			</image>
			<view class="qrcode1"
				style="display: flex;justify-content: center;align-items: center;">
				<uqrcode ref="qrcode2" canvas-id="qrcode2" :start="false" :value="UserQrCodeVal" size="150"
					v-if="detail.order_status == 'paid'">
				</uqrcode>
			</view>
			<view class="name">{{info.tourist_fullname}}</view>
			<view class="mobile">{{info.tourist_mobile}}</view>
		</view>
	</view>
</template>

<script>
	import {
		mapState,
		mapMutations
	} from 'vuex';
	export default {
		data() {
			return {
				UserQrCodeVal: null,
				info: null,
				detail: null,
			};
		},

		onLoad(options) {
			this.info = JSON.parse(options.info);
			if (options.detail) {
				this.detail = JSON.parse(options.detail);
			}
			this.UserQrCodeVal = options.qrcode;
				this.make();
		},

		onShow() {},

		computed: {
			...mapState(['uerInfo']),
		},
		methods: {
			...mapMutations([]),
			make(){
				if (this.detail.order_status == 'paid') {
					let timer = setTimeout(() => {
						this.$refs.qrcode2.make();
						clearTimeout(timer)
					}, 200)
				}
			}
		}
	};
</script>

<style lang="scss">
	.box {
		width: 100%;
		margin: auto;
		background: linear-gradient(180deg, rgba(174, 0, 13, 1) 0%, rgba(247, 247, 247, 1) 30%, rgba(247, 247, 247, 1) 100%);
		padding-top: 130rpx;

		.item {
			width: 92%;
			display: flex;
			align-items: center;
			justify-content: center;
			flex-direction: column;
			background-color: #ffffff;
			border-radius: 20upx;
			margin: auto;
			padding: 100rpx 0;
		}
	}

	.name {
		font-size: 40upx;
		padding-bottom: 15upx;
		font-weight: bold;
	}

	.mobile {
		font-size: 28upx;
	}
</style>