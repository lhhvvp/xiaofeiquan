<?php
/**
 * 阶段性结算对账单模型
 * @author slomoo <1103398780@qq.com> 2022/09/01
 */
namespace app\common\model;

class VerifyAccountingRecord extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    public function admin()
    {
        return $this->belongsTo('Admin', 'admin_id');
    }
    

}