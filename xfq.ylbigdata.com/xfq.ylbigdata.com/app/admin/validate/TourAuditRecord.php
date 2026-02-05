<?php
/**
 * 旅行团核算审核记录验证器
 * @author slomoo <1103398780@qq.com> 2022/08/23
 */
namespace app\admin\validate;

use think\Validate;

class TourAuditRecord extends Validate
{
    protected $rule = [
        'sort|排序' => [
            'max' => '8',
            'number' => 'number',
        ],
        'status|状态' => [
            'max' => '1',
        ],
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
        'remarks|审核备注' => [
            'require' => 'require',
        ],
        'image|文件凭据' => [
            'max' => '80',
        ],
        'aid|核算记录ID' => [
            'require' => 'require',
            'max' => '10',
            'number' => 'number',
        ]
    ];
}