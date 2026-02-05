<?php
/**
 * 商户核验人管理验证器
 * @author slomoo <1103398780@qq.com> 2022/08/01
 */
namespace app\seller\validate;

use think\Validate;

class TicketUser extends Validate
{
    protected $rule = [
        'status|状态' => [
            'require' => 'require',
            'max' => '1',
        ],
        'username|账号' => [
            'require' => 'require',
            'max' => '25',
            'min' => '4',
        ],
        'password|密码' => [
            'require' => 'require',
            'max' => '50',
            'min' => '5',
        ],
        'trust_agreement|核验人诚信协议' => [
            'require' => 'require',
        ],
        'idcard_front_back|身份证正反面' => [
            'require' => 'require',
        ],
        'name|姓名' => [
            'require' => 'require',
        ],
        'mobile|电话' => [
            'require' => 'require',
        ]
    ];
}