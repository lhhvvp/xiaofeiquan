<template>
	<view>
		<view class="search">
			<input placeholder="请输入搜索内容" v-model="keyword" @input="keywordInput" placeholder-class="placeholder-input" />
			<view @click="searchButton()">搜索</view>
		</view>
		<view class="history" v-if="isKeyword">
			<view class="title-box">
				<view class="title">历史记录</view>
				<view class="del" @click="delSearchHistory"><uni-icons type="trash"></uni-icons></view>
			</view>
			<view class="content">
				<view class="item" v-for="(item, index) in SearchHistory" @click="searchButton(item)" :key="index">{{ item }}</view>
			</view>
		</view>
		<view v-if="!isKeyword">
			<my-merchant :lists="list" @click="merchant"></my-merchant>
		</view>
		<!-- 加载loadding -->
		<uni-load-more :status="loadding" v-if="list.length != 0 && keyword != ''"></uni-load-more>
		
		<my-empty :empty="empty" v-if="list.length == 0 && !isKeyword"></my-empty>
		<!-- end -->
	</view>
</template>

<script>
export default {
	data() {
		return {
			keyword: '',
			SearchHistory: [],
			list: [],
			page: 1,
			loadding: 'more',
			loadding_lock: false,
			empty: {
				show: false,
				id: 1
			},
			isKeyword:true
		};
	},
	onLoad() {
		uni.setNavigationBarColor({
			frontColor: '#000000',
			backgroundColor: '#ffffff'
		});
		this.SearchHistory = uni.getStorageSync('SearchHistory') == '' ? (this.SearchHistory = []) : uni.getStorageSync('SearchHistory');
	},
	methods: {
		searchButton(item) {
			if(this.keyword == '' && item==undefined){
				this.$api.msg('关键字不能为空');
				return false;
			}
			this.list = [];
			this.page = 1;
			this.loadding = 'more';
			this.loadding_lock = false;
			Object.assign(this.$data.empty, this.$options.data().empty);
			this.isKeyword = false;
			if (item) {
				this.keyword = item;
			}
			let { keyword, SearchHistory } = this;
			SearchHistory.unshift(keyword);
			let newSearchHistory = [...new Set(SearchHistory)]; //数组去重
			uni.setStorageSync('SearchHistory', newSearchHistory);
			this.SearchHistory = newSearchHistory;
			// ajax
			this.init();
		},
		delSearchHistory() {
			let that = this;
			uni.showModal({
				title: '提示',
				content: '是否清空历史记录！',
				confirmColor: '#e64e59',
				success(res) {
					if (res.confirm) {
						uni.removeStorageSync('SearchHistory');
						that.$data.SearchHistory = [];
						that.$forceUpdate();
					} else if (res.cancel) {
						console.log('用户点击取消');
					}
				}
			});
		},
		init() {
			this.$api
				.httpRequest(
					`/seller/search`,
					{
						nickname: this.keyword
					},
					'POST'
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
						data.map((item, index) => {
							return (data[index].image = this.$api.urli + item.image);
						});
						this.list = [...this.list, ...data];
						if (data.length != res.data.per_page) {
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
		keywordInput(val){
			if(val.detail.value == ''){
				this.list = [];
				this.page = 1;
				this.loadding = 'more';
				this.loadding_lock = false;
				Object.assign(this.$data.empty, this.$options.data().empty);
				this.isKeyword = true;
			}
		},
		merchant(item) {
			uni.navigateTo({
				url: '/pages/merchant/info/info?id=' + item.id
			});
		}
	}
};
</script>

<style lang="scss">
.search {
	width: calc(98% - 40upx);
	padding: 0 20upx;
	margin: auto;
	display: flex;
	align-items: center;
	background-color: #fff;
	height: 80px;
	position: relative;
	& input {
		width: calc(100% - 60upx);
		padding: 15upx 30upx;
		border-radius: 20upx;
		background-color: #f7f7f7;
	}
	& view {
		position: absolute;
		right: 0;
		padding: 18upx 50upx;
		border-radius: 20upx;
		background: $div-bg-color;
		z-index: 99;
		color: #fff;
		margin-right: 20upx;
	}
}
.history {
	width: calc(98% - 40upx);
	padding: 50upx 20upx;
	& .title-box {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 30upx;
		& view {
			color: #b5b5b5;
		}
	}
	& .content {
		width: 100%;
		display: flex;
		flex-wrap: wrap;
		& .item {
			background-color: #fff;
			margin-right: 20upx;
			padding: 6upx 15upx;
			font-size: 22upx;
			border-radius: 25upx;
		}
	}
}
</style>
