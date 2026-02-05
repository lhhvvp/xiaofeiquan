<template>
	<view class="signin">
		<view class="item-" v-for="(item, index) in list" :key="index">
			<view class="box">
				<view class="left">
					<view class="tit">
						<text>{{ item.tags == 1 ? '景区' : '酒店' }}</text>
						<text>{{ item.coupon_title }}</text>
					</view>
					<view class="address">创建时间：{{ item.create_time }}</view>
				</view>
				<view class="right">
					<view :class="[item.is_clock == 1 ? 'button-borders-not' : 'button-borders']" @click="isClock(item.is_clock, item.id, item.tags)">
						{{ item.is_clock == 1 ? '已打卡' : '打卡' }}
					</view>
				</view>
			</view>
			<view class="content">
				<view class="content-item">
					<text>旅行团名称:</text>
					<text>{{ item.tour_name }}</text>
				</view>
				<view class="content-item">
					<text>打卡地点:</text>
					<text>{{ item.address == 0 ? '暂未打卡' : item.address }}</text>
				</view>
				<view class="content-item">
					<text>打卡时间:</text>
					<text>{{ item.clock_time == 0 ? '暂未打卡' : item.clock_time }}</text>
				</view>
			</view>
			<view class="image-box" v-if="item.imagesArray.length != 0">
				<view
					class="img-item"
					:style="'background-image:url(' + urlis(its) + ');'"
					v-for="(its, key) in item.imagesArray"
					:key="key"
					@click="goImages(item.imagesArray, key)"
				></view>
			</view>
		</view>
		<my-is-clock v-model="spot_name" @submit="submit" v-if="myIsClock" @down="down"></my-is-clock>
		<my-empty :empty="empty"></my-empty>
	</view>
</template>

<script>
import { mapState } from 'vuex';
import { getLocation } from "../../../common/common.js"
export default {
	data() {
		return {
			list: [],
			empty: {
				show: false,
				id: 2
			},
			myIsClock: false,
			//打卡
			spot_name: null, //打卡景点
			address: null, //位置,
			longitude: null,
			latitude: null,
			descs: '',
			id: null,
			tags: null //1景区，2酒店
		};
	},
	onShow() {
		this.init();
	},
	computed: {
		...mapState(['uerInfo'])
	},
	methods: {
		init() {
			this.list = [];
			this.$api
				.httpRequest(
					`/user/clock_list`,
					{
						uid: this.uerInfo.uid
					},
					'POST'
				)
				.then(res => {
					let data = res.data.sort(this.SortUp);
					data.forEach(item => {
						if (item.clock_time != 0) {
							item.clock_time = this.$api.moment(item.clock_time * 1000).format('YYYY-MM-DD HH:mm:ss');
						}
						item.create_time = this.$api.moment(item.create_time * 1000).format('YYYY-MM-DD HH:mm:ss');
						item.imagesArray = [];
						if (item.images != null) {
							item.imagesArray = item.images.split(',');
						}
						this.list.push(item);
					});
					this.list.length == 0 && this.$set(this.empty, 'show', true);
				});
		},
		SortUp(x, y) {
			return x.is_clock - y.is_clock;
		},
		urlis(url) {
			return this.$api.urli + url;
		},
		statusValue() {
			let obj = {};
			obj.name = '已完成';
			obj.color = '#a00000';
			obj.bgColor = '#a00000';
			return obj;
		},
		goImages(image, index) {
			let images = [];
			image.map(item => {
				item = this.$api.urli + item;
				images.push(item);
			});
			uni.previewImage({
				urls: images,
				current: index
			});
		},
		isClock(status, id, tags) {
			if (status == 0) {
				uni.navigateTo({
					url:`/pages/user/signIn/info?type=user&id=${id}&tags=${tags}`
				});
			}
			return false;
			// tags == 1 是景区打卡,酒店打卡2
			if (status == 0 && this.latitude == null && this.longitude == null && this.spot_name == null && this.address == null) {
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
									if(res.code == 0){
										this.address = res.data.result.address;
										this.spot_name = res.data.result.formatted_addresses.rough;
										this.id = id;
										this.tags = tags;
										this.myIsClock = true;
									}else{
										this.$api.msg(res.msg, 'none');
									}
									
								}
							);
					},
					fail => {
						// 失败
					}
				);
				
				
			} else {
				if(status == 1 ){
					this.$api.msg('您已经打过卡了','none');
					return false;
				}
				this.id = id;
				this.tags = tags;
				this.myIsClock = true;
			}
		},
		down() {
			this.myIsClock = false;
		},
		submit(e) {
			let { images, spot_name, desc } = e;
			let { longitude, latitude, address, tags } = this;
			if (!images) {
				images = 'https://oss.wlxfq.dianfengcms.com/admins/6602b01b17f64ecf18798e4868f394bb.png';
			}
			uni.showLoading({
				mask: true,
				title: '打卡提交中...'
			});
			if (tags == 1) {
				// 景区
				this.$api
					.httpRequest(
						`/user/clock`,
						{
							clock_uid: this.uerInfo.uid,
							tour_issue_user_id: this.id,
							spot_name,
							images,
							address,
							longitude,
							latitude,
							dess: desc
						},
						'POST'
					)
					.then(res => {
						if (res.code == 0) {
							this.init();
							setTimeout(() => {
								uni.hideLoading();
								this.myIsClock = false;
							}, 2500);
						}
						this.$api.msg(res.msg, 'none');
					});
			} else {
				//酒店
				if(!desc){
					this.$api.msg('请填写打卡留言!');
					return false;
				}
				this.$api
					.httpRequest(
						`/user/hotel_clock`,
						{
							id: this.id,
							images,
							address,
							longitude,
							latitude,
							descs: desc
						},
						'POST'
					)
					.then(res => {
						if (res.code == 0) {
							this.init();
							setTimeout(() => {
								uni.hideLoading();
								this.myIsClock = false;
							}, 2500);
						}
						this.$api.msg(res.msg, 'none');
					});
			}
		}
	}
};
</script>

<style lang="scss" scoped>
.signin {
	width: 95%;
	margin: auto;
	padding-top: 20upx;
	& .item- {
		width: calc(100% - 40upx);
		padding: 20upx;
		box-shadow: 3px 2px 8px #00000010;
		background-color: #fff;
		border-radius: 20upx;
		margin-bottom: 20upx;

		& .box {
			display: flex;
			justify-content: space-between;
			& .left {
				width: 80%;
				display: flex;
				align-items: flex-start;
				justify-content: center;
				flex-direction: column;
				& .tit {
					display: flex;
					width: 100%;
					align-items: center;
					font-weight: bold;
					font-size: 30upx;
					& text:first-child {
						width: 80upx;
						color: #fff;
						border-radius: 10upx;
						margin-right: 10upx;
						display: flex;
						justify-content: center;
						align-items: center;
						padding: 2upx 6upx;
						font-size: 24upx;
						font-weight: 500;
						background-color: $div-bg-color;
					}
					& text:last-child {
						width: 100%;
						display: inline-block;
						overflow: hidden;
						text-overflow: ellipsis;
						display: -webkit-box;
						-webkit-line-clamp: 1;
						-webkit-box-orient: vertical;
					}
				}
				& .address {
					padding-top: 10upx;
					color: #757575;
					font-size: 22upx;
					overflow: hidden;
					text-overflow: ellipsis;
					display: -webkit-box;
					-webkit-line-clamp: 1;
					-webkit-box-orient: vertical;
				}
			}
			& .right {
				width: 20%;
				display: flex;
				align-items: center;
				justify-content: center;
				& .button-borders {
					width: calc(95% - 2px);
					height: 50upx;
					color: #fff;
					display: flex;
					border-radius: 10upx;
					align-items: center;
					justify-content: center;
					font-size: 22upx;
					background-color: $div-bg-color;
				}
				& .button-borders-not {
					width: calc(95% - 2px);
					height: 50upx;
					color: $div-bg-color;
					display: flex;
					border-radius: 10upx;
					align-items: center;
					justify-content: center;
					font-size: 22upx;
					border: 1px solid $div-bg-color;
				}
			}
		}
		& .content {
			width: calc(100% - 40upx);
			background-color: #f7f7f7;
			margin-top: 20upx;
			border-radius: 20upx;
			padding: 10upx 20upx;
			display: flex;
			flex-direction: column;
			justify-content: center;
			& .content-item {
				font-size: 22upx;
				padding: 5upx 0;
				display: flex;
				justify-content: space-between;
				color: #757575;
				& text:first-child {
					color: #b4b4b4;
				}
			}
		}
		& .image-box {
			width: 100%;
			display: flex;
			justify-content: space-between;
			flex-wrap: wrap;
			overflow: hidden;
			padding-top: 20upx;
			&::after {
				width: 32%;
				height: 0%;
				content: '';
			}
			& .img-item {
				width: 32%;
				height: 180upx;
				border-radius: 10upx;
				background-size: cover;
				background-repeat: no-repeat;
				background-position: center;
			}
		}
	}
	& .item:first-child {
		margin-top: 20upx;
	}
}
</style>
