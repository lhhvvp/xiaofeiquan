<?php
/**
 * 数据-基础-支付控制器
 * @author slomoo <1103398780@qq.com> 2022/10/24
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;

class BasePayment extends Base
{
    // 验证器
    protected $validate = 'BasePayment';

    // 当前主表
    protected $tableName = 'base_payment';

    // 当前主模型
    protected $modelName = 'BasePayment';
}
