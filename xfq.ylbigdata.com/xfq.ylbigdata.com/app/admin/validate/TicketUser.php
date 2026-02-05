<?php
/**
 * 票务-商户-售票员验证器
 * @author slomoo <1103398780@qq.com> 2023/08/07
 */
namespace app\admin\validate;

use think\Validate;

class TicketUser extends Validate
{
    protected $rule = [
        'sort|排序' => [
            'require' => 'require',
            'max' => '8',
            'number' => 'number',
        ],
        'status|状态' => [
            'require' => 'require',
            'max' => '1',
        ],
        'delete_time|delete_time' => [
            'max' => '11',
        ],
        'username|用户名' => [
            'require' => 'require',
            'max' => '25',
        ],
        'password|密码' => [
            'require' => 'require',
            'max' => '255',
        ],
        'mobile|手机' => [
            'max' => '20',
        ],
        'login_time|登录时间' => [
            'max' => '11',
        ],
        'login_ip|登录IP' => [
            'max' => '255',
        ],
        'mid|商户名称' => [
            'require' => 'require',
            'max' => '10',
            'number' => 'number',
        ],
        'trust_agreement|售票员诚信协议' => [
            'max' => '200',
        ],
        'idcard_front_back|身份证正反面' => [
            'max' => '200',
        ],
        'loginnum|登录次数' => [
            'max' => '11',
            'number' => 'number',
        ],
        'err_num|错误次数' => [
            'max' => '10',
            'number' => 'number',
        ],
        'lock_time|锁定时间' => [
            'max' => '255',
        ],
        'signpass|passwd token' => [
            'max' => '33',
        ],
        'expiry_time|token过期时间' => [
            'max' => '10',
        ]
    ];
}