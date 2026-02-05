<?php
/**
 * 核销记录验证器
 * @author slomoo <1103398780@qq.com> 2022/07/29
 */
namespace app\admin\validate;

use think\Validate;

class WriteOff extends Validate
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
        'coupon_issue_user_id|领取记录ID' => [
            'require' => 'require',
            'number' => 'number',
        ],
        'mid|商户ID' => [
            'require' => 'require',
            'number' => 'number',
        ],
        'userid|核销人ID' => [
            'require' => 'require',
            'number' => 'number',
        ]
    ];
}