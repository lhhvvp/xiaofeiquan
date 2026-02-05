<?php
/**
 * 散客核销记录转游客表验证器
 * @author slomoo <1103398780@qq.com> 2022/09/05
 */
namespace app\admin\validate;

use think\Validate;

class TourGuest extends Validate
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
        'coupon_issue_user_id|领取记录ID' => [
            'max' => '10',
            'number' => 'number',
        ],
        'mid|商户名称' => [
            'max' => '10',
            'number' => 'number',
        ],
        'userid|核销人' => [
            'max' => '10',
            'number' => 'number',
        ],
        'orderid|订单ID' => [
            'max' => '10',
            'number' => 'number',
        ],
        'enstr_salt|核销加密串' => [
            'max' => '255',
        ],
        'coupon_title|消费券名称' => [
            'max' => '255',
        ],
        'coupon_price|消费券面额' => [
            'max' => '10',
        ],
        'use_min_price|最低消费多少可使用优惠券' => [
            'max' => '10',
        ],
        'time_start|消费券开启时间' => [
            'max' => '10',
            'number' => 'number',
        ],
        'time_end|消费券结束时间' => [
            'max' => '10',
            'number' => 'number',
        ],
        'qrcode_url|领取二维码图片地址' => [
            'max' => '255',
        ],
        'uuno|消费券编号' => [
            'max' => '50',
        ],
        'coupon_issue_id|消费券ID' => [
            'max' => '10',
            'number' => 'number',
        ],
        'accounting_id|是否核算' => [
            'max' => '10',
            'number' => 'number',
        ],
        'is_allow_settlement|是否允许结算  1=是  0=否' => [
            'max' => '1',
            'number' => 'number',
        ],
        'is_uploads_cert|是否上传保单合同  1=是 0=否' => [
            'max' => '1',
            'number' => 'number',
        ],
        'contract|旅游合同' => [
            'require' => 'require',
        ],
        'insurance|旅游保单' => [
            'require' => 'require',
        ],
        'is_transfer|是否转移' => [
            'number' => 'number',
        ]
    ];
}