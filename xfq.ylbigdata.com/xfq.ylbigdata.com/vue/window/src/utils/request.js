import axios from 'axios'
import {ElMessage, ElMessageBox} from 'element-plus'
import store from '@/store'
import {getToken, getUserInfo} from '@/utils/auth'
import qs from 'qs'
import {api} from "@/settings"
import router from "@/router";

// create an axios instance
const service = axios.create({
    // baseURL: api, // url = base url + request url
    baseURL: "/window", // withCredentials: true, // send cookies when cross-domain requests
    timeout: 5000 // request timeout
})

// 请求拦截器
service.interceptors.request.use(config => {
    // token
    if (store.state.token && store.state.token != undefined) {
        let user = getUserInfo();
        if (user) {
            config.headers['Uuid'] = user.uuid;
        }
        config.headers['Token'] = getToken();
    }
    // signature
    if (config.method === 'post') {
        // config.data = getSignature(config.data)
        config.data = qs.stringify(config.data)
    } else if (config.method === 'get') {
        config.params = config.data
        // config.params = getSignature(config.params)
    }
    return config
}, error => {
    // do something with request error
    console.log(error) // for debug
    return Promise.reject(error)
})

// 响应拦截器
service.interceptors.response.use(/**
 * If you want to get http information such as headers or status
 * Please return  response => response
 */response => {
    const res = response.data
    // 业务逻辑错误
    if (res.code === 1 || res.code === 0) {
        return res
    } else if (res.code === 112 || res.code === 113 || res.code === 114) {
        // token 信息

        ElMessageBox.confirm('你已被登出，可以取消继续留在该页面，或者重新登录', '确定登出', {
            confirmButtonText: '重新登录', cancelButtonText: '取消', type: 'warning'
        }).then((res) => {
            router.push({path: '/login'});
        }).catch(err => {

        })

        return Promise.reject(new Error(res.msg || 'Error'));

    } else {
        ElMessage({
            message: res.msg || 'Error', type: 'error', showClose: true, duration: 3 * 1000
        });
        return Promise.reject(new Error(res.msg || 'Error'))
    }
    // if (res.status !== 1) {
    //     // token 过期了
    //     if (res.code === 11102 || res.code === 11103) {
    //         // to re-login
    //         ElMessageBox.confirm('你已被登出，可以取消继续留在该页面，或者重新登录', '确定登出', {
    //             confirmButtonText: '重新登录',
    //             cancelButtonText: '取消',
    //             type: 'warning'
    //         }).then(() => {
    //             store.dispatch('user/resetToken').then(() => {
    //                 location.reload()
    //             })
    //         })
    //     } else {
    //         ElMessage({
    //             message: res.msg || 'Error',
    //             type: 'error',
    //             showClose: true,
    //             duration: 3 * 1000
    //         });
    //     }
    //     return Promise.reject(new Error(res.msg || 'Error'))
    // } else {
    //     return res
    // }
}, error => {
    console.log('err' + error) // for debug
    ElMessage({
        message: error.message, type: 'error', showClose: true, duration: 3 * 1000
    })
    return Promise.reject(error)
})

export default service