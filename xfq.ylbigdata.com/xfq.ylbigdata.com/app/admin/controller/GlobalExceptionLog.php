<?php
/**
 * 异常日志表控制器
 * @author slomoo <1103398780@qq.com> 2023/07/21
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;

class GlobalExceptionLog extends Base
{
    // 验证器
    protected $validate = 'GlobalExceptionLog';

    // 当前主表
    protected $tableName = 'global_exception_log';

    // 当前主模型
    protected $modelName = 'GlobalExceptionLog';
}
