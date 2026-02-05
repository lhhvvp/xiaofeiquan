<?php
/**
 * @desc   窗口售票员登录基础信息API
 * @author slomoo
 * @email slomoo@aliyun.com
 */
declare (strict_types=1);

namespace app\xc\controller;

use app\common\model\TicketPrice;
use app\xc\BaseController;
use app\xc\middleware\Auth;
use think\facade\Db;
use think\facade\Request;
use think\facade\Cache;
use app\xc\service\XiechengService;
use app\common\model\ticket\Ticket as TicketModel;
use app\common\model\ticket\Price as PriceModel;
use app\common\model\ticket\Order as OrderModel;
use app\common\model\ticket\OrderDetail as OrderDetailModel;
use app\common\model\ticket\OrderDetailRights as OrderDetailRightsModel;
use app\xc\model\OrderOtaItemModel;
use app\xc\model\OrderOtaModel;
use think\facade\Config;
use think\Exception;

class Order extends BaseController
{
    /**
     * 订单接收入口
     * @var array
     */
    // 上传验证规则
    protected $uploadValidate = [];

    protected $middleware = [
        Auth::class => ['except' => ['OrderTravelNotice', 'OrderConsumedNotice', 'CancelOrderConfirm', 'RefundOrderConfirm', 'testGetOrder']]
    ];

    // 初始化
    protected function initialize()
    {
        $this->aes = new XiechengService;
        // 接口帐号
        $this->accountId = Config::get('ota.xiecheng.accountId');
        // 接口密钥
        $this->signKey = Config::get('ota.xiecheng.signKey');
        // AES 加密密钥
        $this->aesKey = Config::get('ota.xiecheng.aesKey');
        // AES 加密初始向量
        $this->aesIv = Config::get('ota.xiecheng.aesIv');
    }

    // 报文接收地址
    public function accept()
    {

    }

    public function CreatePreOrder($body = [])
    {
        /*$body         = '{
            "sequenceId": "2017-10-10abcd95774f17c3e354e73f7aaf21b5ec",
            "otaOrderId": "20230830006",
            "contacts": [
              {
                  "name": "联系人",
                "mobile": "13000000000",
                "intlCode": "",
                "optionalMobile": "13000000000",
                "optionalIntlCode": "",
                "email": ""
              }
            ],
            "items": [
              {
                  "PLU": "T7928641283224950346",
                "locale": "test-plu-1",
                "distributionChannel": "",
                "useStartDate": "2023-09-06",
                "useEndDate": "2023-09-06",
                "remark": "备注信息",
                "price": 100,
                "priceCurrency": "",
                "cost": 90,
                "costCurrency": "",
                "suggestedPrice": "",
                "suggestedPriceCurrency": "",
                "quantity": 1,
                "passengers": [
                  {
                      "name": "出行人",
                    "firstName": "",
                    "lastName": "",
                    "mobile": "13100000000",
                    "intlCode": "",
                    "cardType": "",
                    "cardNo": "",
                    "birthDate": "",
                    "ageType": "",
                    "gender": "",
                    "nationalityCode": "CN",
                    "nationalityName": "中国",
                    "cardIssueCountry": "中国",
                    "cardIssuePlace": "上海",
                    "cardIssueDate": "2016-12-12",
                    "cardValidDate": "2020-12-12",
                    "birthPlace": "上海",
                    "height": 175,
                    "weight": 80,
                    "myopiaDegreeL": 200,
                    "myopiaDegreeR": 200,
                    "shoeSize": 42
                  }
                ],
                "adjunctions": [
                  {
                      "name": "取件点",
                    "nameCode": "name-qjd",
                    "content": "虹桥机场",
                    "contentCode": "content-123"
                  }
                ],
                "deposit": {
                  "type": 1,
                  "amount": 100,
                  "amountCurrency": ""
                },
                "expressDelivery": {
                  "type": "1",
                  "name": "收件人姓名",
                  "mobile": "13000000000",
                  "intlCode": "",
                  "country": "中国",
                  "province": "上海",
                  "city": "上海",
                  "district": "长宁区",
                  "address": "中国上海市长宁区福泉路99号携程技术大厦 200335"
                }
              }
            ]
          }';*/

        $data         = json_decode($body, true);
        $code         = '0000';
        $msg          = '';
        $result_items = [];
        $order_info   = [];
        //先查询携程订单号是否已加下单
        $ota_order_info = OrderOtaModel::where("otaOrderId", $data["otaOrderId"])->find();
        if ($ota_order_info) {
            //重复下单，直接返回订单信息
            $ota_order_item_list = OrderOtaItemModel::where("ota_id", $ota_order_info['id'])->select()->toArray();
            $order_info          = OrderModel::where("out_trade_no", $ota_order_info["out_trade_no"])->find();
            foreach ($ota_order_item_list as $val) {
                $result_items[] = [
                    'PLU'        => $val["ticket_code"],
                    'inventorys' => [
                        'useDate'  => $val['date'],
                        'quantity' => $val['stock']
                    ]
                ];
            }
        } else {
            //首次下单，创建订单信息
            Db::startTrans();
            try {
                $origin_price  = '0';
                $amount_price  = '0';
                $mch_id        = '0';
                $order_remark  = '';
                $contact_man   = "";
                $contact_phone = "";
                $trade_no      = uniqidDate(20);
                $orderData     = [
                    'openid'           => "",
                    'trade_no'         => $trade_no,
                    'out_trade_no'     => "MP" . $trade_no,
                    'channel'          => "ota_xc",
                    'type'             => "ota_xc",
                    'payment_terminal' => "",
                    'order_remark'     => "",
                    'order_status'     => "created",
                    'refund_status'    => "not_refunded",
                    'create_time'      => time(),
                    'update_time'      => time()
                ];
                $order_info    = OrderModel::create($orderData);
                foreach ($data['contacts'] as $item) {
                    //将联系人数组得最后一人写入订单联系人信息。
                    $contact_man   = $item['name'];
                    $contact_phone = $item['mobile'];
                }
                $ota_order_data = [
                    'channel'      => 'xc',
                    'otaOrderId'   => $data['otaOrderId'],
                    'out_trade_no' => $order_info['out_trade_no'],
                    'raw_data'     => serialize($data),
                    'create_time'  => time()
                ];
                $ota_order_data = OrderOtaModel::create($ota_order_data);
                foreach ($data['items'] as $item) {
                    $item['remark'] = isset($item['remark']) ? $item['remark'] : "";
                    if (strtotime($item['useStartDate']) < strtotime('today midnight')) {
                        $code = '1002';
                        throw new Exception("{$item['useStartDate']}已过期！");
                    }
                    $origin_price = bcadd(strval($origin_price), bcmul(strval($item['price']), strval($item['quantity']), 2), 2);
                    $amount_price = bcadd(strval($amount_price), bcmul(strval($item['cost']), strval($item['quantity']), 2), 2);
                    //查询票种
                    $ticket_info = TicketModel::where("code", $item['PLU'])->append(['category_text', 'rights_list'])->find();
                    if (!$ticket_info) {
                        $code = '1001';
                        throw new Exception("门票不存在");
                    }
                    if ($ticket_info['status'] != 1) {
                        $code = '1002';
                        throw new Exception("该门票已下架");
                    }
                    $ticket_price = PriceModel::where([["ticket_id", "=", $ticket_info['id']], ["date", "=", $item['useStartDate']]])->find();
                    if (!$ticket_price) {
                        $code = '1007';
                        throw new Exception("价格不存在");
                    }
                    if ($ticket_price['stock'] < $item['quantity']) {
                        $code = '1003';
                        throw new Exception("库存不足!");
                    }
                    $mch_id = $ticket_info['seller_id'];

                    $order_remark        .= $ticket_info['title'] . ':' . $item['remark'] . '/';
                    $ticket_price->stock = $ticket_price['stock'] - $item['quantity'];
                    $ticket_price->save();
                    $ota_order_item_data = [
                        'code'         => uniqidNumber(20, "XCI"),
                        'ota_id'       => $ota_order_data['id'],
                        'out_trade_no' => $order_info['out_trade_no'],
                        'detail_ids'   => [],
                        'quantity'     => $item['quantity'],
                        'date'         => $item['useStartDate'],
                        'remark'       => $item['remark'],
                        'price'        => $item['price'],
                        'cost'         => $item['cost'],
                        'ticket_id'    => $ticket_info['id'],
                        'ticket_code'  => $ticket_info['code'],
                        'stock'        => $ticket_price['stock'],
                    ];

                    //开始写入订单从表
                    foreach ($item['passengers'] as $val) {
                        $orderDetailData   = [
                            'uuid'                => '0',
                            'trade_no'            => $order_info['trade_no'],
                            'out_trade_no'        => uniqidDate(20, "DMP"),
                            'out_refund_no'       => uniqidDate(20, "REF"),
                            'ticket_code'         => uniqidDate(20, "TC"),
                            'tourist_fullname'    => $val['name'],
                            'tourist_cert_type'   => $val['cardType'], //传过来的是汉字，需要转为tinyint
                            'tourist_cert_id'     => $val['cardNo'],
                            'tourist_mobile'      => $val['mobile'],
                            'ticket_cate_id'      => $ticket_info['category_id'],
                            'ticket_id'           => $ticket_info['id'],
                            'ticket_title'        => $ticket_info['title'],
                            'ticket_date'         => $ticket_price['date'],
                            'ticket_cover'        => $ticket_info['cover'],
                            'ticket_price'        => $ticket_price['team_price'],
                            'ticket_rights_num'   => $ticket_info['rights_num'],
                            'writeoff_rights_num' => 0,
                            'explain_use'         => $ticket_info['explain_use'],
                            'explain_buy'         => $ticket_info['explain_buy'],
                            'create_time'         => time(),
                            'update_time'         => time(),
                        ];
                        $order_detail_info = OrderDetailModel::create($orderDetailData);
                        //开始写入核销权益表
                        if ($ticket_info['rights_num'] > 0) {
                            foreach ($ticket_info['rights_list'] as $rr) {
                                $insertData = [
                                    'order_id'     => $order_info['id'],
                                    'detail_id'    => $order_detail_info['id'],
                                    'detail_date'  => $order_detail_info['ticket_date'],
                                    'detail_code'  => $order_detail_info['ticket_code'],
                                    'rights_title' => $rr['title'],
                                    'rights_id'    => $rr['id'],
                                    'status'       => 0,
                                    'create_time'  => time(),
                                    'update_time'  => time(),
                                    'code'         => uniqidDate(20, "RS"),
                                    'seller_id'    => $ticket_info['seller_id']
                                ];
                                OrderDetailRightsModel::create($insertData);
                            }
                        }
                        //开始写入ota订单项目表
                        $ota_order_item_data['detail_ids'][] = $order_detail_info['id'];
                    }
                    $ota_order_item_data['detail_ids'] = $ota_order_item_data['detail_ids'] ? implode(', ', $ota_order_item_data['detail_ids']) : "";
                    //生成核销二维码json串
                    $ota_order_item_data["voucher_data"] = json_encode([
                        "type"       => "order",
                        "qrcode_str" => sys_encryption(("ticket&" . $ota_order_item_data['out_trade_no'] . "_" . $ota_order_item_data['ticket_id'] . "&0"), "ota") . "_ota",
                        "be_id"      => "ota",
                        "use_lat"    => 1,
                        "use_lng"    => 1
                    ]);
                    OrderOtaItemModel::create($ota_order_item_data);
                    // 扣除库存
                    if ($ticket_price['stock'] <= 0 || ($ticket_price['stock'] - $item['quantity']) < 0) {
                        $code = '1003';
                        throw new Exception("当前日期门票" . $ticket_info['title'] . ' 库存不足');
                    }

                    $result_items_info = [
                        'PLU'        => $ticket_info['code'],
                        'inventorys' => [
                            'useDate'  => $ticket_price['date'],
                            'quantity' => $ticket_price->stock
                        ]
                    ];
                    $result_items[]    = $result_items_info;
                    //将订单信息记录
                    // 提交事务

                }
                $order_info->origin_price  = $origin_price;
                $order_info->amount_price  = $amount_price;
                $order_info->mch_id        = $mch_id;
                $order_info->order_remark  = $order_remark;
                $order_info->contact_man   = $contact_man;
                $order_info->contact_phone = $contact_phone;
                $order_info->save();
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                if ($code == '0000') {
                    $code = '1111';
                }
                $msg = $e->getMessage();
            }
        }
        if ($code !== '0000') {
            $body = [];
        } else {
            $body = [
                "otaOrderId"      => $data['otaOrderId'],
                "supplierOrderId" => $order_info['out_trade_no'],
                "items"           => $result_items
            ];
        }
        // 转成json字符串之后在加密
        $body = json_encode($body);
        // 加密body
        $strBody = $this->aes->encrypt($body, $this->aesKey, $this->aesIv);
        //$this->result(sprintf('%04d', 0), '预下单创建成功', $strBody);
        $this->result($code, $msg, $strBody);
    }


    //预下单支付
    public function PayPreOrder($body = [])
    {
        /*$body = '{
            "sequenceId": "2017-10-10abcd95774f17c3e354e73f7aaf21b5ec",
            "otaOrderId": "20230830001",
            "supplierOrderId": "MP20230830494905977434",
            "confirmType": 1,
            "lastConfirmTime": "2017-01-05 10:00:00",
            "items": [
              {
                "itemId": "123456",
                "PLU": "test-plu-1"
              }
            ],
            "coupons": [
              {
                "type": 1,
                "code": "优惠券编码",
                "name": "优惠券名称",
                "amount": 100,
                "amountCurrency": ""
              }
            ]
          }';
        */
        $data       = json_decode($body, true);
        $code       = "0000";
        $msg        = '';
        $vouchers   = [];
        $order_info = [];
        try {
            $order_ota_info = OrderOtaModel::where("out_trade_no", $data['supplierOrderId'])->find();
            if (!$order_ota_info) {
                $code = '2001';
                throw new Exception("订单不存在");
            }
            $order_info = OrderModel::where("out_trade_no", $order_ota_info['out_trade_no'])->find();
            if (!$order_info) {
                $code = '2001';
                throw new Exception("订单不存在");
            }
            if ($order_info["refund_status"] != "not_refunded") {
                $code = '2101';
                throw new Exception("订单存在退款");
            }
            if ($order_info["order_status"] == "used") {
                $code = '2002';
                throw new Exception("订单已使用");
            } else if ($order_info["order_status"] == "cancelled") {
                $code = '2102';
                throw new Exception("订单已取消");
            } else if ($order_info["order_status"] == "refunded") {
                $code = '2103';
                throw new Exception("订单已退款");
            }
            if ($order_info["order_status"] == "created") {
                $order_info->order_status = 'paid';
                $order_info->payment_datetime = date("YmdHis");
                $order_info->payment_status = 1;
                $order_info->type = "ota_xc";
                $order_info->save();
            }
            foreach ($data['items'] as $item) {
                $item_info = OrderOtaItemModel::where(["ota_id" => $order_ota_info['id'], "ticket_code" => $item["PLU"]])->find();
                if ($item_info) {
                    $item_info['item_id'] = $item["itemId"];
                    $item_info->save();
                    $vouchers[] = [
                        'itemId'          => $item["itemId"],
                        'voucherType'     => 3,
                        'voucherCode'     => "",
                        'voucherData'     => urlencode($item_info["voucher_data"]),
                        'voucherSeatInfo' => "",
                    ];
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            if ($code == "0000") {
                $code = '2111';
            }
            $msg = $e->getMessage();
        }
        if ($code !== '0000') {
            $body = [];
        } else {
            $body = [
                "otaOrderId"          => $data['otaOrderId'],
                "supplierOrderId"     => $data['supplierOrderId'],
                "supplierConfirmType" => 1,//供应商确认类型：1.支付已确认（当confirmType =1/2时可同步返回确认结果）2.支付待确认（当confirmType =2时需异步返回确认结果的）
                "voucherSender"       => 1,//凭证发送方：1.携程发送凭证 2.供应商发送凭证
                "vouchers"            => $vouchers
            ];
            // 支付成功，出行通知
            // 2023-09-07 去掉出行通知
            //\app\xc\service\NoticeService::OrderTravelNotice($order_info);
        }
        // 转成json字符串之后在加密
        $body = json_encode($body);
        // 加密body
        $strBody = $this->aes->encrypt($body, $this->aesKey, $this->aesIv);
        //$this->result(sprintf('%04d', 0), '预下单创建成功', $strBody);
        $this->result($code, $msg, $strBody);
    }

    //预下单支付   未付款取消
    public function CancelPreOrder($body = [])
    {
        /* $body   = '{
             "sequenceId": "2017-10-10abcd95774f17c3e354e73f7aaf21b5ec",
             "otaOrderId": "20230830001"
         }';
        */
        $data   = json_decode($body, true);
        $result = OrderModel::cancelOtaOrder($data['otaOrderId']);
        $this->result($result['code'], $result['msg']);
    }

    //订单取消
    public function CancelOrder($body = [])
    {
        /*$body = '{
             "sequenceId": "2017-10-10abcd95774f17c3e354e73f7aaf21b5ec",
             "otaOrderId": "20230830006",
             "supplierOrderId": "MP20230906472513559984",
             "confirmType": 2,
             "items": [
               {
                 "itemId": "123456",
                 "PLU": "T7928641283224950346",
                 "lastConfirmTime": "2017-01-05 10:00:00",
                 "quantity": 2,
                 "amount": 100,
                 "amountCurrency": ""
               }
             ]
           }
         ';*/

        $data = json_decode($body, true);
        $code = '0000';
        $msg  = '';
        $body = [
            "supplierConfirmType" => 1,
            "items"               => []
        ];
        Db::startTrans();
        try {
            $ota_order_info = OrderOtaModel::where("otaOrderId", $data["otaOrderId"])->find();
            if (!$ota_order_info) {
                $code = '2001';
                //$msg = "该订单号不存在！";
                throw new Exception("该订单号不存在！");
            }
            $order_info = OrderModel::where("out_trade_no", $ota_order_info["out_trade_no"])->find();
            if (!$order_info) {
                $code = '2001';
                //$msg = "该订单号不存在！";
                throw new Exception("该订单号不存在！");
            }
            if (!in_array($data['confirmType'], ['1', '2'])) {
                throw new Exception("确认类型不符！");
            }
            if ($order_info['order_status'] == 'used') {
                $code = '2002';
                throw new Exception("订单已使用！");
            }
            $total_refund_count = 0;
            $total_count        = OrderDetailModel::where("trade_no", $order_info["trade_no"])->count();
            foreach ($data['items'] as $item) {

                $ota_order_item = OrderOtaItemModel::where(["ticket_code" => $item["PLU"], "out_trade_no" => $order_info['out_trade_no']])->find();
                //验证取消数量是否匹配
                $detail_list      = OrderDetailModel::whereIn("id", $ota_order_item['detail_ids'])->select();
                $can_refund_count = 0;//可以退的
                $have_refund_count = 0;//已经退掉的
                foreach ($detail_list as $kk => $vv) {
                    if ($vv["refund_status"] == 'not_refunded') {
                        //退这个游客
                        $vv->refund_status   = "fully_refunded";
                        $vv->refund_progress = "completed";
                        $vv->save();
                        //归还库存
                        PriceModel::where(["ticket_id" => $vv['ticket_id'], "date" => $vv['date']])->inc("stock")->update();
                        $can_refund_count++;
                    }else{
                        $have_refund_count++;
                    }
                    $total_refund_count++;
                }
                if ($data['confirmType'] == 2){
                    if ($item["quantity"] != $can_refund_count && $item["quantity"] != $have_refund_count) {
                        $code = '2004';
                        throw new Exception("取消数量不符！");
                    }
                }
                $body["items"][] = ['itemId' => $item["itemId"]];
            }
            if ($total_refund_count > 0) {
                if ($total_refund_count == $total_count) {
                    $order_info->refund_status = "fully_refunded";
                    $order_info->payment_status = 2;
                    $order_info->order_status  = "refunded";
                } else {
                    $order_info->refund_status = "partially_refunded";
                }
                $order_info->save();
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            if ($code === '0000') {
                $code = "2111";
            } else if ($code === '00000') {
                $code = "0000";
            }
            $msg = $e->getMessage();
        }
        // 转成json字符串之后在加密
        $body = json_encode($body);
        // 加密body
        $strBody = $this->aes->encrypt($body, $this->aesKey, $this->aesIv);
        //$this->result(sprintf('%04d', 0), '预下单创建成功', $strBody);
        $this->result($code, $msg, $strBody);
    }

    //查询订单
    public function QueryOrder($body = [])
    {

       /* $body = '{
             "sequenceId": "2017-10-10abcd95774f17c3e354e73f7aaf21b5ec",
             "otaOrderId": "20230830005",
             "supplierOrderId": "MP20230906185624924515"
           }
         ';*/

        $data = json_decode($body, true);
        $code = '0000';
        $msg  = '获取成功';
        $body = [
            "otaOrderId"      => $data["otaOrderId"],
            "supplierOrderId" => $data["supplierOrderId"],
            "items"           => []
        ];
        try {
            $order_ota_info = OrderOtaModel::where("otaOrderId", $data["otaOrderId"])->find();
            if (!$order_ota_info) {
                $code = '4001';
                throw new Exception("该订单号不存在！");
            }
            $order_info = OrderModel::where("out_trade_no", $order_ota_info['out_trade_no'])->find();
            if (!$order_info) {
                $code = '4001';
                throw new Exception("该订单号不存在！");
            }
            $order_ota_item_list = OrderOtaItemModel::where("ota_id", $order_ota_info['id'])->select();
            /*
                *  1	新订待确认
                   2	新订已确认
                   3	取消待确认
                   4	部分取消	使用前的部分取消的状态
                   5	全部取消
                   6	已取物品（票券、物件）
                   7	部分使用	使用后的部分使用状态
                   8	全部使用
                   9	已还物品（票券、物件）
                   10	已过期
                   11	待支付
                   12	支付待确认
                   13	支付已确认
                   14	预下单取消成功
                    'created','paid','used','cancelled','refunded'
                * */
            $order_list = [
                "created"   => 11,
                "paid"      => 13,
                "cancelled" => 14,
                "used"      => 8,
                "refunded"  => 5,
            ];
            foreach ($order_ota_item_list as $item) {
                $useQuantity     = OrderDetailModel::whereIn("id", $item['detail_ids'])->where([["enter_time", ">", 0], ["refund_status", "=", "not_refunded"]])->count();
                $cancelQuantity  = OrderDetailModel::whereIn("id", $item['detail_ids'])->where([["refund_status", "=", "fully_refunded"]])->count();
                $body["items"][] = [
                    'itemId'         => !empty($item['item_id']) ? $item['item_id'] : 0,
                    'useStartDate'   => $item['date'],
                    'useEndDate'     => $item['date'],
                    'orderStatus'    => $order_list[$order_info['order_status']],
                    'quantity'       => $item['quantity'],
                    'useQuantity'    => $useQuantity,
                    'cancelQuantity' => $cancelQuantity,
                ];
            }
        } catch (\Exception $e) {
            Db::rollback();
            if ($code == '0000') {
                $code = "4101";
            }
            $msg = $e->getMessage();
        }
        if ($code !== '0000') {
            $body = [];
        }
        // 转成json字符串之后在加密
        $body = json_encode($body);
        // 加密body
        $strBody = $this->aes->encrypt($body, $this->aesKey, $this->aesIv);
        //$this->result(sprintf('%04d', 0), '预下单创建成功', $strBody);
        $this->result($code, $msg, $strBody);
    }

    //资源日期库存同步接口
    public function DateInventoryModify($body = [])
    {

        /* $body = '{
             "sequenceId": "2017-10-10abcd95774f17c3e354e73f7aaf21b5ec",
             "otaOptionId": "568898",
             "supplierOptionId": "568898",
             "inventorys": [
               {
                 "date": "2018-12-12",
                 "quantity": "120"
               }
             ]
           }';
        */
        $data = json_decode($body, true);
        $code = '0000';
        $msg  = '操作成功';
        try {
            $ticket_info = TicketModel::where("code", $data["supplierOptionId"])->find();
            if (!$ticket_info) {
                $code = '2103';
                throw new Exception("订单不存在！");
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            if ($code == '0000') {
                $code = "2111";
            }
            $msg = $e->getMessage();
        }
        $this->result($code, $msg);
    }


    /**
     * [VerifyOrder 订单验证]
     * @apiDescription  描述：下单验证接口在客人下单时提前将下单信息提交给供应商系统进行校验，及时告知客人是否可以预定成功，有助于提高订单预定成功率和提升客人预定体验。
     * @Author   slomoo@aliyun.com
     * @DateTime        2023-08-25
     * @LastTime        2023-08-25
     * @version         [1.0.0]
     */
    public function VerifyOrder($originalArray)
    {
        // 出行人信息验证
        $resultCode    = '1006';
        $resultMessage = '';

        foreach ($originalArray['items'] as $item) {
            foreach ($item['passengers'] as $passenger) {
                $name   = $passenger['name'];
                $mobile = $passenger['mobile'];
                $cardNo = $passenger['cardNo'];

                if (empty($name) || empty($mobile) || empty($cardNo)) {
                    $resultCode    = '1005';
                    $resultMessage = '出行人信息缺失';
                    break 2;
                }

                if (!checkNameFilter($name)) {
                    $resultMessage = '出行人名称错误';
                    break 2;
                }

                if (!check_phone($mobile)) {
                    $resultMessage = '出行人手机号码错误';
                    break 2;
                }

                if ($passenger['cardType'] == 1 && !isCreditNo($cardNo)) {
                    $resultMessage = '出行人证件号码错误';
                    break 2;
                }
            }
        }

        // 结果返回
        if (!empty($resultMessage)) {
            $this->result($resultCode, $resultMessage);
        }

        $resultCode    = '1001';
        $resultMessage = '';

        // 门票信息验证
        $ticketData = $originalArray['items'];
        $items      = [];

        foreach ($ticketData as $key => $value) {
            //校验门票信息 2023-8-1
            $ticketInfo = \app\common\model\Ticket::where("tkno", $value['PLU'])->append(['rights_list'])->find();
            if ($ticketInfo === NULL) {
                $resultMessage = '产品PLU不存在/错误' . $value['PLU'];
                break;
            }
            if ($ticketInfo->status != 1) {
                $resultCode    = '1002';
                $resultMessage = '产品已经下架' . $ticketInfo->title;
                break;
            }
            $ticketData[$key]['ticket'] = $ticketInfo->toArray();
            // 检查门票对应的日期是否添加报价
            $quotation = \app\common\model\TicketPrice::where('ticket_id', $ticketInfo->id)
                ->where('date', $value['useStartDate'])
                ->find();
            if ($quotation === NULL) {
                $resultCode    = '1007';
                $resultMessage = '产品价格不存在/未设置' . $ticketInfo->title;
                break;
            }
            // 检查限购
            if (count($value['passengers']) > $ticketInfo->quota_order) {
                $resultCode    = '1004';
                $resultMessage = '该门票限每单限购' . $ticketInfo->quota_order . '张';
                break;
            }
            if ($ticketInfo->quota > 0) {
                // 检查当天用户购买总数
                $todayStart = strtotime(date('Y-m-d 00:00:00'));
                $todayEnd   = strtotime(date('Y-m-d 23:59:59'));
                $quota      = \app\common\model\TicketOrder::where('contact_man', $originalArray['contacts'][0]['name'])
                    ->where('contact_phone', $originalArray['contacts'][0]['mobile'])
                    ->whereBetween('create_time', [$todayStart, $todayEnd])
                    ->count();
                if ($quota > $ticketInfo->quota) {
                    $resultCode    = '1004';
                    $resultMessage = '该门票每天限购' . $ticketInfo->quota . '单';
                    break;
                }
            }
            $ticketData[$key]['quotation'] = $quotation->toArray(); // 对应的当天的报价信息

            // 产品库存不足时需返回使用日期中的实际库存数量，加密
            $inventory = [
                'useDate'  => $value['useStartDate'],
                'quantity' => $quotation->stock
            ];
            // 校验库存
            if ($quotation->stock < count($value['passengers'])) {
                $resultCode    = '1003';
                $resultMessage = '库存不足';
                break;
            }
            $items[] = [
                'PLU'        => $value['PLU'],
                'inventorys' => [$inventory]
            ];
        }

        // 结果返回
        if (!empty($resultMessage)) {
            $this->result($resultCode, $resultMessage);
        }
        // 验证成功返回结果
        $resultCode    = '0000';
        $resultMessage = '验证成功';
        // 加密body
        $newItems = $this->aes->encrypt(json_encode($items), $this->aesKey, $this->aesIv);
        $this->result($resultCode, $resultMessage, $newItems);
    }

    // 出行通知 测试
    public function OrderTravelNotice()
    {
        $out_trade_no = Request::get("out_trade_no/s");
        $order_info   = OrderModel::where("out_trade_no", $out_trade_no)->findOrEmpty()->toArray();
        $result       = \app\xc\service\NoticeService::OrderTravelNotice($order_info);
        var_dump($result);
        die;
    }


    // 核销通知
    public function OrderConsumedNotice()
    {
        $out_trade_no = Request::get("out_trade_no/s");
        $order_info   = OrderModel::where("out_trade_no", $out_trade_no)->findOrEmpty()->toArray();
        $result       = \app\xc\service\NoticeService::OrderConsumedNotice($order_info,1);
        var_dump($result);
        die;
    }

    public function testGetOrder()
    {
        $out_trade_no        = Request::get("out_trade_no/s");
        $order_info          = OrderOtaModel::where("out_trade_no", $out_trade_no)->findOrEmpty()->toArray();
        $order_info['items'] = OrderOtaItemModel::where("ota_id", $order_info['id'])->append(["qrcode_str"])->select()->toArray();
        var_dump($order_info);
        die;
    }
}