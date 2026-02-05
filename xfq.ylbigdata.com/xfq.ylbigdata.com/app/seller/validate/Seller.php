<?php
/**
 * 商户管理验证器
 * @author slomoo <1103398780@qq.com> 2022/07/22
 */
namespace app\seller\validate;

use think\Validate;

class Seller extends Validate
{
    protected $rule = [
        'status|状态' => [
            'max' => '1',
        ],
        'username|登录账号' => [
            'max' => '25',
        ],
        'password|登录密码' => [
            'max' => '255',
        ],
        'login_time|登录时间' => [
            'max' => '11',
        ],
        'login_ip|登录IP' => [
            'max' => '255',
        ],
        'nickname|商户名称' => [
            'require' => 'require',
            'max' => '25',
        ],
        'image|LOGO图标' => [
            'require' => 'require',
            'max' => '80',
        ],
        'last_login_time|上次登录时间' => [
            'max' => '10',
        ],
        'last_login_ip|上次登录IP' => [
            'max' => '64',
        ],
        'loginnum|登录次数' => [
            'max' => '11',
            'number' => 'number',
        ],
        'mobile|电话' => [
            'require' => 'require',
        ],
        'do_business_time|营业时间' => [
            'require' => 'require',
        ],
        'address|商户位置' => [
            'require' => 'require',
        ],
        'content|商户描述' => [
            'require' => 'require',
        ],
        'class_id|所属分类' => [
            'require' => 'require',
            'max' => '3',
        ],
        'cart_number|银行卡号' => [
            'require' => 'require',
            'number' => 'number',
            'max' => '23',
            'min' => '9',
        ],
        'card_name|收款名称' => [
            'require' => 'require',
        ],
        'card_deposit|开户行' => [
            'require' => 'require',
        ],
        'area|所属区域' => [
            'require' => 'require',
            'number' => 'number',
        ],
        'email|邮箱' => [
            'require' => 'require',
        ],
        'email_validated|邮箱验证' => [
            'number' => 'number',
        ],
        'business_license_set|营业资质' => [
            'require' => 'require',
        ],
        /*'business_license|营业执照地址' => [
            'require' => 'require',
        ]*/
        'longitude|经度' => [
            'require' => 'require',
            'float' => 'float',
            'max' => '180',
            'min' => '1',
        ],
        'latitude|纬度' => [
            'require' => 'require',
            'float' => 'float',
            'max' => '90',
            'min' => '1',
        ],
        'idcard_front|法人身份证正面' => [
            'require' => 'require',
        ],
        'idcard_back|法人身份证反面' => [
            'require' => 'require',
        ],
    ];
}