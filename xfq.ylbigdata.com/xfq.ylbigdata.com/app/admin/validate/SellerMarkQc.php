<?php
/**
 * 商户打卡管理验证器
 * @author xuemm 2024/01/15
 */
namespace app\admin\validate;

use think\Validate;

class SellerMarkQc extends Validate
{
    protected $rule = [
        'seller_id|商户id' => [
            'require' => 'require',
            'max' => '11',
        ],
        'day_threshold_value|每日打卡阈值' => [
            'require' => 'require',
            'max' => '11',
            'number' => 'number',
        ],
        'range|打卡范围' => [
            'require' => 'require',
            'max' => '11',
            'number' => 'number',
        ],
    ];
}