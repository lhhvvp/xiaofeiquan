<?php
/**
 * @desc   小程序支付API
 * @author slomoo
 * @email slomoo@aliyun.com
 * 2023-06-30 门票支付
 */
declare (strict_types=1);

namespace app\selfservice\controller;

use app\common\libs\Http;
use app\selfservice\BaseController;
use app\selfservice\middleware\Auth;
use app\selfservice\service\JwtAuth;
use app\common\model\ticket\Order;
use app\common\model\ticket\Order as OrderModel;
use app\common\model\TicketPrice;
use think\Exception;
use think\facade\Db;
use think\facade\Request;
use think\facade\Validate;
use app\common\model\ticket\Ticket as TicketModel;
use app\common\model\ticket\Category as CategoryModel;
use app\common\model\ticket\Price as PriceModel;
use app\common\model\ticket\OrderDetail as OrderDetailModel;
use app\common\model\ticket\OrderDetailRights as OrderDetailRightsModel;
use app\common\model\ticket\WriteOff as WriteOffModel;

class Ticket extends BaseController
{
    /**
     * 控制器中间件 [不需要鉴权set]
     * @var array
     */

    protected $middleware = [
        Auth::class => ['except' => ['stats', 'submit', 'travelOrderPay','queryOrder']]
    ];

    //初始化
    protected function initialize()
    {
        parent::initialize();
        $this->TicketPay         = new \app\common\model\TicketPay;
        $this->TicketOrder       = new \app\common\model\TicketOrder;
        $this->TicketOrderDetail = new \app\common\model\TicketOrderDetail;
        $this->TicketRefunds     = new \app\common\model\TicketRefunds;
    }

    /**
     * @api             {post} /ticket/submit 创建订单
     * @apiDescription  提交订单
     */
    public function submit()
    {
        if (!Request::isPost()) {
            $this->apiError('请求方式错误');
        }
        $ticketData  = Request::param('data/s', '');          // 门票&出行信息【门票：uuno,number,price,出行人信息：fullname、cert_type、cert_id】
        $contactData = Request::param('contact/s', '');       // 订单联系人信息【contact_man,contact_phone】
        $orderRemark = Request::param('order_remark/s', '');  // 订单用户备注
        $ticket_date = Request::param('ticket_date', '');
        $no          = Request::param('no', '');
        // 批量非空校验
        $requiredParams = [
            'no'          => $no,
            'data'        => $ticketData,
            'contact'     => $contactData,
            'ticket_date' => $ticket_date
        ];

        if (!isJson($requiredParams['data'])) {
            $this->apiError('data请求的格式不是json');
        }

        if (!isJson($requiredParams['contact'])) {
            $this->apiError('contact请求的格式不是json');
        }
        $contact                          = json_decode($requiredParams['contact'], true);
        $requiredParams['contact_man']    = $contact['contact_man'];
        $requiredParams['contact_phone']  = $contact['contact_phone'];
        $requiredParams['contact_certno'] = $contact['contact_certno'];

        foreach ($requiredParams as $param => $value) {
            if (empty($value)) {
                $this->apiError($param . '不能为空');
            }
        }

        // 校验商户信息
        $seller = \app\common\model\Seller::where('no', $no)->find();
        if ($seller === NULL) {
            $this->apiError('未找到商户');
        }

        if ($seller->status != 1) {
            $this->apiError('商户异常');
        }
        //2023-08-30 增加验证日期
        if (strtotime($ticket_date) < strtotime(date('Y-m-d'))) {
            $this->apiError("购买门票日期{$ticket_date}已过");
        }
        // 门票信息
        $ticketData = json_decode($requiredParams['data'], true);
        // 实际要去支付的金额=调起微信支付使用的=所有门票加起来的价格
        $amount_price = "0.00";
        $origin_price = "0.00";
        $total_number = 0;
        $orderDetail  = [];
        foreach ($ticketData as $key => $value) {
            if ($value['number'] != count($value['tourist'])) {
                $this->apiError($value['uuno'] . '门票数量与出行人信息不一致');
            }
            //校验门票信息 2023-8-1
            $ticketInfo = \app\common\model\Ticket::where("id", $value['uuno'])->append(['rights_list'])->find();
            if ($ticketInfo === NULL) {
                $this->apiError('未找到相关门票信息' . $value['uuno']);
            }
            $ticketData[$key]['ticket'] = $ticketInfo->toArray();
            // 检查门票对应的日期是否添加报价
            $quotation = \app\common\model\TicketPrice::where('ticket_id', $ticketInfo->id)
                ->where('date', $ticket_date)
                ->find();
            if ($quotation === NULL) {
                $this->apiError('该门票暂未设置报价: ' . $ticketInfo->title);
            }
            $ticketData[$key]['quotation'] = $quotation->toArray(); // 对应的当天的报价信息
            // 计算门票总数量
            $total_number += $value['number'];

            // 计算单张票价
            $signPrice = bcdiv(strval($value['price']), strval($value['number']), 2);
            // 比较单价，防止篡改金额
            if (bccomp(strval($signPrice), $quotation->online_price, 2) !== 0) {
                $this->apiError('当前票价不符,请联系客服');
            }

            // 计算需要支付的金额：单位：元。
            $origin_price = $amount_price = bcadd(strval($amount_price), bcmul(strval($signPrice), strval($value['number']), 2), 2);

            // 查询票种所属商户是否可用
            $sellerInfo = \app\common\model\Seller::find($ticketInfo['seller_id']);
            if ($sellerInfo === NULL) {
                $this->apiError('该门票所属商户未找到');
            }
            if ($sellerInfo->status != 1) {
                $this->apiError('该商户信息异常');
            }

            // 检查限购
            if ($value['number'] > $ticketInfo->quota_order) {
                $this->apiError('该门票限每单限购' . $ticketInfo->quota_order . '张');
            }
            /*if ($ticketInfo->quota > 0) {
                // 检查当天用户购买总数
                $todayStart = strtotime(date('Y-m-d 00:00:00'));
                $todayEnd   = strtotime(date('Y-m-d 23:59:59'));
                $quota      = \app\common\model\TicketOrderDetail::where('uuid', $uuid)->whereBetween('create_time', [$todayStart, $todayEnd])->count();
                if (($quota + $value['number']) > $ticketInfo->quota) {
                    $this->apiError('该门票每天限购' . $ticketInfo->quota . '张,本次最多可购买' . ($ticketInfo->quota - $quota) . "张");
                }
            }*/

            // 检查同行人身份信息
            foreach ($value['tourist'] as $k => $v) {
                /*if (!check_phone($v['tourist_mobile'])) {
                    $this->apiError('当前游客手机号码错误: ' . $value['tourist'][$k]['tourist_fullname']);
                }*/
                if ($v['tourist_cert_type'] == 1) {
                    $tourist_cert_id = trim($v['tourist_cert_id']);
                    if (!isCreditNo($tourist_cert_id)) {
                        $this->apiError('当前游客身份证号码错误: ' . $value['tourist'][$k]['tourist_fullname']);
                    }
                }
            }
        }
        if ($total_number <= 0) {
            $this->apiError('请至少购买一张门票');
        }

        if (bcsub(strval($origin_price), '0.00', 2) <= 0) {
            $this->apiError('消费券面额至少大于0.01，否则无法调起支付');
        }
        $trade_no = date('YmdHis') . GetNumberCode(6);

        //创建订单数据
        $order = [
            'mch_id'               => $sellerInfo['id'],
            'trade_no'             => $trade_no,
            'out_trade_no'         => 'MP' . $trade_no,
            'channel'              => 'selfservice',
            'type'                 => 'miniapp',
            'origin_price'         => $origin_price,
            'amount_price'         => $amount_price,
            'order_remark'         => $orderRemark, // 后期可移植到coupon_data
            'contact_man'          => $requiredParams['contact_man'],
            'contact_phone'        => $requiredParams['contact_phone'],
            'contact_certno'       => $requiredParams['contact_certno'],
            'order_status'         => 'created',
            'refund_status'        => 'not_refunded',
            'create_ip'            => Request::ip(),
            'create_time'          => time(),
            'update_time'          => time(),
            'writeoff_tourist_num' => 0
        ];
        // 开始事务
        Db::startTrans();
        try {
            $order_id    = Db::name('ticket_order')->insertGetId($order);
            $order['id'] = $order_id;

            foreach ($ticketData as $key => $value) {
                foreach ($value['tourist'] as $k => $v) {
                    // 重组订单详情数据
                    $insertData        = [
                        'trade_no'            => $order['trade_no'],
                        'out_trade_no'        => 'DMP' . date('YmdHis') . GetNumberCode(6) . $k,
                        'out_refund_no'       => 'REF' . date('YmdHis') . GetNumberCode(6) . $k,
                        'ticket_code'         => 'TC' . date('YmdHis') . GetNumberCode(6),
                        'tourist_fullname'    => $v['tourist_fullname'],
                        'tourist_cert_type'   => $v['tourist_cert_type'],
                        'tourist_cert_id'     => $v['tourist_cert_id'],
                        'tourist_mobile'      => '',
                        'ticket_cate_id'      => $value['ticket']['category_id'],
                        'ticket_id'           => $value['ticket']['id'],
                        'ticket_title'        => $value['ticket']['title'],
                        'ticket_date'         => $value['quotation']['date'], // 入园日期
                        'ticket_cover'        => $value['ticket']['cover'],
                        'ticket_price'        => $value['quotation']['online_price'], // 当天的价格
                        'ticket_rights_num'   => $value['ticket']['rights_num'],      // 权益数量=核销次数
                        'writeoff_rights_num' => 0,                                   // 已核销权益的数量，已核销次数
                        'explain_use'         => $value['ticket']['explain_use'],
                        'explain_buy'         => $value['ticket']['explain_buy'],
                        'create_time'         => time(),
                        'update_time'         => time()
                    ];
                    $order_detail_info = OrderDetailModel::create($insertData);
                    if ($value['ticket']['rights_num'] > 0) {
                        foreach ($value['ticket']['rights_list'] as $rr) {
                            $insertData = [
                                'order_id'     => $order['id'],
                                'detail_id'    => $order_detail_info['id'],
                                'detail_date'  => $order_detail_info['ticket_date'],
                                'detail_code'  => $order_detail_info['ticket_code'],
                                'rights_title' => $rr['title'],
                                'rights_id'    => $rr['id'],
                                'status'       => 0,
                                'create_time'  => time(),
                                'update_time'  => time(),
                                'code'         => uniqidDate(20, "RS"),
                                'seller_id'    => $value['ticket']['seller_id']
                            ];
                            OrderDetailRightsModel::create($insertData);
                        }
                    }
                }
                /*
                 * 扣除库存操作
                 * */
                // 查询当前库存
                $nowStock = PriceModel::where("ticket_id", $value['quotation']['ticket_id'])->where("date", $value['quotation']['date'])->find();
                if ($nowStock['stock'] <= 0 || ($nowStock['stock'] - $value['number']) < 0) {
                    throw new Exception("当前日期门票" . $value['ticket']['title'] . ' 库存不足');
                }
                $nowStock->stock = $nowStock['stock'] - $value['number'];
                $nowStock->save();
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->apiError('创建订单失败' . $e->getMessage());
        }
        //开始获取二维码
        try {
            $result = self::getTravelWxappQrcode($order);
        } catch (\Exception $e) {
            $this->apiError('小程序码失败！' . $e->getMessage());
        }
        $this->apiSuccess('订单添加成功', $result);
    }

    public static function getTravelWxappQrcode($order_info = null)
    {
        if ($order_info === null) {
            throw new Exception('获取小程序码错误，缺少参数！');
        }
        // 查询订单
        $order_obj  = new Order;
        $order_info = $order_obj->where('trade_no', $order_info['trade_no'])->find();
        //开始获取二维码
        updateAccesstoken();
        $wxInfo = accesstoken();
        if ($wxInfo['code'] == 0 && $wxInfo['msg'] == 'ok') {
            $url                             = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $wxInfo['data']['access_token'];
            $data                            = json_encode(['scene' => "trade_no/" . $order_info['trade_no'], 'page' => "pages/getopenid/travelorderinfo", "env_version" => "release"]);
            $options[CURLOPT_HEADER]         = 'image/gif';
            $options[CURLOPT_SSL_VERIFYPEER] = false;
            $options[CURLOPT_HTTPHEADER]     = [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data)
            ];
            $options[CURLOPT_RETURNTRANSFER] = 1;
            $result                          = Http::post($url, $data, $options);
            $result_json                     = json_decode($result, true);
            if ($result_json != NULL) {
                throw new Exception($result_json['errmsg']);
            }
            $filePath = "static/wximg/tot_" . uniqidDate(20) . ".png";
            $file_res = file_put_contents($filePath, $result);
            if ($file_res === false) {
                throw new Exception('保存小程序码错误！');
            }
            $fileurl                         = alioss("/" . $filePath, 'selfservice', true, 'wlxfq');
            $order_info->travel_wxapp_qrcode = $fileurl;
            if ($order_info->save() === true) {
                return ['trade_no' => $order_info['trade_no'], 'url' => $fileurl];
            } else {
                throw new Exception('保存小程序码错误！');
            }
        } else {
            throw new Exception('access_token获取错误！');
        }

    }

    /*
     *
     * 支付自助购票订单。
     *
     * */
    public function travelOrderPay()
    {
        $code     = Request::param('code/s', '');      // 小程序wxlogin的code
        $trade_no = Request::param('trade_no/s', '');  // 订单内部编码
        if ($code === "" || $trade_no === "") {
            $this->apiError('缺少参数！');
        }
        // 获取微信小程序参数
        $wechat = \app\common\model\System::find(1);
        // 微信登录地址
        $infourl  = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $wechat['appid'] . "&secret=" . $wechat['appsecret'] . "&js_code=" . $code . "&grant_type=authorization_code";
        $jsonData = http_curl_get($infourl, true);
        $jsonData = json_decode($jsonData, true);

        if (!isset($jsonData['session_key'])) {
            $this->apiError('数据异常：sessionKey不存在');
        }
        $openid = $jsonData['openid'];
        $order  = OrderModel::where('trade_no', $trade_no)->find();
        if ($order === NULL) {
            $this->apiError('订单不存在');
        }
        switch ($order->order_status) {
            case 'paid':
                $this->apiError('该订单已经支付');
                break;
            case 'used':
                $this->apiError('该订单已经使用');
                break;
            case 'cancelled':
                $this->apiError('该订单已经取消');
                break;
            case 'refunded':
                $this->apiError('该订单已经退款');
                break;
        }
        $order->openid = $openid;
        $order->save();
        // 开始付款 构建微信支付
        $payret = $this->TicketPay->wxminipay($order['amount_price'] * 100, $order, $openid, $order['type']);
        if ($payret) {
            $start                = '{';
            $end                  = '}';
            $data['pay']          = $order['type'] == 'app' ? $start . getstripos($payret, '{', '}') . $end : $payret;
            $data['trade_no']     = $order['trade_no'];
            $data['amount_price'] = $order['amount_price'];
            $this->apiSuccess('构建支付成功', $data);
        }
        $this->apiError('微信支付构建错误');
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
        $oneDay  = Request::param('oneday/s', '');
        $channel = Request::param('channel/s', ''); //渠道，不同渠道返回不同价格
        $bstr    = Request::param('bstr/s', '');
        $no      = Request::param('no/s', '');
        if (!$bstr) $this->apiError('缺少商户参数');
        $mid = sys_decryption($bstr, $no);
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

    /*
     *
     * 查询订单
     *
     * */
    public function getTradeNo()
    {
        $trade_no = Request::param('trade_no/s', '');  // 订单内部编码
        if ($trade_no === "") {
            $this->apiError('缺少参数！');
        }
        $order = OrderModel::where('trade_no', $trade_no)->find();
        if ($order === NULL) {
            $this->apiError('订单不存在');
        }
        $this->apiSuccess('查询成功', $order->payment_status);
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
                $order_info = OrderModel::where("trade_no",$item["trade_no"])->find();
                //核销权益
                if($item->ticket_rights_num > 0){
                    //获取未核销得
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
                        $item->writeoff_rights_num += 1;
                    }
                    $order_info->wirteoff_rights_num += 1;
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
                $item->enter_time = time();
                $item->save();
                $order_info->writeoff_tourist_num += 1;
                //检查这个订单的游客是否还存在没有核销够的
                //直接判断入园时间。只要入园了，就算使用了。
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