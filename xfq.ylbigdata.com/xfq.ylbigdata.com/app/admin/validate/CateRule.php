<?php
/**
 * 栏目规则权限验证器
 * @author slomoo <1103398780@qq.com> 2022/07/20
 */
namespace app\admin\validate;

use think\Validate;

class CateRule extends Validate
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
        'pid|父ID' => [
            'max' => '10',
            'number' => 'number',
        ],
        'name|控制器/方法' => [
            'max' => '255',
        ],
        'title|权限名称' => [
            'max' => '20',
        ],
        'type|type' => [
            'max' => '1',
            'number' => 'number',
        ],
        'condition|condition' => [
            'max' => '100',
        ],
        'auth_open|验证权限' => [
            'max' => '2',
            'number' => 'number',
        ],
        'icon|图标名称' => [
            'max' => '50',
        ],
        'param|参数' => [
            'max' => '50',
        ]
    ];
}