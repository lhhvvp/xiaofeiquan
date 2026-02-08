<?php
/**
 * 基础-退款-交易数据模型
 * @author slomoo <1103398780@qq.com> 2022/10/26
 */
namespace app\common\model;
use think\facade\Db;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Log;
use think\facade\Request;
class BaseRefunds extends Base
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
            'appid' => $cacheconfig['wechat_appid'], // APP APPID
            'app_id' => $cacheconfig['wechat_appid'], // 公众号 APPID
            'miniapp_id' => $cacheconfig['wechat_appid'], // 小程序 APPID
            'mch_id' => $cacheconfig['wechat_mch_id'],
            'key' => $cacheconfig['wechat_mch_key'],
            'notify_url' => Request::domain().'/api/notify/refund/model/'.$model.'.html',
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
    
    public function refund($total_fee,$refund_fee,$order_no,$reason,$model)
    {
        $config        = self::wxConfig($model);//获取配置
        $out_refund_no = date('YmdHis').GetNumberCode(6);//退款订单号
        $order = [
            'out_trade_no'  => 'XFQ'.$order_no,
            'out_refund_no' => $out_refund_no,
            'total_fee'     => $total_fee,
            'refund_fee'    => $refund_fee,
            'refund_desc'   => $reason,
        ];
        
        // Dev-only: offline mock refund for golden replay (avoid calling real WeChat).
        if (env('rewrite.mock_wechat_refund')) {
            $result = [
                'return_code'    => 'SUCCESS',
                'result_code'    => 'SUCCESS',
                'appid'          => $config['miniapp_id'] ?? ($config['appid'] ?? 'wx-dev-appid'),
                'mch_id'         => $config['mch_id'] ?? '1900000001',
                'transaction_id' => 'mock-transaction-id',
                'refund_id'      => 'mock-refund-id',
            ];
        } else {
            $result = Pay::wechat($config)->refund($order);//退款
        }
        if($result['return_code']=='SUCCESS' && $result['result_code']=='SUCCESS'){
            $data['appid']      =   $result['appid'];
            $data['mch_id']     =   $result['mch_id'];
            $data['order_no']   =   $order_no;//订单ID
            $data['out_refund_no']  =   $out_refund_no; // 内部退款ID
            $data['refund_desc']    =   $reason;//退款原因
            $data['total_fee']      =   $total_fee;//支付金额
            $data['refund_fee']     =   $refund_fee;//退款金额
            $data['model']      =   $model;//模型
            $data['refund_ip']  =   request()->ip();//退款IP
            $data['return_code']=   $result['return_code'];//返回状态码 
            $data['transaction_id'] =   $result['transaction_id'];//微信支付订单号
            $data['refund_id']      =   $result['refund_id'];//微信退款单号
            $res = self::create($data);
            // 更新订单为退款中 = 1
            if($model=="Coupon"){
                Db::name('CouponOrder')
                ->where('order_no',$order_no)
                ->update(['is_refund'=>1,'order_remark'=>$reason,'update_time'=>time()]);
            }
            $rsData = ['code' => '0', 'msg' => 'SUCCESS'];
            return $rsData;
        }
        $rsData = ['code' => '1', 'msg' => $result];
        return $rsData;
    }

}
