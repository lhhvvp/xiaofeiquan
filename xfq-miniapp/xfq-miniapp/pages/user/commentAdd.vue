<template>
	<view style="width: 95%;margin: auto;background-color: #ffffff;margin-top: 26upx;border-radius: 20upx;">
		<view class="item">
			<view class="label" style="font-size: 30upx">评分:</view>
			<view>
				<uni-rate :value="0" @change="change" size="36" allowHalf="true"></uni-rate>
			</view>
		</view>

		<view class="item" style="display: flex;flex-direction: column;">
			<view class="label" style="width: 100%;font-size: 30upx;margin-bottom: 20upx;">评论内容:</view>
			<view class="input" style="width: 100%;">
				<textarea v-model="content" placeholder="请输入评论内容"
					style="height: 200upx;padding: 20upx;width: 100%;background-color: #f7f7f7;border-radius: 20upx;margin: auto;width: 95%;"></textarea>
			</view>
		</view>
		<view class="submit">
			<view @click="add()">我要评价</view>
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
				content: null,
				rate: null,
				order_id: null,
			};
		},
		onLoad(options) {
			this.order_id = options.id
		},
		onShow() {},
		
		computed: {
			...mapState(['uerInfo']),
		},
		methods: {
			change(e) {
				this.rate = e.value
			},
			add() {
				let {
					order_id,
					content,
					rate
				} = this;
				if (!order_id) {
					this.$api.msg('参数错误!', 'none')
					return false
				}
				if (!rate) {
					this.$api.msg('请打分!', 'none')
					return false
				}

				this.$api
					.httpRequest(
						`/ticket/writeComment`, {
							order_id,
							content,
							rate,
						},
						'POST'
					)
					.then(res => {
						if (res.code == 0) {
							this.$api.msg(res.msg, 'success');
							setTimeout(() => {
								uni.navigateBack();
							}, 1500)
						} else {
							this.$api.msg(res.msg, 'none')
						}
					});


			}
		}
	};
</script>

<style lang="scss">
	page {
		background: linear-gradient(180deg, rgba(174, 0, 13, 1) 0%, rgba(247, 247, 247, 1) 30%, rgba(247, 247, 247, 1) 100%);
	}

	.item {
		width: calc(95% - 40upx);
		padding: 30upx 20upx;
		margin: auto;
		display: flex;
		align-items: center;
		justify-content: space-between;
		border-bottom: 1upx solid #f7f7f7;
		.label {
			width: 30%;
		}

		.input {
			width: 70%;
		}
	}

	.submit {
		position: fixed;
		width: 100%;
		height: 120upx;
		background-color: #ffffff;
		bottom: 0;
		left: 0;
		display: flex;
		align-items: center;
		justify-content: space-between;

		& view {
			width: 80%;
			color: #ffffff;
			background-color: #932027;
			padding: 20upx;
			margin: auto;
			text-align: center;
			border-radius: 20upx;
		}
	}
</style>