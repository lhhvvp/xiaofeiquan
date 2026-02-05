<?php

namespace app\api\listener;

use app\common\model\CouponIssue;

class CouponIssueCheck
{
    public function handle($issue_coupon_ids)
    {
        // 事件监听处理
        CouponIssue::CouponIssueCheck($issue_coupon_ids);
    }
}