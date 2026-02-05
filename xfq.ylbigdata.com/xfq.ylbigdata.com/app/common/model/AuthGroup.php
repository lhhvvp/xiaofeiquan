<?php
/**
 * 角色分组模型
 * @author slomoo <slomoo@aliyun.com> 2022-07-19
 */

namespace app\common\model;

// 引入框架内置类
use think\facade\Request;

// 引入构建器
use app\common\facade\MakeBuilder;

class AuthGroup extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

}