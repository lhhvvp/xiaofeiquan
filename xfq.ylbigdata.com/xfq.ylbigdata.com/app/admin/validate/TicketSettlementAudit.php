<?php
/**
 * 门票-结算-审核记录验证器
 * @author slomoo <1103398780@qq.com> 2023/08/16
 */
namespace app\admin\validate;

use think\Validate;

class TicketSettlementAudit extends Validate
{
    protected $rule = [
        'status|状态' => [
            'require' => 'require',
            'max' => '1',
        ],
        'uuno|结算编码' => [
            'max' => '30',
        ],
        'admin_id|审核人' => [
            'max' => '11',
            'number' => 'number',
        ]
    ];
}