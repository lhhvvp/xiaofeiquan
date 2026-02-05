<?php
/**
 * 门票模型
 * @author slomoo <1103398780@qq.com> 2022/07/26
 */

namespace app\common\model\ticket;

// 引入框架内置类
use app\common\model\Base;

class Price extends Base
{
    protected $table = 'tp_ticket_price';

    
    public function ticket()
    {
        return $this->belongsTo('Ticket', 'ticket_id');
    }
}