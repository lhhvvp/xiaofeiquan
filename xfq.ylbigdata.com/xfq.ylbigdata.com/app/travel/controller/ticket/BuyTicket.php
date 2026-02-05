<?php
/**
 * 线路产品控制器
 * @author slomoo <1103398780@qq.com> 2022/09/05
 */

namespace app\travel\controller\ticket;

use app\common\libs\Http;
use app\common\model\Seller;
use \app\travel\controller\Base;

// 引入框架内置类
use think\facade\Request;

use think\facade\Session;
use think\facade\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\facade\Validate;
use \app\common\model\Ticket as TicketModel;
use \app\common\model\ticket\Price as PriceModel;
use \app\common\model\ticket\Order as OrderModel;
use \app\common\model\ticket\OrderDetail as OrderDetailModel;
use \app\common\model\ticket\OrderDetailRights as OrderDetailRightsModel;
use think\facade\Db;
use Yansongda\Pay\Pay;
use think\Exception;

class BuyTicket extends Base
{

    public function index()
    {

        return View::fetch();
    }

    public function readImportTourist()
    {

        $file        = Request::file("file");
        $filePath    = $file->getPathname();
        $spreadsheet = IOFactory::load($filePath);
        $worksheet   = $spreadsheet->getActiveSheet();

        $data = [];

        foreach ($worksheet->getRowIterator() as $row) {
            $rowData      = [];
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getValue();
            }

            $data[] = $rowData;
        }
        $result = [];


        foreach ($data as $key => $val) {
            if ($key > 0) {
                //第二行开始 前四个
                $rowResult = [
                    'fullname'  => $val[0],
                    'cert_type' => $val[1],
                    'cert_id'   => $val[2],
                    'mobile'    => $val[3],
                    'auth'      => 0,
                    'auth_msg'  => '待验证'
                ];
                $result[]  = $rowResult;
            }
        }
        return $this->result($result);
    }
    public function checkTourist()
    {
        $tourist_list = Request::post("tourist_list");
        $system = \app\common\model\System::find(1);
        foreach($tourist_list as &$item){
            if($item['cert_type'] != '身份证'){
                $item['auth_msg'] = '不是身份证类型！';
                $item['auth'] = 1;
                continue;
            }
            //转成大写
            $item["cert_id"] = strtoupper($item['cert_id']);
            //类型是身份证的时候验证身份证号是否正确
            $pattern = '/(^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$)|(^[1-9]\d{5}\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}$)/'; // 身份证号码的正则表达式
            if (!preg_match($pattern, $item['cert_id'])) {
                $item['auth_msg'] = '身份证号格式不符';
                $item['auth'] = 2;
                continue;
            }
            //先查看用户表是否有认证过的。
            $has_u = \app\common\model\Users::where(["name"=>$item["fullname"],"idcard"=>$item["cert_id"],"auth_status"=>1])->find();
            if(!$has_u){
                //联网认证
                if($system['app_code']==''){
                    $item['auth_msg'] = '请配置认证代码';
                    $item['auth'] = 0;
                    continue;
                }
                $options[CURLOPT_HTTPHEADER] = ['Authorization:APPCODE '.$system['app_code'],'Content-Type:application/x-www-form-urlencoded; charset=UTF-8'];
                $options[CURLOPT_FAILONERROR] = false;
                $result = \app\common\libs\Http::post("https://dfidveri.market.alicloudapi.com/verify_id_name",http_build_query(["id_number"=>$item['cert_id'],"name" =>$item['fullname']]),$options);
                if($result === ""){
                    $item['auth_msg'] = '验证身份失败';
                    $item['auth'] = 2;
                    continue;
                }
                $jsonData = json_decode($result,true);
                // 记录认证信息
                //$this->user_auth_log($jsonData,$param);
                if ($jsonData['status'] == 'OK' && $jsonData['state']==1) {
                    //认证通过
                    $item['auth_msg'] = '身份验证通过';
                    $item['auth'] = 1;
                    continue;
                } elseif ($jsonData['status'] == 'OK' && $jsonData['state']==2) {
                    // 认证不通过
                    $item['auth_msg'] = '姓名和身份证号不匹配';
                    $item['auth'] = 2;
                    continue;
                } elseif ($jsonData['status'] == 'RATE_LIMIT') {
                    $item['auth_msg'] = '同一名字30分钟内只能认证10次';
                    $item['auth'] = 2;
                    continue;
                } elseif ($jsonData['status'] == 'INVALID_ARGUMENT') {
                    $item['auth_msg'] = '认证失败';
                    $item['auth'] = 2;
                    continue;
                } else {
                    $item['auth_msg'] = '身份认证无法通过';
                    $item['auth'] = 2;
                    continue;
                }
            }else{
                $item['auth_msg'] = '身份验证通过';
                $item['auth'] = 1;
                continue;
            }
        }
        return $this->result($tourist_list);
    }

    /*
     * 创建订单
     * */
    public function createOrder()
    {

        $post = Request::post();
        //开始处理
        $validate = Validate::rule([
            'ticket_id'   => 'require',
            'date'        => 'require|dateFormat:Y-m-d',
            'total_price' => 'require'
        ]);
        $validate->message([
            'ticket_id.require'   => '参数错误！',
            'date.require'        => '日期不能为空！',
            'date.dateFormat'     => '日期格式不符！',
            'total_price.require' => '参数错误！'
        ]);
        if (!$validate->check($post)) {
            $this->error($validate->getError());
        }
        $number = count($post['tourist_list']);
        if ($number < 1) {
            $this->error("请导入游客！");
        }
        //2023-08-30 增加验证日期
        if(strtotime($post['date']) < strtotime(date('Y-m-d'))){
            $this->apiError("购买门票日期{$post['date']}已过");
        }
        $ticket_info = TicketModel::where("id", $post['ticket_id'])->append(['category_text', 'rights_list'])->find();
        if (!$ticket_info) {
            $this->error("门票不存在！");
        }
        if ($ticket_info['status'] != 1) {
            $this->error("该门票已下架！");
        }
        $ticket_price = PriceModel::where([["ticket_id", "=", $post['ticket_id']], ["date", "=", $post['date']]])->find();
        if (!$ticket_price) {
            $this->error("价格不存在！");
        }
        if ($ticket_price['stock'] < $number) {
            $this->error("库存不足！");
        }
        //$seller_info = Seller::where("id",$ticket_info['seller_id'])->find();
        //$travel = session()['travel'];
        $travel = Seller::where("id", session()['travel']['id'])->find();

        $cert_type_list = array_flip(\app\common\model\UsersTourist::getCertTypeList());
        //全部完毕，开始写入订单信息
        Db::startTrans();
        $total_price = bcmul($ticket_price['team_price'], $number, 2);
        if (strval($total_price) !== strval($post['total_price'])) {
            $this->error("金额不符！");
        }
        try {
            $trade_no   = uniqidDate(20);
            $orderData  = [
                'openid'           => "",
                'mch_id'           => $ticket_info['seller_id'],
                'trade_no'         => $trade_no,
                'out_trade_no'     => "MP" . $trade_no,
                'channel'          => "travel",
                'travel_id'        => $travel['id'],
                'type'             => "miniapp",
                'origin_price'     => $total_price,
                'amount_price'     => $total_price,
                'payment_terminal' => 1,
                'contact_man'      => $travel['name'],
                'contact_phone'    => $travel['mobile'],
                'contact_certno'   => "",
                'order_remark'     => SafeFilter($post['order_remark']),
                'order_status'     => "created",
                'refund_status'    => "not_refunded",
                'create_ip'        => Request::ip(),
                'create_time'      => time(),
                'update_time'      => time()
            ];
            $order_info = OrderModel::create($orderData);
            //开始写入订单从表
            foreach ($post['tourist_list'] as $item) {
                $orderDetailData   = [
                    'uuid'                => '0',
                    'trade_no'            => $order_info['trade_no'],
                    'out_trade_no'        => uniqidDate(20, "DMP"),
                    'out_refund_no'       => uniqidDate(20, "REF"),
                    'ticket_code'         => uniqidDate(20, "TC"),
                    'tourist_fullname'    => $item['fullname'],
                    'tourist_cert_type'   => (isset($cert_type_list[$item['cert_type']]) ? $cert_type_list[$item['cert_type']] : '0'), //传过来的是汉字，需要转为tinyint
                    'tourist_cert_id'     => $item['cert_id'],
                    'tourist_mobile'      => $item['mobile'],
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
                            'order_id'            => $order_info['id'],
                            'detail_id'           => $order_detail_info['id'],
                            'detail_date'         => $order_detail_info['ticket_date'],
                            'detail_code'         => $order_detail_info['ticket_code'],
                            'rights_title'        => $rr['title'],
                            'rights_id'           => $rr['id'],
                            'status'              => 0,
                            'create_time'         => time(),
                            'update_time'         => time(),
                            'code'                => uniqidDate(20, "RS"),
                            'seller_id'           => $ticket_info['seller_id']
                        ];
                        OrderDetailRightsModel::create($insertData);
                    }
                }
            }
            // 扣除库存
            if ($ticket_price['stock'] <= 0 || ($ticket_price['stock'] - $number) < 0) {
                throw new Exception("当前日期门票" . $ticket_info['title'] . ' 库存不足');
            }
            $ticket_price->stock = $ticket_price['stock'] - $number;
            $ticket_price->save();
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->error("写入数据失败！" . $e->getMessage());
        }
        //开始获取二维码
        try {
            $result = self::getTravelWxappQrcode($order_info);
        } catch (\Exception $e) {
            $this->error("小程序码失败！" . $e->getMessage());
        }
        $this->success("", "", $result);
    }
    /*
     * 更新订单的小程序二维码
     * */
    public function updateQrcode()
    {
        $order_id = Request::post("order_id/d", 0);
        $trade_no = Request::post("trade_no/s", "");
        if($order_id === 0 && $trade_no === ""){
            $this->error("参数错误！");
        }
        try {
            if($order_id !== 0){
                $order_info = OrderModel::where("id", $order_id)->find();
            }else{
                $order_info = OrderModel::where("trade_no", $trade_no)->find();
            }
            $result  = self::getTravelWxappQrcode($order_info);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success("","", $result);
    }

    public static function getTravelWxappQrcode($order_info = null)
    {
        if ($order_info === null) {
            throw new Exception('获取小程序码错误，缺少参数！');
        }
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
            $fileurl = alioss("/".$filePath, 'travel', true, 'wlxfq');
            $order_info->travel_wxapp_qrcode = $fileurl;
            if ($order_info->save() === true) {
                return $fileurl;
            }else{
                throw new Exception('保存小程序码错误！');
            }
        } else {
            throw new Exception('access_token获取错误！');
        }

    }
}
