<template>
	<view class="order">
		<view class="items">
			<view class="title">景区名称【成人票】门票</view>
			<view class="item">
				<view class="item-tit">选择日期</view>
				<view class="item-date">
					<my-date :dataDate="dataDate" @FormData="FormData" v-if="dataDate.length!=0"></my-date>
				</view>
			</view>
			<view class="item" style="display: flex;justify-content: space-between;">
				<view class="item-tit">选择数量</view>
				<view class="item-number">
					<uv-number-box v-model="From.number" :max="From.stock" @change="valChange"></uv-number-box>
				</view>
			</view>
		</view>
		<view class="items" style="padding-bottom: 0;">
			<view class="title">请选择游客信息</view>
			<view class="item" style="border: none;padding: 0;">
				<view class="items-person">
					<view class="items-person-item" v-for="(item,index) in SelectPerson.data " :key="index">
						<view class="items-person-item-left" @click="personDel(item)">
							<view>游客</view>
							<view><uni-icons type="closeempty"></uni-icons></view>
						</view>
						<view class="items-person-item-centent">
							<view>{{item.fullname}}</view>
							<view>手机号:{{item.mobile}}</view>
							<view>证件号:{{item.cert_id}}</view>
						</view>
						<view class="items-person-item-right" @click="personEdit(item)">
							编辑
						</view>
					</view>
				</view>
				<view class="item-tit" style="text-align: center;font-weight: 500;padding-top: 30upx;">
					<text
						style="border: 1upx solid #f7f7f7;padding: 20upx 30upx; border-radius: 20upx;display: inline-block;"
						@click="personSelect">选择游客</text>
					<my-person v-if="is_person_open" :items="persons" :SelectPerson="SelectPerson.id"
						:number="From.number" v-on:confirm="personConfirm" v-on:add="personAdd" v-on:close="personClose"
						v-on:edit="personEdit($event,false)" ref="myPerson"></my-person>
				</view>
			</view>
		</view>

		<view class="items" style="padding-bottom: 0;">
			<view class="title">联系人信息</view>
			<view class="item" style="border: none;padding: 0;">
				<view class="items-person">
					<view class="input">
						<view class="label">姓名 :</view>
						<input v-model="From.contact_man" placeholder="请输入联系人姓名" />
					</view>
					<view class="input">
						<view class="label">手机号 :</view>
						<input v-model="From.contact_phone" placeholder="请输入联系人手机号" />
					</view>
				</view>
			</view>
		</view>
		<view class="items" style="padding-bottom: 0;margin-bottom: 100upx;">
			<view class="title">订单备注（选填）</view>
			<view class="item" style="border: none;padding: 0;">
				<view class="items-person">
					<textarea v-model="From.order_remark" placeholder="请填写订单备注"
						style="width: 100%;height: 140upx;padding-top: 20upx;"></textarea>
				</view>
			</view>
		</view>

		<view class="goods-nav">
			<uni-goods-nav :buttonGroup="buttonGroup" :money="(From.number * Number(From.price))"
				@buttonClick="buttonClick"></uni-goods-nav>
		</view>
	</view>
</template>
<!-- https://v2.wlxfq.dianfengcms.com/api/user/auth_info -->
<script>
	import {
		mapState,
		mapMutations
	} from 'vuex';
	import {
		getLocation
	} from "@/common/common.js"
	export default {
		data() {
			return {
				buttonGroup: [{
					text: '提交订单',
					backgroundColor: 'linear-gradient(90deg, #FE6035, #EF1224)',
					color: '#fff',
				}],
				persons: [],
				SelectPerson: {},
				is_person_open: false,
				ticket_id: null,
				dataDate: [],
				From: {
					date: null,
					number: 1,
					contact_man: null,
					contact_phone: null,
					order_remark: '',
				},
			};
		},

		onLoad(options) {
			this.ticket_id = options.id
			this.init();
			this.contactFun();
		},

		onShow() {
			this.persons = [];
			this.Getpersons();
		},

		computed: {
			...mapState(['uerInfo']),
		},
		methods: {
			...mapMutations([]),
			Getpersons() {
				this.$api
					.httpRequest(
						`/user/getTouristList`, {
							page: 1,
							page_size: 999
						},
						'GET'

					)
					.then(res => {
						if (res.code == 0) {
							this.persons = res.data.data;
						}
					})
			},
			personEdit(val, bool = true) {
				// bool 等于flase的时候是组件的编辑,完成重新调用接口
				if (bool) {
					val.val_edit = true;
				}
				uni.navigateTo({
					url: "/pages/user/person/add?val=" + JSON.stringify(val)
				})
				if (!bool) {
					this.is_person_open = false;
				}
			},
			inits(val) {
				//修改完回调。
				if (this.SelectPerson.data) {
					this.SelectPerson.data.forEach(item => {
						if (item.id == val.id) {
							this.$set(item, 'cert_id', val.cert_id)
							this.$set(item, 'fullname', val.fullname)
							this.$set(item, 'mobile', val.mobile)
							this.$set(item, 'cert_type', val.cert_type)
						}
					})
				}

			},
			valChange(e) {
				this.From.number = e.value;
			},
			personClose() {
				this.is_person_open = false;
			},

			buttonClick(e) {
				//计算价格
				getLocation().then(res => {
					let {
						price,
						number,
						contact_man,
						contact_phone,
						date,
						order_remark
					} = this.From;
					let maxPrice = Number(price) * number;
					let open_id = this.uerInfo.openid;
					let uuid = this.uerInfo.uuid;
					let tourist = null;
					console.log(open_id);
					console.log(uuid);
					if (!open_id || !uuid) {
						this.$api.msg('参数错误!', 'none');
						return false;
					}

					if (!!this.SelectPerson.data && this.SelectPerson.data != 0) {
						tourist = this.SelectPerson.data.map(item => {
							return {
								tourist_fullname: item.fullname,
								tourist_cert_type: item.cert_type,
								tourist_cert_id: item.cert_id,
								tourist_mobile: item.mobile
							};
						});
					};
					if (!this.SelectPerson.data) {
						this.$api.msg('请添加游客', 'none', );
						return false;
					}

					if (!!this.SelectPerson.data && number != this.SelectPerson.data.length) {
						this.$api.msg('还需要添加' + (number - this.SelectPerson.data.length) + '人', 'none')
						return false;
					};

					let data = [{
						uuno: this.ticket_id,
						number,
						price: String(maxPrice),
						tourist,
					}];
					let contact = {
						contact_man,
						contact_phone
					};

					if (!contact_man) {
						this.$api.msg('请完善订单联系人', 'none')
						return false;
					};
					if (!contact_phone) {
						this.$api.msg('请完善订单手机号', 'none')
						return false;
					};

					let ticket_date = date;
					let create_lat = res.latitude;
					let create_lng = res.longitude;

					let submit = {
						openid: open_id,
						uuid,
						ticket_date,
						data: JSON.stringify(data),
						contact: JSON.stringify(contact),
						create_lat,
						create_lng,
						order_remark
					};
					this.$api
						.httpRequest(
							`/ticket/pay`,
							submit,
							'POST'
						)
						.then(res => {
							let that = this;
							if (res.code == 0) {
								let data = res.data;
								uni.requestPayment({
									provider: 'wxpay',
									timeStamp: data.pay.timeStamp,
									nonceStr: data.pay.nonceStr,
									package: data.pay.package,
									signType: data.pay.signType,
									paySign: data.pay.paySign,
									success: function(res) {
										//接口调用成功的回调
										uni.hideLoading();
										uni.redirectTo({
											url: '/pages/user/paySuccess'
										});
									},
									fail: function(err) {
										uni.hideLoading();
										that.$api.msg('支付失败！');
									}
								});
							} else {
								that.$api.msg(res.msg, 'none');
							}
						})

				})

			},
			personDel(e) {
				//删除游客,没有删除游客id，传参需要游客详细信息，这里只处理了游客信息
				var index = this.SelectPerson.data.findIndex(item => {
					if (item.id == e.id) {
						return true
					}
				})
				var IdIndex = this.SelectPerson.id.findIndex(item => {
					if (item == e.id) {
						return true
					}
				})
				this.SelectPerson.id.splice(IdIndex, 1);
				this.SelectPerson.data.splice(index, 1);
			},
			personConfirm(e) {
				if (e) {
					this.is_person_open = false;
					this.SelectPerson = e;
				}
			},
			personSelect() {
				this.is_person_open = true;
			},
			personAdd() {
				this.is_person_open = false;
				uni.navigateTo({
					url: '/pages/user/person/add'
				})
			},
			init() {
				// api/ticket/getTicketPirce?ticket_id=1&channel=online
				this.$api
					.httpRequest(`/ticket/getTicketPirce`, {
						ticket_id: this.ticket_id,
						channel: 'online',
						// uid: this.uerInfo.uid 
					})
					.then(res => {
						this.dataDate = res.data;
						this.From.price = res.data[0].price;
						this.From.date = res.data[0].date;
						this.From.stock = res.data[0].stock;
					})
			},
			FormData(e) {
				this.From.date = e.date;
				this.From.stock = e.stock;
				this.From.price = e.price;
			},
			contactFun() {
				// 联系人
				this.$api.httpRequest(
						`/user/auth_info`, {
							uid: this.uerInfo.uid,
						},
						'POST'
					)
					.then(res => {
						this.From.contact_phone = res.data.mobile
						this.From.contact_man = res.data.name
					})

			}
		}
	};
</script>

<style lang="scss">
	.order {
		width: 100%;
		background: rgb(147, 32, 39);
		background: linear-gradient(180deg, rgba(147, 32, 39, 1) 0%, rgba(247, 247, 247, 1) 100%);
		padding: 20upx 0;
	}

	.items {
		width: calc(95% - 60upx);
		background: #ffffff;
		margin: auto;
		padding: 30upx;
		border-radius: 20upx;
		margin-bottom: 20upx;

		.title {
			font-weight: 600;
			font-size: 36upx;
			padding-bottom: 20upx;
			border-bottom: 1upx solid #f7f7f7;
		}

		.item {
			width: 100%;
			// border-bottom: 1upx solid #f7f7f7;
			padding: 30upx 0 0;

			.items-person {
				display: flex;
				align-items: center;
				flex-direction: column;
				border-bottom: 1upx solid #f7f7f7;

				.input {
					padding-top: 40upx;

					.label {
						width: 15%;
						text-align: right;
					}

					width: 100%;
					display: flex;

					input {
						width: 75%;
						margin-left: 20upx;
					}
				}

				.input:last-child {
					padding-bottom: 40upx;
				}

				&-item {
					display: flex;
					width: 100%;
					padding: 20rpx 0;
					border-bottom: 1upx solid #f7f7f7;

					&-left {
						width: 100upx;
						display: flex;
						align-items: center;
						justify-content: center;
						flex-direction: column;
						color: #777777;
					}

					&-centent {
						width: calc(100% - 200upx);
						display: flex;
						flex-direction: column;

						& view {
							margin-bottom: 10upx;
						}

						& view:last-child {
							margin-bottom: 0;
						}
					}

					&-right {
						width: 100upx;
						display: flex;
						align-items: center;
						justify-content: center;
						color: #932027;
					}
				}
			}

			&-tit {
				font-size: 32upx;
				font-weight: bold;
				padding-bottom: 30upx;
			}

			&-date {
				width: 100%;
			}
		}
	}
</style>