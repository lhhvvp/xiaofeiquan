<template>
	<view>
		<!-- <view class="flex">
			<button @click="show = 1">默认显示</button>
			<button @click="show = 2">主题色修改</button>
			<button @click="show = 'slot'">自定义插槽</button>
		</view> -->
		<lee-logistics v-if="show == 1" :list="dataList" :cardInfo="cardInfo"></lee-logistics>
		<lee-logistics v-if="show == 'slot'" :list="dataList">
			<!-- 卡片插槽 -->
			<template v-slot:card="{row}">
				<view class="">
					<view>自定义流程内容</view>
					<view class="">物流公司:{{row.type}}</view>
				</view>
			</template>
			<!-- 流程插槽 -->
			<template v-slot:process="{row}">
				<view class="">
					<view>自定义流程内容</view>
					<view>时间：{{row.time}}</view>
				</view>
			</template>
		</lee-logistics>
		<lee-logistics v-if="show == 2" :list="dataList" color="#00aaff"></lee-logistics>
	</view>
</template>

<script>
	import {
		mapState,
		mapMutations
	} from 'vuex';
	import leeLogistics from '../../components/StarLee-logistics/logistics.vue'
	export default {
		components: {
			leeLogistics
		},
		data() {
			return {
				trackingNumber: null,
				coupon_issue_user_id: null,
				show: 1,
				dataList: [],
				cardInfo: {
					type: '未知',
					no: null
				}
			}
		},
		onLoad(option) {
			console.log(option);
			if (option && option.trackingNumber) {
				this.trackingNumber = option.trackingNumber;
				this.cardInfo.no = option.trackingNumber;
				this.coupon_issue_user_id = option.coupon_issue_user_id;
				this.init();
			}
		},
		computed: {
			...mapState(['uerInfo', 'merchant', 'is_refresh'])
		},
		methods: {
			init() {
				console.log("查询快递信息：" + this.trackingNumber)
				this.$api.httpRequest(
						`/user/getLogisticsInformation`, {
							uid: this.uerInfo.uid,
							coupon_issue_user_id: this.coupon_issue_user_id,
							tracking_number: this.trackingNumber
						},
						'GET'
					)
					.then(res => {
						if (res.code == 0) {
							this.dataList = res.data.data;
							this.cardInfo.type = res.data.exp_name;
						} else {
							uni.showModal({
								title: '提示',
								content: res.msg,
								showCancel: false
							});
						}

					});

			}
		}
	}
</script>

<style>
	page {
		background: #eee;
	}

	.flex {
		display: flex;
		padding: 20rpx 0;
	}
</style>