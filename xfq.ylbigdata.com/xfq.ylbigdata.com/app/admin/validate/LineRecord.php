<?php
/**
 * 旅投审核线路验证器
 * @author slomoo <1103398780@qq.com> 2022/09/07
 */
namespace app\admin\validate;

use think\Validate;

class LineRecord extends Validate
{
    protected $rule = [
        'status|状态' => [
            'require' => 'require',
            'max' => '1',
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
        'line_id|线路ID' => [
            'require' => 'require',
            'max' => '10',
            'number' => 'number',
        ],
        'image|审核凭据' => [
            'max' => '80',
        ],
        'step|审核阶段' => [
            'require' => 'require',
            'number' => 'number',
        ]
    ];
}