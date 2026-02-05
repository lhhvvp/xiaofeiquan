<?php
// +----------------------------------------------------------------------
// | OTA景区密钥相关配置
// +----------------------------------------------------------------------

return [
    
    // 携程
    'xiecheng_test' => [
        // 接口帐号
        'accountId'=> '047f579ef36b093f',
        // 接口密钥
        'signKey'  => 'd0a862bc967b8256939f24094f640b36',
        // AES 加密密钥
        'aesKey'   => 'd719fef8bbfce19d',
        // AES 加密初始向量
        'aesIv'    => '1930dccc074dfd83',
        // 订单对接通知接口地址
        'url'      => 'https://ttdopen.ctrip.com/api/order/notice.do'
    ],
    // 携程
    'xiecheng' => [
        // 接口帐号
        'accountId'=> '5931ac6d70f46ed2',
        // 接口密钥
        'signKey'  => 'be8c6b51e5817111a4d7d8757093d4ec',
        // AES 加密密钥
        'aesKey'   => 'bc3cd7f181409eda',
        // AES 加密初始向量
        'aesIv'    => 'ce0e70accadb339f',
        // 订单对接通知接口地址
        'url'      => 'https://ttdentry.ctrip.com/ttd-connect-orderentryapi/supplier/order/notice.do'
    ],
    // 美团
    'meituan' => [
        // 接口密钥 美团分配
        'clientSecret'=> 'pw4user2test@RA',
        // 美团分配
        'partnerID'  => '703',
        // 美团分配
        'clientID'   => '703',
        // 
        'url'        => 'http://rat.config.trip.test.sankuai.com'
    ]
];