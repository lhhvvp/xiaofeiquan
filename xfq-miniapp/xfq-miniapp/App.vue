<script>
	export default {
		onLaunch: function() {

			return false;
		},
		onShow: function() {
			const updateManager = uni.getUpdateManager();
			updateManager.onCheckForUpdate(function(res) {
				// 请求完新版本信息的回调
				if (res.hasUpdate) {
					updateManager.onUpdateReady(function(res2) {
						updateManager.applyUpdate();
					});
				}
			});
			updateManager.onUpdateFailed(function(res) {
				// 新的版本下载失败
				uni.showModal({
					title: '提示',
					content: '检查到有新版本，但下载失败，请检查网络设置',
					showCancel: false,
					success(res) {
						if (res.confirm) {
							// 新的版本已经下载好，调用 applyUpdate 应用新版本并重启
							updateManager.applyUpdate();
						}
					}
				});
			});
		},
		onError: function(e) {
			// console.log(e);
		},
		onHide: function() {
			// console.log('App Hide');
		}
	};
</script>

<style lang="scss">
	@import 'common/diygw-ui/index.scss';

	.goods-nav {
		position: fixed;
		bottom: 0;
		width: 100%;
		background-color: #ffffff;
		z-index: 999;
	}

	.coupon-box {
		opacity: 1;
		animation-name: linear;
		animation-duration: 1s;
		animation-timing-function: linear;
		animation-direction: alternate;
	}

	@keyframes linear {
		from {
			opacity: 0
		}

		to {
			opacity: 1
		}
	}

	rich-text {
		.p_tit {
			font-size: 34upx;
			line-height: 55upx;
			font-weight: bold;
			padding-top: 40upx;
		}

		.p_c {
			padding: 0 0 20upx;
		}

		.p_class:first-child {}

		.p_class {
			line-height: 50upx;
		}
	}

	@font-face {
		font-family: yticon;
		font-weight: normal;
		font-weight: 500;
		font-style: normal;
		src: url('https://at.alicdn.com/t/font_1078604_w4kpxh0rafi.ttf') format('truetype');
	}

	.yticon {
		font-family: 'yticon' !important;
		font-size: 28px;
		font-style: normal;
		color: $div-color;
		-webkit-font-smoothing: antialiased;
		-moz-osx-font-smoothing: grayscale;
	}

	.icon-yishouhuo:before {
		content: '\e71a';
	}

	.icon-shouhoutuikuan:before {
		content: '\e631';
	}

	.icon-daifukuan:before {
		content: '\e68f';
	}

	.icon-shouye:before {
		content: '\e626';
	}

	page {
		background-color: #f7f7f7;
		font-size: 26upx;
		color: #333333;
	}

	/*每个页面公共css */
	.banner {
		width: 100%;
		padding-top: 30upx;
	}

	.bannerFull {
		width: 100%;
	}

	.placeholder-input {
		font-size: 26upx;
	}

	.button {
		height: 85upx;
		width: 80%;
		font-size: 26upx;
		margin: 50upx auto 0;
		border-radius: 10upx;
		color: #ffffff;
		display: flex;
		align-items: center;
		justify-content: center;
		background-color: $div-bg-color;
	}
</style>