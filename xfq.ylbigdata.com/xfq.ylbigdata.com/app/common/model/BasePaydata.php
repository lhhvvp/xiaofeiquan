<?php
/**
 * 基础-支付-交易数据模型
 * @author slomoo <1103398780@qq.com> 2022/10/25
 */
namespace app\common\model;
use think\facade\Db;
use think\facade\Request;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Log;
class BasePaydata extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    /**
     * [wxConfig 获取微信配置]
     * @param  [type] $model [产品支付模型]
     * @return [type]        [description]
     */
    public static function wxConfig($model) 
    {
        $cacheconfig = cache('pay_cache');
        if(empty($cacheconfig)){
            // 读取配置
            $base_payment = Db::name('base_payment')->where('id',1)->find();
            cache('pay_cache',$base_payment);
            $cacheconfig = $base_payment;
        }
        $config = [
            //'appid' => $cacheconfig['appid'], // APP APPID
            //'app_id' => $cacheconfig['app_id'], // 公众号 APPID
            'miniapp_id' => $cacheconfig['wechat_appid'], // 小程序 APPID
            'mch_id' => $cacheconfig['wechat_mch_id'],
            'key' => $cacheconfig['wechat_mch_key'],
            'notify_url' => Request::domain().'/api/notify/pay_async_notice/model/'.$model.'.html',
            'cert_client' => '/www/cert/apiclient_cert.pem', // optional，退款等情况时用到
            'cert_key' => '/www/cert/apiclient_key.pem',// optional，退款等情况时用到
            'log' => [ // optional
                'file' => './logs/wechat.log',
                'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
                'type' => 'daily', // optional, 可选 daily.
                'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
            ],
            'http' => [ // optional
                'timeout' => 5.0,
                'connect_timeout' => 5.0,
                // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
            ],
            //'mode' => 'dev', // optional, dev/hk;当为 `hk` 时，为香港 gateway。
        ];

        return $config;
    }
    
    public function wxminipay($total_fee,$order,$openid,$type,$model)
    {
        $config =self::wxConfig($model);//获取配置

        if($type==''){
            $type='miniapp';
        }
        switch ($type){
        case "miniapp"://小程序支付
            $orderInfo = [
                'out_trade_no' => $order['order_out_no'],//订单ID
                'total_fee' => $total_fee, //支付金额 **单位：分**
                'body' => '小程序支付',
                'openid' => $openid,
            ];
        break;
        case 'mp'://公众号支付
            $orderInfo = [
                'out_trade_no' => $order['order_out_no'],//订单ID
                'total_fee' => $total_fee, //支付金额 **单位：分**
                'body' => '公众号支付',
                'openid' => $openid,
            ];
        break;
        case 'wap'://手机网站支付
            $orderInfo = [
                'out_trade_no' => $order['order_out_no'],
                'body' => '手机网站支付',
                'total_fee' => $total_fee,
            ];
        break;
        case 'app'://APP 支付
            $orderInfo = [
                'out_trade_no' => $order['order_out_no'],//订单ID
                'body' => 'APP 支付',
                'total_fee' => $total_fee, //支付金额 **单位：分**
            ];
        break;
        default://综合排序
            $orderInfo = [];
        }
        
        $data['order_no']   = $order['order_no'];//订单ID
        $data['openid'] =   $openid;//用户ID
        $data['body']   =   $orderInfo['body'];
        $data['money']  =   $total_fee;//支付金额
        $data['model']  =   $model;//支付模型
        $data['payip']  =   request()->ip();//支付IP
        $res = self::create($data);
        if ($res !== false) {
            switch ($type){
            case "miniapp"://小程序支付
                $pay = Pay::wechat($config)->miniapp($orderInfo);//小程序
                break;
            case 'mp'://公众号支付
                $pay = Pay::wechat($config)->mp($orderInfo);//公众号支付
                break;
            case 'wap'://手机网站支付
                //return $wechat->wap($orderInfo)->send();
                $mweb_url = Pay::wechat($config)->wap($orderInfo);//手机网站支付
                $pay = $mweb_url->getTargetUrl();
                break;
            case 'app'://APP 支付
                $pay = Pay::wechat($config)->app($orderInfo);//APP 支付
                break;
            default://支付失败
                $pay = '';
            }
            return $pay;
        }
    }

}