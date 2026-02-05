<?php
/**
 * 消费券领取记录验证器
 * @author slomoo <1103398780@qq.com> 2022/07/28
 */
namespace app\admin\validate;

use think\Validate;

class CouponIssueUser extends Validate
{
    protected $rule = [
        'uid|用户ID' => [
            'number' => 'number',
        ],
        'issue_coupon_id|消费券ID' => [
            'number' => 'number',
        ]
    ];
}