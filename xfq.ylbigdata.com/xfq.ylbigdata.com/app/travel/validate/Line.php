<?php
/**
 * 线路产品验证器
 * @author slomoo <1103398780@qq.com> 2022/09/06
 */
namespace app\travel\validate;

use think\Validate;

class Line extends Validate
{
    protected $rule = [
        'status|状态' => [
            'max' => '1',
        ],
        'merchants_id|商户id' => [
            'max' => '11',
            'number' => 'number',
        ],
        'category_id|分类' => [
            'require' => 'require',
            'max' => '11',
        ],
        'title|名称' => [
            'require' => 'require',
            'max' => '60',
        ],
        'sellpoint|卖点' => [
            'max' => '160',
        ],
        'lineday|线路天数' => [
            'max' => '5',
            'number' => 'number',
        ],
        'linenight|多少晚' => [
            'max' => '5',
            'number' => 'number',
        ],
        'sellprice|价格' => [
            'max' => '11',
        ],
        'startcity|出发城市' => [
            'max' => '200',
        ],
        'overcity|目的城市' => [
            'max' => '200',
        ],
        'linebefore|提前报名天数' => [
            'max' => '11',
            'number' => 'number',
        ],
        'price|报价' => [
            'require' => 'require',
            'max' => '11',
        ],
        'price_date|最新价格时间' => [
            'max' => '11',
            'number' => 'number',
        ],
        'phone|联系电话' => [
            'max' => '20',
        ],
        'images|封面图片' => [
            'require' => 'require',
            'max' => '255',
        ],
        'photo|相册' => [
            'max' => '1000',
        ],
        'photo_count|相册图片数量' => [
            'max' => '3',
            'number' => 'number',
        ],
        'video|短视频' => [
            'max' => '255',
        ],
        'content|行程安排' => [
            'require' => 'require',
        ],
        'notice|注意事项' => [
            'require' => 'require',
        ],
        'feeinclude|费用包含' => [
            'require' => 'require',
        ],
        'access_count|访问次数' => [
            'max' => '11',
            'number' => 'number',
        ],
        'sales_count|销售数量' => [
            'max' => '11',
            'number' => 'number',
        ],
        'recommend|首页推荐（0否, 1是）' => [
            'max' => '1',
            'number' => 'number',
        ],
        'delete_time|是否已删除（0 未删除, 大于0则是删除时间）' => [
            'max' => '11',
        ],
        'tags|线路景点' => [
            'require' => 'require',
            'max' => '50',
        ]
    ];
}