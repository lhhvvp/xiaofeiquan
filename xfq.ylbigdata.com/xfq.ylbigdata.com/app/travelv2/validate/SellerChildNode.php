<?php
/**
 * 子机构列表验证器
 * @author slomoo <1103398780@qq.com> 2022/09/06
 */
namespace app\travel\validate;

use think\Validate;

class SellerChildNode extends Validate
{
    protected $rule = [
        'nickname|机构名称' => [
            'max' => '25',
            'require' => 'require',
        ],
        'name|联系人' => [
            'max' => '30',
            'require' => 'require',
        ],
        'mobile|电话' => [
            'max' => '20',
            'require' => 'require',
        ],
        'address|机构位置' => [
            'require' => 'require',
            'max' => '100',
        ],
        'longitude|经度' => [
            'max' => '10',
        ],
        'latitude|纬度' => [
            'max' => '10',
        ],
        'mid|父级' => [
            'number' => 'number',
        ]
    ];
}