<?php
/**
 * 会员管理模型-目前仅用于商户端核销记录的导出
 * @author slomoo <slomoo@aliyun.com> 2023-09-18
 */
namespace app\common\model;

class UsersModel extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $table = 'tp_users';
}