<?php
/**
 * 门票模型
 * @author slomoo <1103398780@qq.com> 2022/07/26
 */

namespace app\common\model\ticket;

// 引入框架内置类
use app\common\model\Base;

class OrderDetailRights extends Base
{
    protected $table = 'tp_ticket_order_detail_rights';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    /*
     * 权益核销码
     * */
    public function getQrcodeStrAttr($value,$data){
        $str = "rights&".$data['code']."&".(time()+600);
        return sys_encryption($str,$data['id']);
    }
}