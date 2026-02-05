<?php
/**
 * 门票模型
 * @author slomoo <1103398780@qq.com> 2022/07/26
 */

namespace app\common\model\ticket;

// 引入框架内置类
use app\common\model\Base;
use app\common\model\ticket\Order as OrderModel;
use app\common\model\ticket\OrderDetailRights as OrderDetailRightsModel;
use app\common\model\ticket\Price as PriceModel;
use app\common\model\TicketRefunds as TicketRefundsModel;
use app\common\model\ticket\OrderDetail as OrderDetailModel;
use think\facade\Db;
use think\Exception;
use think\facade\Request;

class Order extends Base
{
    protected $table = 'tp_ticket_order';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    public static function getChannelList(){
        return [
            'online'=>'线上',
            'window'=>'窗口',
            'ota_xc'=>'携程',
            'ota_mt'=>'美团',
            'travel'=>'旅行社'
        ];
    }
    public static function getOrderStatusList(){
        return [
            'created'=>'待支付',
            'paid'=>'已付款',
            'used'=>'已使用',
            'cancelled'=>'已取消',
            'refunded'=>'已退款'
        ];
    }
    public static function getPaymentStatusList(){
        return [
            '1'=>'已支付',
            '0'=>'未支付',
            '2'=>'已退款'
        ];
    }
    public static function getRefundStatusList(){
        return [
            'not_refunded'=>'未退款',
            'partially_refunded'=>'部分退款',
            'fully_refunded'=>'全部退款'
        ];
    }
    public static function getTypeList()
    {
        return [
            'miniapp'=>'小程序',
            'weixin'  =>'微信',
            'cash'      =>'现金',
            'unionpay'     =>'银联',
            'alipay'         =>'支付宝',
            'ota_xc'=>'携程'
        ];
    }
    // 定义支付渠道字段的访问器
    public function getTypeTextAttr($value,$data)
    {
        $list = self::getTypeList();
        return isset($list[$data['type']]) ? $list[$data['type']] : '-';
    }
    public static function getChannelTextAttr($value,$data){
        $list = self::getChannelList();
        return isset($list[$data['channel']]) ? $list[$data['channel']] : '-';
    }
    public static function getOrderStatusTextAttr($value,$data){
        $list = self::getOrderStatusList();
        return isset($list[$data['order_status']]) ? $list[$data['order_status']] : '-';
    }
    public static function getPaymentStatusTextAttr($value,$data){
        $list = self::getPaymentStatusList();
        return isset($list[$data['payment_status']]) ? $list[$data['payment_status']] : '-';
    }
    public static function getRefundStatusTextAttr($value,$data){
        $list = self::getRefundStatusList();
        return isset($list[$data['refund_status']]) ? $list[$data['refund_status']] : '-';
    }
    public function getIscommentAttr($value,$data){
        $iscomment = false;
        if($data['order_status'] == 'used'){
            $row = Comment::where("order_id",$data['id'])->find();
            if($row){
                $iscomment = true;
            }
        }
        return $iscomment;
    }
    public function getQrcodeStrAttr($value,$data){
        $str = "order&".$data['trade_no'].'&'.(time()+600);
        return sys_encryption($str,$data['id']);
    }
    public function getRightsQrcodeListAttr($value,$data){
        $array = [];
        $rights_list = OrderDetailRightsModel::where("order_id",$data['id'])->select()->toArray();

        if($rights_list){
            $detail_ids = array_unique(array_column($rights_list,"detail_id"));
            $detail_list = OrderDetailModel::whereIn("id",$detail_ids)->column("id,trade_no,refund_status,refund_progress","id");
            foreach($rights_list as $item){
                $new_item = [];
                if(!isset($array[$item['rights_id']])){
                    $new_item['id'] = $data['id'];
                    $new_item['rights_id'] = $item['rights_id'];
                    $new_item['rights_title'] = $item['rights_title'];
                    $new_item['rights_num'] = 0;
                    $new_item['writeoff_num'] = 0;
                    $new_item['qrcode_str'] = sys_encryption(("orderrights&".$data['trade_no']."_".$new_item['rights_id']."&".(time()+600)),$data['id']);
                }else{
                    $new_item = $array[$item['rights_id']];
                }
                if(in_array($detail_list[$item['detail_id']]['refund_progress'],['init','refuse'])){
                    $new_item['rights_num'] +=1;
                }
                if($item['status'] == 1){
                    $new_item['writeoff_num'] +=1;
                }
                $array[$item['rights_id']] = $new_item;
            }
            $array = array_values($array);
        }

        return $array;
    }
    public function getRefundListAttr($value,$data){
        return TicketRefundsModel::where([["trade_no","=", $data['trade_no']],["order_detail_no","=","0"]])->append(['status_text'])->order("id desc")->select()->toArray();
    }
    public function seller(){
        return $this->belongsTo("\app\common\model\Seller", 'mch_id');
    }



    //取消OTA订单
    public static function cancelOtaOrder($otaOrderId = ""){
        $result = [
            'code'=>'0000',
            'msg'=>'取消成功'
        ];
        try {
            $order_ota_info = Db::name("ticket_order_ota")->where("otaOrderId",$otaOrderId)->find();
            if (!$order_ota_info) {
                $result['code'] = '2001';
                throw new Exception("订单不存在");
            }
            $order_info = self::where("out_trade_no", $order_ota_info['out_trade_no'])->find();

            if ($order_info["refund_status"] != "not_refunded") {
                $result['code'] = '2101';
                throw new Exception("订单存在退款");
            }
            if ($order_info["order_status"] == "used") {
                $result['code'] = '2002';
                throw new Exception("订单已使用");
            }else if ($order_info["order_status"] == "refunded") {
                $result['code'] = '2103';
                throw new Exception("订单已退款");
            }else if ($order_info["order_status"] == "paid") {
                $result['code'] = '2103';
                throw new Exception("订单已支付");
            }else if ($order_info["order_status"] == "cancelled"){
                $result['msg']='订单已取消';
            }else{
                if ($order_info["writeoff_tourist_num"] >0 || $order_info["wirteoff_rights_num"] >0) {
                    $result['code'] = '2103';
                    throw new Exception("订单存在已使用信息");
                }
                $order_info["order_status"] = "cancelled";
                $order_info->save();
                //归还对应库存
                $order_ota_item_list = Db::name("ticket_order_ota_item")->where("ota_id",$order_ota_info['id'])->select();
                foreach($order_ota_item_list as $vv){
                    PriceModel::where(["ticket_id"=>$vv['ticket_id'],"date"=>$vv['date']])->inc("stock",$vv['quantity'])->update();
                }
                Db::commit();
            }
        } catch (\Exception $e) {
            Db::rollback();
            if($result['code']  == '0000'){
                $result['code'] = '2111';
            }
            $result['msg'] = $e->getMessage();
        }
        return $result;
    }



    //退订单
    //$order_sn 传外部订单号。
    //$refund_desc 退款描述
    //$force 强制退单，适用已核销门票，进行强退。
    //$is_refund_amount 是否退钱，默认开启，false表示只退单，不退款。
    public static function refundOrder($order_sn = "",$refund_desc = '',$force = false, $is_refund_amount = true){
        $result = [
            'code'=>1,
            'msg'=>'退单成功！'
        ];
        Db::startTrans();
        try {
            if($order_sn == ""){
                throw new Exception("请传订单号！");
            }
            $order_info = self::where('out_trade_no', $order_sn)->find();
            if (!$order_info) {
                throw new Exception("订单不存在！");
            }
            if($order_info['refund_status']  == 'fully_refunded'){
                throw new Exception('该订单已经全额退款!');
            }else if($order_info['refund_status']  == 'fully_refunded'){
                throw new Exception('该订单已部分退款，请单退!');
            }
            if($order_info['order_status']  == 'created'){
                throw new Exception('未支付订单无法退款!');
            }else if($order_info['order_status']  == 'created'){
                throw new Exception('已使用订单无法退款!');
            }else if($order_info['order_status']  == 'used'){
                throw new Exception('已取消订单无法退款!');
            }else if($order_info['order_status']  == 'cancelled'){
                throw new Exception('已取消订单无法退款!');
            }else if($order_info['order_status']  == 'refunded'){
                throw new Exception('该订单已经全额退款!');
            }
            //查询该订单下所有门票
            $order_detail_list = OrderDetailModel::where(["trade_no"=>$order_info["trade_no"]])->select();
            if($order_detail_list->isEmpty()){
                throw new Exception('该订单下不存在门票!');
            }
            //再次判断门票
            foreach($order_detail_list as $item){
                if($item['refund_status'] == 'fully_refunded'){
                    throw new Exception('该订单下的门票已有退款，请使用单退功能!');
                }
                if(!$force && $item['enter_time'] > 0){
                    throw new Exception('该订单下的又门票已使用，请使用单退功能!');
                }
            }

            //全部验证完毕  开始执行退款
            //先生成退款单号
            $out_refund_no = uniqidDate(20,'BIG');
            //全退生成一条退款记录
            $refundData = [
                'uuid'          => '',
                'mch_id'        => $order_info['mch_id'],
                'trade_no'      => $order_info['trade_no'],
                'order_detail_no' => '0',
                'out_refund_no' => $out_refund_no,
                'total_fee'     => $order_info['amount_price'],
                'refund_desc'   => $refund_desc,
                'refund_ip'     => Request::ip(),
                'transaction_id' => $order_info['transaction_id'],
                'create_time'   => time()
            ];
            if(!in_array($order_info['channel'],['online','travel','selfservice'])){
                //不是线上，旅行社，自助机来源订单；一律申请金额=0，退款状态=退款失败成功！！
                if($is_refund_amount){
                    throw new Exception('订单来源渠道不符!');
                }
                $refundData['refund_fee'] = '0';
                $refundData['status'] = 1;
            }else{
                if($is_refund_amount){
                    //退单退钱
                    if(!empty($refundData['transaction_id'])){
                        throw new Exception('没找到微信支付单号!');
                    }
                    $refundData['refund_fee'] = $order_info['amount_price'];
                    $refundData['status'] = 0;
                }else{
                    //退单不退钱
                    $refundData['refund_fee'] = '0';
                    $refundData['status'] = 1;
                }
            }
            //先写入退款记录
            $refunds_info = TicketRefundsModel::create($refundData);
            if($refundData['status'] == 1){
                //说明不退钱，无需审核，二话不说，直接退
                $update_data = [
                    'refund_status'=>'fully_refunded',
                    'refund_progress'=>'completed',
                    'refund_time'=>time(),
                    'refund_amount'=>'fully_refunded',
                    'refund_id'=>$refunds_info['id'],
                    'is_full_refund'=>1
                ];
                foreach($order_detail_list as $item){
                    //直接更新
                    $item->save($update_data);
                    //顺便还原下库存 总库存和剩余都需要来一下。
                    Db::name("ticket_price")->where("ticket_id",$item['ticket_id'])->whereTime("date",$item['ticket_date'])->inc("stock")->inc("total_stock")->update();
                }
                //修改订单状态
                $order_info->order_status = 'refunded';
                $order_info->refund_status = 'fully_refunded';
                $order_info->save();
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $result['code'] = 0;
            $result['msg'] = $e->getMessage();
        }
        return $result;
    }


    //单退门票
    //$out_trade_no 传从表的out_trade_no
    //$out_trade_no
    public static function refundTicket($out_trade_no = "",$refund_desc = '',$force = false, $is_refund_amount = true){
        $result = [
            'code'=>1,
            'msg'=>'退票成功！'
        ];
        Db::startTrans();
        try {
            if($out_trade_no == ""){
                throw new Exception("请传订单号！");
            }
            $order_detail_info = OrderDetailModel::where('out_trade_no', $out_trade_no)->find();
            if (!$order_detail_info) {
                throw new Exception("订单不存在！");
            }
            if($order_detail_info['refund_status']  == 'fully_refunded'){
                throw new Exception('该门票已退!');
            }
            if(!$force && $order_detail_info['enter_time'] > 0){
                //不是强退，并且已使用，不允许退
                throw new Exception('该门票已使用!');
            }
            //查询订单
            $order_info = self::where("trade_no",$order_detail_info["trade_no"])->find();
            if(!$order_info){
                throw new Exception('该门票订单不存在!');
            }
            if($order_info['refund_status']  == 'fully_refunded'){
                throw new Exception('该门票订单已全额退款，无需退票!');
            }
            if($order_info['order_status']  == 'created'){
                throw new Exception('未支付订单无法退款!');
            }else if($order_info['order_status']  == 'used'){
                throw new Exception('已取消订单无法退款!');
            }else if($order_info['order_status']  == 'cancelled'){
                throw new Exception('已取消订单无法退款!');
            }else if($order_info['order_status']  == 'refunded'){
                throw new Exception('该订单已经全额退款!');
            }

            //全部验证完毕  开始执行退款
            //先生成退款单号
            $out_refund_no = uniqidDate(20,'BIG');
            //全退生成一条退款记录
            $refundData = [
                'uuid'          => '',
                'mch_id'        => $order_info['mch_id'],
                'trade_no'      => $order_info['trade_no'],
                'order_detail_no' => $order_detail_info['out_trade_no'],
                'out_refund_no' => $out_refund_no,
                'total_fee'     => $order_info['amount_price'],
                'refund_desc'   => $refund_desc,
                'refund_ip'     => Request::ip(),
                'transaction_id' => $order_info['transaction_id'],
                'create_time'   => time()
            ];
            if(!in_array($order_info['channel'],['online','travel','selfservice'])){
                //不是线上，旅行社，自助机来源订单；一律申请金额=0，退款状态=退款失败成功！！
                if($is_refund_amount){
                    throw new Exception('订单来源渠道不符!');
                }
                $refundData['refund_fee'] = '0';
                $refundData['status'] = 1;
            }else{
                if($is_refund_amount){
                    //退单退钱
                    if(!empty($refundData['transaction_id'])){
                        throw new Exception('没找到微信支付单号!');
                    }
                    $refundData['refund_fee'] = $order_info['amount_price'];
                    $refundData['status'] = 0;
                }else{
                    //退单不退钱
                    $refundData['refund_fee'] = '0';
                    $refundData['status'] = 1;
                }
            }
            //先写入退款记录
            $refunds_info = TicketRefundsModel::create($refundData);
            if($refundData['status'] == 1){
                //说明不退钱，无需审核，二话不说，直接退
                $update_data = [
                    'refund_status'=>'fully_refunded',
                    'refund_progress'=>'completed',
                    'refund_time'=>time(),
                    'refund_amount'=>'fully_refunded',
                    'refund_id'=>$refunds_info['id'],
                    'is_full_refund'=>0
                ];
                $order_detail_info->save($update_data);
                //顺便还原下库存 总库存和剩余都需要来一下。
                Db::name("ticket_price")->where("ticket_id",$order_detail_info['ticket_id'])->whereTime("date",$order_detail_info['ticket_date'])->inc("stock")->inc("total_stock")->update();
                //修改订单状态
                //查一下是否还存在未退的
                $is_has = OrderDetailModel::where(["trade_no"=>$order_info["trade_no"],"refund_status"=>"not_refunded"])->find();
                if($is_has){
                    //还存在，就是部分退，订单状态不做调整
                    $order_info->refund_status = 'partially_refunded';
                    //排除已退的还有没有未核销的
                    $has_hx = OrderDetailModel::where(["enter_time"=>0,"refund_status"=>"not_refunded"])->find();
                    if(!$has_hx){
                        $order_info->order_status = 'used';
                    }
                }else{
                    $order_info->order_status = 'refunded';
                    $order_info->refund_status = 'fully_refunded';
                }
                $order_info->save();
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $result['code'] = 0;
            $result['msg'] = $e->getMessage();
        }
        return $result;
    }
}