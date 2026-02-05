<template>
	<view class="person">
		<view class="person-item">
			<checkbox-group @change="checkboxChange">
				<view class="items-person-item" v-for="(item,index) in person" :key="index">
					<view class="items-person-item-left">
						<checkbox :value="item.id" :checked="item.checked" :disabled="item.disabled" />
					</view>
					<view class="items-person-item-centent" style="align-items:baseline;">
						<view><text class="tag">{{item.cert_type_text}}</text> {{item.fullname}}</view>
						<view>手机号:{{item.mobile}}</view>
						<view>证件号:{{item.cert_id}}</view>
					</view>
					<view class="items-person-item-right" @click="edit(item)">
						编辑
					</view>
				</view>
			</checkbox-group>
		</view>
		<uni-load-more :status="loadding" v-if="!empty.show"></uni-load-more>
		<my-empty :empty="empty" v-if="empty.show && person.length == 0"></my-empty>
		<view class="submit">
			<view @click="del()">删除</view>
			<view @click="add()">添加</view>
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
				person: [],
				page: 1,
				loadding: 'more',
				loadding_lock: false,
				empty: {
					show: false,
					id: 3
				},
				dels: [],
			};
		},

		onLoad(options) {
			this.init();
		},
		onReachBottom() {
			this.init();
		},

		computed: {
			...mapState(['uerInfo']),
		},
		methods: {
			...mapMutations([]),
			add() {
				uni.navigateTo({
					url: '/pages/user/person/add'
				})
			},
			del() {
				let that = this;
				if (this.dels.length == 0) {
					this.$api.msg('请选择一个或多个常用人', 'none')
					return false;
				};
				uni.showModal({
					title: "提示",
					content: '您确定要删除吗?',
					success: function(res) {
						if (res.confirm) {
							let ids = that.dels.join(",");
							that.$api
								.httpRequest(
									`/user/delTourist`, {
										ids,
									},
									'POST'
								)
								.then(res => {
									if (res.code == 0) {
										that.$api.msg(res.msg, 'success');
										setTimeout(() => {
											that.inits();
										}, 2500)
									} else {
										that.$api.msg(res.msg, 'none');
									}
								})
						}
					}
				})

			},
			checkboxChange(e) {
				this.dels = e.detail.value;
			},
			inits() {
				this.page = 1;
				this.person = [];
				this.loadding = 'more';
				this.loadding_lock = false;
				this.init();
			},
			init() {
				if (this.loadding_lock) {
					//加载锁，防止无限请求接口
					return false;
				}
				this.$api
					.httpRequest(
						`/user/getTouristList`, {

							page: this.page,
							page_size: 12
						},
						'GET'

					)
					.then(res => {
						if (res.code == 0) {
							let data = res.data.data;
							if (this.page == 1 && data.length == 0) {
								// 如果没有数据则显示为空
								this.$set(this.empty, 'show', true);
								this.loadding_lock = true;
								return false;
							}
							this.person = [...this.person, ...data];

							if (data.length != 12) {
								//判断是否有下一页
								this.loadding = 'no-more';
								this.loadding_lock = true;
								return false;
							}

							if (data.length == 0) {
								//判断是否有下一页
								this.loadding = 'no-more';
								this.loadding_lock = true;
								return false;
							}
							this.page++;
							this.loadding = 'loading';
						}
					});
			},
			edit(val) {
				uni.navigateTo({
					url: '/pages/user/person/add?val=' + JSON.stringify(val),
				})
			}
		}
	};
</script>

<style lang="scss">
	.person {
		width: calc(100% - 40upx);
		padding: 20upx;
		background-color: #ffffff;
		border-radius: 20upx;

		.submit {
			position: fixed;
			width: 100%;
			height: 100upx;
			background-color: #ffffff;
			bottom: 0;
			left: 0;
			display: flex;
			justify-content: space-between;

			& view:first-child {
				color: #932027;
				background-color: #e2e2e2;
			}

			& view {
				width: 40%;
				color: #ffffff;
				background-color: #932027;
				padding: 20upx;
				margin: auto;
				text-align: center;
				border-radius: 20upx;
			}
		}

		&-item {
			display: flex;
			flex-direction: column;
			width: 100%;
			padding: 20rpx 0;

			.items-person-item:first-child {
				padding: 0 0 30rpx 0;
			}

			.items-person-item {
				display: flex;
				width: 100%;
				padding: 30rpx 0;
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
	}

	.tag {
		padding: 4upx 10upx;
		background-color: $div-bg-color;
		color: #ffffff;
		border-radius: 6upx;
		margin-right: 10upx;
		font-size: 20upx;
	}
</style>