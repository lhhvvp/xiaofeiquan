<?php
/**
 * 核算审核记录验证器
 * @author slomoo <1103398780@qq.com> 2022/08/05
 */
namespace app\admin\validate;

use think\Validate;

class AuditRecord extends Validate
{
    protected $rule = [
        'sort|排序' => [
            'max' => '8',
            'number' => 'number',
        ],
        'status|状态' => [
            'max' => '1',
            'number' => 'number',
        ],
        'step|审核阶段' => [
            'require' => 'require',
            'number' => 'number',
        ],
        'admin_id|审核人' => [
            'require' => 'require',
            'number' => 'number',
        ],
        'group_id|角色' => [
            'require' => 'require',
            'number' => 'number',
        ],
        'remarks|审核备注' => [
            'require' => 'require',
        ]
    ];
}