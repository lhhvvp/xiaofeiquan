<?php
/**
 * 消费券-订单-消费券控制器
 * @author slomoo <1103398780@qq.com> 2022/10/28
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;

class CouponOrderItem extends Base
{
    // 验证器
    protected $validate = 'CouponOrderItem';

    // 当前主表
    protected $tableName = 'coupon_order_item';

    // 当前主模型
    protected $modelName = 'CouponOrderItem';
}
