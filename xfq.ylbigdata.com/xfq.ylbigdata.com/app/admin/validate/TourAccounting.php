<?php
/**
 * 旅行社消费券核算表验证器
 * @author slomoo <1103398780@qq.com> 2022/08/22
 */
namespace app\admin\validate;

use think\Validate;

class TourAccounting extends Validate
{
    protected $rule = [
        'sort|排序' => [
            'require' => 'require',
            'max' => '8',
            'number' => 'number',
        ],
        'status|状态' => [
            'require' => 'require',
            'max' => '1',
        ],
        'data_detail|旅行社结算申请资料明细' => [
            'max' => '200',
        ],
        'write_off_ids|核销记录ID' => [
            'max' => '255',
        ],
        'mid|商户ID' => [
            'max' => '10',
            'number' => 'number',
        ],
        'sum_coupon_price|核算金额' => [
            'max' => '10',
        ],
        'writeoff_total|核算记录数' => [
            'max' => '10',
            'number' => 'number',
        ],
        'card_name|收款账号' => [
            'max' => '255',
        ],
        'card_deposit|开户行' => [
            'max' => '255',
        ],
        'cart_number|卡号' => [
            'max' => '255',
        ],
        'sup_status|监管单位审核  1=通过  0=待审核  2=不通过' => [
            'max' => '1',
            'number' => 'number',
        ],
        'back_status|银行打款        1=已付款  0=待付款  2=拒绝付款' => [
            'max' => '1',
            'number' => 'number',
        ],
        'sup_card|监管单位上传附件地址' => [
            'max' => '200',
        ],
        'back_card|银行打款凭据地址' => [
            'max' => '200',
        ],
        'class_id|商家分类' => [
            'max' => '10',
            'number' => 'number',
        ],
        'area|所属区域' => [
            'max' => '10',
            'number' => 'number',
        ],
        'nickname|商户名称' => [
            'max' => '255',
        ]
    ];
}