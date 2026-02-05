<?php
/**
 * 消费券核算表验证器
 * @author slomoo <1103398780@qq.com> 2022/08/03
 */
namespace app\seller\validate;

use think\Validate;

class Accounting extends Validate
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
            'number' => 'number',
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
        'mid|商户信息' => [
            'require' => 'require',
        ],
        'write_off_ids|核算记录' => [
            'require' => 'require',
            'unique'  => 'accounting', // 唯一
        ]
    ];
}