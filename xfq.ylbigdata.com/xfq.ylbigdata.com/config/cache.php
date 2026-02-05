<?php

// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------

return [
    // 默认缓存驱动
    'default' => env('cache.driver', 'redis'),

    // 缓存连接方式配置
    'stores'  => [
        'file' => [
            // 驱动方式
            'type'       => 'File',
            // 缓存保存目录
            'path'       => '',
            // 缓存前缀
            'prefix'     => '',
            // 缓存有效期 0表示永久缓存
            'expire'     => 0,
            // 缓存标签前缀
            'tag_prefix' => 'tag:',
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize'  => [],
        ],
        // Redis缓存配置
        'redis' => [
			// 驱动方式
            'type'       => 'redis',
            'host'       => env('cache.host', '192.168.0.39'),
            'port'       => env('cache.port', 6379),
            'password'   => env('cache.password', 'ESnySaLcgxTe'),
            'select'     => intval(env('cache.select', 0)),
            'timeout'    => env('cache.timeout', 0),
            // 全局缓存有效期（0为永久有效）
            'expire'     => env('cache.expire', 0),
            'persistent' => env('cache.persistent', ''),
            // 缓存前缀
            'prefix'     => env('cache.prefix', 'PRO_'),
            'tag_prefix' => env('cache.tag_prefix', 'tag:'),
            'serialize'  => [],
        ],
        // 更多的缓存连接
    ],
];
