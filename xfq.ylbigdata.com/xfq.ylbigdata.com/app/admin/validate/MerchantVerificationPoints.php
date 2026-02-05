<?php
/**
 * 商户-核验-点验证器
 * @author slomoo <1103398780@qq.com> 2023/07/10
 */
namespace app\admin\validate;

use think\Validate;

class MerchantVerificationPoints extends Validate
{
    protected $rule = [
        'sort|排序' => [
            'max' => '8',
            'number' => 'number',
        ],
        'status|状态' => [
            'max' => '1',
        ],
        'delete_time|软删除' => [
            'max' => '11',
        ],
        'title|位置名称' => [
            'require' => 'require',
            'max' => '255',
        ],
        'name|位置负责人' => [
            'require' => 'require',
            'max' => '30',
        ],
        'mobile|负责人电话' => [
            'require' => 'require',
            'max' => '255',
        ],
        'address|具体位置' => [
            'require' => 'require',
            'max' => '255',
        ],
        'mid|商户ID' => [
            'require' => 'require',
            'max' => '10',
            'number' => 'number',
        ],
        'longitude|用户核销时经度' => [
            'require' => 'require',
            'max' => '10',
        ],
        'latitude|用户核销时纬度' => [
            'require' => 'require',
            'max' => '10',
        ]
    ];
}