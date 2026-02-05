<?php
/**
 * 游客信息表验证器
 * @author slomoo <1103398780@qq.com> 2022/08/16
 */
namespace app\admin\validate;

use think\Validate;

class Tourist extends Validate
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
        'name|姓名' => [
            'require' => 'require',
        ],
        'mobile|电话' => [
            'require' => 'require',
        ],
        'idcard|身份证号' => [
            'require' => 'require',
        ],
        'tid|旅行团ID' => [
            'require' => 'require',
            'number' => 'number',
        ],
        'mid|旅行社商户ID' => [
            'require' => 'require',
            'number' => 'number',
        ]
    ];
}