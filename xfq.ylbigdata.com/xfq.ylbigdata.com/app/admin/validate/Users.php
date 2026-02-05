<?php
// 会员管理验证器
namespace app\admin\validate;

use think\Validate;

class Users extends Validate
{
    protected $rule = [
        'email|邮箱' => [
            'max' => '100',
        ],
        'password|密码' => [
            'max' => '100',
        ],
        'sex|性别' => [
            'max' => '1',
        ],
        'last_login_time|最后登录时间' => [
            'max' => '10',
        ],
        'last_login_ip|最后登录IP' => [
            'max' => '15',
        ],
        'qq|QQ' => [
            'max' => '20',
        ],
        'mobile|手机' => [
            'max' => '20',
        ],
        'mobile_validated|手机验证' => [
            'require' => 'require',
            'max' => '3',
        ],
        'email_validated|邮箱验证' => [
            'require' => 'require',
            'max' => '3',
        ],
        'type_id|所属分组' => [
            'require' => 'require',
            'max' => '3',
        ],
        'status|状态' => [
            'require' => 'require',
        ],
        'create_ip|注册IP' => [
            'max' => '15',
        ]
    ];
}