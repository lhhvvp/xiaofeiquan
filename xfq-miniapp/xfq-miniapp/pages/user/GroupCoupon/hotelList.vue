<template>
	<view class="group-coupon">
		<view class="item" v-for="(item, index) in info" :key="index">
			<view class="title-box">
				<view class="left">
					<view class="tag"></view>
					<view class="title">{{ item.hotel_name }}</view>
				</view>
				<view class="right"><uni-icons type="right" size="22" color="#bababa"></uni-icons></view>
			</view>
			<view class="desc-box" @click.stop="navto(item.hotel_name, item)">
				<view class="desc-item">打卡人数：{{ item.tourist_numbers }}</view>
				<view class="desc-item">创建时间：{{ item.create_time }}</view>
				<view class="desc-item">备注信息：{{ item.remark }}</view>
			</view>
		</view>
		<view style="height: 154upx;"></view>
		<view class="button-box"><button @click="createCard">创建打卡任务</button></view>
		<!-- 遮罩 -->
		<view class="mask-box" v-if="is_mask">
			<view class="down" @click="isMask()">
				<uni-icons type="closeempty"></uni-icons>
				关闭
			</view>
			<view class="mask">
				<view class="title">生成酒店打卡任务</view>

				<view class="item-mask input">
					<label>酒店名称:</label>
					<input v-model="name" placeholder="请输入打卡酒店名称" />
				</view>
				<!-- 团号，经度纬度(隐藏)，名称，人数（已经打卡人数，需要打卡人数）， -->
				<view class="item-mask file">
					<view class="tit">备注信息:</view>
					<view class="files"><textarea placeholder="请输入旅行社名称+导游姓名+游客人数" v-model="desc"></textarea></view>
				</view>
				<view class="button" @click="submit">提交</view>
			</view>
		</view>
		<!-- 遮罩end -->
	</view>
</template>

<script>
import { mapState, mapMutations } from 'vuex';
import { getLocation } from "../../../common/common.js"
export default {
	data() {
		return {
			info: [],
			is_mask: false,
			desc: null,
			name: null,
			opitons: {
				tid: null,
				longitude: null,
				latitude: null
			},
			tid: null
		};
	},
	computed: {
		...mapState(['uerInfo', 'hotelList'])
	},
	onLoad(option) {
		uni.setNavigationBarTitle({
			title: '打卡列表-' + this.hotelList.tour.name
		});
		this.tid = option.tid;
		this.init();
	},
	onShow() {
		if (this.is_refresh) {
			this.init();
			this.setRefresh(false);
		}
	},
	methods: {
		...mapMutations(['setHotelUserList', 'setRefresh']),
		init() {
			let { tid } = this;
			this.$api
				.httpRequest(
					`/user/hotel_tour`,
					{
						tid
					},
					'POST'
				)
				.then(res => {
					this.info = res.data;
				});
		},
		navto(title, item) {
			this.setHotelUserList(item);
			uni.navigateTo({
				url: 'hotelUserList?title=' + title
			});
		},
		updataList(id = null) {
			this.init();
			let items = null;
			this.info.forEach(item => {
				if ((item.id = id)) {
					items = item;
				}
			});
			this.setHotelUserList(items);
		},
		createCard() {
			let item = this.hotelList;
			let status = item.tour.status;
			if (status == 5 || item.tourist.length == 0) {
				this.$api.msg('当前状态下不可生成酒店打卡任务', 'none');
				return false;
			};
			getLocation().then(
				success => {
					this.$set(this.opitons, 'tid', item.tid);
					this.$set(this.opitons, 'latitude', success.latitude);
					this.$set(this.opitons, 'longitude', success.longitude);
					this.is_mask = true;
				},
				fail => {
					// 失败
				}
			);
			
		},
		
		submit() {
			const that = this;
			const { desc, name } = this;
			const { latitude, longitude, tid } = this.opitons;
			if (!name) {
				this.$api.msg('请输入酒店名称');
				return false;
			}
			if (!desc) {
				this.$api.msg('请输入备注信息');
				return false;
			}
			if (!latitude || !longitude || !tid) {
				this.$api.msg('参数异常');
				return false;
			}
			
			uni.showModal({
				title: '提示',
				content: '请确定提交信息，提交后不能修改！',
				success: function(res) {
					if (res.confirm) {
						submit_add();
					}
				}
			});
			const submit_add = () => {
				this.$api
					.httpRequest(
						`/user/add_sign_record`,
						{
							uid: this.uerInfo.uid,
							tid,
							latitude,
							longitude,
							hotel_name: name,
							remark: desc
						},
						'POST'
					)
					.then(res => {
						if (res.code == 0) {
							this.$api.msg(res.msg, 'none');
							this.isMask();
							this.init();
						} else {
							this.$api.msg(res.msg, 'none');
						}
					});
			};
		},
		isMask() {
			this.desc = null;
			this.name = null;
			this.is_mask = false;
		}
	}
};
</script>

<style lang="scss">
.button-box {
	position: fixed;
	width: calc(100% - 20%);
	bottom: 0;
	padding: 30upx 10%;
	& button {
		background-color: $div-bg-color;
		color: #fff;
		border-radius: 20upx;
		padding: 10upx 0;
		font-size: 24upx;
	}
}
.group-coupon {
	width: 100%;
	margin-top: 20upx;
	& .item {
		width: calc(95% - 40upx);
		padding: 20upx;
		border-radius: 20upx;
		margin: 0 auto 20upx;
		background-color: #fff;
		box-shadow: 2.8px 2.8px 2.2px rgba(0, 0, 0, 0.003), 6.7px 6.7px 5.3px rgba(0, 0, 0, 0.004), 12.5px 12.5px 10px rgba(0, 0, 0, 0.005),
			22.3px 22.3px 17.9px rgba(0, 0, 0, 0.006), 41.8px 41.8px 33.4px rgba(0, 0, 0, 0.007), 100px 100px 80px rgba(0, 0, 0, 0.01);
		& .title-box {
			width: 100%;
			display: flex;
			align-items: center;
			justify-content: space-between;
			& .tag {
			}
			& .title {
				font-weight: bold;
				font-size: 32upx;
				overflow: hidden;
				text-overflow: ellipsis;
				display: -webkit-box;
				word-break: break-all;
				-webkit-line-clamp: 1;
				-webkit-box-orient: vertical;
			}
		}
		& .desc-box {
			width: 100%;
			padding: 20upx 0 20upx;
			font-size: 22upx;
			& .desc-item {
				color: #666666;
				margin-bottom: 10upx;
				overflow: hidden;
				text-overflow: ellipsis;
				display: -webkit-box;
				-webkit-line-clamp: 2;
				-webkit-box-orient: vertical;
			}
			& .info {
				display: flex;
				color: $div-bg-color;
			}
			& .desc-item:last-child {
				margin-bottom: 0;
			}
		}
		& .bottom-box {
			width: 100%;
			border-top: 1px solid #f7f7f7;
			display: flex;
			padding-top: 20upx;
			& .botton {
				width: calc(33% - 42px);
				margin-right: 7%;
				text-align: center;
				padding: 10upx 20upx;
				border-radius: 10upx;
				border: 1px solid;
				font-weight: bold;
			}
			& .botton:last-child {
				margin-right: 0;
			}
		}
	}
}

.mask-box {
	width: 100%;
	height: 100%;
	position: fixed;
	top: 0;
	left: 0;
	background-color: rgba(0, 0, 0, 0.6);
	display: flex;
	align-items: center;
	justify-content: center;
	flex-direction: column;
	& .down {
		width: 90%;
		display: flex;
		align-items: center;
		justify-content: flex-end;
		color: #fff;
		padding-bottom: 20upx;
	}
	& .mask {
		width: calc(85% - 60upx);
		padding: 30upx;
		background-color: #fff;
		border-radius: 20upx;
		& .title {
			padding-bottom: 25upx;
			width: 100%;
			text-align: center;
			font-weight: bold;
			font-size: 36upx;
			color: $div-bg-color;
		}
		& .item-mask.input {
			width: 100%;
			display: flex;
			align-items: center;
			border-bottom: 1px solid #e2e2e2;
			font-size: 28upx;
			height: 80upx;
			& label {
				display: inline-block;
				width: 20%;
				color: #999999;
			}
			& input {
				width: 80%;
				color: #333333;
			}
		}
		& .item-mask.file {
			width: 100%;
			& .tit {
				color: #999999;
				height: 80upx;
				line-height: 80upx;
			}
		}
		& .item-mask.file :last-child {
			margin-bottom: 20upx;
		}
		& .button {
			width: 90%;
			margin: auto;
			color: #fff;
			background-color: $div-bg-color;
		}
	}
}
</style>
