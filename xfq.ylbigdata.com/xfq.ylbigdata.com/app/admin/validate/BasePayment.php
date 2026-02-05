<?php
/**
 * 数据-基础-支付验证器
 * @author slomoo <1103398780@qq.com> 2022/10/24
 */
namespace app\admin\validate;

use think\Validate;

class BasePayment extends Validate
{
    protected $rule = [
        'sort|排序' => [
            'max' => '8',
            'number' => 'number',
        ],
        'status|状态' => [
            'require' => 'require',
            'max' => '1',
        ],
        'type|支付类型' => [
            'require' => 'require',
            'max' => '50',
        ],
        'wechat_appid|绑定小程序' => [
            'require' => 'require',
            'max' => '18',
        ],
        'name|支付名称' => [
            'require' => 'require',
            'max' => '100',
        ],
        'cover|支付图标' => [
            'max' => '500',
        ],
        'wechat_mch_key|微信商户密钥' => [
            'require' => 'require',
            'max' => '100',
        ],
        'wechat_mch_id|微信商户号' => [
            'require' => 'require',
            'max' => '20',
        ],
        'deleted|删除状态' => [
            'max' => '1',
            'number' => 'number',
        ]
    ];
}