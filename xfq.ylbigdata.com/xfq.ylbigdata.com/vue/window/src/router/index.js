import {createRouter, createWebHistory, createWebHashHistory} from 'vue-router'
import {getToken} from '@/utils/auth'
import Layout from "@/views/Layout"
import Login from "@/views/login"

const routes = [// {path: '/', component: Login},
    {
        path: '/', component: Layout, redirect: '/index', meta: {
            requireAuth: true,  // 添加该字段，表示进入这个路由是需要登录的
        }, children: [{
            path: '/index', name: "index", meta: {
                requireAuth: true,  // 添加该字段，表示进入这个路由是需要登录的
            }, component: () => import("@/views/index"),
        }, {
            path: '/ticket', name: "ticket", meta: {
                requireAuth: true,  // 添加该字段，表示进入这个路由是需要登录的
            }, component: () => import("@/views/ticket"),
        }, {
            path: '/order', name: "refund", meta: {
                requireAuth: true,  // 添加该字段，表示进入这个路由是需要登录的
            }, component: () => import("@/views/refund"),
        }, {
            path: '/statistics', name: "statistics", meta: {
                requireAuth: true,  // 添加该字段，表示进入这个路由是需要登录的
            }, component: () => import("@/views/statistics"),
        }],
    }, {path: '/login', component: Login},
    // {
    //     path: "/:catchAll(.*)", redirect: "/login"
    // }
    // ,
    {
        path: '/:pathMatch(.*)', redirect: '/login'
    }


]

// 3. 创建路由实例并传递 `routes` 配置
// 你可以在这里输入更多的配置，但我们在这里

// const routerHistory = createWebHistory();

const router = createRouter({
    // 4. 内部提供了 history 模式的实现。为了简单起见，我们在这里使用 hash 模式。
    history: createWebHashHistory(), routes, // `routes: routes` 的缩写
});

router.beforeEach((to, from, next) => {
    if (to.meta.requireAuth) {  // 判断该路由是否需要登录权限
        if (getToken()) {  // 从本地存储localStorage获取当前的token是否存在
            next()
        } else {
            next('/login') //如果token不存在，就跳到首页
        }
    } else {
        if (getToken() && to.path === '/') {  //token存在时候，进去登录页面就自动跳转到首页
            next('/index')
        } else {
            next()
        }
    }
});

export default router