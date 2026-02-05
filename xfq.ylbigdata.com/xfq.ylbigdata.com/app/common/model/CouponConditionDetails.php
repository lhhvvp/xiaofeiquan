<?php
/**
 * 消费券规则模型
 * @author xuemm 2024/01/08
 */
namespace app\common\model;

// 引入框架内置类
use think\facade\Request;

// 引入构建器
use app\common\facade\MakeBuilder;

class CouponConditionDetails extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';

    // 一对一获取所属模块
    public function sellerClass()
    {
        return $this->belongsTo('SellerClass', 'class_id')->field('id,class_name');
    }

    // 获取列表
    public static function getList(array $where = [], int $pageSize = 0, array $order = ['create_time' => 'desc'])
    {
        if ($pageSize) {
            $list = self::where($where)
                ->order($order)
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $pageSize,
                ]);
        } else {
            $list = self::where($where)
                ->order($order)
                ->select();
        }
        
        foreach ($list as $conditionInfo) {
            $sellerClassInfo = SellerClass::find($conditionInfo['class_id']);
            $conditionInfo['class_id'] = $sellerClassInfo['class_name'];
        }

        return $list;
    }

    // 获取列表: 优惠券页面调用
    public static function getConditionList(array $where = [], int $pageSize = 0, array $order = ['id' => 'desc'])
    {
        $model = new static();
        $model = $model->alias($model->getName());

        // 筛选条件
        if ($where) {
            // 当前模型搜索
            $model = $model->where($where);
        }

        $list = $model->order($order)->select();

        foreach ($list as $conditionInfo) {
            $sellerClassInfo = SellerClass::find($conditionInfo['class_id']);
            $conditionInfo['class_id'] = $sellerClassInfo['class_name'];
        }

        return $list;
    }
}