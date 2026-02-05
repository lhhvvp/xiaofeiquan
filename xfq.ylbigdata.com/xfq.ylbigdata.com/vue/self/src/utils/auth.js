import Cookies from 'js-cookie'
import defaultSettings from '@/settings.js'
const TokenKey = 'token'
const userKey = 'userInfo'

/**
 * 获取token
 */
export function getToken() {
    return Cookies.get(TokenKey)
};

export function getUserInfo() {
    let user = Cookies.get(userKey);
    if(user){
        user = JSON.parse(user)
        return user;
    }
}

/**
 * 设置token
 * @param {*} token
 */
export function setToken(token) {
    return Cookies.set(TokenKey, token, { expires: defaultSettings.cookieExpires })
}

export function setUserInfo(userinfo) {
    userinfo = JSON.stringify(userinfo);
    return Cookies.set(userKey, userinfo, { expires: defaultSettings.cookieExpires })
}

/**
 * 删除token
 */
export function removeToken() {
    return Cookies.remove(TokenKey)
}
export function removeAll(){
    Cookies.remove(TokenKey);
    Cookies.remove(userKey);
    return true;
}

