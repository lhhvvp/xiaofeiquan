let urli = '';
// let baseUrl = 'https://xfq.dianfengcms.com/api';
// let baseUrl = 'http://test.xfq.ylbigdata.com/api';
// let baseUrl = 'http://192.168.1.5:7005/api';
   let baseUrl = 'https://xfq.ylbigdata.com/api';
const httpRequest = (url = '', date = {}, type = 'POST', header = {
	'content-type': 'application/x-www-form-urlencoded',
	'Token': uni.getStorageSync("uerInfo").token != undefined ? uni.getStorageSync("uerInfo").token : '',
	'Userid': uni.getStorageSync("uerInfo").uid != undefined ? uni.getStorageSync("uerInfo").uid : '',
}) => {
	uni.showLoading({
		title: "加载中...",
		mask: true,

	});

	uni.getNetworkType({
		success: function(res) {
			if (res.networkType == 'none') {
				uni.showToast({
					title: '当前无网络连接,请联网后重试',
					icon: 'none',
				});
				return false;
			}
		}
	});

	return new Promise((resolve, reject) => {
		uni.request({
			method: type,
			url: baseUrl + url,
			data: date,
			header: header,
			dataType: "json"
		}).then((response) => {
			uni.hideLoading();
			if (response[0] != null) {
				let data = {
					header,
					url: baseUrl + url,
					statusCode: response[1].statusCode,
				};
				data = JSON.stringify(data);
				businessMonitor(0, response[1].statusCode, data, 0)
				uni.reLaunch({
					url: '/pages/user/login/login',
				});
			};
			if (response[1].statusCode != 200) {
				let data = {
					header,
					url: baseUrl + url,
					statusCode: response[1].statusCode,
				};
				data = JSON.stringify(data);
				businessMonitor(0, response[1].statusCode, data, 0)
				uni.reLaunch({
					url: '/pages/user/login/login',
				});
			}
			if (response[1].data.code == 111 || response[1].data.code == 110 || response[1].data
				.code == 112 || response[1].data.code == 113 || response[1].data.code == 114 ||
				response[1].data.msg.indexOf('用户信息异常') == 0) {
				if (date.is_token) {
					uni.removeStorageSync('uerInfo');
					return false;
				}
				uni.showModal({
					title: "提示",
					content: "您还没登录/登录已过期,请重新登录!",
					showCancel: true,
					cancelText: '取消',
					confirmText: "重新登录",
					success(res) {
						if (res.confirm) {
							uni.removeStorageSync('uerInfo');
							uni.reLaunch({
								url: '/pages/user/login/login',
							});
						}
					}
				})
				return false;
			};
			let userInfo = uni.getStorageSync("uerInfo");
			if (url == '/index/miniwxlogin' || url ==
				'/index/getuserphonenumber' || url == '/user/edit' || url ==
				'/seller/bindCheckOpenid' || url == '/user/get_user_coupon_id' || url ==
				'/user/coupon_issue_user') {} else {
				if ((!userInfo.name || !userInfo.idcard) && userInfo.token) {
					uni.reLaunch({
						url: '/pages/user/login/login?is_moble=true'
					});
					return false;
				}
			};
			let [error, res] = response;
			resolve(res.data);
		}).catch(error => {
			let [err, res] = error;
			reject(err)
		});

	});
};

function businessMonitor(monitorId, errorCode, errorMsg, time, content) {
	// content==业务逻辑内容
	wx.reportEvent && wx.reportEvent('wxdata_perf_monitor', {
		// 接口ID，可以使用URL相似的ID，也可以另外命名
		"wxdata_perf_monitor_id": monitorId,
		//接口等级，0为普通，非0为重要，数值越大越重要，根据实际业务情况进行设置
		"wxdata_perf_monitor_level": 1,
		// 错误码，0为调用成功，非0为调用失败
		"wxdata_perf_error_code": errorCode,
		// 错误信息，选填，可以上报错误相关信息，方便后续排查问题
		"wxdata_perf_error_msg": errorMsg,
		// 接口耗时，选填
		"wxdata_perf_cost_time": time,
		// 以下为补充字段，可额外上报其他监控信息，用于事件分析，非必填
	})
}

export {
	httpRequest,
	baseUrl,
	urli
}