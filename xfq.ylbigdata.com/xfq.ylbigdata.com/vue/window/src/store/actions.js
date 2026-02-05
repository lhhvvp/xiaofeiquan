import {setToken, setUserInfo, removeAll} from '@/utils/auth'
import {login, getTicketPirce, paySubmit, orderList,orderDetail,refundAll,refundOne,statistics,orderListSearch,takeTicket,orderListIdcard} from "@/api/user"

const actions = {
    login(context, userInfo) {
        const {username, password, code, pubkey} = userInfo;
        return new Promise((resolve, reject) => {
            login({username: username.trim(), password: password, code: code, pubkey}).then(response => {
                const {data} = response;
                context.commit('SET_TOKEN', data.token);
                context.commit('USER_INFO', data);
                setToken(data.token);
                setUserInfo(data);
                resolve(response)

            }).catch(error => {
                reject(error)
            })
        })
    },
    loginOut() {
        let remove = removeAll();
        return new Promise((resolve, reject) => {
            resolve(remove);
        })
    },
    getTicketPirce(context, data) {
        return new Promise((resolve, reject) => {
            getTicketPirce(data).then(response => {
                resolve(response)
            }).catch(error => {
                reject(error)
            })
        })
    },
    paySubmit(context, data) {
        return new Promise((resolve, reject) => {
            paySubmit(data).then(response => {
                resolve(response)
            }).catch(error => {
                reject(error)
            })
        })
    },
    orderList(context, data) {
        return new Promise((resolve, reject) => {
            orderList(data).then(response => {
                resolve(response)
            }).catch(error => {
                reject(error)
            })
        })
    },
    orderDetail(context, data) {
        return new Promise((resolve, reject) => {
            orderDetail(data).then(response => {
                resolve(response)
            }).catch(error => {
                reject(error)
            })
        })
    },
    refundAll(context, data) {
        return new Promise((resolve, reject) => {
            refundAll(data).then(response => {
                resolve(response)
            }).catch(error => {
                reject(error)
            })
        })
    },
    refundOne(context, data) {
        return new Promise((resolve, reject) => {
            refundOne(data).then(response => {
                resolve(response)
            }).catch(error => {
                reject(error)
            })
        })
    },
    statistics(context, data) {
        return new Promise((resolve, reject) => {
            statistics(data).then(response => {
                resolve(response)
            }).catch(error => {
                reject(error)
            })
        })
    },
    orderListSearch(context, data) {
        return new Promise((resolve, reject) => {
            orderListSearch(data).then(response => {
                resolve(response)
            }).catch(error => {
                reject(error)
            })
        })
    },
    orderListIdcard(context, data) {
        return new Promise((resolve, reject) => {
            orderListIdcard(data).then(response => {
                console.log(data);
                resolve(response)
            }).catch(error => {
                reject(error)
            })
        })
    },
    takeTickets(context, data) {
        return new Promise((resolve, reject) => {
            takeTicket(data).then(response => {
                resolve(response)
            }).catch(error => {
                reject(error)
            })
        })
    },

};
export default actions