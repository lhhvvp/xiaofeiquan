<?php
/**
 * 消费券-订单-内容控制器
 * @author slomoo <1103398780@qq.com> 2022/10/28
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\View;
class CouponOrder extends Base
{
    // 验证器
    protected $validate = 'CouponOrder';

    // 当前主表
    protected $tableName = 'coupon_order';

    // 当前主模型
    protected $modelName = 'CouponOrder';

    // 列表
    public function index()
    {
        // 获取主键
        $pk = MakeBuilder::getPrimarykey($this->tableName);
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();
            $orderByColumn = $param['orderByColumn'] ?? $pk;
            $isAsc         = $param['isAsc'] ?? 'desc';
            $where = [];
            
            if (@$param['order_out_no']!='') {
                $where[] = ['order_out_no','=',$param['order_out_no']];
            }
            if (@$param['payment_status']!='') {
                $where[] = ['payment_status','=',$param['payment_status']];
            }
            if (@$param['deleted_status']!='') {
                $where[] = ['deleted_status','=',$param['deleted_status']];
            }
            $model  = '\app\common\model\\' . $this->modelName;
            return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
        }
        
        return View::fetch('order/index');
    }
}
