<template>
	<view class="my-person" @click.stop="close()">
		<view class="person" @click.stop="">
			<view class="person-tit">可以选择<text style="color: #000000;">{{num}}</text>位游客</view>
			<view class="person-item">
				<checkbox-group @change.stop="checkboxChange">
					<view class="items-person-item" v-for="(item,index) in data" :key="index">
						<view class="items-person-item-left">
							<checkbox :value="item.id" :checked="item.checked" :disabled="item.disabled" />
						</view>
						<view class="items-person-item-centent" style="align-items:baseline;">
							<view style="display: flex;"><view class="tag">{{item.cert_type_text}}</view>{{item.fullname}}</view>
							<view>手机号:{{item.mobile}}</view>
							<view>证件号:{{item.cert_id}}</view>
						</view>
						<view class="items-person-item-right" @click="edit(item)">
							编辑
						</view>
					</view>
				</checkbox-group>
			</view>
			<view class="submit">
				<view @click.stop="add()">添加游客</view>
				<view @click.stop="submit()">完成</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		name: "my-person",
		props: {
			number: {
				default: 0,
				type: Number,
			},
			items: {
				type: Array,
				default: function() {
					return [];
				}
			},
			SelectPerson: {
				type: Array,
				default: function() {
					return [];
				}
			}
		},

		data() {
			return {
				newItems: {
					id: [],
					data: []
				},
				num: this.number,
				data: this.items,
			};
		},
		mounted() {

			this.data.forEach(item => {
				if (this.SelectPerson.includes(String(item.id))) {
					this.$set(item, 'checked', true)
				} else {
					this.$set(item, 'checked', false);
					if (this.SelectPerson.length == this.number) {
						this.$set(item, "disabled", true);
					}
				};

			})
		},
		methods: {
			submit() {
				if (this.number != this.newItems.data.length) {
					this.$api.msg(`您还需要添加${(this.number - this.newItems.data.length)}位游客`, 'none');
					return false;
				}
				this.$emit('confirm', this.newItems);
			},
			close() {
				this.$emit('close', true);
			},
			edit(item) {
				this.$emit('edit', item);
			},
			checkboxChange: function(e) {
				this.num = this.number - e.detail.value.length;
				this.newItems.data = [];
				var items = this.data,
					values = e.detail.value;
				this.$set(this.newItems, 'id', values);
				for (var i = 0, lenI = items.length; i < lenI; ++i) {
					const item = items[i];
					if (values.includes(String(item.id))) {
						this.$set(item, 'checked', true);
						this.newItems.data.push(item);
					} else {
						this.$set(item, 'checked', false);
					}
				};
				if (e.detail.value.length == this.number) {
					this.data.forEach(item => {
						if (item.checked != true) {
							this.$set(item, "disabled", true);
						} else {
							this.num = this.number - e.detail.value.length;
						}
					})
				} else {
					this.data.forEach(item => {
						if (item.disabled == true) {
							this.$set(item, "disabled", false);
						}
					})
				};
				if (e.detail.value.length > this.number) {
					this.$api.msg(`还可以添加${this.number - e.detail.value.length}位乘客`, 'none');
					return false;
				}
			},
			add() {

				this.$emit('add', true);
			}
		}
	}
</script>

<style lang="scss" scoped>
	.my-person {
		width: 100%;
		height: 100%;
		background-color: #00000050;
		position: fixed;
		left: 0;
		top: 0;
		font-size: 28upx;
		z-index: 9999;

		.person {
			position: absolute;
			width: calc(100% - 40upx);
			padding: 20upx;
			height: 80%;
			bottom: 0;
			background-color: #ffffff;
			border-radius: 20upx;

			.submit {
				position: absolute;
				width: 100%;
				height: 100upx;
				background-color: #ffffff;
				bottom: 0;
				left: 0;
				display: flex;
				justify-content: space-between;

				& view:last-child {
					color: #ffffff;
					background-color: #932027;
				}

				& view {
					width: 40%;
					color: #932027;
					background-color: #f7f7f7;
					padding: 20upx;
					margin: auto;
					border-radius: 20upx;
				}
			}

			&-tit {
				font-weight: bold;
				padding: 20upx 0 30upx 0;
				border-bottom: 1upx solid #f7f7f7;
			}

			&-item {
				display: flex;
				flex-direction: column;
				width: 100%;
				padding: 20rpx 0;
				height: 1000upx;
				overflow: auto;

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