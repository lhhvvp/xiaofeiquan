<?php
/**
 * 后台审核商户记录模型
 * @author slomoo <1103398780@qq.com> 2022/08/15
 */
namespace app\common\model;

class ExamineRecord extends Base
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