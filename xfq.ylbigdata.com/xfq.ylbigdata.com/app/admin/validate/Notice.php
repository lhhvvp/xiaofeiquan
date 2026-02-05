<?php
/**
 * 通知公告验证器
 * @author slomoo <1103398780@qq.com> 2022/07/21
 */
namespace app\admin\validate;

use think\Validate;

class Notice extends Validate
{
    protected $rule = [
        'title|标题' => [
            'require' => 'require',
        ],
        'sort|排序' => [
            'require' => 'require',
            'max' => '8',
            'number' => 'number',
        ],
        'status|状态' => [
            'require' => 'require',
            'max' => '1',
        ],
        'hits|点击次数' => [
            'number' => 'number',
        ],
        'description|描述' => [
            'max' => '255',
        ],
        'template|模板' => [
            'max' => '30',
        ]
    ];
}