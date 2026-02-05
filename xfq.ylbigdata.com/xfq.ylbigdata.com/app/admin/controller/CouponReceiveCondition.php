<?php
/**
 * 消费券领取规则控制器
 * @author xuemm 2024/01/07
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

class CouponReceiveCondition extends Base
{
    // 验证器
    protected $validate = 'CouponReceiveCondition';

    // 当前主表
    protected $tableName = 'coupon_receive_condition';

    // 当前主模型
    protected $modelName = 'CouponReceiveCondition';


}
