<template>
	<view class="content">
		<view class="introduce-section">
			<text class="title">{{ newsInfo.title }}</text>
			<view class="meta-box">
				<text class="date">{{ newsInfo.create_time }}</text>
			</view>
		</view>
		<view class="detail-desc" style="padding-top: 15px;" id="content"><rich-text :space="true" :nodes="newsInfo.content"></rich-text></view>
		<view class="bottom-nav">
			<view class="look-box">浏览次数: {{ newsInfo.hits }}</view>
		</view>
	</view>
</template>
<script>
import { mapState, mapMutations } from 'vuex';
export default {
	data() {
		return {
			newsInfo: {}, //基础信息
			id: 0
		};
	},
	onLoad(options) {
		this.id = options.id;
		if (this.id) {
			this.loadData(this.id);
		} else {
			this.$api.msg(`参数错误！`);
			setTimeout(function() {
				uni.navigateTo({
					url: '/pages/news/index'
				});
			}, 5000);
		}
	},
	computed: {
		...mapState(['hasLogin', 'uerInfo'])
	},
	methods: {
		//请求数据
		async loadData(id) {
			this.$api
				.httpRequest(
					'/index/note_detail',
					{
						id: this.id
					},
					'GET'
				)
				.then(res => {
					if (res.code == 0) {
						let content = res.data.content; //详情介绍
						const regex = new RegExp('<img', 'gi');
						content = content.replace(regex, '<img style="max-width: 100%;"'); //转换图片大小
						content = content.replace(/(<img[^>]*src=['"])(?:(?!(https|http)))([^>]*>)/g, `$1${this.$api.urli}$2/$3`);
						res.data.content = content
						this.newsInfo = res.data;
					}
				});
		}
	}
};
</script>

<style lang="scss">
.news-box {
	background-color: #ffffff;
}
.botHeight {
	height: 120upx;
}
.line-weight {
	background-color: #efefef;
	height: 10px;
}
.video-wrapper {
	height: 422upx;
	.video {
		width: 100%;
		height: 100%;
	}
}
/* 标题简介 */
.introduce-section {
	background: #fff;
	padding: 20upx 30upx 0;

	.title {
		font-size: 38upx;
		color: #333333;
		font-weight: 600;
		line-height: 50upx;
		display: block;
		margin-bottom: 10upx;
	}
	.meta-box {
		display: inline-block;
		width: 100%;
		height: 50upx;
		padding: 10upx 0 0px;
		font-size: 26upx;
		color: #909399;
		border-bottom: 1px dashed #dcdfe6;
	}
	.copyfrom {
		font-size: 26upx;
		color: #333333;
		margin-right: 20upx;
	}
	.author {
		margin-right: 20upx;
	}
	.date {
		margin-right: 20upx;
	}
}

/*  详情 */
.detail-desc {
	background: #fff;
	padding: 10upx 40upx 30upx;
	color: #333333;
	font-size: 28upx;
	line-height: 50upx;
}

/* 底部操作菜单 */
.page-bottom {
	position: fixed;
	left: 0;
	bottom: 0;
	z-index: 95;
	display: flex;
	justify-content: center;
	align-items: center;
	width: 750upx;
	height: 100upx;
	background: rgba(255, 255, 255, 1);
	border-top: 1px solid #9e9e9e;

	.p-b-btn {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		font-size: 28upx;
		color: #333;
		width: 120upx;
		height: 80upx;
		.logo {
			width: 50upx;
			height: 48upx;
		}
		.yticon {
			font-size: 40upx;
			line-height: 48upx;
			color: #333;
		}
		&.active,
		&.active .yticon {
			color: #333;
		}
		.icon-fenxiang2 {
			font-size: 42upx;
			transform: translateY(-2upx);
		}
		.icon-shoucang {
			font-size: 46upx;
		}
	}
	.action-btn {
		height: 76upx;
		border-radius: 100px;
		background: #333;
		margin-left: 30upx;
		color: #ffffff;
		text-align: center;
		line-height: 76upx;
		width: 350upx;
		font-size: 16px;
	}
}

.news-list {
	padding: 20upx 20upx 0upx;
	display: flex;
	justify-content: space-between;
	flex-wrap: wrap;
}
.news {
	width: 700upx;
	border-radius: 10upx;
	background-color: #ffffff;
	border-bottom: 1px solid #e1e1e1;
	display: flex;
	flex-direction: row;
}
.news-l {
	padding: 20upx 10upx;
	display: flex;
	width: 100%;
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
		height: 80upx;
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
.bottom-nav {
	width: 100%;
	background-color: #ffff;
	border-top: 1px solid #f7f7f7;
	display: flex;
	justify-content: space-between;
	& .look-box {
		font-size: 24rpx;
		padding: 40upx;
		color: #555;
	}
}

.news-list {
	padding: 20upx 20upx 0upx;
	display: flex;
	justify-content: space-between;
	flex-wrap: wrap;
}
.news {
	width: 100%;
	border-radius: 10upx;
	background-color: #ffffff;
	border-bottom: 1px solid #f1f1f1;
	display: flex;
	flex-direction: row;
}
.news-l {
	padding: 20upx 30upx 20upx 20upx;
	display: flex;
	width: 100%;
	position: relative;
	.name {
		& .tag {
			padding: 5upx 10upx;
			font-size: 20upx;
			border-radius: 10upx;
			margin-right: 10upx;
			background-color: #333;
			color: #ffffff;
		}
		display: block;
		text-overflow: -o-ellipsis-lastline;
		overflow: hidden; //溢出内容隐藏
		text-overflow: ellipsis; //文本溢出部分用省略号表示
		display: -webkit-box; //特别显示模式
		-webkit-line-clamp: 2; //行数
		line-clamp: 2;
		-webkit-box-orient: vertical; //盒子中内容竖直排列
		font-size: 30upx;
		line-height: 50upx;
		color: #333;
		height: 100upx;
	}
	.info {
		position: absolute;
		width: 90%;
		left: 20upx;
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
		line-height: 24upx;
		& text {
			margin-left: 8upx;
		}
	}
}
.news-r {
	display: flex;
	float: right;
	align-items: flex-end;
	justify-content: space-between;
	width: 270upx;
	padding: 20upx 20upx 20upx 10upx;
	image {
		width: 240upx;
		height: 160upx;
		border-radius: 10upx;
	}
	.empty-img {
		width: 240upx;
		height: 160upx;
		border-radius: 10upx;
		display: flex;
		font-size: 30upx;
		align-items: center;
		justify-content: center;
		color: #ffffff;
	}
}
</style>
