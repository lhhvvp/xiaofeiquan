<?php
/**
 * 旅行团酒店关联表验证器
 * @author slomoo <1103398780@qq.com> 2022/10/17
 */
namespace app\admin\validate;

use think\Validate;

class TourHotel extends Validate
{
    protected $rule = [
        'sort|排序' => [
            'max' => '8',
            'number' => 'number',
        ],
        'status|状态' => [
            'max' => '1',
        ],
        'tid|旅行团ID' => [
            'number' => 'number',
        ]
    ];
}