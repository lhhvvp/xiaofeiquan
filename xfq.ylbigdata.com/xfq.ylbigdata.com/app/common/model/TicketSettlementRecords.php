<?php
/**
 * 门票-结算-记录模型
 * @author slomoo <1103398780@qq.com> 2023/08/16
 */
namespace app\common\model;

class TicketSettlementRecords extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $pk = 'uuno';
    
    public function ticketSettlement()
    {
        return $this->belongsTo('TicketSettlement', 'update_time');
    }
    public function ticketOrder()
    {
        return $this->belongsTo('TicketOrder', 'trade_no');
    }
    

}