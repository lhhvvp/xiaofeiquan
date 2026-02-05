<?php
/**
 * 线路产品模型
 * @author slomoo <1103398780@qq.com> 2022/09/07
 */
namespace app\common\model;

class Line extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    public function lineCategory()
    {
        return $this->belongsTo('LineCategory', 'category_id');
    }
    public function seller()
    {
        return $this->belongsTo('Seller', 'mid')->field('id,create_time,update_time,status,login_time,login_ip,last_login_time,last_login_ip,loginnum,mobile,nickname,image,do_business_time,address,content,longitude,latitude,class_id,cart_number,card_name,card_deposit,mtype,credit_code,area,email_validated,email,business_license,permit_foroperation,social_liability_insurance');
    }
    
    // 获取列表: 优惠券页面调用
    public static function getLineList(array $where = [], int $pageSize = 0, array $order = ['id' => 'desc'])
    {
        $model = new static();
        $model = $model->alias($model->getName());

        // 筛选条件
        if ($where) {
            // 当前模型搜索
            $model = $model->where($where);
        }

        $list = $model->order($order)
            ->select();

        return $list;
    }
}