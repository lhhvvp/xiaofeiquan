<?php
/**
 * 验证器
 * @author slomoo <1103398780@qq.com> 2023/07/04
 */
namespace app\admin\validate;

use think\Validate;

class TicketPrice extends Validate
{
    protected $rule = [
        'online_price|线上价' => [
            'max' => '10',
        ],
        'casual_price|散客价' => [
            'max' => '10',
        ],
        'team_price|团体票价' => [
            'max' => '10',
        ],
        'stock|剩余库存' => [
            'max' => '10',
            'number' => 'number',
        ],
        'total_stock|总库存' => [
            'max' => '10',
            'number' => 'number',
        ]
    ];
}