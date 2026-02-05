<?php
/**
 * 商户分类验证器
 * @author slomoo <1103398780@qq.com> 2022/07/26
 */
namespace app\admin\validate;

use think\Validate;

class SellerClass extends Validate
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
        ]
    ];
}