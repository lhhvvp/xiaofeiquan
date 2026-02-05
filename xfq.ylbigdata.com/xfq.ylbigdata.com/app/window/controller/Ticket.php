<?php
/**
 * @desc   小程序支付API
 * @author slomoo
 * @email slomoo@aliyun.com
 * 2023-06-30 门票支付
 */
declare (strict_types=1);

namespace app\window\controller;

use app\common\model\ticket\Order as OrderModel;
use app\common\model\ticket\OrderDetail;
use app\common\model\ticket\OrderDetailRights as OrderDetailRightsModel;
use app\common\model\ticket\Rights as RightsModel;
use app\common\model\ticket\WriteOff as WriteOffModel;
use app\window\BaseController;
use app\window\middleware\Auth;
use app\window\service\JwtAuth;
use app\common\model\ticket\Order;
use app\common\model\TicketPrice;
use think\Exception;
use think\facade\Db;
use think\facade\Request;
use think\facade\Validate;
use app\common\model\ticket\Ticket as TicketModel;
use app\common\model\ticket\Category as CategoryModel;
use app\common\model\ticket\Price as PriceModel;
use app\common\model\ticket\OrderDetail as OrderDetailModel;
class Ticket extends BaseController
{
    /**
     * 控制器中间件 [不需要鉴权set]
     * @var array
     */

    protected $middleware = [
        Auth::class => ['except' => ['stats']]
    ];

    //初始化
    protected function initialize()
    {
        parent::initialize();
        $this->TicketPay     = new \app\common\model\TicketPay;
        $this->TicketOrder   = new \app\common\model\TicketOrder;
        $this->TicketOrderDetail = new \app\common\model\TicketOrderDetail;
        $this->TicketRefunds = new \app\common\model\TicketRefunds;
    }

    /**
     * @api             {post} /ticket/pay 创建订单
     * @apiDescription  提交订单
     */
    public function pay()
    {
        if (!Request::isPost()) {
            $this->apiError('请求方式错误');
        }
        $ticketData  = Request::param('data/s', '');          // 门票&出行信息【门票：uuno,number,price,出行人信息：fullname、cert_type、cert_id】
        $contactData = Request::param('contact/s', '');       // 订单联系人信息【contact_man,contact_phone】
        $ticket_date = Request::param('ticket_date', '');
        $paytype     = Request::param('paytype/s','');  // 支付方式
        $uuid        = Request::param('uuid/s', '');    // 商户账号
        // 批量非空校验
        $requiredParams = [
            'data'        => $ticketData,
            'contact'     => $contactData,
            'ticket_date' => $ticket_date,
            'paytype'     => $paytype,
            'uuid'        => $uuid
        ];

        if (!isJson($requiredParams['data'])) {
            $this->apiError('data请求的格式不是json');
        }

        if (!isJson($requiredParams['contact'])) {
            $this->apiError('contact请求的格式不是json');
        }
        $contact                         = json_decode($requiredParams['contact'], true);
        $requiredParams['contact_man']   = $contact['contact_man'];
        $requiredParams['contact_phone'] = $contact['contact_phone'];
        //$requiredParams['contact_certno'] = $contact['contact_certno'];
        foreach ($requiredParams as $param => $value) {
            if (empty($value)) {
                $this->apiError($param . '不能为空');
            }
        }

        $userInfo = \app\common\model\TicketUser::where('uuid', $uuid)->find();
        if ($userInfo === NULL) {
            $this->apiError('未找到用户');
        }
        if ($userInfo->status != 1) {
            $this->apiError('当前账户已被锁定');
        }
        // 查询票种所属商户是否可用
        $sellerInfo = \app\common\model\Seller::find($userInfo['mid']);
        if ($sellerInfo === NULL) {
            $this->apiError('该门票所属商户未找到');
        }
        if ($sellerInfo->status != 1) {
            $this->apiError('该商户信息异常');
        }
        // 门票信息
        $ticketData = json_decode($requiredParams['data'], true);
        // 实际要去支付的金额=调起微信支付使用的=所有门票加起来的价格
        $amount_price = "0.00";
        $origin_price = "0.00";
        $total_number = 0;
        //先验证数量
        foreach ($ticketData as $key => $value) {
            $total_number += $value['number'];

            // 计算单张票价
            $signPrice = bcdiv(strval($value['price']), strval($value['number']), 2);
            // 比较单价，防止篡改金额
            $quotation = \app\common\model\TicketPrice::where('ticket_id', $value["uuno"])->where('date', $ticket_date)->find();
            if (bccomp(strval($signPrice), $quotation->online_price, 2) !== 0) {
                $this->apiError('当前票价不符,请刷新后再试，或联系客服');
            }
            $origin_price = $amount_price = bcadd(strval($amount_price), bcmul(strval($signPrice), strval($value['number']), 2), 2);
        }
        if ($total_number <= 0) {
            $this->apiError('请至少购买一张门票');
        }
        if (bcsub(strval($origin_price), '0.00', 2) <= 0) {
            $this->apiError('消费券面额至少大于0.01，否则无法调起支付');
        }
        $trade_no = date('YmdHis') . GetNumberCode(6);
        // 开始事务
        Db::startTrans();
        try {
            //创建订单数据
            $insert_order = [
                'uuid'          => $uuid,
                'openid'        => '',
                'mch_id'        => $sellerInfo['id'],
                'trade_no'      => $trade_no,
                'out_trade_no'  => 'MP' . $trade_no,
                'channel'       => 'window',
                'type'          => $paytype,
                'origin_price'  => $origin_price,
                'amount_price'  => $amount_price,
                'order_remark'  => '', // 后期可移植到coupon_data
                'contact_man'   => $requiredParams['contact_man'],
                'contact_phone' => $requiredParams['contact_phone'],
                //'contact_certno'        => $requiredParams['contact_certno'],
                'order_status'  => 'paid',
                'refund_status' => 'not_refunded',
                'create_lat'    => '0.000000',
                'create_lng'    => '0.000000',
                'create_ip'     => Request::ip(),
                'create_time'   => time(),
                'update_time'   => time(),
                'writeoff_tourist_num'=>0
            ];
            $order = OrderModel::create($insert_order);
            foreach ($ticketData as $key => $value) {
                $ticketInfo = \app\common\model\Ticket::where("id",$value['uuno'])->append(['rights_list'])->find();
                if (!$ticketInfo) {
                    $this->apiError('门票未找到！');
                }
                if ($ticketInfo['status'] != 1) {
                    $this->apiError('门票未上架！');
                }
                //查询价格
                $price_info = PriceModel::where("ticket_id",$ticketInfo->id)->whereTime("date",$ticket_date)->find();
                if($price_info['stock'] <= 0 || ($price_info['stock'] - $value['number']) < 0){
                    throw new Exception("当前日期门票".$ticketInfo['title'] . ' 库存不足');
                }
                // 写入订单从表
                if($value['number'] > 0){

                    //判断是否多权益门票
                    $rights_list = [];
                    if($ticketInfo['rights_num'] > 0){
                       $rights_list =  RightsModel::where("ticket_id",$ticketInfo["id"])->select()->toArray();
                    }
                    for($i = 0; $i < $value['number']; $i++){
                        $insertData = [
                            'uuid'              => $uuid,
                            'trade_no'          => $order['trade_no'],
                            'out_trade_no'      => 'DMP' . date('YmdHis') . GetNumberCode(6),
                            'out_refund_no'     => 'REF' . date('YmdHis') . GetNumberCode(6),
                            'ticket_code'       => 'TC' . date('YmdHis') . GetNumberCode(6),
                            'tourist_fullname'  => '',
                            'tourist_cert_type' => 1,
                            'tourist_cert_id'   => '',
                            'tourist_mobile'    => '',
                            'ticket_cate_id'    => $ticketInfo['category_id'],
                            'ticket_id'         => $ticketInfo['id'],
                            'ticket_title'      => $ticketInfo['title'],
                            'ticket_date'       => $price_info['date'], // 入园日期
                            'ticket_cover'      => $ticketInfo['cover'],
                            'ticket_price'      => $price_info['online_price'], // 当天的价格
                            'ticket_rights_num'    => $ticketInfo['rights_num'], // 权益数量=核销次数
                            'writeoff_rights_num'  => 0, // 已核销权益的数量，已核销次数
                            'explain_use'       => $ticketInfo['explain_use'],
                            'explain_buy'       => $ticketInfo['explain_buy'],
                            'create_time'       => time(),
                            'update_time'       => time(),
                            // 2023-08-09 新增相同门票购买数量 线下售票使用
                            //去掉ticket_number,改为一张票生成一个订单从表记录
                            //'ticket_number'     => $value['number']
                        ];
                        $detail_info = OrderDetailModel::create($insertData);
                        //判断是否多权益门票
                        if(count($rights_list) > 0){
                            //写入权益表
                            foreach($rights_list as $rv){
                                $rights_data = [
                                    'order_id'=>$order["id"],
                                    'trade_no'=>$order["trade_no"],
                                    'detail_id'=>$detail_info["id"],
                                    'detail_code'=>$detail_info["ticket_code"],
                                    'detail_date'=>$detail_info["ticket_date"],
                                    'rights_id'=>$rv["id"],
                                    'rights_title'=>$rv["title"],
                                    'status'=>0,
                                    'create_time'=>time(),
                                    'code'=>uniqidDate(20,"RS"),
                                    'seller_id'=>$sellerInfo["id"],
                                ];
                                OrderDetailRightsModel::create($rights_data);
                            }
                        }
                    }
                }
                //扣除库存
                $price_info->stock = $price_info->stock - $value['number'];
                $price_info->save();
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->apiError('创建订单失败'.$e->getMessage());
        }
        $this->apiSuccess('购票成功', []);
    }

    /**
     * @api {post} /ticket/refund 单条退款
     * @apiDescription  提交退款整单退款
     */
    public function single_refund()
    {
        if (!Request::isPost()) {
            $this->apiError('请求方式错误');
        }
        $refund_desc  = Request::param('refund_desc');        // 退款理由
        $out_trade_no = Request::param('out_trade_no');       // 退款详情单号
        $uuid         = Request::param('uuid/s', '');    // 商户账号
        // 批量非空校验
        $requiredParams = [
            'refund_desc'  => $refund_desc,
            'ticket_code' => $out_trade_no,
            'uuid'         => $uuid
        ];
        foreach ($requiredParams as $param => $value) {
            if (empty($value)) {
                $this->apiError($param . '不能为空');
            }
        }
        $result =  OrderModel::refundTicket($out_trade_no,$refund_desc,false,false);
        if($result['code'] == 1){
            $this->apiSuccess($result['msg']);
        }else{
            $this->apiError($result['msg']);
        }
    }

    /**
     * @api {post} /ticket/refund 整单退款
     * @apiDescription  提交退款整单退款
     */
    public function refund()
    {
        if (!Request::isPost()) {
            $this->apiError('请求方式错误');
        }
        $refund_desc  = Request::param('refund_desc');        // 退款理由
        $out_trade_no = Request::param('out_trade_no');       // 退款订单号
        $uuid         = Request::param('uuid/s', '');    // 商户账号

        // 批量非空校验
        $requiredParams = [
            'refund_desc'  => $refund_desc,
            'out_trade_no' => $out_trade_no,
            'uuid'         => $uuid
        ];
        foreach ($requiredParams as $param => $value) {
            if (empty($value)) {
                $this->apiError($param . '不能为空');
            }
        }
        $result =  OrderModel::refundOrder($out_trade_no,$refund_desc,false,false);
        if($result['code'] == 1){
            return $this->apiSuccess($result['msg']);
        }else{
            $this->apiError($result['msg']);
        }
    }

    /**
     * 获取门票价格
     * @return array
     */
    public function getTicketPirce()
    {
        if (!Request::isPost()) {
            $this->apiError('请求方式错误');
        }
        $oneDay    = Request::param('oneday/s', '');
        $channel   = Request::param('channel/s', ''); //渠道，不同渠道返回不同价格
        $bstr      = Request::param('bstr/s', '');
        if (!$bstr) $this->apiError('缺少商户参数');
        $mid = sys_decryption($bstr,'mid');
        if (!$mid) $this->apiError('商户信息错误');
        $where = [
            ['seller_id', '=', $mid]
        ];
        // 默认为当天的数据
        if (empty($oneDay)) {
            $where[] = ['date', '=', date("Y-m-d")];
        } else {
            $where[] = ['date', '=', $oneDay];
        }
        $field = ['ticket_id', 'stock', 'total_stock', 'date'];
        switch ($channel) {
            case 'online':
                $field['online_price'] = 'price';
                break;
            case 'casual':
                $field['casual_price'] = 'price';
                break;
            case 'team':
                $field['team_price'] = 'price';
                break;
        }
        $price_list = PriceModel::where($where)->field($field)->with('ticket')->select()->toArray();

        $this->apiSuccess('', $price_list);
    }

    /**
     * @api {post} /ticket/list 获取订单列表
     * @apiDescription  获取订单列表
     */
    public function list()
    {
        if (!Request::isPost()) {
            $this->apiError('请求方式错误');
        }
        $map       = [];//查询条件
        $bstr      = Request::param('bstr/s','');       // 商户加密串
        $limit     = Request::param('limit/d', 10);     // 分页 每页展示几条
        $page      = Request::param('page/d', 1);       // 分页 第几页
        $keyword   = Request::param('keyword/s', '');   // 搜索
        if(!$bstr)
            $this->apiError('参数错误！');

        $mid = sys_decryption($bstr,'mid');

        if (!$mid) {
            $this->apiError('商户信息错误');
        }

        // 搜索条件
        if (!isJson($keyword)) {
            $this->apiError('请输入有效的搜索条件');
        }
        $keyword  = json_decode($keyword, true);

        foreach ($keyword as $param => $value) {
            if (!empty($value)) {
                switch ($param) {
                    case 'contact_man':
                    case 'contact_phone':
                        $map[] = [$param,'like',"%".$value."%"];
                        break;
                    //case 'trade_no':
                    case 'order_status':
                        $map[] = [$param,'=',$value];
                        break;
                    case 'create_time':
                        $start = strtotime($value[0]);
                        $end   = strtotime($value[1]);
                        $map[] = [$param,'between',[$start,$end]];
                        break;
                    default:
                        // code...
                        break;
                }
            }
        }

        $map[]   = ['mch_id','=',$mid];
        $map[]   = ['channel','=','window'];

        $list = $this->TicketOrder::where($map)
            ->when($page != 0, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->append(['order_status_text','refund_status_text'])->order('create_time desc,id desc')->select()->toArray();


        /*foreach ($list as &$v) {
            $v['create_time'] = date('Y/m/d', $v['create_time']);
        }*/
        $cnt = $this->TicketOrder::where($map)->count();
        $this->apiSuccess('查询成功',['list'=>$list,'cnt'=>$cnt]);
    }

    /**
     * @api {post} /ticket/detail 订单详情
     * @apiDescription  订单详情
     */
    public function detail()
    {
        if (!Request::isPost()) {
            $this->apiError('请求方式错误');
        }
        $bstr       = Request::param('bstr/s','');      // 商户加密串
        $trade_no     = Request::param('trade_no/s','');      // 订单编号
        if(!$bstr || !$trade_no) $this->apiError('参数错误');

        $mid = sys_decryption($bstr,'mid');
        if (!$mid) {
            $this->apiError('商户信息错误');
        }

        $map[]   = ['mch_id','=',$mid];
        $map[]   = ['channel','=','window'];
        $map[]   = ['trade_no','=',$trade_no];

        $order = $this->TicketOrder::where($map)->append(['order_status_text','refund_status_text'])->find();
        if(!$order){
            $this->apiError('订单不存在');
        }
        $order['detail'] = $this->TicketOrderDetail::where('trade_no',$trade_no)->append(['refund_progress_text','refund_status_text','refund_time_text'])->select()->toArray();
        $this->apiSuccess('查询成功',$order);
    }

    /**
     * @api {post} /ticket/stats 报表统计
     * @apiDescription  报表统计
     */
    public function stats()
    {
        if (!Request::isPost()) {
            $this->apiError('请求方式错误');
        }
        $bstr      = Request::param('bstr/s','');    // 商户加密串
        $uuid      = Request::param('uuid/s', '');   // 搜索
        if(!$bstr || !$uuid) $this->apiError('参数错误！');

        $mid = sys_decryption($bstr,'mid');
        if (!$mid) $this->apiError('商户信息错误');

        $userInfo = \app\common\model\TicketUser::where('uuid', $uuid)->find();
        if ($userInfo === NULL) {
            $this->apiError('未找到用户');
        }
        if ($userInfo->mid != $mid) {
            $this->apiError('当前用户信息异常');
        }
        
        $map     = [];
        // 今日累计收款
        $map[]   = ['mch_id','=',$mid];
        $map[]   = ['channel','=','window'];
        $map[]   = ['uuid','=',$uuid];
        $start = strtotime(date("Y-m-d 00:00:00",time()));
        $end   = strtotime(date("Y-m-d 23:59:59",time()));
        $mapTime[] = ['create_time','between',[$start,$end]];
        // 总收款
        $todayTotal = $this->TicketOrder::where($map)->where($mapTime)->fieldRaw('SUM(amount_price - refund_fee) as total')->find();
        //echo $this->TicketOrder->getLastSql();die;
        // 实际累计收款
        //$todayTotal = bcsub((string) $amount_price, (string) $refund_fee,2);

        // 现金支付
        $onmap[] = ['type','=','cash'];
        $cashTotal = $this->TicketOrder::where($map)->where($onmap)->where($mapTime)->fieldRaw('SUM(amount_price - refund_fee) as total')->find();

        // 非现金支付
        $nomap[] = ['type','<>','cash'];
        $cashNotTotal = $this->TicketOrder::where($map)->where($nomap)->where($mapTime)->fieldRaw('SUM(amount_price - refund_fee) as total')->find();

        // 统计最近7天：每天的现金销售额、非现金销售额
        $result = [];
        $dateRange = getDateRange();

        $data = Db::name('ticket_order')
        ->field("DATE(FROM_UNIXTIME(create_time)) AS ref_date")
        ->field("SUM(IF(type = 'cash', amount_price - refund_fee, 0)) AS cash_total")
        ->field("SUM(IF(type != 'cash', amount_price - refund_fee, 0)) AS cash_not_total")
        ->where("DATE(FROM_UNIXTIME(create_time)) >= CURDATE() - INTERVAL 6 DAY")
        ->where($map)
        ->group("DATE(FROM_UNIXTIME(create_time))")
        ->select();

        foreach ($dateRange as $date) {
            // 初始默认值
            $result[$date] = [
                "ref_date"       => $date,
                "cash_total"     => "0.00",
                "cash_not_total" => "0.00"
            ];
            
            foreach ($data as $value) {
                if ($date == $value['ref_date']) {
                    $result[$date] = $value;
                    break;
                }
            }
        }
        
        $this->apiSuccess('查询成功',[
            'today_price'   =>(float)$todayTotal['total'],
            'cash_total'    =>(float)$cashTotal['total'],
            'cash_not_total'=>(float)$cashNotTotal['total'],
            'data_chart'    => $result
        ]);
    }

    /*
    * 根据身份证查询未出行门票
    * */
    public function queryTourist()
    {
        if(!Request::isGet()){
            $this->apiError('请求方式错误！');
        }
        $idcard = Request::get("idcard/s", "", ["htmlspecialchars", "strip_tags", "trim"]);
        if (empty($idcard)) {
            $this->apiError('参数错误！');
        }
        $new_list = [];
        $list = OrderDetailModel::whereTime("ticket_date",">=",date("Y-m-d"))->where(["tourist_cert_id" => $idcard, "tourist_cert_type" => 1, "enter_time" => 0,"refund_status"=>"not_refunded"])->where(function($query){
            $query->where('refund_progress','init')->whereOr('refund_progress','refuse');
        })->select()->toArray();
        //提取订单号
        if($list){
            $order_sn_array = array_column($list,"trade_no");
            $order_list = OrderModel::whereIn("trade_no",$order_sn_array)->column("id,trade_no,order_status","trade_no");
            if($order_list){
                foreach($list as $item){
                    if($order_list[$item['trade_no']]["order_status"] == 'paid'){
                        $new_list[] = $item;
                    }
                }
            }
        }
        //$list              = $order_detail_list->visible(["ticket_code", "ticket_title", "ticket_date", "out_trade_no", "explain_use", "explain_buy","create_time"])->toArray();
        $this->apiSuccess('查询成功', $new_list);
    }

    /*
     * 查询订单
     * */
    public function queryOrder()
    {
        if(!Request::isGet()){
            $this->apiError('请求方式错误！');
        }
        $order_sn = Request::get("order_sn/s", "", ["htmlspecialchars", "strip_tags", "trim"]);
        if (empty($order_sn)) {
            $this->apiError('参数错误！');
        }
        $list = [];
        $order_info        = OrderModel::where(["trade_no" => $order_sn])->find();
        if($order_info && $order_info["order_status"] == 'paid'){
                $order_detail_list = OrderDetailModel::whereTime("ticket_date",">=",date("Y-m-d"))->where(["trade_no" => $order_info["trade_no"], "enter_time" => 0,"refund_status"=>"not_refunded"])->where(function($query){
                    $query->where('refund_progress','init')->whereOr('refund_progress','refuse');
                })->select();
                $list = $order_detail_list->toArray();

        }
        $this->apiSuccess('查询成功', $list);

    }
    /*
     * 取票操作 = 核销
     * */
    public function takeTicket(){
        if(!Request::isPost()){
            $this->apiError('请求方式错误！');
        }
        //获取门票编号
        $ticket_codes = Request::post("codes/s", "", ["htmlspecialchars", "strip_tags", "trim"]);
        if (empty($ticket_codes)) {
            $this->apiError('参数错误！');
        }
        //获取未核销的
        $order_detail_list = OrderDetailModel::whereIn("ticket_code",$ticket_codes)->where(["enter_time"=>0,"refund_status"=>"not_refunded"])->select();
        if ($order_detail_list->isEmpty()) {
            $this->apiError('没有找到待取的门票！');
        }
        Db::startTrans();
        try{
            //提取订单编号
            //$order_sn_array = array_column($order_detail_list,"trade_no");
            //先全部核销门票
            foreach($order_detail_list as $item){
                //检查订单是否全部核销玩
                $o_writeoff_rights_num = 0;
                $t_writeoff_rights_num = 0;
                $order_info = OrderModel::where("trade_no",$item["trade_no"])->find();
                //核销权益
                if($item->ticket_rights_num > 0){
                    $rights_list = OrderDetailRightsModel::where(["detail_id"=>$item["id"],"status"=>0])->select();

                    foreach($rights_list as $rv){
                        $rv->status = 1;
                        $rv->writeoff_time = time();
                        $rv->save();
                        //写入核销表
                        $insertData = [
                            'order_detail_id'=>$rv["detail_id"],
                            'order_detail_rights_id'=>$rv["id"],
                            'ticket_code'=>$rv['detail_code'],
                            'use_device'=>"自助机",
                            'writeoff_id'=>0,
                            'writeoff_name'=>"自助机取票",
                            'use_lat'=>1,
                            'use_lng'=>1,
                            'use_address'=>"",
                            'use_ip'=>Request::ip(),
                            'status'=>1,
                            'create_time'=>time()
                        ];
                        WriteOffModel::create($insertData);
                        //累加一核销权益
                        $t_writeoff_rights_num++;
                        //$item['writeoff_rights_num'] = $item['writeoff_rights_num']+1;
                    }
                    $o_writeoff_rights_num++;
                    //$order_info['wirteoff_rights_num'] = $order_info['wirteoff_rights_num'] + 1;
                }else{
                    //写入核销表
                    $insertData = [
                        'order_detail_id'=>$item["id"],
                        'order_detail_rights_id'=>0,
                        'ticket_code'=>$item['ticket_code'],
                        'use_device'=>"自助机",
                        'writeoff_id'=>0,
                        'writeoff_name'=>"自助机取票",
                        'use_lat'=>1,
                        'use_lng'=>1,
                        'use_address'=>"",
                        'use_ip'=>Request::ip(),
                        'status'=>1,
                        'create_time'=>time()
                    ];
                    WriteOffModel::create($insertData);
                }
                $item->writeoff_rights_num = $item->writeoff_rights_num + $t_writeoff_rights_num;
                $item->enter_time = time();
                $item->save();
                //订单变更
                $order_info->wirteoff_rights_num = $order_info->wirteoff_rights_num + $o_writeoff_rights_num;
                $order_info['writeoff_tourist_num'] = $order_info['writeoff_tourist_num'] + 1;
                //检查这个订单的游客是否还存在没有核销够的
                //2023-09-18去掉核销次数判断。whereColumn("ticket_rights_num",">","writeoff_rights_num")，直接判断入园时间，只要用一次，订单状态就变为已使用
                $is_has = OrderDetailModel::where(["trade_no"=>$order_info['trade_no'],"refund_status"=>"not_refunded","enter_time"=>0])->find();
                if(!$is_has){
                    //该订单全部核销完毕，状态变为已使用
                    $order_info->order_status = "used";
                }
                $order_info->save();
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            $this->apiError("核销失败！".$e->getMessage());
        }
        $this->apiSuccess("取票成功！");
    }
}