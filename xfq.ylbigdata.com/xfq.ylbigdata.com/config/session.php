<?php
// +----------------------------------------------------------------------
// | 会话设置
// +----------------------------------------------------------------------

return [
    // session name
    'name'           => 'PHPSESSID', 
    // SESSION_ID的提交变量,解决flash上传跨域
    'var_session_id' => '',
    // 驱动方式 支持file cache
    'type'           => env('session.type', 'cache'),
    // 存储连接标识 当type使用cache的时候有效
    'store'          => env('session.store', 'redis'),
    // 过期时间
    'expire'         => intval(env('session.expire', 86400)),
    // 前缀
    'prefix'         => env('session.prefix', ''),
];
