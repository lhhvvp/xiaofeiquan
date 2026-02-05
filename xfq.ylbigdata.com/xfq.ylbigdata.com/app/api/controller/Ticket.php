<?php
/**
 * @desc   小程序支付API
 * @author slomoo
 * @email slomoo@aliyun.com
 * 2023-06-30 门票支付
 */
declare (strict_types=1);

namespace app\api\controller;

use app\api\BaseController;
use app\api\middleware\Auth;
use app\api\service\JwtAuth;
use app\common\model\ticket\Order;
use app\common\model\ticket\Order as OrderModel;
use app\common\model\TicketPrice;
use app\common\model\Users;
use app\xc\model\OrderOtaItemModel;
use think\Exception;
use think\facade\Db;
use think\facade\Request;
use think\facade\Validate;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Log;
use app\common\model\TicketPayNotify;
use app\common\model\TicketRefundsNotify;
use app\common\model\ticket\Ticket as TicketModel;
use app\common\model\ticket\Category as CategoryModel;
use app\common\model\ticket\Price as PriceModel;
use app\common\model\ticket\OrderDetail as OrderDetailModel;
use app\common\model\ticket\Rights as TicketRightsModel;
use app\common\model\ticket\OrderDetailRights as OrderDetailRightsModel;
use app\common\model\ticket\Comment as TicketCommentModel;
use app\common\model\ticket\WriteOff as WriteOffModel;
use app\common\model\MerchantVerifier as MerchantVerifierModel;
use app\common\model\TicketRefunds as TicketRefundsModel;
use app\common\model\Seller as SellerModel;
class Ticket extends BaseController
{
    /**
     * 控制器中间件 [不需要鉴权set]
     * @var array
     */

    protected $middleware = [
        Auth::class => ['except' => ['notify_pay','notify_refund', 'getTicketList', 'getTicketPirce', 'getScenicList', 'getCommentList','getTravelOrderDetail','travelOrderPay']]
    ];

    //初始化
    protected function initialize()
    {
        parent::initialize();
        $this->TicketPay     = new \app\common\model\TicketPay;
        $this->TicketOrder   = new \app\common\model\TicketOrder;
        $this->TicketRefunds = new \app\common\model\TicketRefunds;
    }

    /**
     * @api             {post} /ticket/pay 提交订单
     * @apiDescription  提交订单
     */
    /*public function pay()
    {
        $uuid        = Request::param('uuid/s', '');          // 用户uid
        $openid      = Request::param('openid/s', '');        // 付款OPENID
        $ticketData  = Request::param('data/s', '');          // 门票&出行信息【门票：uuno,number,price,出行人信息：fullname、cert_type、cert_id】
        $contactData = Request::param('contact/s', '');       // 订单联系人信息【contact_man,contact_phone】
        $orderRemark = Request::param('order_remark/s', '');  // 订单用户备注
        $createLat   = Request::param('create_lat', '');
        $createLng   = Request::param('create_lng', '');
        $ticket_date = Request::param('ticket_date', '');

        // 批量非空校验
        $requiredParams = [
            'uuid'        => $uuid,
            'openid'      => $openid,
            'data'        => $ticketData,
            'contact'     => $contactData,
            'create_lat'  => $createLat,
            'create_lng'  => $createLng,
            'ticket_date' => $ticket_date
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

        // 校验用户信息 uuid 关联的 openid
        $userInfo = \app\common\model\Users::where('uuid', $uuid)->find();
        if ($userInfo === NULL) {
            $this->apiError('未找到用户');
        }
        if ($userInfo->openid !== $openid) {
            $this->apiError('当前用户信息异常，禁止提交');
        }

        if ($userInfo->auth_status != 1) {
            $this->apiError('当前用户未实名认证');
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

            // 校验门票信息
            //$ticketInfo = \app\common\model\Ticket::find($value['uuno']);
            //校验门票信息 2023-8-1
            $ticketInfo = \app\common\model\Ticket::where("id",$value['uuno'])->find();
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
            if ($ticketInfo->quota > 0) {
                // 检查当天用户购买总数
                $todayStart = strtotime(date('Y-m-d 00:00:00'));
                $todayEnd   = strtotime(date('Y-m-d 23:59:59'));
                $quota      = \app\common\model\TicketOrderDetail::where('uuid', $uuid)->whereBetween('create_time', [$todayStart, $todayEnd])->count();
                if (($quota + $value['number']) > $ticketInfo->quota) {
                    $this->apiError('该门票每天限购' . $ticketInfo->quota . '张,本次最多可购买' . ($ticketInfo->quota - $quota));
                }
            }

            // 检查同行人身份信息
            foreach ($value['tourist'] as $k => $v) {
                if (!check_phone($v['tourist_mobile'])) {
                    $this->apiError('当前游客手机号码错误: ' . $value['tourist'][$k]['tourist_fullname']);
                }
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
            'uuid'          => $requiredParams['uuid'],
            'openid'        => $requiredParams['openid'],
            'mch_id'        => $sellerInfo['id'],
            'trade_no'      => $trade_no,
            'out_trade_no'  => 'MP' . $trade_no,
            'channel'       => 'online',
            'type'          => 'miniapp',
            'origin_price'  => $origin_price,
            'amount_price'  => $amount_price,
            'order_remark'  => $orderRemark, // 后期可移植到coupon_data
            'contact_man'   => $requiredParams['contact_man'],
            'contact_phone' => $requiredParams['contact_phone'],
            //'contact_certno'        => $requiredParams['contact_certno'],
            'order_status'  => 'created',
            'refund_status' => 'not_refunded',
            'create_lat'    => $requiredParams['create_lat'],
            'create_lng'    => $requiredParams['create_lng'],
            'create_ip'     => Request::ip(),
            'create_time'   => time(),
            'update_time'   => time()
        ];


        // 开始事务
        Db::startTrans();
        // 订单添加
        $order_id = Db::name('ticket_order')->insertGetId($order);
        if ($order_id > 0) {
            // 添加订单详情数据
            $detailRet = self::OrderDetailInsert($order, $userInfo, $ticketData);
            if (!$detailRet) {
                Db::rollback();
                $this->apiError('订单详情添加失败');
            }

            // 扣减指定日期门票库存 - number
            $deductRet = self::OrderInventoryDeduct($ticketData);
            if ($deductRet !== true) {
                Db::rollback();
                $this->apiError('当前日期门票 ' . $deductRet . ' 库存不足');
            }

            // 订单提交成功
            Db::commit();
            // 开始付款 构建微信支付
            $payret = $this->TicketPay->wxminipay($order['amount_price'] * 100, $order, $openid, $order['type']);
            if ($payret) {
                $start                = '{';
                $end                  = '}';
                $data['pay']          = $order['type'] == 'app' ? $start . getstripos($payret, '{', '}') . $end : $payret;
                $data['trade_no']     = $order['trade_no'];
                $data['amount_price'] = $order['amount_price'];
                $this->apiSuccess('订单添加成功', $data);
            }
            $this->apiError('微信支付构建错误');
        } else {
            Db::rollback();
            $this->apiError('订单添加失败');
        }
    }*/

    // 二次支付
    public function orderpay()
    {
        $uuid     = Request::param('uuid/s', '');      // 用户uid
        $openid   = Request::param('openid/s', '');    // 付款OPENID
        $trade_no = Request::param('trade_no/s', '');  // 订单内部编码

        // 批量非空校验
        $requiredParams = [
            'uuid'     => $uuid,
            'openid'   => $openid,
            'trade_no' => $trade_no
        ];

        foreach ($requiredParams as $param => $value) {
            if (empty($value)) {
                $this->apiError($param . '不能为空');
            }
        }

        $order = $this->TicketOrder::where('trade_no', $trade_no)->find();
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

        // 检查下单人与二次支付人是否一致
        if ($order->uuid != $uuid) {
            $this->apiError('当前订单归属错误');
        }

        // 校验用户信息 uuid 关联的 openid
        $userInfo = \app\common\model\Users::where('uuid', $uuid)->find();
        if ($userInfo === NULL) {
            $this->apiError('未找到用户');
        }
        if ($userInfo->openid !== $openid) {
            $this->apiError('当前用户信息异常，禁止提交');
        }

        // 开始付款 构建微信支付
        $payret = $this->TicketPay->wxminipay($order['amount_price'] * 100, $order, $openid, $order['type']);
        if ($payret) {
            $start                = '{';
            $end                  = '}';
            $data['pay']          = $order['type'] == 'app' ? $start . getstripos($payret, '{', '}') . $end : $payret;
            $data['trade_no']     = $order['trade_no'];
            $data['amount_price'] = $order['amount_price'];
            $this->apiSuccess('支付成功', $data);
        }
        $this->apiError('微信支付构建错误');
    }

    // 添加订单详情数据
    private function OrderDetailInsert($order, $userInfo, $ticketData)
    {
        $data = [];
        foreach ($ticketData as $key => $value) {
            foreach ($value['tourist'] as $k => $v) {
                // 重组订单详情数据
                $data[] = [
                    'uuid'              => $userInfo['uuid'],
                    'trade_no'          => $order['trade_no'],
                    'out_trade_no'      => 'DMP' . date('YmdHis') . GetNumberCode(6) . $k,
                    'out_refund_no'     => 'REF' . date('YmdHis') . GetNumberCode(6) . $k,
                    'ticket_code'       => 'TC' . date('YmdHis') . GetNumberCode(6),
                    'tourist_fullname'  => $v['tourist_fullname'],
                    'tourist_cert_type' => $v['tourist_cert_type'],
                    'tourist_cert_id'   => $v['tourist_cert_id'],
                    'tourist_mobile'    => $v['tourist_mobile'],
                    'ticket_cate_id'    => $value['ticket']['category_id'],
                    'ticket_id'         => $value['ticket']['id'],
                    'ticket_title'      => $value['ticket']['title'],
                    'ticket_date'       => $value['quotation']['date'], // 入园日期
                    'ticket_cover'      => $value['ticket']['cover'],
                    'ticket_price'      => $value['quotation']['online_price'], // 当天的价格
                    'explain_use'       => $value['ticket']['explain_use'],
                    'explain_buy'       => $value['ticket']['explain_buy'],
                    'create_time'       => time(),
                    'update_time'       => time()
                ];
            }
        }
        //$two_dimensional_array = array_merge(...$data);
        $res = Db::name('ticket_order_detail')->insertAll($data); // 返回的是成功的条数

        if ($res == count($data)) {
            return true;
        }
        return false;
    }




    // 扣减指定日期门票库存 - number
    private function OrderInventoryDeduct($ticketData)
    {
        foreach ($ticketData as $key => $value) {
            // 查询当前库存
            $nowStock = Db::name('ticket_price')
                ->where('ticket_id', $value['quotation']['ticket_id'])
                ->where('date', $value['quotation']['date'])
                ->find();
            if ($nowStock['stock'] <= 0 || ($nowStock['stock'] - $value['number']) <= 0) {
                return $value['ticket']['title'];
            }
            // 扣除库存操作
            Db::name('ticket_price')
                ->where('ticket_id', $value['quotation']['ticket_id'])
                ->where('date', $value['quotation']['date'])
                ->Dec('stock', $value['number']);
        }

        return true;
    }


    /**
     * @api             {post} /ticket/pay 创建订单
     * @apiDescription  提交订单
     */
    public function pay()
    {
        $uuid        = Request::param('uuid/s', '');          // 用户uid
        $openid      = Request::param('openid/s', '');        // 付款OPENID
        $ticketData  = Request::param('data/s', '');          // 门票&出行信息【门票：uuno,number,price,出行人信息：fullname、cert_type、cert_id】
        $contactData = Request::param('contact/s', '');       // 订单联系人信息【contact_man,contact_phone】
        $orderRemark = Request::param('order_remark/s', '');  // 订单用户备注
        $createLat   = Request::param('create_lat', '');
        $createLng   = Request::param('create_lng', '');
        $ticket_date = Request::param('ticket_date', '');
        // 批量非空校验
        $requiredParams = [
            'uuid'        => $uuid,
            'openid'      => $openid,
            'data'        => $ticketData,
            'contact'     => $contactData,
            'create_lat'  => $createLat,
            'create_lng'  => $createLng,
            'ticket_date' => $ticket_date
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

        // 校验用户信息 uuid 关联的 openid
        $userInfo = \app\common\model\Users::where('uuid', $uuid)->find();
        if ($userInfo === NULL) {
            $this->apiError('未找到用户');
        }
        if ($userInfo->openid !== $openid) {
            $this->apiError('当前用户信息异常，禁止提交');
        }

        if ($userInfo->auth_status != 1) {
            $this->apiError('当前用户未实名认证');
        }
        //2023-08-30 增加验证日期
        if(strtotime($ticket_date) < strtotime(date('Y-m-d'))){
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
            // 校验门票信息
            //$ticketInfo = \app\common\model\Ticket::find($value['uuno']);
            //校验门票信息 2023-8-1
            $ticketInfo = \app\common\model\Ticket::where("id",$value['uuno'])->append(['rights_list'])->find();
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
            if ($ticketInfo->quota > 0) {
                // 检查当天用户购买总数
                $todayStart = strtotime(date('Y-m-d 00:00:00'));
                $todayEnd   = strtotime(date('Y-m-d 23:59:59'));
                $quota      = \app\common\model\TicketOrderDetail::where('uuid', $uuid)->whereBetween('create_time', [$todayStart, $todayEnd])->count();
                if (($quota + $value['number']) > $ticketInfo->quota) {
                    $this->apiError('该门票每天限购' . $ticketInfo->quota . '张,本次最多可购买' . ($ticketInfo->quota - $quota) . "张");
                }
            }

            // 检查同行人身份信息
            foreach ($value['tourist'] as $k => $v) {
                if (!check_phone($v['tourist_mobile'])) {
                    $this->apiError('当前游客手机号码错误: ' . $value['tourist'][$k]['tourist_fullname']);
                }
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
            'uuid'          => $requiredParams['uuid'],
            'openid'        => $requiredParams['openid'],
            'mch_id'        => $sellerInfo['id'],
            'trade_no'      => $trade_no,
            'out_trade_no'  => 'MP' . $trade_no,
            'channel'       => 'online',
            'type'          => 'miniapp',
            'origin_price'  => $origin_price,
            'amount_price'  => $amount_price,
            'order_remark'  => $orderRemark, // 后期可移植到coupon_data
            'contact_man'   => $requiredParams['contact_man'],
            'contact_phone' => $requiredParams['contact_phone'],
            //'contact_certno'        => $requiredParams['contact_certno'],
            'order_status'  => 'created',
            'refund_status' => 'not_refunded',
            'create_lat'    => $requiredParams['create_lat'],
            'create_lng'    => $requiredParams['create_lng'],
            'create_ip'     => Request::ip(),
            'create_time'   => time(),
            'update_time'   => time(),
            'writeoff_tourist_num'=>0
        ];
        // 开始事务
        Db::startTrans();
        try {
            $order_id = Db::name('ticket_order')->insertGetId($order);
            $order['id'] = $order_id;

            //  $detailRet = self::OrderDetailInsert($order, $userInfo, $ticketData);

            foreach ($ticketData as $key => $value) {
                foreach ($value['tourist'] as $k => $v) {
                    // 重组订单详情数据
                    $insertData = [
                        'uuid'              => $userInfo['uuid'],
                        'trade_no'          => $order['trade_no'],
                        'out_trade_no'      => 'DMP' . date('YmdHis') . GetNumberCode(6) . $k,
                        'out_refund_no'     => 'REF' . date('YmdHis') . GetNumberCode(6) . $k,
                        'ticket_code'       => 'TC' . date('YmdHis') . GetNumberCode(6),
                        'tourist_fullname'  => $v['tourist_fullname'],
                        'tourist_cert_type' => $v['tourist_cert_type'],
                        'tourist_cert_id'   => $v['tourist_cert_id'],
                        'tourist_mobile'    => $v['tourist_mobile'],
                        'ticket_cate_id'    => $value['ticket']['category_id'],
                        'ticket_id'         => $value['ticket']['id'],
                        'ticket_title'      => $value['ticket']['title'],
                        'ticket_date'       => $value['quotation']['date'], // 入园日期
                        'ticket_cover'      => $value['ticket']['cover'],
                        'ticket_price'      => $value['quotation']['online_price'], // 当天的价格
                        'ticket_rights_num'    => $value['ticket']['rights_num'], // 权益数量=核销次数
                        'writeoff_rights_num'  => 0, // 已核销权益的数量，已核销次数
                        'explain_use'       => $value['ticket']['explain_use'],
                        'explain_buy'       => $value['ticket']['explain_buy'],
                        'create_time'       => time(),
                        'update_time'       => time()
                    ];
                    $order_detail_info = OrderDetailModel::create($insertData);
                    if($value['ticket']['rights_num'] > 0){
                        foreach($value['ticket']['rights_list'] as $rr){
                            $insertData = [
                                'order_id'      => $order['id'],
                                'detail_id'     => $order_detail_info['id'],
                                'detail_date'     => $order_detail_info['ticket_date'],
                                'detail_code'     => $order_detail_info['ticket_code'],
                                'rights_title'  => $rr['title'],
                                'rights_id'     => $rr['id'],
                                'status'        => 0,
                                'create_time'   => time(),
                                'update_time'   => time(),
                                'code'          => uniqidDate(20,"RS"),
                                'seller_id'     => $value['ticket']['seller_id'],
                                'user_id'       => $userInfo['id'],
                                'uuid'          => $userInfo['uuid']
                            ];
                            OrderDetailRightsModel::create($insertData);
                        }
                    }
                }
                /*
                 * 扣除库存操作
                 * */
                // OrderInventoryDeduct()
                // 查询当前库存
                $nowStock = PriceModel::where("ticket_id",$value['quotation']['ticket_id'])->where("date",$value['quotation']['date'])->find();
                if ($nowStock['stock'] <= 0 || ($nowStock['stock'] - $value['number']) < 0) {
                    throw new Exception("当前日期门票".$value['ticket']['title'] . ' 库存不足');
                }
                $nowStock->stock = $nowStock['stock'] - $value['number'];
                $nowStock->save();
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->apiError('创建订单失败'.$e->getMessage());
        }
        $data = [];
        try{
            // 开始付款 构建微信支付
            $payret = $this->TicketPay->wxminipay($order['amount_price'] * 100, $order, $openid, $order['type']);
            if (!$payret) {
                throw new Exception("微信支付构建错误");
            }
            $start                = '{';
            $end                  = '}';
            $data['pay']          = $order['type'] == 'app' ? $start . getstripos($payret, '{', '}') . $end : $payret;
            $data['trade_no']     = $order['trade_no'];
            $data['amount_price'] = $order['amount_price'];
        }catch (\Exception $e) {
            $this->apiError("微信支付构建错误！".$e->getMessage());
        }
        $this->apiSuccess('订单添加成功', $data);
    }
    /**
     * @api {post} /ticket/refund 单条退款
     * @apiDescription  提交退款整单退款
     */
    public function single_refund()
    {
        $uuid         = Request::param('uuid/s', '');         // 用户uid
        $openid       = Request::param('openid');             // 付款OPENID
        $refund_desc  = Request::param('refund_desc');        // 退款理由
        $out_trade_no = Request::param('out_trade_no');       // 退款详情单号

        // 批量非空校验
        $requiredParams = [
            'uuid'         => $uuid,
            'openid'       => $openid,
            'refund_desc'  => $refund_desc,
            'out_trade_no' => $out_trade_no
        ];

        foreach ($requiredParams as $param => $value) {
            if (empty($value)) {
                $this->apiError($param . '不能为空');
            }
        }

        // 校验用户信息 uid 关联的 openid
        $userInfo = \app\common\model\Users::where(['uuid' => $uuid])
            ->find();
        if ($userInfo['openid'] != $openid) {
            $this->apiError('当前用户信息异常，禁止提交');
        }

        $order_detail = Db::name('ticket_order_detail')->where('out_trade_no', $out_trade_no)->where('uuid', $uuid)->find();
        if (!$order_detail) {
            $this->apiError('支付订单不存在!');
        }
        if ($order_detail['enter_time'] > 0) {
            $this->apiError('该游客已入园，不允许退款!');
        }
        switch ($order_detail['refund_status']) {
            case 'fully_refunded':
                $this->apiError('该订单已经全额退款!');
                break;
            default:
                // code...
                break;
        }
        switch ($order_detail['refund_progress']) {
            case 'pending_review':
                $this->apiError('该订单已经提交退款');
                break;
            case 'approved':
                $this->apiError('该订单已经通过退款审核，请稍后查看');
                break;
            case 'completed':
                $this->apiError('该订单已经完成退款');
                break;
            default:
                // code...
                break;
        }

        // 查询订单主表信息
        $order = Db::name('ticket_order')->where('trade_no', $order_detail['trade_no'])->find();
        switch ($order['order_status']) {
            case 'created':
                $this->apiError('未支付订单无法退款!');
                break;
            case 'used':
                $this->apiError('已使用订单无法退款!');
                break;
            case 'cancelled':
                $this->apiError('已取消订单无法退款!');
                break;
            case 'refunded':
                $this->apiError('该订单已经全额退款!');
                break;
            default:
                // code...
                break;
        }
        if ($order['refund_status'] == 'fully_refunded') {
            $this->apiError('该订单已经全额退款!');
        }

        // 处理退款
        $res = $this->OrderRefundDetail($order,$order_detail,$refund_desc,$userInfo);

        if ($res === true) {
            $this->apiSuccess('申请成功',$res);
        }
        return $this->apiError('申请失败:'.$res);
    }

    /**
     * 订单申请退款
     * @param array $order
     * @param str   $order_remark
     * @return mixed
     */
    public function OrderRefundDetail($order,$order_detail, $refund_desc, $userInfo)
    {
        // 开启事务
        Db::startTrans();
        try {
            // 生成一条退款交易数据
            $refundData = [
                'uuid'          => $userInfo['uuid'],
                'mch_id'        => $order['mch_id'],
                'trade_no'      => $order['trade_no'],
                'order_detail_no' => $order_detail['out_trade_no'],
                'out_refund_no' => $order_detail['out_refund_no'],
                'total_fee'     => $order['amount_price'],
                'refund_fee'    => $order_detail['ticket_price'],
                'refund_desc'   => $refund_desc,
                'refund_ip'     => Request::ip(),
                'transaction_id' => $order['transaction_id'],
                'create_time'   => time()
            ];
            $refundId = Db::name('ticket_refunds')->insertGetId($refundData);

            // 修改当前订单退款信息
            Db::name('ticket_order_detail')
                ->where('id', $order_detail['id'])
                ->update([
                    'refund_progress' => 'pending_review', // 已提交
                    'is_full_refund' => 0,                // 是否整单退 1=是 0=否
                    'refund_id'     => $refundId
                ]);

            // 提交事务
            Db::commit();
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();

            // 返回失败信息
            return $e->getMessage();
        }
    }

    /**
     * @api {post} /ticket/refund 整单退款
     * @apiDescription  提交退款整单退款
     */
    public function refund()
    {
        $uuid         = Request::param('uuid/s', '');         // 用户uid
        $openid       = Request::param('openid');             // 付款OPENID
        $refund_desc  = Request::param('refund_desc');        // 退款理由
        $out_trade_no = Request::param('out_trade_no');       // 退款订单号

        // 批量非空校验
        $requiredParams = [
            'uuid'         => $uuid,
            'openid'       => $openid,
            'refund_desc'  => $refund_desc,
            'out_trade_no' => $out_trade_no
        ];

        foreach ($requiredParams as $param => $value) {
            if (empty($value)) {
                $this->apiError($param . '不能为空');
            }
        }

        // 校验用户信息 uid 关联的 openid
        $userInfo = \app\common\model\Users::where(['uuid' => $uuid])
            ->find();
        if ($userInfo['openid'] != $openid) {
            $this->apiError('当前用户信息异常，禁止提交');
        }
        $order = Db::name('ticket_order')->where('out_trade_no', $out_trade_no)->where('uuid', $uuid)->find();
        if (!$order) {
            $this->apiError('支付订单不存在!');
        }
        switch ($order['order_status']) {
            case 'created':
                $this->apiError('未支付订单无法退款!');
                break;
            case 'used':
                $this->apiError('已使用订单无法退款!');
                break;
            case 'cancelled':
                $this->apiError('已取消订单无法退款!');
                break;
            case 'refunded':
                $this->apiError('该订单已经全额退款!');
                break;
            default:
                // code...
                break;
        }
        if ($order['refund_status'] == 'fully_refunded') {
            $this->apiError('该订单已经全额退款!');
        }
        //验证该订单下的游客是否全部可退
        $detail_list = OrderDetailModel::where("trade_no",$order["trade_no"])->select()->toArray();
        foreach($detail_list as $item){
            if($item['enter_time'] > 0){
                $this->apiError('该订单中已有游客使用，不允许全退！');
                break;
            }
            if($item['refund_status'] == "fully_refunded"){
                $this->apiError('该订单中已有游客退款，不允许全退！');
                break;
            }
            if(!in_array($item['refund_progress'],["init","refuse"])){
                $this->apiError('该订单中已有游客有退款行为，不允许全退！');
                break;
            }
        }
        // 处理退款
        $res = $this->OrderRefund($order,$refund_desc,$userInfo);
        if ($res === true) {
            $this->apiSuccess('申请成功',$res);
        }
        return $this->apiError('申请失败:'.$res);
    }

    /**
     * 订单申请退款
     * @param array $order
     * @param str   $order_remark
     * @return mixed
     */
    public function OrderRefund($order, $refund_desc, $userInfo)
    {
        // 查询待退款的全部订单
        $order_detail = Db::name('ticket_order_detail')
            ->where('trade_no', $order['trade_no'])
            ->where('uuid', $userInfo['uuid'])
            ->where('refund_status', 'not_refunded') // 退款状态 未退款的
            ->where('refund_progress', 'init')       // 退款进度 为初始化状态的
            ->select()
            ->toArray();
        if(!$order_detail){
            return '当前订单下未检测到可退款的子单';
        }
        // 开启事务
        Db::startTrans();
        try {
            // 生成一条退款交易数据
            $refundData = [
                'uuid'          => $userInfo['uuid'],
                'mch_id'        => $order['mch_id'],
                'trade_no'      => $order['trade_no'],
                'out_refund_no' => 'BIG'.date('YmdHis') . GetNumberCode(6),
                'total_fee'     => $order['amount_price'],
                'refund_fee'    => 0.00, // 退款总金额
                'refund_desc'   => $refund_desc,
                'refund_ip'     => Request::ip(),
                'transaction_id' => $order['transaction_id'],
                'create_time'   => time()
            ];
            $refundId = Db::name('ticket_refunds')->insertGetId($refundData);

            // 计算退款总金额
            $refund_fee = 0.00; // 退款总金额
            foreach ($order_detail as $key => $value) {
                $refund_fee = bcadd(strval($refund_fee), $value['ticket_price'], 2);
                // 修改当前订单退款信息
                Db::name('ticket_order_detail')
                    ->where('id', $value['id'])
                    ->update([
                        'refund_progress' => 'pending_review', // 已提交
                        'is_full_refund' => 1,                // 是否整单退 1=是 0=否
                        'refund_id'     => $refundId
                    ]);
            }

            // 修改总退款金额
            Db::name('ticket_refunds')->where('id', $refundId)->update(['refund_fee' => $refund_fee]);

            // 提交事务
            Db::commit();
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();

            // 返回失败信息
            return $e->getMessage();
        }
    }

    // 微信异步支付结果通知
    public function notify_pay()
    {
        $config  = \app\common\model\TicketPay::wxConfig();
        $pay     = Pay::wechat($config);
        $payinfo = [];
        try {
            $resultData = $pay->verify(); // 验签
            if ($resultData->result_code == 'SUCCESS' && $resultData->return_code == 'SUCCESS') {
                // 验签通过，处理支付成功逻辑
                $payinfo['appid']          = $resultData->appid;                  //小程序ID
                $payinfo['mch_id']         = $resultData->mch_id;                 //商户号
                $payinfo['result_code']    = $resultData->result_code;            //业务结果SUCCESS/FAIL
                $payinfo['openid']         = $resultData->openid;                 //用户标识
                $payinfo['trade_type']     = $resultData->trade_type;             //交易类型
                $payinfo['total_fee']      = $resultData->total_fee;              //订单金额
                $payinfo['transaction_id'] = $resultData->transaction_id;         //微信支付订单号
                $payinfo['trade_no']       = substr($resultData->out_trade_no, 2);//商户订单号
                $payinfo['time_end']       = $resultData->time_end;               //支付完成时间
                $payinfo['status']         = 1;                                   //更改支付状态

                // 查询订单支付记录
                $res = $this->TicketPay::where(['trade_no' => $payinfo['trade_no'], 'status' => 0])
                    ->order('id', 'desc')
                    ->find();
                if ($res !== NULL) {
                    // 更新支付记录
                    $this->TicketPay::where('id', $res->id)
                        ->data($payinfo)
                        ->update();

                    // 更新订单支付状态
                    $orderPay = [
                        'transaction_id'   => $payinfo['transaction_id'],
                        'payment_status'   => $payinfo['status'], // 支付状态
                        'payment_datetime' => $payinfo['time_end'],
                        'order_status'     => 'paid',   // 更改订单状态为已支付
                        'payment_terminal' => 1,        // 支付配置ID 1=小程序
                        'pay_id'           => $res->id, // 支付交易记录ID
                        'update_time'      => time(),
                    ];
                    Db::name('ticket_order')
                        ->where('trade_no', $payinfo['trade_no'])
                        ->data($orderPay)
                        ->update();

                    $payinfo['msg'] = '支付成功';
                } else {
                    $payinfo['msg'] = '交易数据异常';
                }
            } else {
                // 支付失败
                $payinfo['msg'] = '支付失败';
            }
        } catch (\Exception $e) {
            // 验签失败或其他异常错误
            $payinfo['msg'] = '回调异常' . $e->getMessage();
        }
        // 记录通知结果
        $payinfo['ip'] = request()->ip();
        TicketPayNotify::create($payinfo);
        return $pay->success()->send();
    }

    // 微信退款结果通知
    public function notify_refund()//退款验证
    {
        $logDate = date("Ymd",time());
        $config     = $this->TicketRefunds::wxConfig();
        $pay        = Pay::wechat($config);
        $this->create_file($logDate.'-1.txt', 'logs/error/', '回调已接收');
        $refundinfo = [];
        try {
            $setData = $pay->verify(null, true); // 是的，验签就这么简单！  返回数据格式未集合
            
            $this->create_file($logDate.'-2.txt', 'logs/error/', $setData);
            if ($setData->return_code == 'SUCCESS') {//退款成功
                $refundinfo['return_code'] = $setData->return_code; //返回状态码
                $refundinfo['return_msg']            = $setData->return_code; //返回信息
                $refundinfo['appid']                 = $setData->appid;//公众账号ID
                $refundinfo['mch_id']                = $setData->mch_id;//退款的商户号
                $refundinfo['req_info']              = $setData->req_info;//加密信息
                $refundinfo['transaction_id']        = $setData->transaction_id; //微信订单号
                $refundinfo['out_trade_no']          = substr($setData->out_trade_no, 2); //商户订单号
                $refundinfo['refund_id']             = $setData->refund_id;//微信退款单号
                $refundinfo['out_refund_no']         = $setData->out_refund_no;//商户退款单号
                $refundinfo['refund_fee']            = $setData->refund_fee; //退款金额
                $refundinfo['settlement_refund_fee'] = $setData->settlement_refund_fee;   //退款金额
                $refundinfo['refund_status']         = $setData->refund_status;//退款状态
                $refundinfo['success_time']          = $setData->success_time;//退款成功时间
                $refundinfo['refund_recv_accout']    = $setData->refund_recv_accout;//退款入账账户

                $this->create_file($logDate.'-3.txt', 'logs/error/', json_encode($refundinfo));
                // 查询退款交易数据记录
                $res = $this->TicketRefunds::where(['refund_id' => $refundinfo['refund_id'], 'status' => 0])->order('id', 'desc')->find();
                //更新订单支付状态
                if ($refundinfo['refund_status'] == 'SUCCESS') {
                    
                    
                    // 更新退款进度
                    Db::name('ticket_order_detail')
                        ->where('refund_id', $res['id'])
                        ->update(
                            [
                                'update_time'   => time(),
                                'refund_progress'=>'completed'
                            ]
                        );
                    // 如果某个订单下的详情全部退款，则将订单主表退款状态改为全部退款，否则部分退款
                    // 构建子查询
                    $subquery = Db::name('ticket_order')
                        ->alias('a')
                        ->field('a.id, a.order_status, a.trade_no')
                        ->where('a.trade_no', $refundinfo['out_trade_no'])
                        ->fieldRaw('(SELECT COUNT(*) FROM tp_ticket_order_detail AS b WHERE b.trade_no = a.trade_no AND b.refund_status = "not_refunded") AS refund_count')
                        ->buildSql();
                    // 未退款等于0返回数据证明已经全部退款，否则还存在未退款订单
                    $result = Db::table($subquery)
                        ->alias('subquery')
                        ->where('refund_count', 0)
                        ->find();
                    if ($result) {
                        $upOrderData['refund_status']  = 'fully_refunded';// 全部退款
                        $upOrderData['order_status']   = 'refunded';      // 全部退款
                        $upOrderData['payment_status'] = 2;               // 实际支付状态  1=已支付  0=未支付 2=已退款
                    } else {
                        // 还存在未退款的订单，改为部分退款
                        $upOrderData['refund_status'] = 'partially_refunded';
                    }

                    // 更新订单主表状态
                    $upOrderData['update_time'] = time();
                    Db::table('tp_ticket_order')->where('trade_no', $refundinfo['out_trade_no'])
                        ->data($upOrderData)
                        ->inc('refund_fee', $refundinfo['refund_fee'] / 100) // 每次的退款金额累加:单位元
                        ->update();

                    $payinfo['status'] = 1;//更改退款状态
                } else {
                    $payinfo['status'] = 2;//更改退款状态
                }

                $payinfo['settlement_refund_fee'] = $refundinfo['settlement_refund_fee'];//退款金额
                $payinfo['refund_status']         = $refundinfo['refund_status'];        //退款状态
                $payinfo['success_time']          = $refundinfo['success_time'];         //退款成功时间
                $payinfo['refund_recv_accout']    = $refundinfo['refund_recv_accout'];   //退款入账账户

                // 更新退款记录
                $this->TicketRefunds::where('id', $res['id'])
                    ->data($payinfo)
                    ->update();
                //$sql = $this->TicketRefunds::getLastSql();
                //$this->create_file("999.txt", 'logs/', json_encode($sql));
                // 修改订单状态
                $refundinfo['msg'] = '退款成功';
            } else {
                $refundinfo['msg'] = '退款失败！';
            }
        } catch (\Exception $e) {
            $refundinfo['msg'] = '回调异常:' . $e->getMessage();
            $this->create_file($logDate.'-4.txt', 'logs/error/', '回调异常:' . $e->getMessage());
        }
        $refundinfo['ip'] = request()->ip();
        TicketRefundsNotify::create($refundinfo);
        return $pay->success()->send();
    }

    public function create_file($name, $path, $content)
    {
        $toppath = $path . $name;
        $Ts      = fopen($toppath, "a+");
        fputs($Ts, $content . "\r\n");
        fclose($Ts);
    }

    /**
     * 获取指定景区门票列表
     * @param array $seller_id
     * @return array
     */
    public function getTicketList()
    {
        $seller_id = Request::get('seller_id/d', 0);
        if ($seller_id === 0) {
            $this->apiError('缺少商户参数');
        }
        $ticket_list   = TicketModel::where('seller_id', $seller_id)->append(['min_price','rights_list'])->select()->toArray();
        $category_list = [];
        if (!empty($ticket_list)) {
            $category_ids  = array_unique(array_column($ticket_list, "category_id"));
            $category_list = CategoryModel::where('id', 'in', $category_ids)->order("sort desc")->select()->toArray();
            $ticket_list   = array_reduce($ticket_list, function ($result, $item) {
                $category_id = $item['category_id'];
                if (!isset($result[$category_id])) {
                    $result[$category_id] = [];
                }
                $result[$category_id][] = $item;
                return $result;
            }, []);
            foreach ($category_list as &$cate) {
                $cate['ticket_list'] = $ticket_list[$cate['id']];
            }
        }
        $this->apiSuccess('', $category_list);
    }

    /**
     * 获取门票价格
     * @return array
     */
    public function getTicketPirce()
    {
        $ticket_id  = Request::param('ticket_id/d', 0);
        $date_start = Request::param('date_start/s', '');
        $date_end   = Request::param('date_end/s', '');
        $channel    = Request::param('channel/s', ''); //渠道，不同渠道返回不同价格
        if ($ticket_id === 0) {
            $this->apiError('缺少门票参数');
        }
        $where = [
            ['ticket_id', '=', $ticket_id]
        ];
        if (empty($date_start) && empty($date_end)) {
            $where[] = ['date', '>=', date("Y-m-d")];
        } else {
            if (!empty($date_start)) {
                $where[] = ['date', '>=', $date_start];
            }
            if (!empty($date_end)) {
                $where[] = ['date', '<=', $date_end];
            }
        }
        $field = ['ticket_id', 'stock', 'total_stock', 'date'];
        switch ($channel) {
            case 'online':
                $field['online_price'] = 'price';
            case 'casual':
                $field['casual_price'] = 'price';
            case 'team':
                $field['team_price'] = 'price';
        }
        $price_list = PriceModel::where($where)->field($field)->order('date asc')->select()->toArray();
        $this->apiSuccess('', $price_list);
    }


    /**
     * @api             {post} api/ticket/getScenicList 获取景区列表
     * @apiDescription  返回景区列表信息
     */
    public function getScenicList()
    {
        if (!Request::isGet()) {
            $this->apiError('请求方式错误！');
        }
        $param              = Request::get();
        $param['page']      = max([1, (isset($param['page']) ? intval($param['page']) : 1)]);
        $param['page_size'] = isset($param['page_size']) ? intval($param['page_size']) : 10;
        $where              = 'class_id = 2 and status = 1';
        $orderBy            = " id DESC ";
        if (isset($param['orderby']) && !empty($param['orderby'])) {
            if ($param['orderby'] == 'distance') {
                $orderBy = " distance ASC ";
            } else if ($param['orderby'] == 'comment') {
                $orderBy = " comment_rate DESC ";
            }
        }
        if (isset($param['area']) && !empty($param['area'])) {
            $where .= " and area = " . $param['area'];
        }
        if (isset($param['out_id']) && !empty($param['out_id'])) {
            $where .= " and id <> " . $param['out_id'];
        }
        //2023-08-07 增加景区名搜索
        if (isset($param['keywords']) && $param['keywords'] != '') {
            $where .= " and nickname LIKE '%" . $param['keywords']."%'";
        }
        //2023-08-07 增加拥有门票的景区
        if (isset($param['hasTicket']) && $param['hasTicket'] === "true") {
            //先获取有门票的所有商户ID
            $seller_ids = TicketModel::where("status",1)->column("seller_id");
            if(empty($seller_ids)){
                $this->apiSuccess('请求成功', []);
            }
            $seller_ids = implode(",",array_unique($seller_ids));
            $where .= " and id IN(".$seller_ids.")";
        }
        $latitude  = isset($param["latitude"]) ? $param["latitude"] : 1;
        $longitude = isset($param["longitude"]) ? $param["longitude"] : 1;
        $SQRT      = 'SQRT(POW( SIN( PI()*( ' . $latitude . ' - latitude )/ 360 ), 2 )+ COS( PI()* 29.504164 / 180 )* COS( ' . $latitude . ' * PI()/ 180 )* POW( SIN( PI()*( ' . $longitude . ' - longitude )/ 360 ), 2 ))';
        $sql       = "SELECT id,status,nickname,image,mobile,do_business_time,area,comment_rate,comment_num,address,content,longitude,latitude,class_id,distance FROM (SELECT  *,round(( 2 * 6378.137 * ASIN($SQRT)) * 1000 ) AS distance FROM tp_seller) a WHERE " . $where . " ORDER BY " . $orderBy . " limit " . ($param['page'] - 1) * $param['page_size'] . "," . $param['page_size'] . "";
        $list      = Db::query($sql);
        if (!empty($list)) {
            $area_list = $this->app->config->get('lang.area');
            foreach ($list as $key => $value) {
                // 2023-03-18 经纬度未获取到返回 0
                $list[$key]['distance'] = 0;
                if ($latitude != 1) {
                    $list[$key]['distance'] = $value['distance'] / 1000;
                }
                $list[$key]['area_text'] = isset($area_list[$value['area']]) ? $area_list[$value['area']] : '-';
                $list[$key]['min_price'] = Db::name('ticket_price')->where("seller_id", $value['id'])->whereDay("date")->value('online_price');

            }
        }
        $this->apiSuccess('请求成功', $list);
    }

    /**
     * 写评论
     * $param {content,rate,order_id,}
     */
    public function writeComment()
    {
        if (!Request::isPost()) {
            $this->apiError('请求方式错误！');
        }
        $post     = Request::post();
        $validate = Validate::rule([
            'order_id' => 'require',
            'content'  => 'require|min:10',
            'rate'     => 'require|max:5'
        ]);
        $validate->message([
            'order_id.require' => '参数错误！',
            'content.require'  => '评论内容不能为空！',
            'content.min'      => '评论内容太短！',
            'rate.require'     => '请选择评分！',
            'rate.max'         => '评分不能超过5！',
        ]);
        if (!$validate->check($post)) {
            $this->apiError($validate->getError());
        }
        $order_info = OrderModel::where("id", "=", $post['order_id'])->find();

        if (!$order_info) {
            $this->apiError('订单不存在！');
        }
        $userInfo = Users::where('id', Request::header('Userid'))->find();
        if ($userInfo['uuid'] !== $order_info['uuid']) {
            $this->apiError('订单不存在！');
        }
        if ($order_info['order_status'] != 'used') {
            $this->apiError('请使用后再评论！');
        }
        $find = TicketCommentModel::where("order_id", $order_info['id'])->find();
        if ($find) {
            $this->apiError('该订单已评论！');
        }
        $insertData = [
            'order_id'    => $post['order_id'],
            'content'     => purXss($post['content']),
            'seller_id'   => $order_info['mch_id'],
            'user_id'     => $userInfo['id'],
            'rate'        => number_format(floatval($post['rate']), 2),
            'create_time' => time(),
            'update_time' => time(),
            'ip'          => Request::ip(),
            'status'      => 0
        ];
        $result     = TicketCommentModel::insert($insertData);
        if ($result) {
            $this->apiSuccess('评论成功！');
        }
        $this->apiError('评论失败！');


    }

    /*
     * 获取指定景区的评论列表
     * */
    public function getCommentList()
    {
        if (!Request::isGet()) {
            $this->apiError('请求方式错误！');
        }
        $param = Request::get();
        $where = [];
        $where[] = ['status', '=', 1];
        if (isset($param['mid']) && $param['mid'] != '') {
            $where[] = ['seller_id', '=', $param['mid']];
        }
        if (isset($param['user_id']) && $param['user_id'] != '') {
            $where[] = ['user_id', '=', $param['user_id']];
        }
        $orderby = "id desc";
        if (isset($param['orderby']) && $param['orderby'] == 'rate') {
            $orderby = "rate desc";
        }
        $page      = max(1, (isset($param['page']) ? intval($param['page']) : 1));
        $page_size = isset($param['page_size']) ? intval($param['page_size']) : 10;
        $list = TicketCommentModel::where($where)->with('users')->order($orderby)->page($page, $page_size)->select();
        $list->hidden(['order_id', 'seller_id', 'user_id', 'update_time', 'status', 'ip']);
        $list->visible(['users' => ['headimgurl', 'nickname']])->toArray();
        $this->apiSuccess('获取成功！', $list);
    }

    /*
     * 分页获取我的订单列表
     * */
    public function getOrderList()
    {
        if (!Request::isGet()) {
            $this->apiError('请求方式错误！');
        }
        $param    = Request::get();
        $where    = [];
        $userInfo = Users::where('id', Request::header('Userid'))->findOrEmpty();
        $where[]  = ['uuid', "=", $userInfo->uuid];
        if (isset($param['status']) && $param['status'] != '') {
            $where[] = ["order_status", "=", $param['status']];
        }
        $page      = max(1, (isset($param['page']) ? (int)$param['page'] : 1));
        $page_size = isset($param['page_size']) ? (int)$param['page_size'] : 10;
        $list      = OrderModel::where($where)->with(["seller"])->page($page, $page_size)->append(['order_status_text', 'channel_text', 'iscomment'])->order("id desc")->select();
        $list->visible(['id', 'trade_no', 'origin_price', 'amount_price', 'channel', 'create_time', 'order_status','refund_status','refund_fee','write_off_num','seller' => ['image', 'nickname']]);
        $list = $list->toArray();
        if (!empty($list)) {
            $trade_no_list = array_column($list, "trade_no");
            $detail_list   = OrderDetailModel::where("trade_no", "in", $trade_no_list)->select();
            $detail_list->hidden(['update_time', 'delete_time'])->toArray();
            if (!empty($detail_list)) {
                $result = [];
                foreach ($detail_list as $item) {
                    $name = $item['trade_no'];
                    if (!isset($result[$name])) {
                        $result[$name] = [];
                    }
                    $result[$name][] = $item;
                }
                $detail_list = $result;
                foreach ($list as &$item) {
                    $item['detail_list'] = isset($detail_list[$item['trade_no']]) ? $detail_list[$item['trade_no']] : [];
                }
            }
        }
        $this->apiSuccess('获取成功！', $list);
    }

    /**
     * 获取订单详情
     * @return array
     */
    public function getOrderDetail()
    {
        if (!Request::isGet()) {
            $this->apiError('请求方式错误！');
        }
        $order_id = Request::get('order_id/d', 0);
        if ($order_id === 0) {
            $this->apiError('缺少订单参数');
        }
        $where['id'] = $order_id;
        $userInfo    = Users::where('id', Request::header('Userid'))->find();
        $orderInfo   = OrderModel::where($where)->with("seller")->append(['order_status_text', 'channel_text', 'iscomment', 'qrcode_str','rights_qrcode_list'])->find();
        if ($userInfo['uuid'] != $orderInfo['uuid']) {
            //判断是否是核销员
            $hx_man = MerchantVerifierModel::where([["uid","=",$userInfo['id']],["type","=","ticket"]])->find();
            if (!$hx_man) {
                $this->apiError('权限不足！');
            }
        }
        if ($orderInfo) {
            $orderInfo   = $orderInfo->visible(['id', 'trade_no','out_trade_no', 'origin_price', 'amount_price', 'channel', 'create_time', 'order_status','refund_status','refund_fee','write_off_num','seller' => ['image', 'nickname']])->toArray();
            $detail_list = OrderDetailModel::where("trade_no", "=", $orderInfo['trade_no'])->append(["tourist_cert_type_text", "refund_status_text", 'qrcode_str','rights_list'])->select();
            $detail_list = $detail_list->hidden(['update_time', 'delete_time','uuid'])->toArray();
            $orderInfo['detail_list'] = $detail_list;
            if (!empty($detail_list)) {
                //订单只能买一种门票，所以门票信息一样，提取出来放在订单里
                $orderInfo['ticket_info'] = [
                    'id'          => $detail_list[0]['ticket_id'],
                    'title'       => $detail_list[0]['ticket_title'],
                    'date'        => $detail_list[0]['ticket_date'],
                    'cover'       => $detail_list[0]['ticket_cover'],
                    'price'       => $detail_list[0]['ticket_price'],
                    'explain_use' => $detail_list[0]['explain_use'],
                    'explain_buy' => $detail_list[0]['explain_buy']
                ];
            }
        }
        $this->apiSuccess('', $orderInfo);
    }

    public function writeOff()
    {
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
        $user_id = Request::header("Userid");
        $hx_man  = MerchantVerifierModel::where([["uid","=",$user_id],["type","=","ticket"]])->find();
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
        // order单次核销所有游客，detail单次核销某个游客 orderrights 核销所有游客的某一个权益，rights 核销指定游客的有一个权益；ticket核销订单的某个票种的所有游客
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

    public function getOrderDetailDetail()
    {
        if (!Request::isGet()) {
            $this->apiError("请求方式错误！");
        }
        $order_detail_id = Request::get('order_detail_id/d', 0);
        if ($order_detail_id === 0) {
            $this->apiError('缺少参数');
        }
        $userInfo    = Users::where('id', Request::header('Userid'))->find();
        $detail_info = OrderDetailModel::where("id", "=", $order_detail_id)->append(["tourist_cert_type_text", "refund_status_text", 'qrcode_str','rights_list'])->find();
        if(!$userInfo || !$detail_info){
            $this->apiError('未找到记录！');
        }
        if ($userInfo['uuid'] != $detail_info['uuid']) {
            //判断是否是核销员
            $hx_man = MerchantVerifierModel::where([["uid","=",$userInfo['id']],["type","=","ticket"]])->find();
            if (!$hx_man) {
                $this->apiError('权限不足！');
            }
        }
        $detail_info           = $detail_info->hidden(['update_time', 'delete_time', 'uuid'])->toArray();
        $detail_info['seller'] = \app\common\model\Seller::where("id", "=", function ($query) use ($detail_info) {
            $query->name("ticket")->where("id", $detail_info['ticket_id'])->field("seller_id");
        })->field("nickname,image")->findOrEmpty()->toArray();
        $this->apiSuccess('', $detail_info);
    }
    public function getRefundLogList(){
        if (!Request::isGet()) {
            $this->apiError("请求方式错误！");
        }
        $where = [];
        $userInfo    = Users::where('id', Request::header('Userid'))->find();
        if (!$userInfo) {
            $this->apiError("用户不存在！");
        }
        $where[] = ["uuid","=",$userInfo['uuid']];
        $page      = max(1, (isset($param['page']) ? (int)$param['page'] : 1));
        $page_size = isset($param['page_size']) ? (int)$param['page_size'] : 10;
        $list = TicketRefundsModel::where($where)->append(['status_text'])->order("id desc")->page($page,$page_size)->select()->toArray();
        if($list){
            $order_codes = [];
            $order_detail_codes = [];
            $seller_ids = [];
            foreach($list as $item){
                $order_codes[] = $item['trade_no'];
                $order_detail_codes[] = $item['order_detail_no'];
                $seller_ids[] = $item['mch_id'];
            }
            $order_codes = array_unique($order_codes);
            $order_detail_codes = array_unique($order_detail_codes);
            $seller_ids = array_unique($seller_ids);
            //开始挨个查询
            $order_list = OrderModel::where("trade_no","in",$order_codes)->append(['channel_text','order_status_text','payment_status_text','refund_status_text'])->select();
            $order_list = $order_list->visible(['id', 'trade_no', 'origin_price', 'amount_price', 'channel', 'create_time', 'order_status','payment_status','refund_status'])->toArray();
            $order_list = array_combine(array_column($order_list, 'trade_no'), $order_list);

            $order_detail_list = OrderDetailModel::where("out_trade_no","in",$order_detail_codes)->append(['refund_progress_text','cert_type_text','refund_status_text'])->select();
            $order_detail_list = $order_detail_list->hidden(['update_time', 'delete_time'])->toArray();
            $order_detail_list = array_combine(array_column($order_detail_list, 'out_trade_no'), $order_detail_list);
            $seller_list = SellerModel::where("id","in",$seller_ids)->column("nickname,image","id");
            foreach($list as &$item){
                $item['info_order'] =isset($order_list[$item['trade_no']]) ? $order_list[$item['trade_no']] :null;
                $item['info_order_detail'] = isset($order_detail_list[$item['order_detail_no']]) ? $order_detail_list[$item['order_detail_no']] :null;
                $item['info_seller'] =isset($seller_list[$item['mch_id']]) ? $seller_list[$item['mch_id']] :null;
            }
        }
        $this->apiSuccess('', $list);
    }

    /*
     * 退款详情
     * */
    public function getRefundLogDetail(){
        if (!Request::isGet()) {
            $this->apiError("请求方式错误！");
        }
        $where = [];
        $userInfo    = Users::where('id', Request::header('Userid'))->find();
        if (!$userInfo) {
            $this->apiError("用户不存在！");
        }
        $id = Request::get("id/d",0);
        if ($id < 1) {
            $this->apiError("参数错误！");
        }
        $info = TicketRefundsModel::where("id",$id)->append(['status_text'])->find();
        if(!$info){
            $this->apiError("记录不存在！");
        }
        if($info['uuid'] != $userInfo['uuid']){
            $this->apiError("参数错误！");
        }
        $order_info = OrderModel::where("trade_no","=",$info['trade_no'])->append(['channel_text','order_status_text','payment_status_text','refund_status_text'])->find();
        $order_info = $order_info->visible(['id', 'trade_no', 'origin_price', 'amount_price', 'channel', 'create_time', 'order_status','payment_status','refund_status'])->toArray();
        $order_detail_list = OrderDetailModel::where("refund_id","=",$info['id'])->append(['refund_progress_text','cert_type_text','refund_status_text'])->select();
        $order_detail_list = $order_detail_list->hidden(['update_time', 'delete_time'])->toArray();
        $seller_info = SellerModel::where("id","=",$info['mch_id'])->field("nickname,image")->find();
        $info['info_order'] = $order_info;
        $info['info_order_detail'] = $order_detail_list;
        $info['info_seller'] = $seller_info;
        $this->apiSuccess('', $info);
    }

    /**
     * 取消退款申请
     * @return array
     */
    public function cancelRefund()
    {
        if (!Request::isPost()) {
            $this->apiError('请求方式错误！');
        }
        $type = Request::post('type/s', "");
        $id = Request::post('id/d', 0);
        if ($type === "" || $id === 0) {
            $this->apiError('参数错误！');
        }
        $userInfo = Users::where('id', Request::header('Userid'))->find();
        if($type == 'order'){
            $order_info = OrderModel::where("id",$id)->find();
            if(!$order_info){
                $this->apiError('订单不存在！');
            }
            if($order_info['uuid'] !== $userInfo['uuid']){
                $this->apiError('订单不存在！');
            }
            if($order_info["order_status"] != 'paid'){
                $this->apiError('订单状态不符！');
            }
            Db::startTrans();
            try {
                //将退款记录状态变为3 3为用户主动取消
                TicketRefundsModel::where(["trade_no"=>$order_info["trade_no"],"uuid"=>$userInfo["uuid"],"status"=>0])->update(['status'=>3]);
                //将订单从表退款进度变为初始化
                OrderDetailModel::where(["trade_no"=>$order_info["trade_no"],"refund_progress"=>"pending_review"])->update(['refund_progress'=>"init"]);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->apiError("取消失败！".$e->getMessage());
            }
            $this->apiSuccess('取消成功！');
        }else if($type == 'order_detail'){
            $order_detail_info = OrderDetailModel::where("id",$id)->find();
            if(!$order_detail_info){
                $this->apiError('门票不存在！');
            }
            if($order_detail_info['uuid'] !== $userInfo['uuid']){
                $this->apiError('门票不存在！');
            }
            if($order_detail_info['refund_progress'] != 'pending_review'){
                $this->apiError('该门票状态不符！');
            }
            Db::startTrans();
            try {
                //将此门票相关的退款记录状态变为3；3为用户主动取消
                TicketRefundsModel::where(["order_detail_no"=>$order_detail_info["out_trade_no"],"uuid"=>$userInfo["uuid"],"status"=>0])->update(['status'=>3]);
                $order_detail_info->refund_progress = "init";
                $order_detail_info->save();
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->apiError("取消失败！".$e->getMessage());
            }
            $this->apiSuccess('取消成功！');
        }
        $this->apiError('参数错误！');
    }
    /**
     * 获取旅行社订单详情
     * @return array
     */
    public function getTravelOrderDetail()
    {
        if (!Request::isGet()) {
            $this->apiError('请求方式错误！');
        }
        $trade_no = Request::get('trade_no/s', "");
        if ($trade_no === "") {
            $this->apiError('缺少订单参数');
        }
        $where['trade_no'] = $trade_no;
        $orderInfo   = OrderModel::where($where)->with("seller")->append(['order_status_text', 'channel_text', 'iscomment', 'qrcode_str','rights_qrcode_list'])->find();
        if ($orderInfo) {
            $orderInfo   = $orderInfo->visible(['id', 'trade_no','out_trade_no', 'origin_price', 'amount_price', 'channel', 'create_time', 'order_status','refund_status','refund_fee','write_off_num','seller' => ['image', 'nickname']])->toArray();
            $detail_list = OrderDetailModel::where("trade_no", "=", $orderInfo['trade_no'])->append(["tourist_cert_type_text", "refund_status_text", 'qrcode_str','rights_list'])->select();
            $detail_list->hidden(['update_time', 'delete_time','uuid'])->toArray();
            $orderInfo['detail_list'] = $detail_list;
            if (!empty($detail_list)) {
                //订单只能买一种门票，所以门票信息一样，提取出来放在订单里
                $orderInfo['ticket_info'] = [
                    'id'          => $detail_list[0]['ticket_id'],
                    'title'       => $detail_list[0]['ticket_title'],
                    'date'        => $detail_list[0]['ticket_date'],
                    'cover'       => $detail_list[0]['ticket_cover'],
                    'price'       => $detail_list[0]['ticket_price'],
                    'explain_use' => $detail_list[0]['explain_use'],
                    'explain_buy' => $detail_list[0]['explain_buy']
                ];
            }
        }
        $this->apiSuccess('', $orderInfo);
    }


    /*
     *
     * 支付旅行社订单。
     *
     * */
    public function travelOrderPay(){
        $code     = Request::param('code/s', '');      // 小程序wxlogin的code
        $trade_no = Request::param('trade_no/s', '');  // 订单内部编码
        if($code ==="" || $trade_no === ""){
            $this->apiError('缺少参数！');
        }
        // 获取微信小程序参数
        $wechat = \app\common\model\System::find(1);
        // 微信登录地址
        $infourl  = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $wechat['appid'] . "&secret=" . $wechat['appsecret'] . "&js_code=" . $code . "&grant_type=authorization_code";
        $jsonData = http_curl_get($infourl, true);
        $jsonData = json_decode($jsonData,true);

        if(!isset($jsonData['session_key'])){
            $this->apiError('数据异常：sessionKey不存在');
        }
        $openid = $jsonData['openid'];
        $order =  OrderModel::where('trade_no', $trade_no)->find();
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
}