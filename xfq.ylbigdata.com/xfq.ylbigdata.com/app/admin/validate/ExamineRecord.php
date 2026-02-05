<?php
/**
 * 后台审核商户记录验证器
 * @author slomoo <1103398780@qq.com> 2022/08/15
 */
namespace app\admin\validate;

use think\Validate;

class ExamineRecord extends Validate
{
    protected $rule = [
        'step|审核阶段' => [
            'require' => 'require',
            'max' => '10',
            'number' => 'number',
        ],
        'admin_id|审核人' => [
            'require' => 'require',
            'max' => '10',
            'number' => 'number',
        ],
        'group_id|角色' => [
            'require' => 'require',
            'max' => '10',
            'number' => 'number',
        ],
        'sid|类型ID' => [
            'require' => 'require',
            'max' => '10',
            'number' => 'number',
        ],
        'tags|审核类型' => [
            'require' => 'require',
            'number' => 'number',
        ]
    ];
}