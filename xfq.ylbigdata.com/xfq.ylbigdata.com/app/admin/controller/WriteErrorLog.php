<?php
/**
 * 散客-核销-日志控制器
 * @author slomoo <1103398780@qq.com> 2023/07/17
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;

class WriteErrorLog extends Base
{
    // 验证器
    protected $validate = 'WriteErrorLog';

    // 当前主表
    protected $tableName = 'write_error_log';

    // 当前主模型
    protected $modelName = 'WriteErrorLog';
}
