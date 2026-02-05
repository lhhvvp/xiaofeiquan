<?php

namespace app\xc\service;

use think\facade\Config;
use app\common\model\ticket\Order as OrderModel;
use app\common\model\ticket\OrderDetail as OrderDetailModel;
use app\xc\model\OrderOtaModel;
use app\xc\model\OrderOtaItemModel;
use think\facade\Db;
use think\Exception;

class NoticeService
{

    public function __construct()
    {


    }

    // 出行通知
    public static function OrderTravelNotice($order_info = [])
    {
        $data = [];
        $result = [];
        if (!$order_info) {
            $result['bool'] = false;
            $result['msg'] = "订单信息不能为空！";
            return $result;
            //throw new Exception("订单信息不能为空！");
        }
        if ($order_info["channel"] != "ota_xc") {
            $result['bool'] = false;
            $result['msg'] = "订单来源不符！";
            return $result;
            //throw new Exception("订单来源不符！");
        }
        $order_ota_info = OrderOtaModel::where("out_trade_no", $order_info["out_trade_no"])->find();
        if (!$order_ota_info) {
            $result['bool'] = false;
            $result['msg'] = "订单信息不能为空！";
            return $result;
            //throw new Exception("订单信息不能为空！");
        }
        $order_ota_item_list = OrderOtaItemModel::where("ota_id", $order_ota_info["id"])->append(["qrcode_str"])->select();
        if (!$order_ota_item_list) {
            $result['bool'] = false;
            $result['msg'] = "没有要通知的数据！";
            return $result;
            //throw new Exception("没有要通知的数据！");
        }
        $data["otaOrderId"]      = $order_ota_info["otaOrderId"];
        $data["supplierOrderId"] = $order_ota_info["out_trade_no"];
        $data["vouchers"]        = [];
        foreach ($order_ota_item_list as $item) {
            /*$qrcode_array       = [
                'qrcode_str' => $item['qrcode_str'],
                'id'         => $order_info['id'],
                'be_id'      => $item['ticket_id'],
                'use_lat'    => 1,
                'use_lng'    => 1,
                'type'       => "ticket"
            ];*/
            $data["vouchers"] = [
                [
                    "itemId"          => $item["item_id"],
                    "voucherType"     => 3,
                    "voucherCode"     => "",
                    "voucherData"     => rawurlencode($item['voucher_data']),
                    "voucherSeatInfo" => ""
                ],
                [
                    "itemId"          => $item["item_id"],
                    "voucherType"     => 3,
                    "voucherCode"     => "",
                    "voucherData"     => rawurlencode($item['voucher_data']),
                    "voucherSeatInfo" => ""
                ]
            ];
            $data["items"][]    = [
                "itemId"             => $item["item_id"],
                "remark"             => $item["remark"],
                "expressDelivery"=>[
                    "deliveryType"=>2,
                    "companyCode"=> "",
                    "companyName"=> "",
                    "trackingNumber"=> "",
                    "goodsName"=> "",
                    "goodsQuantity"=> "",
                    "sendMessage"=> "",
                    "receiveMessage"=> ""
                ],
                "travelInformations" => [
                    [
                        "name"    => "入园日期",
                        "content" => $item["date"]
                    ]
                ]
            ];
        }
        $result      = self::request("OrderTravelNotice", $data);
        //$result      = '{"header":{"resultCode":"0006","resultMessage":"[STP]请求数据异常。supplierOrderId的值\"MP20230830692978426015\"错误\r\n[STP]Invalid request data. supplierOrderId has a wrong value \"MP20230830692978426015\"","version":"1.0"}}';
        $result_json = json_decode($result, true);
        if($result_json && $result_json['header']['resultCode'] == '0000'){
            return true;
        }
        return false;
    }

    // 核销通知
    public static function OrderConsumedNotice($order_info = [],$useQuantity = 0)
    {
        $data = [];
        if (!$order_info) {
            $result['bool'] = false;
            $result['msg'] = "订单信息不能为空！";
            return $result;
        }
        if ($order_info["channel"] != "ota_xc") {
            $result['bool'] = false;
            $result['msg'] = "订单来源不符！";
            return $result;
        }
        $order_ota_info = OrderOtaModel::where("out_trade_no", $order_info["out_trade_no"])->find();
        if (!$order_ota_info) {
            throw new Exception("订单信息不能为空！");
        }
        $order_ota_item_list = OrderOtaItemModel::where("ota_id", $order_ota_info["id"])->select();
        if (!$order_ota_item_list) {
            throw new Exception("没有要通知的数据！");
        }
        $data["otaOrderId"]      = $order_ota_info["otaOrderId"];
        $data["supplierOrderId"] = $order_ota_info["out_trade_no"];
        $data["items"]        = [];
        foreach ($order_ota_item_list as $item) {

            $data["items"][]    = [
                "itemId"             => $item["item_id"],
                "useStartDate"             => $item["date"],
                "useEndDate"=>$item["date"],
                "quantity"=>$item["quantity"],
                "useQuantity"=>$useQuantity,
                "remark"=>$item["remark"]
            ];
        }
        $result      = self::request("OrderConsumedNotice", $data);
        //$result      = '{"header":{"resultCode":"0006","resultMessage":"[STP]请求数据异常。supplierOrderId的值\"MP20230830692978426015\"错误\r\n[STP]Invalid request data. supplierOrderId has a wrong value \"MP20230830692978426015\"","version":"1.0"}}';
        $result_json = json_decode($result, true);
        if($result_json && $result_json['header']['resultCode'] == '0000'){
            return true;
        }
        return false;
    }

    public static function request($serviceName = '', $data = [], $method = 'post', $options = [])
    {

        $aes = new XiechengService;
        // 接口帐号
        $accountId = Config::get('ota.xiecheng.accountId');
        // 接口密钥
        $signKey = Config::get('ota.xiecheng.signKey');
        // AES 加密密钥
        $aesKey = Config::get('ota.xiecheng.aesKey');
        // AES 加密初始向量
        $aesIv = Config::get('ota.xiecheng.aesIv');


        $url = Config::get('ota.xiecheng.url');

        $body    = [
            'sequenceId' => date("Y-m-d") . str_replace("-", "", gen_uuid())
        ];
        $body    = array_merge($body, $data);
        $bodyStr = $aes->encrypt(json_encode($body), $aesKey, $aesIv);
        $header  = [
            "accountId"   => $accountId,
            "serviceName" => $serviceName,
            "requestTime" => date("Y-m-d H:i:s"),
            "version"     => "1.1",
        ];
        // 签名
        $header['sign'] = $aes->sign($header, $bodyStr);
        $params         = [
            "header" => $header,
            "body"   => $bodyStr
        ];
        $params         = json_encode($params);
        $headerStr      = [
            "Content-Type: application/json; charset=utf-8"
        ];
        $res_json       = http_curl_post_header($url, $params, $headerStr);
        return $res_json;
    }
}