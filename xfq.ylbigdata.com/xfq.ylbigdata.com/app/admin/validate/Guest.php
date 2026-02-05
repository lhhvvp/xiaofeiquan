<?php
/**
 * 游客表验证器
 * @author slomoo <1103398780@qq.com> 2022/09/08
 */
namespace app\admin\validate;

use think\Validate;

class Guest extends Validate
{
    protected $rule = [
        'status|状态' => [
            'require' => 'require',
            'max' => '1',
        ],
        'openid|小程序openid' => [
            'max' => '255',
        ],
        'name|姓名' => [
            'max' => '255',
        ],
        'headimgurl|微信头像' => [
            'max' => '200',
        ],
        'mobile|手机号' => [
            'max' => '20',
        ],
        'idcard|身份证号' => [
            'max' => '18',
        ],
        'nickname|微信昵称' => [
            'max' => '30',
        ],
        'mid|旅行社ID' => [
            'max' => '10',
            'number' => 'number',
        ],
        'mid_sub|旅行社分支机构' => [
            'max' => '10',
            'number' => 'number',
        ],
        'uid|用户' => [
            'number' => 'number',
        ]
    ];
}