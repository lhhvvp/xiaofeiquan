<?php
// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------

return [
    // 应用地址
    'app_host'         => env('app.host', ''),
    // 应用的命名空间
    'app_namespace'    => '',
    // 是否启用路由
    'with_route'       => true,
    // 默认应用
    'default_app'      => '',
    // 默认时区
    'default_timezone' => 'Asia/Shanghai',

    // 开启应用快速访问
    'app_express'      => true,
    // 应用映射（自动多应用模式有效）
    'app_map'          => [],
    // 域名绑定（自动多应用模式有效）
    'domain_bind'      => [],
    // 禁止URL访问的应用列表（自动多应用模式有效）
    'deny_app_list'    => [],

    // 异常页面的模板文件
    //'exception_tmpl'   => app()->getThinkPath() . 'tpl/think_exception.tpl',

    'exception_tmpl'   => app()->isDebug()==true ? app()->getThinkPath() . 'tpl/think_exception.tpl':base_path().'sorry.html',
    

    // 错误显示信息,非调试模式有效
    'error_message'    => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'   => false,
    'http_exception_template'=> [
        404 =>base_path().'404.html',
        403 =>base_path().'404.html',
    ],

    //阿里云oss配置
    'aliyun_oss' => [
        'accessKeyId'      => 'LTAI5tPDvSnJe4e3NjGCRuRx',        // Access Key ID
        'accessKeySecret'  => 't87cJnvQvb7MaGgUtQwE1CKPArQfit',  // Access Key Secret
        'endpoint'   => 'oss-cn-wulanchabu.aliyuncs.com',           // 阿里云oss 外网地址endpoint
        'bucket'     => 'cyyl-wlxfq-bd',                                 // Bucket名称
        'url'        => 'https://oss.ylbigdata.com'       // 访问的地址 (可不配置)
    ],

    //私有静态资源服务器
    'slomoo_oss' => [
        'remoteHost'  => '8.142.211.240',  // 远程服务器的主机名或IP地址
        'remotePort'  => 21,
        'remoteUser'  => 'slomoo',                  // 远程服务器的用户名
        'remotePass'  => 'Bedf7c8xcf5cKXBL',        // 远程服务器的密码
        'url'         => 'http://v2.static.slomoo.cn', // 访问的地址
        'timeOut'     => 30 // 连接超时时间 单位 秒
    ],
];
