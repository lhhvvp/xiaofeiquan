<?php
/**
 * 门票验证器
 * @author slomoo <1103398780@qq.com> 2023/06/29
 */
namespace app\admin\validate;

use think\Validate;

class Ticket extends Validate
{
    protected $rule = [
        'sort|排序' => [
            'max' => '8',
            'number' => 'number',
        ],
        'status|状态' => [
            'max' => '1',
        ],
        'seller_id|所属商户' => [
            'require' => 'require',
            'max' => '8',
        ],
        'category_id|所属票种分类' => [
            'require' => 'require',
            'max' => '8',
        ],
        'title|票种名称' => [
            'require' => 'require',
            'max' => '255',
        ],
        'cover|票种封面图' => [
            'max' => '255',
        ],
        'quota|每人每天限购0表示不限' => [
            'max' => '8',
            'number' => 'number',
        ],
        'explain_use|使用说明' => [
            'require' => 'require',
        ],
        'explain_buy|购买说明' => [
            'require' => 'require',
        ],
        'crossed_price|划线价' => [
            'require' => 'require',
            'max' => '10',
        ]
    ];
}