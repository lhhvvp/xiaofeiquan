<?php
/**
 * 审批流程表模型
 * @author slomoo <1103398780@qq.com> 2022/09/08
 */
namespace app\common\model;

class Flow extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    public function flowType()
    {
        return $this->belongsTo('FlowType', 'flow_cate');
    }
    public function admin()
    {
        return $this->belongsTo('Admin', 'admin_id');
    }
    

}