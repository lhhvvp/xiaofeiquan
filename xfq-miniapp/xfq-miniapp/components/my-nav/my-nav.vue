<template>
	<view>
		<scroll-view scroll-x class="scroll-x" v-if="!graphic">
			<view
				class="item"
				@click="navClick(index, item)"
				:class="[navList.current == index ? 'active' : '']"
				:style="'width:' + navList.width + '%'"
				v-for="(item, index) in navList.list"
				:key="index"
			>
				{{ item.title }}
			</view>
		</scroll-view>


		<view class="items" v-if="graphic">
			<scroll-view scroll-x class="scroll-xx">
				<view class="item" @click="navClick(index, item)" v-for="(item, index) in navListArray" :key="index" :style="navListArray.length <= 4 ? 'width:25%':'width:23%'">
					<!-- -->
					<button class="button"  v-if="item.is_button != undefined " :open-type="item.is_button" >
						<image class="image" :class="[padding ? 'marginBottom' : '']" :src="item.url"></image>
						<text style="color: #a00000;">{{ item.title }}</text>
					</button>
					<view v-if="item.is_button == undefined || item.is_button == ''" style="display: flex;flex-direction: column;align-items: center;">
						<image class="image" :class="[padding ? 'marginBottom' : '']" :src="item.url"></image>
						<text style="color: #a00000;">{{ item.title }}</text>
					</view>
				</view>
				
			</scroll-view>
		</view>
	</view>
</template>

<script>
/**
 * nav组件
 * @property {Boolean} graphic 样式切换 - 图文：true，纯文：false
 **/
export default {
	name: 'my-nav',
	props: {
		navList: {
			type: Object,
			default: function() {
				return {};
			}
		},
		navListArray: {
			type: Array,
			default: function() {
				return [];
			}
		},
		graphic: {
			type: Boolean,
			default: false
		},
		padding: {
			type: Boolean,
			default: true
		}
	},
	data() {
		return {};
	},
	methods: {
		navClick(index, item) {
			this.$emit('current', { index, item });
		}
	}
};
</script>

<style lang="scss" scoped>
$color: #a00000;

.scroll-x {
	width: 100%;
	white-space: nowrap;
	position: relative;
	& .item {
		padding: 30upx 0;
		text-align: center;
		display: inline-block;
		
	}
	
	& .active {
		position: relative;
		&::before {
			position: absolute;
			content: '';
			bottom: 6upx;
			left: 20%;
			height: 4upx;
			width: 60%;
			background-color: $div-bg-color;
		}
	}
}
.scroll-xx{
	padding: 0;
	white-space: nowrap;
}
.items {
	width: 100%;
	& .item {
		display: inline-block;
		width: 23%;
		text-align: center;
		height: 160upx;
		& .button{
			display: flex;
			padding: 0;
			line-height: 1;
			font-size: 26upx;
			margin: auto;
			height: 160rpx;
			align-items: center;
			justify-content: center;
			flex-direction: column;
			background: none;
			&::after{
				border: none;
			}
		
		}
	}
	& .image {
		width: 80upx;
		height: 80upx;
		border-radius: 20upx;
		display: inline-block;
	}
}
.marginBottom{
	margin-bottom: 20upx;
}
</style>
