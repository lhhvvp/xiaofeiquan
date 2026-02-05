<?php
/**
 * @desc   小程序支付API
 * @author slomoo
 * @email slomoo@aliyun.com
 * 2023-06-30 门票支付
 */
declare (strict_types=1);

namespace app\xc\controller;

use app\xc\BaseController;
use app\xc\middleware\Auth;
use app\xc\service\JwtAuth;
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

        // 门票信息
        $ticketData = json_decode($requiredParams['data'], true);
        // 实际要去支付的金额=调起微信支付使用的=所有门票加起来的价格
        $amount_price = "0.00";
        $origin_price = "0.00";
        $total_number = 0;
        $orderDetail  = [];
        foreach ($ticketData as $key => $value) {
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
            /*if ($ticketInfo->quota > 0) {
                // 检查当天用户购买总数
                $todayStart = strtotime(date('Y-m-d 00:00:00'));
                $todayEnd   = strtotime(date('Y-m-d 23:59:59'));
                $quota      = \app\common\model\TicketOrderDetail::where('uuid', $uuid)->whereBetween('create_time', [$todayStart, $todayEnd])->count();
                if (($quota + $value['number']) > $ticketInfo->quota) {
                    $this->apiError('该门票每天限购' . $ticketInfo->quota . '张,本次最多可购买' . ($ticketInfo->quota - $quota));
                }
            }*/
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
        // 开始事务
        Db::startTrans();
        try {
            $order_id = Db::name('ticket_order')->insertGetId($order);
            $order['id'] = $order_id;

            foreach ($ticketData as $key => $value) {
                // 重组订单详情数据
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
                    'update_time'       => time(),
                    // 2023-08-09 新增相同门票购买数量 线下售票使用
                    'ticket_number'     => $value['number']
                ];
                $order_detail_info = OrderDetailModel::create($insertData);
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
        $this->apiSuccess('订单添加成功', []);
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
        $ticket_number = Request::param('ticket_number');       // 退款张数
        $uuid         = Request::param('uuid/s', '');    // 商户账号

        // 批量非空校验
        $requiredParams = [
            'refund_desc'  => $refund_desc,
            'out_trade_no' => $out_trade_no,
            'ticket_number'=> $ticket_number,
            'uuid'         => $uuid
        ];

        foreach ($requiredParams as $param => $value) {
            if (empty($value)) {
                $this->apiError($param . '不能为空');
            }
        }

        $order_detail = Db::name('ticket_order_detail')->where('out_trade_no', $out_trade_no)->find();
        if (!$order_detail) {
            $this->apiError('支付订单不存在!');
        }

        if($ticket_number > $order_detail['ticket_number']){
            $this->apiError('最多能退'.$order_detail['ticket_number'].'张');
        }

        // 检验是否为窗口订单
        $order = Db::name('ticket_order')->where('trade_no', $order_detail['trade_no'])->find();
        if (!$order) {
            $this->apiError('订单异常');
        }
        if($order['channel'] != 'window'){
            $this->apiError('非窗口订单禁止退款');
        }

        switch ($order_detail['refund_status']) {
            case 'fully_refunded':
                $this->apiError('该订单已经全额退款!');
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
        $res = $this->OrderRefundDetail($order,$order_detail,$refund_desc,$ticket_number,$uuid);

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
    public function OrderRefundDetail($order,$order_detail, $refund_desc,$ticket_number,$uuid)
    {
        // 开启事务
        Db::startTrans();
        try {
            // 生成一条退款交易数据
            $refundData = [
                'uuid'          => $uuid,
                'mch_id'        => $order['mch_id'],
                'trade_no'      => $order['trade_no'],
                'order_detail_no' => $order_detail['out_trade_no'],
                'out_refund_no' => $order_detail['out_refund_no'],
                'total_fee'     => $order['amount_price'],
                'refund_fee'    => $order_detail['ticket_price'] * $ticket_number,
                'refund_desc'   => $refund_desc,
                'refund_ip'     => Request::ip(),
                'transaction_id' => $ticket_number, // 以此作为本次退款的张数
                'create_time'   => time(),
                'status'        => 2
            ];
            $refundId = Db::name('ticket_refunds')->insertGetId($refundData);

            $refund_progress = 'init';
            $refund_status   = 'not_refunded';
            
            if(($order_detail['ticket_number'] - $ticket_number) == 0) {
                $refund_progress = 'completed';
                $refund_status   = 'fully_refunded';
            }
            // 修改当前订单退款信息
            Db::name('ticket_order_detail')
                ->where('id', $order_detail['id'])
                ->update([
                    'refund_progress' => $refund_progress,
                    'is_full_refund' => 0,                 // 是否整单退 1=是 0=否
                    'refund_status'  => $refund_status,
                    'refund_id'      => $refundId,
                    'ticket_number'  => $order_detail['ticket_number'] - $ticket_number
                ]);

            // 如果某个订单下的详情全部退款，则将订单主表退款状态改为全部退款，否则部分退款
            // 构建子查询
            $subquery = Db::name('ticket_order')
                ->alias('a')
                ->field('a.id, a.order_status, a.trade_no')
                ->where('a.trade_no', $order['trade_no'])
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
            Db::table('tp_ticket_order')->where('trade_no', $order['trade_no'])
                ->data($upOrderData)
                ->inc('refund_fee', $ticket_number * $order_detail['ticket_price']) // 每次的退款金额累加:单位元
                ->update();

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

        $order = Db::name('ticket_order')->where('out_trade_no', $out_trade_no)->find();
        if (!$order) {
            $this->apiError('支付订单不存在!');
        }
        if($order['channel'] != 'window'){
            $this->apiError('非窗口订单禁止退款');
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

        // 处理退款
        $res = $this->OrderRefund($order,$refund_desc,$uuid);

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
    public function OrderRefund($order, $refund_desc,$uuid)
    {
        // 查询待退款的全部订单
        $order_detail = Db::name('ticket_order_detail')
            ->where('trade_no', $order['trade_no'])
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
                'uuid'          => $uuid,
                'mch_id'        => $order['mch_id'],
                'trade_no'      => $order['trade_no'],
                'out_refund_no' => 'BIG'.date('YmdHis') . GetNumberCode(6),
                'total_fee'     => $order['amount_price'],
                'refund_fee'    => 0.00, // 退款总金额
                'refund_desc'   => $refund_desc,
                'refund_ip'     => Request::ip(),
                'transaction_id' => $order['transaction_id'],
                'create_time'   => time(),
                'status'        => 2
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
                        'refund_progress' => 'completed', // 已提交
                        'is_full_refund' => 1,                // 是否整单退 1=是 0=否
                        'refund_id'     => $refundId,
                        'refund_status' => 'fully_refunded',
                        'ticket_number' => 0
                    ]);
            }

            // 修改总退款金额
            Db::name('ticket_refunds')->where('id', $refundId)->update(['refund_fee' => $refund_fee]);

            $upOrderData['refund_status']  = 'fully_refunded';// 全部退款
            $upOrderData['order_status']   = 'refunded';      // 全部退款
            $upOrderData['payment_status'] = 2;               // 实际支付状态  1=已支付  0=未支付 2=已退款

            // 更新订单主表状态
            $upOrderData['update_time'] = time();
            Db::table('tp_ticket_order')->where('trade_no', $order['trade_no'])
                ->data($upOrderData)
                ->inc('refund_fee', (float) $refund_fee) // 每次的退款金额累加:单位元
                ->update();

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
        $order['detail'] = $this->TicketOrderDetail::where('trade_no',$trade_no)->append(['refund_progress_text','refund_status_text'])->select()->toArray();
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
}