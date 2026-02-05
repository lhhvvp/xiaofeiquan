<?php
/**
 * 消费券-订单-内容模型
 * @author slomoo <1103398780@qq.com> 2022/10/28
 */
namespace app\common\model;

class CouponOrder extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    public function users()
    {
        return $this->belongsTo('Users', 'uuid','uuid');
    }
    public function couponOrderItem()
    {
        return $this->belongsTo('CouponOrderItem', 'order_no','order_no');
    }
    

}