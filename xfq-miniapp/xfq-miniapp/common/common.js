import {
	httpRequest,
	urli
} from "../httpRequest.js"

import moment from 'moment'



export function imgVersion() {
	let globalData = {};
	globalData.imgVersion = '?v=24'
	let that = this;
	// 图片添加版本号
	var padDigits = function(number, digits) {
		return Array(Math.max(digits - String(number).length + 1, 0)).join(0) + number;
	};
	var datetime = new Date();
	return globalData.imgVersion = '?v=' + datetime;
}
/**
 * 显示消息提示框。
 */
export function toast(title) {
	uni.showToast({
		icon: 'none',
		title: title
	})
}


/**
 * 显示消息提示框。
 */
export function msg(title, icon = 'error', duration = 2500, mask = false) {
	/**
	 * 统一提示方便全局修改
	 * success	显示成功图标，此时 title 文本在小程序平台最多显示 7 个汉字长度
	 * error	显示错误图标，此时 title 文本在小程序平台最多显示 7 个汉字长度。
	 * loading	显示加载图标，此时 title 文本在小程序平台最多显示 7 个汉字长度。
	 **/
	if (Boolean(title) === false) {
		return;
	}
	uni.showToast({
		title: title,
		duration: duration,
		mask: mask,
		icon: icon
	});
}

/**
 * 获取页面栈。
 */
export function prePage() {
	let pages = getCurrentPages();
	let prePage = pages[pages.length - 2];
	// #ifdef H5
	return prePage;
	// #endif
	return prePage.$vm;
}

/**
 * 手机验证
 */
export function checkPhone(value) {
	let reg = /^(0|86|17951)?(13[0-9]|15[0-9]|16[0-9]|17[0-9]|18[0-9]|14[0-9]|19[0-9])[0-9]{8}$/
	if (reg.test(value)) {
		return true;
	}
	toast('手机号格式错误');
	return false
}

/**
 * 身份证验证
 **/

export function checkIdNumber(value) {
	let reg =
		/^(^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$)|(^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[Xx])$)$/
	if (reg.test(value)) {
		return true;
	}
	toast('手机号格式错误');
	return false
}

/**
 * 真实姓名验证
 **/
export function checkName(value) {
	let reg = /^[\u4E00-\u9FA5]{2,10}(·[\u4E00-\u9FA5]{2,10}){0,2}$/;
	if (reg.test(value)) {
		return true;
	}
	return false
}



/**
 * 时间戳转换
 */
function padLeftZero(str) {
	return ('00' + str).substr(str.length)
}

export function formatDate(date, that) {
	// date时间戳， that = this
	date = date * 1000
	return that.$api.moment(date).format('YYYY-MM-DD')
}

/**
 * 函数节流
 */
export function throttle(fn, interval) {
	var enterTime = 0; //触发的时间
	var gapTime = interval || 300; //间隔时间，如果interval不传，则默认300ms
	return function() {
		var context = this;
		var backTime = new Date(); //第一次函数return即触发的时间
		if (backTime - enterTime > gapTime) {
			fn.call(context, arguments);
			enterTime = backTime; //赋值给第一次触发的时间，这样就保存了第二次触发的时间
		}
	};
}

/*函数防抖*/
export function debounce(fn, interval) {
	var timer;
	var gapTime = interval || 200; //间隔时间，如果interval不传，则默认1000ms
	return function() {
		clearTimeout(timer);
		var context = this;
		var args = arguments; //保存此处的arguments，因为setTimeout是全局的，arguments不是防抖函数需要的。
		timer = setTimeout(function() {
			fn.call(context, args);
		}, gapTime);
	};
}
export function urlis(url) {
	return urli + url;
}

// 缓存+时间
export function myCache(key, value, seconds = 3000) {
	// 300 5分钟刷新
	let nowTime = Date.parse(new Date()) / 1000;
	if (key && value) {
		let expire = nowTime + Number(seconds);
		uni.setStorageSync(key, JSON.stringify(value) + '|' + expire)
		// console.log('已经把' + key + '存入缓存,过期时间为' + expire)
	} else if (key && !value) {
		let val = uni.getStorageSync(key);
		if (val) {
			// 缓存存在，判断是否过期
			let temp = val.split('|')
			if (!temp[1] || temp[1] <= nowTime) {
				uni.removeStorageSync(key);
				return false;
			} else {
				return JSON.parse(temp[0]);
			}
		}
	}

}

export function replaceContent(content) {
	if (!content) return;
	const regex = new RegExp('<img', 'gi');
	if (regex) {
		content = content.replace(regex, '<img style="max-width: 100%;"'); //转换图片大小
		content = content.replace(/(<img[^>]*src=['"])(?:(?!(https|http)))([^>]*>)/g, `$1${urli}$2/$3`);
		content = content.replace(/<p/gi, '<p class="p_class"')
	} else {
		content = content.replace(/<p/gi, '<p class="p_class"')
	}

	return content
};

export function dateTime(time) {
	time = time * 1000;
	return moment(time).format('MM-DD HH:mm');
}
export function YmdHm(time) {
	time = time * 1000
	return moment(time).format('Y-M-D H:m:s');
}
export function timeToTimestamp(time) {
	let timestamp = moment(time).unix(); //log:当前时间时间戳 1577808000
	return timestamp;  }
export function hasIllegalChar(str) {
	var RexStr = /\<|\>|\"|\'|\&/g
	str = str.replace(RexStr, function(MatchStr) {
		if (MatchStr == '<') {
			return "&lt;";
		}
		if (MatchStr == '>') {
			return "&gt;";
		}
	})
	return str;
}
export function pay(data) {
	return new Promise((paySuccess, payError) => {
		uni.requestPayment({
			provider: 'wxpay',
			timeStamp: data.timeStamp,
			nonceStr: data.nonceStr,
			package: data.package,
			signType: data.signType,
			paySign: data.paySign,
			success: function(res) {
				paySuccess(res);
			},
			fail: function(err) {
				payError(err);
			}
		});
	})
};

export function authorize(navigateBack = false) {
	let that = this;
	uni.authorize({
		scope: 'scope.userLocation',
		success() {
			uni.getLocation({
				type:"gcj02",
				altitude: true,
				success: function(res) {
					let obj = {
						latitude: res.latitude,
						longitude: res.longitude
					};
					myCache('coord', JSON.stringify(obj));
					if (navigateBack) {
						uni.navigateBack();
					}
				}
			});
		},
		fail() {
			openSetting();
		}
	});
	const systemFun = () => {
		const system = uni.getSystemInfoSync();
		if (!system.locationEnabled) {
			uni.showModal({
				title: '定位服务未开启',
				content: '请在设置中开启位置权限,以便为您推荐附近的商户!',
				showCancel: true,
				confirmText: '确定',
				success(res) {
					if (res.confirm) {
						// systemFun();
					}
				},
			});
		} else {
			return true;
		}
	};
	systemFun();
	const openSetting = () => {
		uni.showModal({
			title: '提示',
			content: '为了更好的为您提供服务,请授权您的地理位置信息!',
			showCancel: true,
			confirmText: '确定',
			success(res) {
				if (res.confirm) {
					uni.openSetting({
						success(res) {
							if (!res.authSetting['scope.userLocation']) {
								// openSetting();
							} else {
								// authorize(navigateBack)
							}
						}
					});
				}
			}
		});

	};
}

export function getLocation(cache=true) {
	let that = this;
	let coord = myCache('coord');
	return new Promise((PromiseRes, PromiseErr) => {
		if(!!coord && cache){
			//缓存有经纬度的时候
			coord = JSON.parse(coord);
			PromiseRes(coord);
		}else{
			//缓存没有经纬度的时候
			uni.getLocation({
				type:"gcj02",
				altitude: true,
				success(res) {
					let obj = { latitude: res.latitude, longitude: res.longitude };
					myCache('coord', JSON.stringify(obj),30);
					PromiseRes(res);
				},
				fail(error) {
					let obj = { latitude: 1, longitude: 1 };
					myCache('coord', JSON.stringify(obj),30);
					PromiseRes(obj);
					toast('未获取到定位,请稍后重试');
					authorize(true);
					// PromiseErr(error);
				},
				complete(c){
				}
			});
		}
	});
}
