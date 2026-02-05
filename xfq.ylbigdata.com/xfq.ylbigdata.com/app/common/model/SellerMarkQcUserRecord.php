<?php
/**
 * 商户打卡记录管理模型
 * @author xuemm 2024/01/20
 */
namespace app\common\model;

// 引入框架内置类
use think\facade\Request;

// 引入构建器
use app\common\facade\MakeBuilder;

class SellerMarkQcUserRecord extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';

    // 一对一获取所属模块
    public function sellerMarkQc()
    {
        return $this->belongsTo('SellerMarkQc', 'qc_id');
    }

    // 一对一获取所属模块
    public function users()
    {
        return $this->belongsTo('Users', 'uid')->field('id,sex,mobile,name');
    }

    // 一对一获取所属模块
    public function seller()
    {
        return $this->belongsTo('Seller', 'seller_id')->field('id,class_id,nickname,image');
    }

    // 一对一获取所属模块
    public function couponIssue()
    {
        return $this->belongsTo('CouponIssue', 'coupon_id')->field('id,coupon_title');
    }

    // 一对一获取所属模块
    public function sellerClass()
    {
        return $this->belongsTo('SellerClass', 'class_id')->field('id,class_name');
    }

    // 获取列表
    public static function getList(array $where = [], int $pageSize = 0, array $order = ['id' => 'desc'])
    {
        if ($pageSize) {
            $list = self::where($where)
                ->with(['users','seller','couponIssue'])
                ->order($order)
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $pageSize,
                ]);
        } else {
            $list = self::where($where)
                ->with(['users','seller','couponIssue'])
                ->order($order)
                ->select();
        }
        return MakeBuilder::changeTableData($list, 'SellerMarkQcUserRecord');
    }
}