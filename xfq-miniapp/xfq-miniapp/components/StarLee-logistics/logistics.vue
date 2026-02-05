<template>
	<view>
		<view class="lee-card lee-flex" v-if="showCard">
			<slot name="card" :row="cardInfo">
				<!-- <image class="lee-logo" :src="cardInfo[params.src]" mode=""></image> -->
				<view class="no">
					<view class="pd ">
						<text>物流公司：</text>
						<text>{{cardInfo[params.type]}}</text>
					</view>
					<view>
						<text>物流单号：</text>
						<text>{{cardInfo[params.no]}}</text>
						<text class="copy" @click="copy" :style="{'--pic':mainColor}">复制</text>
					</view>
				</view>
			</slot>
		</view>
		<view class="lee-card lee-list-box">
			<view class="list lee-flex" v-for="(item,index) in list">
				<view class="left-box">
					<view class="left">
						<view class="point" :class="list.length>=1 && index == 0 ? 'point1':''"
							:style="{'--pic':opacityColor}">
							<view class="pint-son" :style="{'--pic':mainColor}"></view>
						</view>
						<view v-if="list.length > 1 && (index + 1) !== list.length" :style="{'--pic':mainColor}"
							class="line" :class="index == 0 ? 'light-line':''"></view>
					</view>
				</view>
				<slot name="process" :row="item">
					<view class="right" :class="index == 0 ? 'lee-light':'gray'" :style="{'--pic':mainColor}">
						<view class="status">{{item[params.status]}}</view>
						<view class="pd">{{item[params.content]}}</view>
						<view class="pd time" :class="index == 0 ? 'lee-light':''" :style="{'--pic':mainColor}">
							{{item[params.time]}}</view>
					</view>
				</slot>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		props: {
			// 物流数据列表
			list: {
				type: Array,
				default () {
					return []
				}
			},
			// 数据默认字段
			params: {
				type: Object,
				default () {
					return {
						src: 'src',
						type: 'type',
						no: 'no',
						status: 'status',
						content: 'content',
						time: 'time'
					}
				}
			},
			// 是否显示物流卡片信息
			showCard: {
				type: Boolean,
				default () {
					return true
				}
			},
			// 物流卡片信息
			cardInfo: {
				type: Object,
				default () {
					// return {
					// 	src: 'https://t10.baidu.com/it/u=996032835,1968172858&fm=58',
					// 	type: '韵达速递',
					// 	no: 'YD34592423445154'
					// }
				}
			},
			// 物流卡片信息-是否显示复制按钮
			showCopy: {
				type: Boolean,
				default () {
					return true
				}
			},
			// 主题颜色
			color: {
				type: String,
				default () {
					return '#ff5a07'
				}
			},
		},
		computed: {
			mainColor() {
				return this.set16ToRgb(this.color)
			},
			opacityColor() {
				return this.set16ToRgb(this.color, 0.5)
			}
		},
		data() {
			return {

			}
		},
		methods: {
			// 复制
			copy() {
				uni.setClipboardData({
					data: this.cardInfo.no,
					success: () => {}
				});
			},
			// 16进制转rgb/rgba
			set16ToRgb(str, opacity) {
				var reg = /^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/
				if (!reg.test(str)) {
					return;
				}
				let newStr = (str.toLowerCase()).replace(/\#/g, '')
				let len = newStr.length;
				if (len == 3) {
					let t = ''
					for (var i = 0; i < len; i++) {
						t += newStr.slice(i, i + 1).concat(newStr.slice(i, i + 1))
					}
					newStr = t
				}
				let arr = []; //将字符串分隔，两个两个的分隔
				for (var i = 0; i < 6; i = i + 2) {
					let s = newStr.slice(i, i + 2)
					arr.push(parseInt("0x" + s))
				}
				let res = opacity ? `rgb(${arr.join(",")},${opacity})` : `rgb(${arr.join(",")})`
				return res;
			}
		}
	}
</script>

<style lang="scss" scoped>
	$eee: #eee;

	.lee-flex {
		display: flex;
	}

	.gray {
		color: #9f9f9f;
	}

	.lee-light {
		color: var(--pic) !important;
	}

	.lee-card {
		margin: 20rpx auto;
		padding: 25rpx 35rpx;
		border-radius: 10rpx;
		background: white;

		.no {
			font-size: 28rpx;
		}

		.copy {
			background: var(--pic);
			color: white;
			padding: 4rpx 14rpx;
			font-size: 28rpx;
			border-radius: 10rpx;
			margin-left: 20rpx;
		}

		.lee-logo {
			width: 90rpx;
			height: 90rpx;
			margin-right: 30rpx;
		}

		.pd {
			padding-bottom: 10rpx;
		}
	}

	.lee-list-box {
		padding: 5rpx 35rpx;

		.list {
			padding-top: 30rpx;
			position: relative;

			.left-box {
				.left {
					width: 50rpx;

					.point {
						z-index: 10;
						width: 30rpx;
						height: 30rpx;
						border-radius: 50%;
						margin-left: calc(20rpx / 2);
						margin-top: 5rpx;
						background: $eee;
						display: flex;
						justify-content: center;
						align-items: center;

					}

					.point1 {
						background: var(--pic);
						margin-top: 0;
						margin-left: 0rpx;
						width: 50rpx;
						height: 50rpx;
						color: white;

						.pint-son {
							width: 60%;
							height: 60%;
							background: var(--pic);
							border-radius: 50%;
						}
					}

					.line {
						display: flex;
						justify-content: center;
					}

					.line::after {
						content: '';
						position: absolute;
						background: $eee;
						height: calc(100% - 30rpx);
						width: 4rpx;
						// margin-top: 10rpx;
					}

					.light-line::after {
						background: var(--pic);
					}
				}
			}

			.right {
				padding-left: 15rpx;

				.status {
					font-weight: bold;
				}

				.time {
					color: #8b8b8b;
					font-size: 28rpx;
				}
			}
		}

		.list:last-child {
			padding-bottom: 30rpx;
		}
	}
</style>