<?php
/**
 * 导游生成酒店打卡记录验证器
 * @author slomoo <1103398780@qq.com> 2022/08/31
 */
namespace app\admin\validate;

use think\Validate;

class TourHotelSign extends Validate
{
    protected $rule = [
        'status|状态' => [
            'require' => 'require',
            'max' => '1',
        ],
        'no|记录编号  用于展示' => [
            'max' => '50',
        ],
        'uid|操作人ID' => [
            'max' => '10',
            'number' => 'number',
        ],
        'tid|旅行团ID' => [
            'max' => '10',
            'number' => 'number',
        ],
        'mid|商户ID' => [
            'max' => '10',
            'number' => 'number',
        ],
        'need_numbers|需要打卡数=游客数' => [
            'number' => 'number',
        ],
        'tourist_numbers|游客数' => [
            'number' => 'number',
        ]
    ];
}