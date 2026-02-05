import App from './App'
import {
	httpRequest,
	baseUrl,
	urli
} from "@/httpRequest.js"
import moment from 'moment'
import {
	msg,
	prePage,
	myCache,
	hasIllegalChar,
	imgVersion,
	authorize
} from '@/common/common.js'
import store from './store'
import '@/common/baidu/mtj-wx-sdk.js';
Vue.prototype.$store = store
Vue.prototype.$api = {
	httpRequest,
	baseUrl,
	urli,
	msg,
	prePage,
	moment,
	hasIllegalChar,
	myCache,
	imgVersion,
	authorize,
};
Vue.filter('date', date => {
	date = date * 1000
	return moment(date).format('YYYY-MM-DD')
})

Vue.filter('dateHMS', date => {
	date = date * 1000
	return moment(date).format('YYYY-MM-DD HH:mm:ss')
})

// #ifndef VUE3
import Vue from 'vue'
Vue.config.productionTip = false
App.mpType = 'app'
const app = new Vue({
	...App
})
app.$mount()
// #endif

// #ifdef VUE3
import {
	createSSRApp
} from 'vue'
export function createApp() {
	const app = createSSRApp(App);
	// app.config.globalProperties.$api = {
	// 	httpRequest,
	// 	msg,
	// };
	return {
		app
	}
}
// #endif
