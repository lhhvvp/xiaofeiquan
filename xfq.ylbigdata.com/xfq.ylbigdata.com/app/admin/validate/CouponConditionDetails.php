<?php
/**
 * 规则验证器
 * @author xuemm 2024/01/10
 */
namespace app\admin\validate;

use think\Validate;

class CouponConditionDetails extends Validate
{
    protected $rule = [
        'class_id|商户分类' => [
            'require' => 'require',
            'max' => '3',
        ],
        'mark_num|打卡次数' => [
            'require' => 'require',
            'max' => '11',
            'number' => 'number',
        ],
    ];
}