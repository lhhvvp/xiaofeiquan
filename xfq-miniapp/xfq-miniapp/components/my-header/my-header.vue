 <template>
	<view class="bg" :style="'padding-top:' + menuButton.top + 'px'">
		<view class="logo-address-box" :style="'min-height:' + menuButton.height + 'px;width:' + menuButton.left + 'px;'">
			榆林市旅游消费平台
		</view>
		<view class="search-box" v-if="false">
			<view class="search">
				<uni-icons class="icons-search" type="search" size="24" color="#c7c7c7"></uni-icons>
				<input class="input" placeholder="请输入关键字" @click="searchClick" disabled="true" placeholder-class="placeholder-input" />
			</view>
			<view class="search-qrcode">
				<view class="qrcode"><uni-icons type="scan" size="30" color="#fff"></uni-icons></view>
				<view class="qrcode-log"><uni-icons type="wallet" size="32" color="#fff"></uni-icons></view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	name: 'my-header',
	data() {
		return {
			menuButton: {
			}
		};
	},
	mounted() {
		
		this.menuButton = uni.getMenuButtonBoundingClientRect();
		this.menuButton.left = this.menuButton.left - 10;
	},
	methods:{
		searchClick(){
			this.$emit('search',true);
		}
	}
};
</script>

<style lang="scss" scoped>
.bg {
	width: calc(100% - 20px);
	position: sticky;
	top: 0;
	z-index: 99;
	padding: 10px;
	background-color: $div-bg-color;
	background-image: url(./bg.png);
	background-size: 100% 100%;
	& .logo-address-box {
		display: flex;
		color: #fff;
		align-items: center;
		font-size: 34upx;
		font-weight: bold;
	
	}
}
.search-box {
	width: 100%;
	padding-top: 20upx;
	display: flex;
	justify-content: space-between;
	align-items: center;
	& .search {
		// width: calc(95% - 60px);
		position: relative;
		display: flex;
		flex: 1;
		align-items: center;
		& .input {
			width: calc(100% - 80upx);
			background: #fff;
			padding: 10upx 10upx 10upx 70upx;
			border-radius: 20upx;
		}
		& .icons-search {
			position: absolute;
			margin-left: 8upx;
		}
	}

	& .search-qrcode{
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-left: 20upx;
		& .qrcode{
			margin-right: 5px;
		}
		& .qrcode, &.qrcode-log {
			width: 30px;
			display: flex;
			align-items: center;
			justify-content: center;
		}
	}
}

</style>
