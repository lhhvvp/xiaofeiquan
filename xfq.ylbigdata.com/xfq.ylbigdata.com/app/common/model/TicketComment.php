<?php
/**
 * 门票分类模型
 * @author slomoo <1103398780@qq.com> 2023/06/28
 */
namespace app\common\model;

class TicketComment extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';


    public function seller()
    {
        return $this->belongsTo('Seller', 'seller_id');
    }
    public function order()
    {
        return $this->belongsTo('TicketOrder', 'order_id');
    }
    public function users()
    {
        return $this->belongsTo('Users', 'user_id');
    }
}