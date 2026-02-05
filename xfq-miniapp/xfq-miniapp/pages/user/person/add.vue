<template>
	<view>
		<view class="item">
			<view class="label">证件类型</view>
			<view class="input">
				<uni-data-select v-model="value" :localdata="range" @change="change"></uni-data-select>
			</view>
		</view>
		<view class="item">
			<view class="label">姓名</view>
			<view class="input">
				<input type="text" v-model="fullname" placeholder="请输入姓名">
			</view>
		</view>
		<view class="item">
			<view class="label">证件号</view>
			<view class="input">
				<input type="text" v-model="cert_id" placeholder="请输入证件号">
			</view>
		</view>
		<view class="item">
			<view class="label">手机号</view>
			<view class="input">
				<input type="text" v-model="mobile" placeholder="请输入手机号">
			</view>
		</view>
		<view class="submit">
			<view @click="add()">保存</view>
		</view>
	</view>
</template>

<script>
	import {
		mapState,
		mapMutations
	} from 'vuex';
	import {
		checkPhone,
		checkName
	} from "@/common/common.js"
	export default {
		data() {
			return {
				value: 1,
				range: [],
				id: null,
				cert_type: null,
				fullname: null,
				mobile: null,
				cert_id: null,
			};
		},

		onLoad(options) {
			let val = options.val;
			if (val) {
				const {
					cert_type,
					fullname,
					mobile,
					cert_id,
					id
				} = JSON.parse(val);
				this.value = String(cert_type);
				this.cert_type = cert_type;
				this.fullname = fullname;
				this.mobile = mobile;
				this.cert_id = cert_id;
				this.id = id;
			}

			if (this.id) {
				uni.setNavigationBarTitle({
					title: "编辑常用人"
				});
			};
			this.cate();
		},

		onShow() {},

		computed: {
			...mapState(['uerInfo']),
		},
		methods: {
			...mapMutations([]),
			change(e) {
				this.cert_type = e;
			},
			cate() {
				this.$api
					.httpRequest(
						`/user/getCertTypeList`, {
							userid: this.uerInfo.uid,
						},
						'POST'
					)
					.then(res => {
						for (let key in res.data) {
							this.range.push({
								value: key,
								text: res.data[key],
							})
						};
					});
			},
			add() {
				let {
					fullname,
					mobile,
					cert_type,
					cert_id,
					id
				} = this;
				if (!id) {
					id = '';
				};
				if (!cert_type) {
					this.$api.msg('请选择证件类型', 'none');
					return false;
				};
				if (!fullname) {
					this.$api.msg('请输入姓名', 'none');
					return false;
				}
				// if (!checkName(fullname)) {
				// 	this.$api.msg('请输入正确的姓名', 'none');
				// 	return false;
				// }
				if (!cert_id) {
					this.$api.msg('请输入身份证号', 'none');
					return false;
				}
				if (!mobile) {
					this.$api.msg('请输入手机号', 'none');
					return false;
				}
				if (!checkPhone(mobile)) {
					this.$api.msg('请输入正确的手机号', 'none');
					return false;
				}
				this.$api
					.httpRequest(
						`/user/postTourist`, {
							fullname,
							mobile,
							cert_type:cert_type,
							cert_id,
							id,
						},
						'POST'
					)
					.then(res => {
						if (res.code == 0) {
							this.$api.msg(res.msg, 'success')
							setTimeout(() => {
								uni.navigateBack();
								var pages = getCurrentPages();
								var currPage = pages[pages.length - 1]
								var prePage = pages[pages.length - 2];
								if (prePage.$vm.inits) {
									prePage.$vm.inits({
										fullname,
										mobile,
										cert_type:cert_type,
										cert_id,
										id
									})
								}
							}, 1000)
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
		background-color: #ffffff;
	}

	.item {
		width: calc(100% - 40upx);
		padding: 30upx 20upx;
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
		height: 100upx;
		background-color: #ffffff;
		bottom: 0;
		left: 0;
		display: flex;
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