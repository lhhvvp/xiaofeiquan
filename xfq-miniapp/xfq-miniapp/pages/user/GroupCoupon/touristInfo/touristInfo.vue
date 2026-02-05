<template>
	<view>
		<view class="instructions" v-if="touristInfo.length != 0">
			<view class="content">
				<view class="item" v-for="(item, index) in touristInfo" :key="index">
					<view class="left">
						<view class="tit">
							<uni-icons type="person-filled" size="20" color="#d8d8d8"></uni-icons>
							<text class="icon_tit">{{ item.name == '' ? '暂无' : item.name }}</text>
						</view>
						<view class="mobile">
							<uni-icons type="phone-filled" size="20" color="#d8d8d8"></uni-icons>
							<text class="icon_tit">{{ item.mobile == '' ? '暂无' : item.mobile }}</text>
						</view>
					</view>
					<view class="right">
						<view class="clockin" @click="tel(item.mobile)">联系TA</view>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
import { mapState, mapMutations } from 'vuex';
export default {
	data() {
		return {};
	},
	onLoad(opiton) {
		uni.setNavigationBarTitle({
			title:'游客信息-'+opiton.title
		})
	},
	computed: {
		...mapState(['uerInfo', 'touristInfo'])
	},
	methods: {
		clockName(status) {
			let obj = {};
			switch (status) {
				case 0:
					obj.name = '未打卡';
					obj.class = 'Notclockin';
					break;
				case 1:
					obj.class = 'clockin';
					obj.name = '已打卡';
					break;
			}
			return obj;
		},
		tel(moblie){
			uni.makePhoneCall({
				phoneNumber: moblie //仅为示例
			});
		}
	}
};
</script>

<style lang="scss">
.clockin {
	width: 80%;
	height: 45upx;
	border-radius: 10upx;
	display: flex;
	align-items: center;
	justify-content: center;
	border: 1px solid $div-bg-color;
	color: $div-bg-color;
}
 .instructions {
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
			color: #333333;
			& .item {
				width: 100%;
				display: flex;
				margin-bottom: 20upx;
				border-bottom: 1px solid #f3f3f3;
				justify-content: space-between;
				& .left {
					width: 80%;
					height: 100upx;
					& .tit,
					& .mobile {
						display: flex;
						align-items: center;
					}
					& .icon_tit {
						margin-left: 10upx;
					}
				}
				& .right {
					width: 20%;
					justify-content: flex-end;
					display: flex;
					align-items: center;
				}
			}
			& .item:last-child {
				margin-bottom: 0;
			}
		}
	}
</style>
