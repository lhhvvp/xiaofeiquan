<?php
/**
 * 商户打卡管理模型
 * @author xuemm 2024/01/15
 */
namespace app\common\model;

// 引入框架内置类
use think\facade\Db;
use think\Model;
use think\facade\Event;
use think\facade\Request;
use think\facade\Session;

// 引入构建器
use app\common\facade\MakeBuilder;

class SellerMarkQc extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 一对一获取所属模块
    public function seller()
    {
        return $this->belongsTo('Seller', 'seller_id');
    }

    // 获取列表
    public static function getRewriteList(array $where = [], int $pageSize = 0, array $order = ['sort', 'id' => 'desc'])
    {
        if ($pageSize) {
            $list = self::with(['Seller' => function ($query) {
                    $query->field('id,class_id,nickname');
                }])
                ->hasWhere("Seller", $where)
                ->order($order)
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $pageSize,
                ]);
        } else {
            $list = self::with(['Seller' => function ($query) {
                    $query->field('id,class_id,nickname');
                }])
                ->hasWhere("Seller", $where)
                ->order($order)
                ->select();
        }

        return MakeBuilder::changeTableData($list, 'CouponIssueUser');
    }
}