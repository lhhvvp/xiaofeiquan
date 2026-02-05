<?php
/**
 * 导游信息表验证器
 * @author slomoo <1103398780@qq.com> 2022/08/16
 */
namespace app\travel\validate;

use think\Validate;

class Guide extends Validate
{
    protected $rule = [
        'sort|排序' => [
            'max' => '8',
            'number' => 'number',
        ],
        'status|状态' => [
            'max' => '1',
        ],
        'name|姓名' => [
            'require' => 'require',
        ],
        'mobile|电话' => [
            'require' => 'require',
            'mobile'=>'mobile',
        ],
        'certificates|导游证件' => [
            'require' => 'require',
        ],
        'idcard|导游身份证号' => [
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