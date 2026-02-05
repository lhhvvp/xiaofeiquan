<?php
/**
 * 线路分类验证器
 * @author slomoo <1103398780@qq.com> 2022/09/05
 */
namespace app\admin\validate;

use think\Validate;

class LineCategory extends Validate
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
        'pid|父id' => [
            'max' => '11',
            'number' => 'number',
        ],
        'icon|图标' => [
            'require' => 'require',
            'max' => '100',
        ],
        'name|名称' => [
            'require' => 'require',
            'max' => '60',
        ],
        'describe|描述' => [
            'max' => '255',
        ],
        'bg_color|css背景色值' => [
            'max' => '30',
        ]
    ];
}