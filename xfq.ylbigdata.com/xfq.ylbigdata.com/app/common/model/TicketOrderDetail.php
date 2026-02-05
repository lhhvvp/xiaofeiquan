<?php
/**
 * 票务-订单-从表模型
 * @author slomoo <1103398780@qq.com> 2023/07/04
 */
namespace app\common\model;

class TicketOrderDetail extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    // 定义退款状态字段的访问器
    public function getRefundStatusTextAttr($value,$data)
    {
        $status = [
            'not_refunded'      =>'未退款',
            'fully_refunded'    =>'已退款'
        ];
        return $status[$data['refund_status']];
    }

    // 定义退款进度字段的访问器
    public function getRefundProgressTextAttr($value,$data)
    {
        $status = [
            'init'     =>'初始化',
            'pending_review'=>'已提交、待审核',
            'refuse'=>'拒绝',
            'approved'=>'通过',
            'completed'=>'完成退款'
        ];
        return $status[$data['refund_progress']];
    }

    public function getRefundTimeTextAttr($value,$data){
        return $data['refund_time'] ? date("Y-m-d H:i:s",$data['refund_time']) : '-';
    }

}