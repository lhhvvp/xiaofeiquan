<?php
/**
 * 旅投审核线路模型
 * @author slomoo <1103398780@qq.com> 2022/09/07
 */
namespace app\common\model;

class LineRecord extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    public function admin()
    {
        return $this->belongsTo('Admin', 'admin_id')->field('id,nickname');
    }
    public function authGroup()
    {
        return $this->belongsTo('AuthGroup', 'group_id')->field('id,title');
    }
    public function line()
    {
        return $this->belongsTo('Line', 'line_id');
    }
    

}