<?php
/**
 * 阶段性结算对账单汇总记录=各商家核算申请记录模型
 * @author slomoo <1103398780@qq.com> 2022/09/01
 */
namespace app\common\model;

class VerifyCollect extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    public function sellerClass()
    {
        return $this->belongsTo('SellerClass', 'class_id');
    }
    
    public function admin()
    {
        return $this->belongsTo('Admin', 'admin_id');
    }

    public function authGroup()
    {
        return $this->belongsTo('AuthGroup', 'group_id');
    }
}