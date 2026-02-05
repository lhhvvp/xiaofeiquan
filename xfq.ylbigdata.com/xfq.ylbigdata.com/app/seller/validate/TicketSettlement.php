<?php
/**
 * 门票核算表验证器
 * @author slomoo <1103398780@qq.com> 2023/08/17
 */
namespace app\seller\validate;

use think\Validate;

class TicketSettlement extends Validate
{
    protected $rule = [
        'sort|排序' => [
            'max' => '8',
            'number' => 'number',
        ],
        'status|状态' => [
            'max' => '1',
        ],
        'mid|商户ID' => [
            'require' => 'require',
            'number' => 'number',
        ],
        'title|结算名称' => [
            'require' => 'require',
            'max' => '100',
            'min' => '6',
        ],
        'remarks|备注信息' => [
            'max' => '200',
        ],
        'card_name|收款名称' => [
            'require' => 'require',
            'max' => '50',
        ],
        'card_deposit|开户银行名称' => [
            'require' => 'require',
            'max' => '80',
        ],
        'cart_number|基本开户账号' => [
            'require' => 'require',
            'number' => 'number',
            'max' => '23',
            'min' => '12',
        ],
        'enstr|订单id串MD5' => [
            'require' => 'require',
            'unique'  => 'ticket_settlement', // 唯一
        ]
    ];
}