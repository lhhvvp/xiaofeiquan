<?php
/**
 * 轮播图验证器
 * @author slomoo <1103398780@qq.com> 2022/07/28
 */
namespace app\admin\validate;

use think\Validate;

class Slide extends Validate
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
        'title|轮播标题' => [
            'require' => 'require',
        ],
        'hits|点击次数' => [
            'number' => 'number',
        ],
        'tags|展示位置' => [
            'require' => 'require',
        ],
        'image|封面图片' => [
            'require' => 'require',
        ]
    ];
}