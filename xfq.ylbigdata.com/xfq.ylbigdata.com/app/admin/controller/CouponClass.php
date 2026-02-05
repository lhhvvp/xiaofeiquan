<?php
/**
 * 消费券分类控制器
 * @author slomoo <1103398780@qq.com> 2022/07/26
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;

class CouponClass extends Base
{
    // 验证器
    protected $validate = 'CouponClass';

    // 当前主表
    protected $tableName = 'coupon_class';

    // 当前主模型
    protected $modelName = 'CouponClass';
}
