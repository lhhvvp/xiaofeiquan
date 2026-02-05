<?php
/**
 * 旅行团团体消费券模型
 * @author slomoo <1103398780@qq.com> 2022/08/18
 */
namespace app\common\model;

class TourCouponGroup extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    public function tour()
    {
        return $this->belongsTo('Tour', 'tid');
    }
    public function couponIssue()
    {
        return $this->belongsTo('CouponIssue', 'coupon_issue_id');
    }
    public function couponClass()
    {
        return $this->belongsTo('CouponClass', 'cid');
    }
    

}