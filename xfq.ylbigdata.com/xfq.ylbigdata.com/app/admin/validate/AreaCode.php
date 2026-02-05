<?php
/**
 * 区域code验证器
 * @author slomoo <1103398780@qq.com> 2022/10/19
 */
namespace app\admin\validate;

use think\Validate;

class AreaCode extends Validate
{
    protected $rule = [
        'areaCode|areaCode' => [
            'max' => '11',
            'number' => 'number',
        ],
        'province|province' => [
            'max' => '50',
        ],
        'city|city' => [
            'max' => '50',
        ],
        'district|district' => [
            'max' => '50',
        ],
        'detail|detail' => [
            'max' => '100',
        ]
    ];
}