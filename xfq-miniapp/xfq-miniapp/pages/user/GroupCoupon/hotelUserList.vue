<template>
	<view>
		<view class="instructions" v-if="hotelUserList.length != 0">
			<view class="content">
				<view class="item" v-for="(item, index) in hotelUserList.tour_hotel_user_record" :key="index">
					<view class="left">
						<view class="tit">
							<uni-icons type="person-filled" size="20" color="#d8d8d8"></uni-icons>
							<text class="icon_tit">{{ item.users.name == '' ? '暂无' : item.users.name }}</text>
						</view>
						<view class="mobile">
							<uni-icons type="phone-filled" size="20" color="#d8d8d8"></uni-icons>
							<text class="icon_tit">{{ item.users.mobile == '' ? '暂无' : item.users.mobile }}</text>
						</view>
					</view>
					<view class="right">
						<view class="clockin bg-a00000" v-if="item.is_clock == 0" @click="isClock(item.is_clock, item.id, index)">代打卡</view>
						<view :class="clockName(item.is_clock).class">{{ clockName(item.is_clock).name }}</view>
					</view>
				</view>
			</view>
		</view>
		<my-is-clock v-model="spot_name" @submit="submit" v-if="myIsClock" @down="down"></my-is-clock>
	</view>
</template>

<script>
import { mapState, mapMutations } from 'vuex';
import { getLocation } from "../../../common/common.js"
export default {
	data() {
		return {
			id: null,
			myIsClock: false,
			longitude: null,
			latitude: null,
			address: null,
			spot_name: null,
			index: 0 ,//代打卡的下标,
			is_clock:true,
		};
	},
	computed: {
		...mapState(['uerInfo', 'hotelUserList'])
	},
	onLoad(option) {
		let title = option.title;
		uni.setNavigationBarTitle({
			title: '打卡游客信息-' + title
		});
	},
	methods: {
		...mapMutations(['setRefresh']),
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
		tel(moblie) {
			uni.makePhoneCall({
				phoneNumber: moblie //仅为示例
			});
		},
		isClock(status, id, index) {
			this.index = index;
			if (status == 0) {
				// 没打卡获取经纬度
				getLocation().then(
					success => {
						this.latitude = success.latitude;
						this.longitude = success.longitude;
						this.$api
							.httpRequest(
								`/index/transform`,
								{
									longitude: this.longitude,
									latitude: this.latitude
								},
								'POST'
							)
							.then(
								res => {
									if(this.longitude != 1 && this.latitude !=1){
										this.address = res.data.result.address;
										this.spot_name = res.data.result.formatted_addresses.rough;
									}
									this.id = id;
									this.myIsClock = true;
								},
								error => {
									this.$api.msg('位置获取错误，请重试!');
								}
							);
					},
					fail => {
						// 失败
					}
				);
			} else {
				if (status == 1) {
					this.$api.msg('您已经打过卡了', 'none');
					return false;
				}
				this.id = id;
				this.myIsClock = true;
			}
		},
		submit(e) {
			let { images, spot_name, desc } = e;
			let { longitude, latitude, address } = this;
			
			if(!spot_name){
				this.$api.msg('请输入打卡地点', 'none');
				return false;
			}
			if(images.length == 0){
				this.$api.msg('请上传打卡图片', 'none');
				return false;
			}
			if (!images) {
				images = 'https://oss.ylbigdata.com/admins/6602b01b17f64ecf18798e4868f394bb.png';
			}
			uni.showLoading({
				mask: true,
				title: '打卡提交中...'
			});
			if(!this.is_clock){
				this.$api.msg('您点太快了休息一下...', 'none');
				let time = setTimeout(()=>{
					this.is_clock = true;
					clearTimeout(time);
				},500);
			};
			this.is_clock = false;
			this.$api
				.httpRequest(
					`/user/hotel_clock`,
					{
						id: this.id,
						images,
						address,
						longitude,
						latitude,
						descs: desc,
						agency_user_id: uni.getStorageSync('guide')
					},
					'POST'
				)
				.then(res => {
					this.$api.msg(res.msg, 'none');
					if (res.code == 0) {
						this.hotelUserList.tour_hotel_user_record[this.index].is_clock = 1;
						this.setRefresh(true);
						setTimeout(() => {
							this.myIsClock = false;
						}, 200);
					}
				});
		},
		down() {
			this.myIsClock = !this.myIsClock;
		}
	}
};
</script>

<style lang="scss">
.bg-a00000 {
	background-color: $div-bg-color;
	border: none !important;
	color: #fff !important;
}
.clockin {
	width: 50%;
	height: 45upx;
	border-radius: 10upx;
	margin-right: 10upx;
	display: flex;
	align-items: center;
	justify-content: center;
	border: 1px solid $div-bg-color;
	color: $div-bg-color;
}
.Notclockin {
	width: 50%;
	height: 45upx;
	border-radius: 10upx;
	display: flex;
	align-items: center;
	justify-content: center;
	border: 1px solid #d8d8d8;
	color: #d8d8d8;
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
				width: 70%;
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
				width: 30%;
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
