<?php
/**
 * 票务-商户-售票员模型
 * @author slomoo <1103398780@qq.com> 2023/08/07
 */
namespace app\common\model;

class TicketUser extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    public function seller()
    {
        return $this->belongsTo('Seller', 'mid');
    }
}