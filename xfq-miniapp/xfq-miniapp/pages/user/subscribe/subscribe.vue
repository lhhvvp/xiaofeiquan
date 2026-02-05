<template>
	<view class="content">
		<view class="items">
			<view class="item-date">
				<view class="item-tit">选择日期</view>
				<my-date :dataDate="dataDate" @FormData="FormData" v-if="dataDate.length!=0"></my-date>
			</view>
			<view class="item" v-for="(item,index) in list" @click="add(item.id,index)" :class="[index == on ?'on':'']">
				<view class="date-start">{{item.time_start_text}}-{{item.time_end_text}}</view>
				<view class="date-end">余{{item.stock}}</view>
			</view>
			<view class="item"
				style="width: 100%;display: flex;justify-content: space-between;align-items: center;border: none;">
				<view class="label">数量</view>
				<view class="input">
					<uv-number-box v-model="number" :max="item.stock" @change="valChange"></uv-number-box>
				</view>
			</view>
		</view>

		<view class="items-person-box" style="padding-bottom: 0;" v-if="id && list.length != 0">
			<view class="title">请选择游客信息</view>
			<view class="item-per" style="border: none;padding: 0;">
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
				<view class="item-tit" style="text-align: center;font-weight: 500;padding: 30upx 0;">
					<text
						style="border: 1upx solid #f7f7f7;padding: 20upx 30upx; border-radius: 20upx;display: inline-block;"
						@click="personSelect">选择游客</text>
					<my-person v-if="is_person_open" :items="persons" :SelectPerson="SelectPerson.id" :number="number"
						v-on:confirm="personConfirm" v-on:add="personAdd" v-on:close="personClose"
						v-on:edit="personEdit($event,false)" ref="myPerson"></my-person>
				</view>
			</view>
		</view>


		<view class="items-" v-if="id && list.length != 0">
			<view class="item">
				<view class="label">姓名</view>
				<view class="input">
					<input type="text" v-model="fullname" placeholder="请输入姓名" />
				</view>
			</view>
			<view class="item">
				<view class="label">身份证号</view>
				<view class="input">
					<input type="text" v-model="idcard" placeholder="请输入身份证号" />
				</view>
			</view>
			<view class="item">
				<view class="label">手机号</view>
				<view class="input">
					<input type="text" v-model="phone" placeholder="请输入手机号" />
				</view>
			</view>

			<view class="button" @click="submit">提交</view>
		</view>
	</view>
</template>

<script>
	import {
		getLocation
	} from "@/common/common.js"
	import {
		mapState,
		mapMutations
	} from 'vuex';
	export default {
		data() {
			return {
				dataDate: [],
				list: [],
				data: {},
				SelectPerson: {},
				is_person_open: false,
				latitude: null,
				longitude: null,

				id: null,
				fullname: null,
				number: 1,
				phone: null,
				idcard: null,
				on: -1,
				maxNumber: null,
				persons: [],
			};
		},

		onLoad(options) {
			this.seller_id = options.seller_id;

		},

		onShow() {
			getLocation().then(res => {
				this.latitude = res.latitude;
				this.longitude = res.longitude;
				this.init();
				this.authInfo();
			});
			this.persons = [];
			this.Getpersons();
		},

		computed: {
			...mapState(['uerInfo']),
		},
		methods: {
			...mapMutations([]),
			personAdd() {
				this.is_person_open = false;
				uni.navigateTo({
					url: '/pages/user/person/add'
				})
			},
			add(id, index) {
				this.on = index;
				if (this.list.length != 0) {
					this.id = this.list[index].id
				}
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
			personSelect() {
				this.is_person_open = true;
			},
			personClose() {
				this.is_person_open = false;
			},
			personConfirm(e) {
				if (e) {
					this.is_person_open = false;
					this.SelectPerson = e;
				}
			},
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
			authInfo() {
				this.$api.httpRequest(
						`/user/auth_info`, {
							uid: this.uerInfo.uid,
						},
						'POST'
					)
					.then(res => {
						this.idcard = res.data.idcard
						this.phone = res.data.mobile
						this.fullname = res.data.name
					})

			},
			init() {
				this.$api
					.httpRequest(
						`/appt/getDatetime`, {
							seller_id: this.seller_id,
						},
						'GET'
					).then(res => {
						let data = res.data.list;
						this.maxNumber = res.data.number;
						this.data = data;
						let obj = {}
						for (let key in data) {
							if (this.$api.moment().format('YYYY-MM-DD') == key) {
								this.list = data[key];
							};
							this.dataDate.push({
								date: key
							})
						};
					})
			},
			valChange(e) {
				this.number = e.value;
			},
			FormData(e) {
				this.list = [];
				if (e) {
					for (let key in this.data) {
						if (key == e.date) {
							this.list = this.data[key];
						}
					};
					this.on = -1;
					this.id = null;
				}
			},
			submit() {
				const {
					latitude,
					longitude,
					id: datetime_id,
					fullname,
					number,
					phone,
					idcard,
					SelectPerson
				} = this;
				if (!datetime_id) {
					this.$api.msg('请选择时间段');
					return false
				}
				if (!fullname) {
					this.$api.msg('请输入姓名');
					return false
				}
				if (!idcard) {
					this.$api.msg('请输入身份证号');
					return false
				}
				if (!phone) {
					this.$api.msg('请输入手机号');
					return false
				}
				if (!number) {
					this.$api.msg('请输入数量');
					return false
				};
				if (!SelectPerson.data) {
					this.$api.msg('请选择游客');
					return false
				};
				let tourist = SelectPerson.data.map(item => {
					const {
						fullname,
						cert_type,
						cert_id,
						mobile
					} = item;
					return {
						fullname,
						cert_type,
						cert_id,
						mobile
					}
				});
				if (tourist) {
					tourist = JSON.stringify(tourist);
				}
				this.$api
					.httpRequest(
						`/appt/createAppt`, {
							datetime_id,
							fullname,
							idcard,
							phone,
							number,
							lat: latitude,
							lng: longitude,
							tourist,
						},
						'POST'
					).then(res => {
						if (res.code == 0) {
							this.$api.msg(res.msg, 'success');
							setTimeout(() => {
								uni.navigateTo({
									url: "/pages/user/subscribe/my_list"
								})
							}, 1500)
						} else {
							this.$api.msg(res.msg, 'none');
						}
					})
			}
		}
	};
</script>

<style lang="scss" scoped>
	.content {
		width: 100%;
		background: rgb(147, 32, 39);
		background: linear-gradient(180deg, rgba(147, 32, 39, 1) 0%, rgba(247, 247, 247, 1) 100%);
		padding: 20upx 0;
	}

	.items {
		width: calc(95% - 60upx);
		padding: 20upx 30upx 50upx;
		margin: auto;
		display: flex;
		background-color: #ffffff;
		align-items: center;
		flex-wrap: wrap;
		border-radius: 20upx;
		margin-bottom: 20upx;

		.item-date {
			width: 100%;
			padding: 30upx 0upx 50upx;

			.item-tit {
				font-size: 32upx;
				font-weight: bold;
				padding-bottom: 30upx;
			}
		}

		.item {
			width: 25%;
			padding: 20upx;
			border-radius: 10upx;
			border: 1upx solid #f7f7f7;
			margin-right: 20upx;
			margin-bottom: 20upx;
		}

		.item:nth-child(3n+4) {
			margin-right: 0upx;
		}

	}

	.content {
		width: 100%;
		background: rgb(147, 32, 39);
		background: linear-gradient(180deg, rgba(147, 32, 39, 1) 0%, rgba(247, 247, 247, 1) 100%);
		padding: 20upx 0;
	}

	.items- {
		width: calc(95% - 40upx);
		padding: 20upx;
		margin: auto;
		background-color: #ffffff;
		border-radius: 20upx;

		.item {
			display: flex;
			align-items: center;
			padding: 30upx 20upx;
			border-bottom: 1upx solid #f7f7f795;

			.label {
				width: 20%;
			}

			.input {
				width: 80%;
			}
		}
	}

	.on {
		border: 1upx solid $div-color !important;
	}

	.items-person-box {
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

		.item-per {
			width: 100%;
			// border-bottom: 1upx solid #f7f7f7;
			padding: 30upx 0 0;

			.items-person {
				display: flex;
				align-items: center;
				flex-direction: column;

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

				.items-person-item {
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