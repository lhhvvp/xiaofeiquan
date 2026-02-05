<?php
// 管理员日志验证器
namespace app\admin\validate;

use think\Validate;

class AdminLog extends Validate
{
    protected $rule = [
        'admin_id|管理员' => [
            'max' => '8',
        ],
        'title|日志标题' => [
            'max' => '100',
        ],
        'ip|操作IP' => [
            'max' => '20',
        ]
    ];
}