<?php
// 会员分组验证器
namespace app\admin\validate;

use think\Validate;

class UsersType extends Validate
{
    protected $rule = [
        'name|分组名称' => [
            'require' => 'require',
            'max' => '100',
        ],
        'sort|排序' => [
            'require' => 'require',
            'max' => '5',
            'number' => 'number',
        ],
        'status|状态' => [
            'require' => 'require',
        ]
    ];
}