<?php

namespace app\xc\model;
use think\Model;
use app\common\model\ticket\Order;

class OrderOtaItemModel extends Model
{
    protected $table = 'tp_ticket_order_ota_item';



    public function getQrcodeStrAttr($value,$data){
        //生成核销二维码的字符串
        //解密的key给固定值，否则无法解密
        //$order_info = Order::where("out_trade_no",$data["out_trade_no"])->findOrEmpty()->toArray();
        $qrcode = sys_encryption(("ticket&".$data['out_trade_no']."_".$data['ticket_id']."&0"),"ota")."_ota";
        $qrcode_arr = [
            "type"=>"order",
            "qrcode_str"=>$qrcode,
            "be_id"=>"ota",
            //"id"=>$order_info->id,
            "use_lat"=>1,
            "use_lng"=>1
        ];
        $qrcode_str = json_encode($qrcode_arr);
        return $qrcode_str;
    }
}