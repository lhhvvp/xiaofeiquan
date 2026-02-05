<template>
	<view class="pay-box" v-if="PayData.type == 1">
		<view class="pay-order" v-for="(item, index) in PayData.data" :key="index">
			<view class="pay-header" @click="detail(item.order_id)">
				<view class="pay-header-orderid">
					<text>订单编号：</text>
					{{ item.order_id }}
				</view>
				<view class="pay-header-status" :style="'color:' + StatusName(item.payment_status,item.is_refund).Color">{{ StatusName(item.payment_status,item.is_refund).Text }}</view>
			</view>
			<view class="pay-core" @click="detail(item.order_id)">
				<view class="pay-core-left"><view class="bg-img" :style="'background-image: url(' + item.images + ');'"></view></view>
				<view class="pay-core-right">
					<view class="pay-core-right-title">{{ item.productName }}</view>
					<view class="pay-core-right-desc">消费券编号：{{ item.desc }}</view>
					<view class="pay-core-right-price">
						<view class="pay-price">
							<text>实付款：</text>
							￥{{ item.price }}
						</view>
						<view class="pay-number">x{{ item.number || 0 }}</view>
					</view>
				</view>
			</view>
			<view class="pay-buttons" v-if="item.button.length != 0">
				<view v-if="item.button.length > 3">
					<view class="pay-more" @click="onMore(index)">更多</view>
					<view
						class="pay-button"
						:style="[
							but.buttonColor && { 'background-color': but.buttonColor },
							but.color && { color: but.color },
							but.borderColor && { 'border-color': but.borderColor }
						]"
						v-for="(but, keys) in item.button.slice(0, 3)"
						:key="keys"
						@click="onButton(JSON.stringify(but), item)"
					>
						{{ but.name }}
					</view>
				</view>
				<view v-if="item.button.length < 3">
					<view
						class="pay-button"
						:style="[
							but.buttonColor && { 'background-color': but.buttonColor },
							but.color && { color: but.color },
							but.borderColor && { 'border-color': but.borderColor }
						]"
						v-for="(but, keys) in item.button"
						:key="keys"
						@click="onButton(JSON.stringify(but), item)"
					>
						{{ but.name }}
					</view>
				</view>
			</view>

			<view class="button-more" v-if="is_more[index] && item.button.length != 0">
				<view>
					<view class="button-more-button" v-for="(but, keys) in item.button.slice(3, 10)" :key="keys" @click="onButton(JSON.stringify(but), item)">{{ but.name }}</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	props: {
		PayData: {
			type: Object,
			default: function() {
				return {};
			}
		}
	},
	name: 'my-order',
	data() {
		return {
			is_more: []
		};
	},
	mounted() {
		let buttonLength = this.PayData.data.length;
		for (let i = 0; i < buttonLength; i++) {
			this.is_more[i] = false;
		};
	},
	methods: {
		StatusName(status,is_refund) {
			let obj = {};
			
			if(is_refund == 1){
				obj = {
					Color: '#a00000',
					Text: '退款中'
				};
				return obj;
			}
			if(is_refund == 2 && status == 2){
				obj = {
					Color: '#333333',
					Text: '已退款'
				};
				return obj;
			};
			switch (status) {
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
			};
			return obj;
		},
		onMore(index) {
			if (this.is_more[index]) {
				this.$set(this.is_more, index, false);
				return;
			}
			this.is_more.forEach((item, ins) => {
				this.$set(this.is_more, ins, false);
			});
			this.$set(this.is_more, index, true);
		},
		onButton(button, order) {
			this.$emit('onClick', { button, order });
		},
		detail(id) {
			this.$emit('onDetail', id);
		}
	}
};
</script>

<style lang="scss">
.pay-order {
	width: calc(100% - 40upx);
	background-color: #fff;
	padding: 20upx;
	margin-bottom: 20upx;
	border-radius: 20upx;
	position: relative;
}
.pay-header {
	width: 100%;
	display: flex;
	align-items: center;
	justify-content: space-between;
	& .pay-header-orderid text {
		color: #999999;
	}
	& .pay-header-status {
		border-radius: 10upx;
		font-size: 24upx;
	}
	border-bottom: 2upx solid #f7f7f7;
	padding-bottom: 20upx;
}
.pay-core {
	padding: 20upx 0;
	display: flex;
	justify-content: space-between;
	& .pay-core-left {
		width: 200upx;
		height: 200upx;
		& .bg-img {
			width: 200upx;
			height: 200upx;
			border-radius: 20upx;
			overflow: hidden;
			background-repeat: no-repeat;
			background-size: cover;
			background-position: center;
		}
	}
	& .pay-core-right {
		width: calc(95% - 200upx);
		height: 200upx;
		justify-content: space-evenly;
		& .pay-core-right-title {
			width: 100%;
			font-size: 30upx;
			font-weight: bold;
			overflow: hidden;
			text-overflow: ellipsis;
			display: -webkit-box;
			-webkit-line-clamp: 1;
			-webkit-box-orient: vertical;
		}
		& .pay-core-right-desc {
			margin: 20upx 0;
			color: #999999;
			overflow: hidden;
			text-overflow: ellipsis;
			display: -webkit-box;
			-webkit-line-clamp: 2;
			-webkit-box-orient: vertical;
		}
		& .pay-core-right-price {
			width: 95%;
			font-size: 34upx;
			font-weight: bold;
			display: flex;
			align-items: center;
			justify-content: space-between;
			position: relative;
			& .pay-price {
				color: #a00000;
				& text {
					color: #333333;
					font-size: 24upx;
					font-weight: 200;
				}
			}
			& .pay-number {
				font-weight: 200;
				font-size: 24upx;
			}
		}
	}
}
.pay-buttons {
	border-top: 1px solid #f7f7f7;
	width: 100%;
	padding: 20upx 0 0;
	& view {
		width: 100%;
		display: flex;
		flex-wrap: nowrap;
		justify-content: flex-end;
	}

	& .pay-button {
		max-width: calc(25% - 10upx);
		height: 50upx;
		line-height: 50upx;
		text-align: center;
		display: block;
		border-radius: 50upx;
		margin: 0 10upx;
		color: #737373;
		border: 1upx solid #999999a1;
		&:last-child {
			margin: 0;
			margin-left: 10upx;
		}
	}
	& .pay-more {
		width: 20%;
		color: #999999;
		height: 50upx;
		line-height: 52upx;
		justify-content: flex-start;
	}
}
.button-more {
	width: 160upx;
	position: absolute;
	left: 18upx;
	padding: 0upx 10upx 0;
	border-radius: 20upx;
	bottom: 90upx;
	background-color: #fff;
	box-shadow: 10upx 10upx 5upx rgba(0, 0, 0, 0.02), 10upx 10upx 7.5upx rgba(0, 0, 0, 0.07);
	& view {
		width: 100%;
		display: flex;
		flex-wrap: nowrap;
		justify-content: flex-end;
	}
	& .button-more-button {
		width: 100%;
		height: 60upx;
		line-height: 60upx;
		text-align: center;
		display: block;
		border-bottom: 1upx solid #f7f7f7;
		& :first-child {
			border: none;
		}
	}
}
</style>
