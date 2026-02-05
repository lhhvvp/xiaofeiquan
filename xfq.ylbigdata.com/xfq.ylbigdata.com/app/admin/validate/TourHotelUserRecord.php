<?php
/**
 * 游客酒店打卡记录验证器
 * @author slomoo <1103398780@qq.com> 2022/08/31
 */
namespace app\admin\validate;

use think\Validate;

class TourHotelUserRecord extends Validate
{
    protected $rule = [
        'sign_id|酒店记录ID' => [
            'max' => '11',
            'number' => 'number',
        ],
        'is_clock|是否完成打卡' => [
            'max' => '1',
            'number' => 'number',
        ],
        'clock_time|打卡时间' => [
            'max' => '10',
        ],
        'spot_name|酒店打卡名称' => [
            'max' => '100',
        ],
        'address|打卡位置' => [
            'max' => '100',
        ],
        'longitude|经度' => [
            'max' => '10',
        ],
        'latitude|纬度' => [
            'max' => '10',
        ],
        'uid|用户ID' => [
            'max' => '10',
            'number' => 'number',
        ],
        'tid|团ID' => [
            'number' => 'number',
        ],
        'guid|导游用户ID' => [
            'number' => 'number',
        ]
    ];
}