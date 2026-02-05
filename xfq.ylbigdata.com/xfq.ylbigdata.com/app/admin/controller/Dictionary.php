<?php
/**
 * 字典控制器
 * @author slomoo <slomoo@aliyun.com> 2022-07-19
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;

class Dictionary extends Base
{
    // 验证器
    protected $validate = 'Dictionary';

    // 当前主表
    protected $tableName = 'dictionary';

    // 当前主模型
    protected $modelName = 'Dictionary';

}
