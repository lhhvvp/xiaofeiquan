<template>
	<view class="content">
		<view class="nullnews" v-if="newsList && newsList.length == 0">
			<image src="/static/order_icon.png"></image>
			<text class="tit">暂无数据</text>
		</view>
		<view class="news-box" v-if="newsList.length > 0">
			<view class="news-list">
				<view v-for="news in newsList" :key="news.id" @tap="tonewsPage(news)">
					<view class="news">
						<view class="news-l">
							<view class="name">{{ news.title }}</view>
							<view class="info">
								<view class="date">{{ news.create_time }}</view>
								<view class="slogan">
									<uni-icons type="eye" color="" style="margin-right: 10upx;"></uni-icons>
									<text>{{ news.hits }}</text>
								</view>
							</view>
						</view>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			newsList: [],
			
			page: 1 //当前页面
		};
	},
	onLoad(options) {
		this.loadData();
	},
	onReachBottom() {
		// 模拟触底刷新
		this.loadData();
	},
	methods: {
		//请求数据
		async loadData() {
			this.$api
				.httpRequest('/index/note_list', {},"POST")
				.then(res => {
					this.newsList = res.data;
				});
		},
		//详情
		tonewsPage(item) {
			let id = item.id;
			
			uni.navigateTo({
				url: `/pages/news/info?id=${id}`,
			});
		}
	}
};
</script>

<style lang="scss" scoped>
.botHeight {
	height: 120upx;
}
.nullnews {
	display: flex;
	flex-direction: column;
	justify-content: center;
	align-items: center;
	margin: 40px auto;
	image {
		width: 71px;
		height: 50px;
	}
	.tit {
		display: flex;
		flex-direction: column;
		font-size: 16px;
		font-weight: bold;
		color: #555;
		margin-top: 6px;
	}
}
.news-list {
	// padding: 20upx 20upx 0upx;
	display: flex;
	justify-content: space-between;
	flex-wrap: wrap;
}
.news {
	width: 750upx;
	border-radius: 10upx;
	background-color: #ffffff;
	border-bottom: 1px solid #e1e1e1;
	display: flex;
	margin-bottom: 20upx;
	flex-direction: row;
	position: relative;
	& .video {
		width: 100%;
		& .video-icon {
			position: absolute;
			top: calc(50% - 46upx);
			left: calc(50% - 46upx);
		}
		& image {
			width: 100%;
			height: 320upx;
			display: block;
		}
		& .video-title {
			position: absolute;
			bottom: 0;
			width: calc(100% - 20upx);
			padding: 0 10upx;
			height: 60upx;
			display: flex;
			align-items: center;
			background-color: rgba($color: #000000, $alpha: 0.5);
			color: #ffffff;
		}
	}
}
.news-l {
	padding: 20upx 10upx;
	display: flex;
	width: calc(100% - 40upx);
	margin: auto;
	position: relative;
	.name {
		display: block;
		text-overflow: ellipsis;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
		line-clamp: 2;
		text-align: justify;
		overflow: hidden;
		text-overflow: -o-ellipsis-lastline;
		font-size: 28upx;
		line-height: 40upx;
		height: 140upx;
	}
	.info {
		position: absolute;
		width: 90%;
		left: 10upx;
		bottom: 20upx;
		display: flex;
		padding-top: 10upx;
		justify-content: space-between;
	}
	.info .date {
		color: #807c87;
		font-size: 24upx;
	}
	.info .slogan {
		margin-left: 20upx;
		color: #807c87;
		display: flex;
		align-items: center;
		font-size: 24upx;
	}
}
.news-r {
	display: flex;
	float: right;
	align-items: flex-end;
	justify-content: space-between;
	width: 230upx;
	padding: 20upx 10upx 20upx 30upx;
	image {
		width: 300upx;
		height: 160upx;
		border-radius: 10upx;
	}
	.empty-img {
		width: 300upx;
		height: 160upx;
		border-radius: 10upx;
		display: flex;
		font-size: 30upx;
		align-items: center;
		justify-content: center;
		color: #ffffff;
	}
}
.loading-text {
	width: 100%;
	display: flex;
	justify-content: center;
	align-items: center;
	height: 60upx;
	line-height: 60upx;
	padding-bottom: 15upx;
	color: #979797;
	font-size: 24upx;
}
</style>
