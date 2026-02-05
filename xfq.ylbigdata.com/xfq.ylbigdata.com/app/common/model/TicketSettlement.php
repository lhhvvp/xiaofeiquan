<?php
/**
 * 门票-订单-结算模型
 * @author slomoo <1103398780@qq.com> 2023/08/16
 */
namespace app\common\model;

class TicketSettlement extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    // 定义结算状态字段的访问器
    public function getStatusTextAttr($value,$data)
    {
        $status = [
            'pending'    => '<span class="badge badge-primary">待申请</span>',
            'in_progress'=> '<span class="badge badge-warning">结算中</span>',
            'settled'    => '<span class="badge badge-success">已结算</span>',
            'cancelled'  => '<span class="badge badge-secondary">已取消</span>',
            'exception'  => '<span class="badge badge-danger">异常</span>'
        ];
        return $status[$data['status']];
    }

    // 定义审核状态字段的访问器
    public function getAuditStatusTextAttr($value,$data)
    {
        $status = [
            'uploaded' =>'<span class="badge badge-primary">待上传资料</span>',
            'pending'   =>'<span class="badge badge-warning">待审核</span>',
            'pass'      =>'<span class="badge badge-success">通过</span>',
            'fail'      =>'<span class="badge badge-danger">未通过</span>'
        ];
        return $status[$data['audit_status']];
    }
}