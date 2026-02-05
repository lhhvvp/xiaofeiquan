<?php
/**
 * 商户核验人管理验证器
 * @author slomoo <1103398780@qq.com> 2022/08/01
 */
namespace app\admin\validate;

use think\Validate;

class MerchantVerifier extends Validate
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
        'name|姓名' => [
            'require' => 'require',
        ],
        'mobile|电话' => [
            'require' => 'require',
        ]
    ];
}