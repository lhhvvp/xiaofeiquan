<?php
/**
 * 基础-退款-交易数据模型
 * @author slomoo <1103398780@qq.com> 2023/07/06
 */
namespace app\common\model;
use think\facade\Db;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Log;
use think\facade\Request;
class TicketRefunds extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    /**
     * [wxConfig 获取微信配置]
     * @param  [type] $model [产品支付模型]
     * @return [type]        [description]
     */
    public static function wxConfig() 
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
            'notify_url' => Request::domain().'/api/ticket/notify_refund.html',
            'cert_client' => '/www/cert/apiclient_cert.pem', // optional，退款等情况时用到
            'cert_key' => '/www/cert/apiclient_key.pem',// optional，退款等情况时用到
            'log' => [ // optional
                'file' => './logs/wechat_ticket_refund.log',
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
    
    public function refund($detail)
    {
        $config = self::wxConfig(); //获取配置

        $order = [
            'transaction_id' => $detail['order']['transaction_id'],
            'out_refund_no' => $detail['out_refund_no'],
            'total_fee' => $detail['total_fee'] * 100,
            'refund_fee' => $detail['refund_fee'] * 100,
            'refund_desc' => $detail['refund_desc'],
        ];

        $result = Pay::wechat($config)->refund($order); //退款请求
        
        
        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
            // 更新退款进度信息
            $data = [
                'appid' => $result['appid'],
                //'mch_id' => $result['mch_id'],
                'return_code' => $result['return_code'], //返回状态码 
                'refund_id' => $result['refund_id'], //微信退款单号
            ];

            Db::name('ticket_refunds')->where('out_refund_no', $result['out_refund_no'])->data($data)->update();

            return ['code' => 0, 'msg' => 'SUCCESS'];
        }
        return ['code' => 1, 'msg' => $result['return_msg']];
    }

    public static function getStatusList(){
        return [
            '0'=>'待退款',
            '1'=>'退款成功',
            '2'=>'退款失败',
            '3'=>'用户取消'
        ];
    }

    public function getStatusTextAttr($value,$data){
        $list = self::getStatusList();
        return isset($list[$data['status']]) ? $list[$data['status']] : '-';
    }
    public function seller()
    {
        return $this->belongsTo('Seller', 'mch_id');
    }


    public function getSettlementRefundFeeAttr($value){
        return intval($value) / 100;
    }
}