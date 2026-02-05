<?php
/**
 * 核销模型
 *
 */

namespace app\common\model\ticket;

// 引入框架内置类
use app\common\model\Base;
use app\common\model\ticket\OrderDetailRights;
class WriteOff extends Base
{
    protected $table = 'tp_ticket_write_off';

    public function getStatusTextAttr($value,$data){
        $list = [
            '0'=>'失败',
            '1'=>'成功'
        ];
        return isset($list[$data['status']])?$list[$data['status']]:'-';
    }
   /* public function getTitleAttr($value,$data){
        $title = "";
        if($data["order_detail_id"]){
            $title .= OrderDetail::where("id",$data["order_detail_id"])->value("ticket_title") . "-";
        }
        if($data["order_detail_rights_id"]){
            $title .= OrderDetailRights::where("id",$data["order_detail_rights_id"])->value("rights_title");
        }
        return $title;
    }*/
    public function detail(){
        return $this->belongsTo(OrderDetail::class,"order_detail_id");
    }
    public function rights(){
        return $this->belongsTo(OrderDetailRights::class,"order_detail_rights_id");
    }
}