<?php
/**
 * 审核类型验证器
 * @author slomoo <1103398780@qq.com> 2022/09/08
 */
namespace app\admin\validate;

use think\Validate;

class FlowType extends Validate
{
    protected $rule = [
        'status|状态' => [
            'max' => '1',
        ],
        'title|审核名称' => [
            'require' => 'require',
            'max' => '100',
        ],
        'name|审核标识' => [
            'require' => 'require',
            'max' => '100',
        ],
        'icon|图标' => [
            'max' => '255',
        ],
        'sort|排序' => [
            'number' => 'number',
        ]
    ];
}