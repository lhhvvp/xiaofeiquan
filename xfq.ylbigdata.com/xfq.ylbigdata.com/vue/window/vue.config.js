const {defineConfig} = require('@vue/cli-service')
module.exports = defineConfig({
    transpileDependencies: true,
    lintOnSave: false,
    publicPath: '/window/',
    devServer: {
        proxy: {
            '/window': {    //这里的/api表示的意思是以/api开头的才生效 ->刷下面的重点
                target: 'https://v2.wlxfq.dianfengcms.com/window',
                changOrigin: true,   //如果接口跨域这里就要这个参数配置
                pathRewrite: {
                    '^/window': ''
                }
            },
        }
    },
})
