<?php
namespace app\handheld\controller;

use app\common\model\ticket\Order as OrderModel;
use app\common\model\ticket\OrderDetail as OrderDetailModel;
use app\common\model\ticket\OrderDetailRights as OrderDetailRightsModel;
use app\common\model\ticket\Rights as TicketRightsModel;
use app\common\model\ticket\WriteOff as WriteOffModel;
use think\facade\Db;
use think\facade\Request;
use think\facade\Validate;
use app\common\model\Seller as SellerModel;
use think\Exception;

class Writeoff extends Base {

    protected $noNeedLogin = ['login'];
    public function do(){
        if (!Request::isPost()) {
            $this->apiError("请求方式错误！");
        }
        $post     = Request::post();
        $validate = Validate::rule([
            'qrcode_str' => 'require',
            'be_id'      => 'require',
            'use_lat'    => 'require',
            'use_lng'    => 'require'
        ]);
        $validate->message([
            'qrcode_str.require' => '参数错误！',
            'be_id.require'      => '参数错误！',
            'be_type.require'    => '核销类型不能为空！',
            'be_type.in'         => '核销类型不存在！',
            'use_lat.require'    => '核销纬度不能为空！',
            'use_lng.require'    => '核销经度不能为空'
        ]);
        if (!$validate->check($post)) {
            $this->apiError($validate->getError());
        }
        //先验证和核销人信息
        $hx_man  = $this->auth->getInfo();

        if (!$hx_man) {
            $this->apiError("您不是核销员！");
        }
        if ($hx_man['type'] != 'ticket') {
            $this->apiError("核销人不允许核销门票！");
        }
        if ($hx_man['status'] != 1) {
            $this->apiError("核销人未通过审核！");
        }
        //2023-09-04 新增ota核销
        if (substr($post['qrcode_str'], -4) === "_ota") {
            $new_qrcode_str = substr($post['qrcode_str'], 0, -4);
            $qrcode_de_str = sys_decryption($new_qrcode_str, "ota");
        }else{
            $qrcode_de_str = sys_decryption($post['qrcode_str'], $post['be_id']);
        }
        if(!is_string($qrcode_de_str)){
            $this->apiError("核销码类型错误！");
        }
        $qrcode_de_arr = explode("&", $qrcode_de_str);
        if (count($qrcode_de_arr) != 3) {
            $this->apiError("核销码长度不符！");
        }

        //判断核销类型是否正确
        // order单次核销所有游客，detail单次核销某个游客 orderrights 核销所有游客的某一个权益，rights 核销指定游客的有一个权益；
        if(!in_array($qrcode_de_arr[0],['order','detail','orderrights','rights','ticket'])){
            $this->apiError("核销码方式不正确！");
        }
        //判断核销码是否过期
        if(in_array($qrcode_de_arr[0],['order','detail','orderrights','rights'])){
            //是这四种才验证时间
            if(intval($qrcode_de_arr[2]) < time()){
                $this->apiError("核销码已过期，刷新后再试！");
            }
        }
        $price = 0;
        $title = '';
        if ($qrcode_de_arr[0] === 'order') {
            $orderInfo = OrderModel::where("trade_no", $qrcode_de_arr[1])->find();
            if (!$orderInfo) {
                $this->apiError("核销的订单不存在！");
            }
            if ($orderInfo['order_status'] != 'paid') {
                $this->apiError("订单状态不允许核销！");
            }
            if ($orderInfo['mch_id'] != $hx_man['mid']) {
                $this->apiError("不允许核销其他商户的门票！");
            }
            //获取该订单的核销权益
            $has_rights = OrderDetailRightsModel::where("order_id",$orderInfo['id'])->find();
            if($has_rights){
                $this->apiError("该订单存在多次核销！");
            }
            //------------
            //验证通过，核销操作
            //--------------
            //获取未核销的门票
            $detail_list = OrderDetailModel::where([["trade_no", "=", $qrcode_de_arr[1]], ['enter_time', '=', 0]])->select();
            if ($detail_list->isEmpty()) {
                $this->apiError("不存在待核销的门票！");
            }
            if ($detail_list[0]['ticket_date'] != date("Y-m-d")) {
                $this->apiError("门票日期不是今天，不允许核销！");
            }
            Db::startTrans();
            try {
                foreach ($detail_list as $item) {
                    $insertData = [
                        'order_detail_id' => $item['id'],
                        'ticket_code'     => $item['ticket_code'],
                        'use_device'      => 'mobile',
                        'create_time'     => time(),
                        'writeoff_id'     => $hx_man['id'],
                        'writeoff_name'   => $hx_man['name'],
                        'use_lat'         => $post['use_lat'],
                        'use_lng'         => $post['use_lng'],
                        'use_address'     => '',
                        'use_ip'          => Request::ip(),
                        'status'          => 1
                    ];
                    WriteOffModel::create($insertData);
                    $item->enter_time = $insertData['create_time'];
                    $item->save();
                    $price+=$item['ticket_price'];
                    $title = $item['ticket_title'];
                }
                $orderInfo->writeoff_tourist_num = $orderInfo->writeoff_tourist_num + count($detail_list);
                $orderInfo->order_status = 'used';
                $orderInfo->save();
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->apiError("核销失败！".$e->getMessage());
            }
            $this->apiSuccess("核销成功！",['number'=>count($detail_list),'price'=>$price,'title'=>$title]);
        } else if ($qrcode_de_arr[0] === 'detail') {
            $detail_info = OrderDetailModel::where("ticket_code", $qrcode_de_arr[1])->find();
            if ($detail_info['enter_time'] > 0) {
                $this->apiError("该门票已被核销！");
            }
            $orderInfo = OrderModel::where("trade_no", $detail_info['trade_no'])->find();
            if (!$orderInfo) {
                $this->apiError("核销的订单不存在！");
            }
            if ($orderInfo['order_status'] != 'paid') {
                $this->apiError("订单状态不允许核销！");
            }
            if ($orderInfo['mch_id'] != $hx_man['mid']) {
                $this->apiError("不允许核销其他商户的门票！");
            }
            if ($detail_info['ticket_date'] != date("Y-m-d")) {
                $this->apiError("门票日期不是今天，不允许核销！");
            }
            Db::startTrans();
            try {
                $insertData = [
                    'order_detail_id' => $detail_info['id'],
                    'ticket_code'     => $detail_info['ticket_code'],
                    'use_device'      => 'mobile',
                    'create_time'     => time(),
                    'writeoff_id'     => $hx_man['id'],
                    'writeoff_name'   => $hx_man['name'],
                    'use_lat'         => $post['use_lat'],
                    'use_lng'         => $post['use_lng'],
                    'use_address'     => '',
                    'use_ip'          => Request::ip(),
                    'status'          => 1
                ];
                WriteOffModel::create($insertData);
                $detail_info->enter_time = $insertData['create_time'];
                $detail_info->save();
                $price+=$detail_info['ticket_price'];
                $title = $detail_info['ticket_title'];
                $orderInfo->writeoff_tourist_num = $orderInfo->writeoff_tourist_num+1;
                //检查是否订单全部核销完；
                $count = OrderDetailModel::where([["trade_no", "=", $orderInfo['trade_no'],["refund_status","=","not_refunded"]], ['enter_time', '=', 0]])->count();
                if ($count < 1) {
                    $orderInfo->order_status = 'used';
                }
                $orderInfo->save();
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->apiError("核销失败！".$e->getMessage());
            }
            $this->apiSuccess("核销成功！",['number'=>1,'price'=>$price,'title'=>$title]);
        }else if ($qrcode_de_arr[0] === 'rights') {
            //核销某个游客的某次权益
            $rights_info = OrderDetailRightsModel::where("code", $qrcode_de_arr[1])->find();
            if (!$rights_info) {
                $this->apiError("核销码不正确，记录不存在！");
            }
            $title = $rights_info['rights_title'];
            //验证核验人
            $ticket_rights = TicketRightsModel::where("id",$rights_info['rights_id'])->find();
            if(!empty($ticket_rights['verifier_ids']) && !in_array($hx_man['id'],explode(",",$ticket_rights['verifier_ids']))){
                $this->apiError("该核销员无权限核销！");
            }
            if ($rights_info['writeoff_time'] > 0 || $rights_info['status'] == 1) {
                $this->apiError("该权益已被核销！");
            }
            $detail_info = OrderDetailModel::where("id", $rights_info['detail_id'])->find();
            if (!$detail_info) {
                $this->apiError("核销门票不存在！");
            }
            $orderInfo = OrderModel::where("id", $rights_info['order_id'])->find();
            if (!$orderInfo) {
                $this->apiError("核销的订单不存在！");
            }
            if ($orderInfo['order_status'] != 'paid') {
                $this->apiError("订单状态不允许核销！");
            }
            if ($orderInfo['mch_id'] != $hx_man['mid']) {
                $this->apiError("不允许核销其他商户的门票！");
            }
            if ($detail_info['ticket_date'] != date("Y-m-d")) {
                $this->apiError("门票日期不是今天，不允许核销！");
            }
            if($detail_info['refund_status'] == 'fully_refunded'){
                $this->apiError("该门票已退款，不允许核销！");
            }
            if($detail_info['refund_progress'] == 'pending_review'){
                $this->apiError("该门票正在退款审核中，不允许核销！");
            }
            if($detail_info['refund_progress'] == 'approved' || $detail_info['refund_progress'] == 'completed'){
                $this->apiError("该门票已退，不允许核销！");
            }
            Db::startTrans();
            try {
                $insertData = [
                    'order_detail_id' => $detail_info['id'],
                    'order_detail_right_id' => $rights_info['id'],
                    'ticket_code'     => $detail_info['ticket_code'],
                    'use_device'      => 'mobile',
                    'create_time'     => time(),
                    'writeoff_id'     => $hx_man['id'],
                    'writeoff_name'   => $hx_man['name'],
                    'use_lat'         => $post['use_lat'],
                    'use_lng'         => $post['use_lng'],
                    'use_address'     => '',
                    'use_ip'          => Request::ip(),
                    'status'          => 1
                ];
                WriteOffModel::create($insertData);
                $rights_info->writeoff_time = $insertData['create_time'];
                if($detail_info->enter_time < 1){
                    $detail_info->enter_time = $insertData['create_time'];
                }
                $rights_info->status = 1;
                $rights_info->save();
                $detail_info->writeoff_rights_num = $detail_info->writeoff_rights_num+1;
                $detail_info->save();
                //$price+=$detail_info['ticket_price'];
                //检查这个游客的所有权益是否核销完
                $is_has = OrderDetailRightsModel::where(["detail_id"=>$detail_info['id'],"status"=>0])->find();
                $orderInfo->writeoff_rights_num = $orderInfo->writeoff_rights_num + 1;
                if(!$is_has){
                    //该游客全部核销完毕，核销的游客+1
                    $orderInfo->writeoff_tourist_num = $orderInfo->writeoff_tourist_num+1;
                }
                //检查这个订单的游客是否还存在没有核销够的
                $is_has = OrderDetailModel::where(["trade_no"=>$orderInfo['trade_no'],"refund_status"=>"not_refunded"])->whereColumn("ticket_rights_num",">","writeoff_rights_num")->find();
                if(!$is_has){
                    //该订单全部核销完毕，状态变为已使用
                    $orderInfo->order_status = "used";
                }
                $orderInfo->save();
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->apiError("核销失败！".$e->getMessage());
            }
            $this->apiSuccess("核销成功！",['number'=>1,'price'=>$price,'title'=>$title]);
        }else if ($qrcode_de_arr[0] === 'orderrights') {
            //核销多个游客的某个权益
            $order_sn_rights_id = explode("_",  $qrcode_de_arr[1]);
            if(count($order_sn_rights_id) != 2){
                $this->apiError("核销码错误！");
            }
            $order_sn = $order_sn_rights_id[0];
            $rights_id = $order_sn_rights_id[1];
            $orderInfo = OrderModel::where("trade_no", $order_sn)->find();
            if (!$orderInfo) {
                $this->apiError("核销的订单不存在！");
            }
            if ($orderInfo['order_status'] != 'paid') {
                $this->apiError("订单状态不允许核销！");
            }
            if ($orderInfo['mch_id'] != $hx_man['mid']) {
                $this->apiError("不允许核销其他商户的门票！");
            }
            //获取所有游客某个权益未核销的
            //先获取正常的门票
            $order_detail_ids = OrderDetailModel::where("trade_no",$orderInfo['trade_no'])->where(function($query){
                $query->where('refund_progress','init')->whereOr('refund_progress','refuse');
            })->column("id");
            $rights_list = OrderDetailRightsModel::where(["order_id"=>$orderInfo['id'],"rights_id"=>$rights_id,"status"=>0])->whereIn("detail_id",$order_detail_ids)->select();
            if ($rights_list->isEmpty()) {
                $this->apiError("不存在待核销的权益！");
            }
            foreach ($rights_list as $item) {
                if($item['detail_date'] != date("Y-m-d")){
                    $this->apiError("门票日期不是今天，不允许核销！");
                }
                $title = $item['rights_title'];
            }
            Db::startTrans();
            try {
                foreach ($rights_list as $item) {
                    //验证核验人
                    $ticket_rights = TicketRightsModel::where("id",$item['rights_id'])->find();
                    if(!empty($ticket_rights['verifier_ids']) && !in_array($hx_man['id'],explode(",",$ticket_rights['verifier_ids']))){
                        throw new Exception($item["rights_title"].",您无核销权限！");
                    }
                    $insertData = [
                        'order_detail_id' => $item['detail_id'],
                        'order_detail_rights_id'=>$item['id'],
                        'ticket_code'     => $item['detail_code'],
                        'use_device'      => 'mobile',
                        'create_time'     => time(),
                        'writeoff_id'     => $hx_man['id'],
                        'writeoff_name'   => $hx_man['name'],
                        'use_lat'         => $post['use_lat'],
                        'use_lng'         => $post['use_lng'],
                        'use_address'     => '',
                        'use_ip'          => Request::ip(),
                        'status'          => 1
                    ];

                    WriteOffModel::create($insertData);
                    $item->writeoff_time = $insertData['create_time'];
                    $item->status = 1;
                    $item->save();
                    //订单从表更新
                    $detail_info = OrderDetailModel::where('id',$item['detail_id'])->find();
                    $detail_info->writeoff_rights_num = $detail_info->writeoff_rights_num + 1;
                    if($detail_info->enter_time < 1){
                        $detail_info->enter_time = $insertData['create_time'];
                    }
                    $detail_info->save();
                    //核销权益次数+1
                    $orderInfo->writeoff_rights_num = $orderInfo->writeoff_rights_num+1;

                    //检查当前游客游客是否全部核销完
                    $is_has = OrderDetailRightsModel::where(["order_id"=>$orderInfo['id'],"detail_id"=>$detail_info['id'],"status"=>0])->find();
                    if(!$is_has){
                        //该订单全部核销完毕，状态变为已使用
                        $orderInfo->writeoff_tourist_num = $orderInfo->writeoff_tourist_num + 1;
                    }
                }
                //检查这个订单的游客是否还存在没有核销够的
                $is_has = OrderDetailModel::where(["trade_no"=>$orderInfo['trade_no'],"refund_status"=>"not_refunded"])->whereColumn("ticket_rights_num",">","writeoff_rights_num")->find();
                if(!$is_has){
                    //该订单全部核销完毕，状态变为已使用
                    $orderInfo->order_status = "used";
                }
                $orderInfo->save();
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->apiError("核销失败！".$e->getMessage());
            }
            $this->apiSuccess("核销成功！",['number'=>count($rights_list),'price'=>$price,'title'=>$title]);
        }else if ($qrcode_de_arr[0] === 'ticket') {
            //核销订单里某个票种的所有游客
            $order_ota_2 = explode("_",  $qrcode_de_arr[1]);
            if(count($order_ota_2) != 2){
                $this->apiError("核销码错误！");
            }
            $out_trade_no = $order_ota_2[0];
            $ticket_id = $order_ota_2[1];
            $orderInfo = OrderModel::where("out_trade_no", $out_trade_no)->find();
            if (!$orderInfo) {
                $this->apiError("核销的订单不存在！");
            }
            if ($orderInfo['order_status'] != 'paid') {
                $this->apiError("订单状态不允许核销！");
            }
            if ($orderInfo['mch_id'] != $hx_man['mid']) {
                $this->apiError("不允许核销其他商户的门票！");
            }
            //获取所有游客某个权益未核销的
            //先获取正常的门票
            $order_detail_list = OrderDetailModel::where("trade_no",$orderInfo['trade_no'])->where("ticket_id",$ticket_id)->where(function($query){
                $query->where('refund_progress','init')->whereOr('refund_progress','refuse');
            })->select();
            if ($order_detail_list->isEmpty()) {
                $this->apiError("不存在待核销的权益！");
            }
            foreach ($order_detail_list as $item) {
                if($item['ticket_date'] != date("Y-m-d")){
                    $this->apiError("门票日期不是今天，不允许核销！");
                }
                if($item['enter_time'] > 0){
                    $this->apiError("{$item['tourist_fullname']}游客已核销！");
                }
            }
            Db::startTrans();
            try {
                foreach ($order_detail_list as $item) {

                    $insertData = [
                        'order_detail_id' => $item['id'],
                        'ticket_code'     => $item['out_trade_no'],
                        'use_device'      => 'mobile',
                        'create_time'     => time(),
                        'writeoff_id'     => $hx_man['id'],
                        'writeoff_name'   => $hx_man['name'],
                        'use_lat'         => $post['use_lat'],
                        'use_lng'         => $post['use_lng'],
                        'use_address'     => '',
                        'use_ip'          => Request::ip(),
                        'status'          => 1
                    ];
                    WriteOffModel::create($insertData);
                    $item->enter_time = $insertData['create_time'];
                    //订单权益表更新
                    //将权益也一并核销了
                    $rights_number = OrderDetailRightsModel::where(["order_id"=>$orderInfo['id'],"detail_id"=>$item['id']])->update(['status'=>1,'writeoff_time'=>$insertData['create_time']]);
                    //核销权益次数增加

                    $item->writeoff_rights_num += $rights_number !== false ? $rights_number : 0;
                    $item->save();
                    $orderInfo->writeoff_tourist_num = $orderInfo->writeoff_tourist_num+1;
                    $title = $item["ticket_title"];
                    $price += $item["ticket_price"];
                }
                //检查这个订单的游客是否还存在没有核销够的
                $is_has = OrderDetailModel::where(["trade_no"=>$orderInfo['trade_no'],"refund_status"=>"not_refunded"])->whereColumn("ticket_rights_num",">","writeoff_rights_num")->find();
                if(!$is_has){
                    //该订单全部核销完毕，状态变为已使用
                    $orderInfo->order_status = "used";
                }
                $orderInfo->save();
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->apiError("核销失败！".$e->getMessage());
            }
            //进行核销回调通知
            $order_ota_info = \app\xc\model\OrderOtaModel::where("out_trade_no",$orderInfo["out_trade_no"])->find();
            if($order_ota_info && $order_ota_info['channel'] == 'xc'){
                //进行携程通知
                \app\xc\service\NoticeService::OrderConsumedNotice($orderInfo,count($order_detail_list));
            }
            $this->apiSuccess("核销成功！",['number'=>count($order_detail_list),'price'=>$price,'title'=>$title,'id'=>$orderInfo['id']]);
        }
        $this->apiError("参数错误！");
    }
    public function getList(){
        $page = Request::get("page/d",1);
        $page = max($page,1);
        $pageSize = Request::get("pageSize/d",10);
        $list = WriteOffModel::with(['detail','rights'])->where("writeoff_id",$this->auth->id)->page($page,$pageSize)->order("id desc")->select();
        $list->visible(['detail' => ['tourist_fullname','tourist_mobile','ticket_rights_num','writeoff_rights_num','ticket_title','ticket_date','ticket_price'],'rights' => ['rights_title']])->toArray();
        $this->apiSuccess("获取成功！",$list);
    }
    public function getData(){
        $data['today_total'] = WriteOffModel::where("writeoff_id",$this->auth->id)->whereTime("create_time","today")->count();
        $data['yesterday_total'] = WriteOffModel::where("writeoff_id",$this->auth->id)->whereTime("create_time","yesterday")->count();
        $data['total'] = WriteOffModel::where("writeoff_id",$this->auth->id)->count();
        $data['mobile'] = SellerModel::where("id",$this->auth->mid)->value("mobile");
        $this->apiSuccess("获取成功！",$data);
    }
}