<?php
/**
 * 审批流程表验证器
 * @author slomoo <1103398780@qq.com> 2022/09/08
 */
namespace app\admin\validate;

use think\Validate;

class Flow extends Validate
{
    protected $rule = [
        'status|状态' => [
            'max' => '1',
        ],
        'name|审核流名称' => [
            'require' => 'require',
            'max' => '50',
        ],
        'check_type|流类型' => [
            'max' => '4',
            'number' => 'number',
        ],
        'flow_cate|应用审核类型' => [
            'max' => '11',
        ],
        'department_ids|应用于角色' => [
            'max' => '500',
        ],
        'copy_uids|抄送人IDS' => [
            'max' => '500',
        ],
        'remark|审核说明' => [
            'max' => '500',
        ],
        'flow_list|流程数据序列化' => [
            'max' => '1000',
        ],
        'admin_id|创建人ID' => [
            'require' => 'require',
            'max' => '11',
            'number' => 'number',
        ],
        'delete_time|删除时间' => [
            'max' => '11',
        ],
        'delete_user_id|删除人ID' => [
            'max' => '11',
            'number' => 'number',
        ]
    ];
}