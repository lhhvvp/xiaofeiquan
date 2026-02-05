<?php
/**
 * 旅行团核算审核记录模型
 * @author slomoo <1103398780@qq.com> 2022/08/23
 */
namespace app\common\model;

class TourAuditRecord extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    public function admin()
    {
        return $this->belongsTo('Admin', 'admin_id');
    }
    public function authGroup()
    {
        return $this->belongsTo('AuthGroup', 'group_id');
    }
    

}