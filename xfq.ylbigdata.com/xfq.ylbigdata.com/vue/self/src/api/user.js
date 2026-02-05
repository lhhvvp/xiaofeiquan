import request from '@/utils/request'

export function login(data) {
    return request({
        url: '/index/selflogin', method: 'post', data
    });
}

export function paySubmit(data) {
    return request({
        url: '/ticket/submit', method: 'post', data
    });
}

export function getTicketPirce(data) {
    return request({
        url: '/ticket/getTicketPirce', method: 'post', data
    });
}

export function orderList(data) {
    return request({
        url: '/ticket/list', method: 'post', data
    });
}

export function orderDetail(data) {
    return request({
        url: '/ticket/detail', method: 'post', data
    });
}

export function refundAll(data) {
    return request({
        url: '/ticket/refund', method: 'post', data
    });
}

export function refundOne(data) {
    return request({
        url: '/ticket/single_refund', method: 'post', data
    });
}

export function statistics(data) {
    return request({
        url: '/ticket/stats', method: 'post', data
    });
}

export function checkOrderStatus(data) {
    return request({
        url: '/ticket/getTradeNo', method: 'post', data
    });
}

export function orderListSearch(data) {
    return request({
        url: '/ticket/queryOrder', method: 'get', data
    });
}

export function orderListIdcard(data) {
    return request({
        url: '/ticket/queryTourist', method: 'get', data
    });
}

export function takeTicket(data) {
    return request({
        url: '/ticket/takeTicket', method: 'post', data
    });
}

