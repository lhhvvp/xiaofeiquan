<template>
	<view class="complaints">
		<view class="title">退款</view>
		<textarea v-model="refund_desc" placeholder="请输入退款备注"></textarea>
		<view class="button-box"><text @click="submit()">提交</text></view>
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
				trade_no: null,
				refund_desc: null,
				single:false,
				user_id:null,
			};
		},

		onLoad(options) {
			this.single = options.single;
			this.trade_no = options.trade_no;
		},

		onShow() {},

		computed: {
			...mapState(['uerInfo']),
		},
		methods: {
			...mapMutations([]),
			submit() {
				let url = '/ticket/refund';
				if(this.single){
					url = '/ticket/single_refund';
				}
				const {
					trade_no,
					refund_desc
				} = this
				this.$api
					.httpRequest(
						`${url}`, {
							out_trade_no:trade_no,
							refund_desc,
							openid: this.uerInfo.openid,
							uuid: this.uerInfo.uuid,
						},
						'POST'
					)
					.then(res => {
						if (res.code == 0) {
							this.$api.msg(res.msg, 'none');
							setTimeout(() => {
								uni.navigateBack();
							}, 2000)
						} else {
							this.$api.msg(res.msg, 'none');
						}
					})
			}
		}
	};
</script>

<style lang="scss">
	page{
		background: linear-gradient(180deg, rgba(174, 0, 13, 1) 0%, rgba(247, 247, 247, 1) 30%, rgba(247, 247, 247, 1) 100%);
		padding-top: 30rpx;
	}
	.complaints {
		width: 94%;
		padding: 0 0 40upx;
		background-color: #fff;
		margin: 0 auto 0;
		border-radius: 20upx;

		& textarea {
			width: calc(90% - 40upx);
			margin: auto;
			height: 150upx;
			border-radius: 20upx;
			padding: 20upx;
			background-color: #f7f7f7;
		}

		& .file {
			width: calc(95% - 40upx);
			height: 150upx;
			margin: auto;
			padding: 20upx;
			border-radius: 0 0 20upx 20upx;
			background-color: #f7f7f7;
		}

		& .title {
			width: 90%;
			margin: auto;
			font-weight: bold;
			font-size: 40upx;
			padding: 30upx 0;
		}

		& .info {
			width: 95%;
			background-color: #f7f7f7;
			margin: 20upx auto 0;
			border-radius: 20upx;
			overflow: hidden;
			display: flex;
			justify-content: space-between;
			align-items: center;
			position: relative;

			& input {
				width: calc(100% - 120upx);
				height: 80upx;
				padding-left: 120upx;
			}

			& text {
				position: absolute;
				left: 20upx;
			}
		}

		& .button-box {
			width: 100%;
			padding-top: 50upx;
			padding-bottom: 30upx;

			& text {
				width: 80%;
				display: block;
				height: 70upx;
				text-align: center;
				margin: auto;
				line-height: 70upx;
				border-radius: 50upx;
				background-color: $div-bg-color;
				color: #fff;
			}
		}
	}
</style>