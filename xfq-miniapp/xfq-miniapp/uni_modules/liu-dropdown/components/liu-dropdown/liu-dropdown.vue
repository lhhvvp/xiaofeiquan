<template>
	<view>
		<view class="popup-box" :style="popupBox">
			<view class="type-list">
				<view class="type-item" :style="'width:'+(100/newMenuList.length)+'%'"
					v-for="(item,index) in newMenuList" :key="index" @click.stop="selectType(index,item)">
					<view class="type-item-name"
						:style="(selectIndex == index || item.showName)?'color:'+themeColor:''">
						{{item.showName || item.name}}
					</view>
					<!-- <view class="type-item-icon"
						:style="(selectIndex == index || item.showName)?'background-color:'+themeColor:''">
						<image class="type-item-icon-img" src="@/static/xiala.png"></image>
					</view> -->
				</view>
			</view>
			<view :catchtouchmove="isShow" class="scroll-popup" :style="scrollPopup" v-if="isShow">
				<view v-if="itemList.length>0">
					<item1 v-if="showType==1" :itemList="itemList" :isMultiple="isMultiple" :themeColor="themeColor"
						@complete="complete"></item1>
					<item2 v-if="showType==2" :itemList="itemList" :isMultiple="isMultiple" :themeColor="themeColor"
						@complete="complete"></item2>
					<item3 v-if="showType==3" :itemList="itemList" :isMultiple="isMultiple" :themeColor="themeColor"
						@complete="complete"></item3>
					<item4 v-if="showType==4" :itemList="itemList" :isMultiple="isMultiple" :themeColor="themeColor"
						@complete="complete"></item4>
				</view>
				<view class="no-data" v-else>
					<!-- <image class="no-data-img" src="@/static/noData.png"></image> -->
					<view class="no-data-name">暂无数据</view>
				</view>
			</view>
		</view>
		<view v-show="isShow" :catchtouchmove="isShow" @click.stop="isMask ? close() : ''" class="scroll-mask"></view>
	</view>
</template>

<script>
	import item1 from './item1.vue'
	import item2 from './item2.vue'
	import item3 from './item3.vue'
	import item4 from './item4.vue'
	export default {
		components: {
			item1,
			item2,
			item3,
			item4
		},
		props: {
			//菜单数据源
			menuList: {
				type: Array,
				default: () => {
					return []
				}
			},
			//下拉框数据源
			dataObj: {
				type: Object,
				default: {}
			},
			//菜单到顶部距离(rpx)
			top: {
				type: Number,
				default: 0,
			},
			//主题色
			themeColor: {
				type: String,
				default: '#932027',
			},
			//圆角(rpx、px、%)
			radius: {
				type: String,
				default: '12rpx',
			},
			//是否点击阴影关闭
			isMask: {
				type: Boolean,
				default: true,
			},
		},
		watch: {
			dataObj: {
				deep: true,
				immediate: true,
				handler(obj) {
					if (obj.itemList1) {
						this.canChoose = true
					}
				},
			},
			menuList: {
				deep: true,
				immediate: true,
				handler(newArr) {
					if (newArr.length) {
						this.newMenuList = newArr
					}
				},
			}
		},
		data() {
			return {
				isShow: false,
				selectIndex: -1,
				screenWidth: 0,
				isMultiple: false,
				showType: 0,
				itemList: [],
				newMenuList: [],
				canChoose: false
			};
		},
		computed: {
			popupBox() {
				return `top : ${this.top+'rpx'};
				border-radius: 0 0  ${this.radius} ${this.radius};`;
			},
			scrollPopup() {
				return `opacity: ${this.isShow ? 1 : 0};`;
			},
		},
		mounted() {
			// #ifdef H5
			document.querySelector('.popup-box').addEventListener('touchmove', function(event) {
				event.preventDefault();
			}, {
				passive: false
			})

			document.querySelector('.scroll-mask').addEventListener('touchmove', function(event) {
				event.preventDefault();
			}, {
				passive: false
			})
			// #endif
		},
		methods: {
			open() {
				this.isShow = true
			},
			close() {
				this.selectIndex = -1
				this.isShow = false
			},
			selectType(index, item) {
				if (!this.canChoose) return
				if (this.selectIndex == index) {
					this.close()
				} else {
					this.selectIndex = index
					let idx = this.selectIndex + 1
					this.itemList = this.dataObj['itemList' + idx]
					this.isMultiple = this.selectIndex > -1 && this.menuList[this.selectIndex].isMultiple ?
						this.menuList[this.selectIndex].isMultiple : false
					this.showType = this.menuList[this.selectIndex].showType
					this.open()
					this.$emit('chooseType', this.selectIndex)
				}
			},
			//选择成功
			complete(obj) {
				let list = obj.list
				let type = obj.type
				let idx = this.selectIndex + 1
				this.dataObj['itemList' + idx] = JSON.parse(JSON.stringify(list))
				if (type == 1) { //有子集
					list.forEach(res => {
						res.childs = res.childs.filter(item => item.chooseState)
					})
					let newList = list.filter(item => item.childs.length > 0)
					if (newList.length > 0) {
						let arr = []
						newList.forEach(res => {
							res.childs.forEach(item => {
								arr.push(item.name)
							})
						})
						this.menuList[this.selectIndex].showName = arr.toString()
					} else {
						this.menuList[this.selectIndex].showName = ''
					}
				} else { //无子集
					let newList = list.filter(item => item.chooseState)
					if (newList.length > 0) {
						let arr = []
						newList.forEach(item => {
							arr.push(item.name)
						})
						this.menuList[this.selectIndex].showName = arr.toString()
					} else {
						this.menuList[this.selectIndex].showName = ''
					}
				}
				let chooseObj = JSON.parse(JSON.stringify(this.dataObj))
				for (let i in chooseObj) {
					if (chooseObj[i][0] && chooseObj[i][0].childs) {
						chooseObj[i].forEach(item => {
							item.childs = item.childs.filter(mes => mes.chooseState)
						})
						chooseObj[i] = chooseObj[i].filter(mes => mes.childs.length > 0)
					} else {
						chooseObj[i] = chooseObj[i].filter(mes => mes.chooseState)
					}
				}
				this.$emit('change', {
					chooseMenu: this.menuList[this.selectIndex],
					chooseInfo: chooseObj
				})
				this.close()
			}
		},
	};
</script>

<style scoped lang="scss">
	.popup-box {
		width: 100%;
		height: auto;
		position: sticky;
		overflow: hidden;
		background-color: #FFFFFF;
		z-index: 99999 !important;

		.scroll-popup {
			width: 100%;
			height: auto;
			transition: all 0.5s ease 0s;
			opacity: 0;

			.no-data {
				text-align: center;
				margin: 70rpx 0 50rpx 0;

				.no-data-img {
					width: 120rpx;
					height: 120rpx;
				}

				.no-data-name {
					margin-top: 10rpx;
					font-size: 28rpx;
					color: #999999;
				}
			}
		}

		.type-list {
			width: 100%;
			height: 88rpx;
			border-bottom: solid #F4F4F4 1rpx;
			display: flex;
			align-items: center;
			justify-content: space-between;
			box-sizing: border-box;
			padding: 0 20rpx;

			.type-item {
				display: flex;
				align-items: center;
				justify-content: center;

				.type-item-name {
					font-size: 28rpx;
					color: #333333;
					overflow: hidden;
					text-overflow: ellipsis;
					display: -webkit-box;
					-webkit-box-orient: vertical;
					-webkit-line-clamp: 1; //控制显示的行数
				}

				.type-item-icon {
					margin-left: 6rpx;
					width: 24rpx;
					height: 24rpx;
					min-width: 24rpx;
					border-radius: 50%;
					background-color: #F0F0F0;
					display: flex;
					align-items: center;
					justify-content: center;

					.type-item-icon-img {
						width: 20rpx;
						height: 20rpx;
					}
				}
			}
		}
	}

	.scroll-mask {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: rgba(0, 0, 0, 0.2);
		z-index: 99998 !important;
		overflow: hidden;
	}
</style>