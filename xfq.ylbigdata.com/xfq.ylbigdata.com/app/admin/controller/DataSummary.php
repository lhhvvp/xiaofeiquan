<?php
/**
 * 消费券-数据汇总控制器
 * @author slomoo <1103398780@qq.com> 2023/03/21
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;

class DataSummary extends Base
{
    // 验证器
    protected $validate = 'DataSummary';

    // 当前主表
    protected $tableName = 'data_summary';

    // 当前主模型
    protected $modelName = 'DataSummary';
}
