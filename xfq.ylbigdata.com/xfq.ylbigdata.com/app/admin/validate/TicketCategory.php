<?php
/**
 * 门票分类验证器
 * @author slomoo <1103398780@qq.com> 2023/06/28
 */
namespace app\admin\validate;

use think\Validate;

class TicketCategory extends Validate
{
    protected $rule = [
        'sort|排序' => [
            'require' => 'require',
            'max' => '8',
            'number' => 'number',
        ],
        'status|状态' => [
            'require' => 'require',
            'max' => '1',
        ],
        'seller_id|所属商户' => [
            'max' => '8',
            'number' => 'number',
        ],
        'title|分类名称' => [
            'max' => '255',
        ]
    ];
}