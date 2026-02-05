<?php
/**
 * 商户分类控制器
 * @author slomoo <1103398780@qq.com> 2022/07/26
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;

class SellerClass extends Base
{
    // 验证器
    protected $validate = 'SellerClass';

    // 当前主表
    protected $tableName = 'seller_class';

    // 当前主模型
    protected $modelName = 'SellerClass';
}
