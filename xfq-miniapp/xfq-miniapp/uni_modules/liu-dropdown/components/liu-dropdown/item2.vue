<template>
	<view class="item-main" :style="height?'height:'+height+'rpx;':''">
		<view class="item-list">
			<scroll-view class="item-left" scroll-y="true">
				<view class="item-left-list" v-for="(item,index) in newList" :key="index"
					:style="selectLeftIndex==index?'color:'+themeColor+';background: #FFFFFF;border-radius:0rpx;':''"
					@click.stop="chooseLeft(index)">{{item.name}}
				</view>
			</scroll-view>
			<scroll-view class="item-right" scroll-y="true" :scroll-with-animation="true"
				:scroll-into-view="scrollIntoView">
				<view class="item-right-list" v-for="(item,index) of scrollLeftObj" :key="index"
					:id="index!='#'?index:'BOTTOM'">
					<view class="left-item-title" v-if="item && item.length">{{index}}</view>
					<view class="item-right-list-name" v-for="(mess,inx) in item" @click.stop="selectRight(mess)">
						<view class="item-right-list-name-info" :style="mess.chooseState?'color:'+themeColor:''">
							{{mess.name}}
						</view>
						<image v-if="mess.chooseState" class="item-right-list-img" src="@/static/select.png">
						</image>
					</view>
				</view>
			</scroll-view>
			<view class="liu-scroll-right">
				<view :style="item==scrollIntoView?'background: '+themeColor:''"
					:class="{'liu-scroll-right-name':true,'liu-scroll-right-select':item==scrollIntoView}"
					v-for="(item,index) in scrollRightList" :key="index" @click.stop="chooseType(item)">{{item}}
				</view>
			</view>
		</view>
		<view class="btn-list">
			<view class="btn-reset" @click.stop="reset">重置</view>
			<view class="btn-complete" :style="'background: '+themeColor" @click.stop="complete">完成</view>
		</view>
	</view>
</template>

<script>
	import {
		pinyinUtil
	} from './pinyinUtil.js';
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
				height: 0,
				selectLeftIndex: 0,
				scrollRightList: [],
				scrollLeftObj: {},
				scrollIntoView: 'A',
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
				this.cleanData()
			})
		},
		methods: {
			chooseLeft(index) {
				this.selectLeftIndex = index
				this.cleanData()
			},
			cleanData() {
				let scrollList = this.getLetter()
				let list = this.newList[this.selectLeftIndex]
				let newList = []
				list.childs.forEach(res => {
					if (!res.chooseState) res.chooseState = false
					let initial = pinyinUtil.getFirstLetter(res.name.trim())
					let firsfirs = initial ? initial.substring(0, 1) : ''
					if (!newList[firsfirs]) newList[firsfirs] = []
					newList[firsfirs].push({
						id: res.id,
						name: res.name,
						chooseState: res.chooseState
					})
				})
				scrollList.forEach(t => {
					if (newList[t]) {
						this.$set(this.scrollLeftObj, t, newList[t])
					} else {
						this.$set(this.scrollLeftObj, t, [])
					}
				})
				let surplusList = []
				for (var i in newList) {
					let han = scrollList.find(v => {
						return v == i
					})
					if (!han) surplusList.push(newList[i])
				}
				surplusList.forEach(item => {
					this.scrollLeftObj['#'] = this.scrollLeftObj['#'].concat(item)
				})
				let messList = []
				for (let i in this.scrollLeftObj) {
					if (this.scrollLeftObj[i].length > 0) {
						messList.push(i)
					}
				}
				this.scrollRightList = messList
				if (this.newList.length >= list.childs.length + this.scrollRightList.length) {
					this.height = this.newList.length * 72 + 102
				} else {
					this.height = (list.childs.length + this.scrollRightList.length) * 72 + 102
				}
			},
			getLetter() {
				let list = []
				for (var i = 0; i < 26; i++) {
					list.push(String.fromCharCode(65 + i))
				}
				list.push('#')
				return list
			},
			chooseType(item) {
				if (item == '#') item = 'BOTTOM'
				this.scrollIntoView = item
			},
			selectRight(item) {
				if (this.isMultiple) {
					item.chooseState = !item.chooseState
				} else {
					this.initial()
					item.chooseState = !item.chooseState
				}
				this.newList[this.selectLeftIndex].childs.forEach(res => {
					if (item.id == res.id) {
						res.chooseState = !res.chooseState
					}
				})
				this.newList = JSON.parse(JSON.stringify(this.newList))
				this.cleanData()
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
				this.cleanData()
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
			position: relative;

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
					padding: 0 48rpx 0 32rpx;

					.left-item-title {
						font-size: 24rpx;
						color: #999999;
						font-weight: bold;
						height: 72rpx;
						display: flex;
						align-items: center;
						justify-content: flex-start;
					}

					.item-right-list-name {
						width: 100%;
						height: 72rpx;
						display: flex;
						align-items: center;
						justify-content: space-between;

						.item-right-list-name-info {
							font-size: 28rpx;
							color: #333333;
						}

						.item-right-list-img {
							width: 34rpx;
							height: 34rpx;
						}
					}
				}
			}

			.liu-scroll-right {
				position: absolute;
				right: 0rpx;
				top: 50%;
				transform: translateY(-50%);
				z-index: 999 !important;
				display: flex;
				align-items: center;
				justify-content: center;
				flex-direction: column;

				.liu-scroll-right-name {
					width: 32rpx;
					padding-right: 14rpx;
					height: 28rpx;
					font-size: 20rpx;
					color: #333333;
					line-height: 22rpx;
					margin-top: 8rpx;
					display: flex;
					align-items: center;
					justify-content: center;
				}

				.liu-scroll-right-select {
					padding: 0;
					margin-right: 14rpx;
					width: 28rpx;
					height: 28rpx;
					border-radius: 50%;
					color: #FFFFFF;
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