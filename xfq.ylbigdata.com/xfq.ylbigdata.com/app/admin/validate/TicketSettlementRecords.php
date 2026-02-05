<?php
/**
 * 门票-结算-记录验证器
 * @author slomoo <1103398780@qq.com> 2023/08/16
 */
namespace app\admin\validate;

use think\Validate;

class TicketSettlementRecords extends Validate
{
    protected $rule = [
        'trade_no|订单编码' => [
            'max' => '30',
        ]
    ];
}