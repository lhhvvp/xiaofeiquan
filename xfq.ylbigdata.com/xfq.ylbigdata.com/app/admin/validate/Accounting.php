<?php
/**
 * 消费券核算表验证器
 * @author slomoo <1103398780@qq.com> 2022/08/03
 */
namespace app\admin\validate;

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
        ]
    ];
}