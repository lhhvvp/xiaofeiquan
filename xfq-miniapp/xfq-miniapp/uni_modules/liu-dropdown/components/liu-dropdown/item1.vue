<template>
	<view class="item-main" :style="height?'height:'+height+'rpx;':''">
		<view class="item-list">
			<scroll-view class="item-left" scroll-y="true">
				<view class="item-left-list" v-for="(item,index) in newList" :key="index"
					:style="selectLeftIndex==index?'color:'+themeColor+';background: #FFFFFF;border-radius:0rpx;':''"
					@click.stop="selectLeft(index)">{{item.name}}
				</view>
			</scroll-view>
			<scroll-view class="item-right" scroll-y="true">
				<view v-if="newList[selectLeftIndex] && newList[selectLeftIndex].childs.length>0">
					<view class="item-right-list" v-for="(mess,inx) in newList[selectLeftIndex].childs" :key="inx"
						@click.stop="selectRight(mess)">
						<view class="item-right-list-name" :style="mess.chooseState?'color:'+themeColor:''">
							{{mess.name}}
						</view>
						<image v-if="mess.chooseState" class="item-right-list-img" src="@/static/select.png">
						</image>
					</view>
				</view>
				<view class="no-data" v-else>暂无数据</view>
			</scroll-view>
		</view>
		<view class="btn-list">
			<view class="btn-reset" @click.stop="reset">重置</view>
			<view class="btn-complete" :style="'background: '+themeColor" @click.stop="complete">完成</view>
		</view>
	</view>
</template>

<script>
	export default {
		props: {
			//数据源
			itemList: {
				type: Array,
				default: () => {
					return []
				}
			},
			//是否多选
			isMultiple: {
				type: Boolean,
				default: false
			},
			//主题色
			themeColor: {
				type: String,
				default: '#FD430E',
			},
		},
		data() {
			return {
				selectLeftIndex: 0,
				height: 0,
				newList: []
			}
		},
		watch: {
			itemList: {
				deep: true,
				immediate: true,
				handler(newArr) {
					if (newArr.length) {
						this.newList = newArr
					}
				},
			}
		},
		mounted() {
			this.$nextTick(() => {
				if (this.itemList.length >= this.itemList[0].childs.length) {
					this.height = this.itemList.length * 72 + 108
				} else {
					this.height = this.itemList[0].childs.length * 72 + 108
				}
			})
		},
		methods: {
			selectLeft(index) {
				this.selectLeftIndex = index
				if (this.newList.length >= this.newList[this.selectLeftIndex].childs.length) {
					this.height = this.newList.length * 72 + 108
				} else {
					this.height = this.newList[this.selectLeftIndex].childs.length * 72 + 108
				}
			},
			selectRight(item) {
				if (!this.isMultiple) this.initial()
				item.chooseState = !item.chooseState
				this.newList = JSON.parse(JSON.stringify(this.newList))
			},
			initial() {
				this.newList.forEach(res => {
					res.childs.forEach(item => {
						item.chooseState = false
					})
				})
			},
			//重置
			reset() {
				this.initial()
				this.newList = JSON.parse(JSON.stringify(this.newList))
			},
			//完成
			complete() {
				this.$emit('complete', {
					type: 1,
					list: this.newList
				})
			},
		}
	}
</script>

<style>
	/deep/ ::-webkit-scrollbar {
		width: 0;
		height: 0;
		color: transparent;
		display: none;
	}
</style>
<style scoped lang="scss">
	.item-main {
		width: 100%;
		position: relative;
		box-sizing: border-box;
		padding-bottom: 102rpx;
		max-height: 60vh;

		.item-list {
			width: 100%;
			height: 100%;
			display: flex;
			align-items: flex-start;
			justify-content: space-between;

			.item-left {
				width: 240rpx;
				height: 100%;
				background-color: #F4F4F4;
				display: flex;
				align-items: flex-start;
				justify-content: flex-start;
				flex-direction: column;

				.item-left-list {
					box-sizing: border-box;
					padding: 0 40rpx;
					width: 100%;
					height: 72rpx;
					background: #F4F4F4;
					border-radius: 0rpx 8rpx 8rpx 0rpx;
					display: flex;
					align-items: center;
					justify-content: flex-start;
					font-size: 28rpx;
					color: #383838;
				}
			}

			.item-right {
				width: calc(100% - 240rpx);
				height: 100%;
				display: flex;
				align-items: flex-start;
				justify-content: flex-start;
				flex-direction: column;
				background-color: #FFFFFF;

				.item-right-list {
					width: 100%;
					box-sizing: border-box;
					height: 72rpx;
					display: flex;
					align-items: center;
					justify-content: space-between;
					padding: 0 32rpx;

					.item-right-list-name {
						font-size: 28rpx;
						color: #333333;
					}

					.item-right-list-img {
						width: 34rpx;
						height: 34rpx;
					}
				}

				.no-data {
					width: 100%;
					height: 100%;
					font-size: 28rpx;
					color: #999999;
					display: flex;
					align-items: center;
					justify-content: center;
				}
			}
		}

		.btn-list {
			position: absolute;
			bottom: 0rpx;
			width: 100%;
			height: 102rpx;
			background: #FEFFFE;
			box-shadow: 0rpx 0rpx 4rpx 0rpx rgba(0, 0, 0, 0.1);
			display: flex;
			align-items: center;
			justify-content: space-around;

			.btn-reset {
				width: 320rpx;
				height: 72rpx;
				background: #FFFFFF;
				border-radius: 40rpx;
				border: 2rpx solid #E6E6E6;
				font-size: 30rpx;
				color: #3E3E3E;
				display: flex;
				align-items: center;
				justify-content: center;
			}

			.btn-complete {
				width: 320rpx;
				height: 76rpx;
				border-radius: 40rpx;
				font-size: 30rpx;
				color: #FFFFFF;
				display: flex;
				align-items: center;
				justify-content: center;
			}
		}
	}
</style>