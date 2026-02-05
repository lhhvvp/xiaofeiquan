<?php
/**
 * 消费券表验证器
 * @author slomoo <1103398780@qq.com> 2022/07/27
 */
namespace app\admin\validate;

use think\Validate;

class CouponIssue extends Validate
{
    protected $rule = [
        'coupon_title|消费券名称' => [
            'require' => 'require',
            'max' => '50',
        ],
        'sort|排序' => [
            'max' => '8',
            'number' => 'number',
        ],
        'status|状态' => [
            'max' => '10',
        ],
        'cid|所属分类' => [
            'require' => 'require',
            'max' => '3',
        ],
        
        'coupon_icon|消费券图标' => [
            'max' => '100',
        ],
        'start_time|消费券领取开启时间' => [
            'max' => '10',
        ],
        'end_time|消费券领取结束时间' => [
            'max' => '10',
        ],
        'total_count|消费券领取数量' => [
            'require' => 'require',
            'max' => '10',
            'number' => 'number',
        ],
        'remain_count|消费券剩余领取数量' => [
            'max' => '10',
            'number' => 'number',
        ],
        'is_permanent|是否无限张数' => [
            'max' => '1',
            'number' => 'number',
        ],
        'is_del|是否删除' => [
            'max' => '1',
            'number' => 'number',
        ],
        'coupon_price|兑换的消费券面值' => [
            'require' => 'require',
            'max' => '8',
        ],
        'use_min_price|最低消费多少金额可用消费券' => [
            'max' => '8',
        ],
        'product_id|所属商品id 根据消费类型选择不同的商品ID' => [
            'max' => '64',
        ],
        'category_id|分类id 根据消费类型选择不同的分类ID' => [
            'max' => '11',
            'number' => 'number',
        ],
        'class_id|栏目类型 1=通用 2=门票 3=线路 4=商品' => [
            'require' => 'require',
            'max' => '11',
            'number' => 'number',
        ],
        'type|消费券类型 1=通用 2=品类券 3=商品券' => [
            'require' => 'require',
            'max' => '2',
            'number' => 'number',
        ],
        'receive_type|1 手动领取，2 新人券，3赠送券，4会员券' => [
            'max' => '2',
            'number' => 'number',
        ],
        'remark|备注=使用细则' => [
            'require' => 'require',
            'max' => '5000',
        ],
        'last_time|最后修改时间' => [
            'max' => '11',
        ],
        'tag|是否在首页展示  1=是  0=否' => [
            'require' => 'require',
            'max' => '2',
        ],
        'limit_total|单人限制领取数量' => [
            'max' => '2',
            'number' => 'number',
        ],
        'use_store|使用门店' => [
            'require' => 'require',
        ],
        'use_type|核销方式 1=线上 2=线下' => [
            'require' => 'require',
        ],
        'use_type_desc|核销规则描述' => [
            'max' => '5000',
        ],
        'receive_crowd|领取人群 1=全部  2=本地 3=外地' => [
            'require' => 'require',
            'max' => '2',
            'number' => 'number',
        ],
        //'limit_time' => 'require|number|checkStatus:limit_time',
    ];

    protected function checkStatus($value,$rule,$data=[]){
        return true;
    }
}