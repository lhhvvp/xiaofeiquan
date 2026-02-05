<?php
/**
 * 旅行团管理验证器
 * @author slomoo <1103398780@qq.com> 2022/08/14
 */
namespace app\travel\validate;

use think\Validate;

class Tour extends Validate
{
    protected $rule = [
        'sort|排序' => [
            'max' => '8',
            'number' => 'number',
        ],
        'status|状态' => [
            'max' => '1',
        ],
        'name|旅行团名称' => [
            'require' => 'require',
        ],
        'no|团号' => [
            'require' => 'require',
        ],
        'term|团期' => [
            'require' => 'require',
        ],
        'numbers|人数' => [
            'require' => 'require',
            'number' => 'number',
        ],
        'planner|计调人' => [
            'require' => 'require',
        ],
        'mobile|联系电话' => [
            'require' => 'require',
        ],
        'line_info|线路信息' => [
            'require' => 'require',
        ],
        'travel_id|旅行消费券ID' => [
            'require' => 'require',
            'number' => 'number',
        ],
        /*'spot_ids|景区消费券IDS' => [
            'require' => 'require',
        ],*/
        'mid|旅行团商户ID' => [
            'number' => 'number',
        ]
    ];
}