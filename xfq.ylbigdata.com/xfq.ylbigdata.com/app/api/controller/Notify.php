<?php
/**
 * @desc   微信异步通知
 * @author slomoo
 * @email slomoo@aliyun.com
 */
declare (strict_types = 1);
namespace app\api\controller;

use app\api\BaseController;
use app\api\middleware\Auth;
use app\api\service\JwtAuth;
use think\facade\Db;
use think\facade\Request;
use app\common\model\BasePaydata;
use app\common\model\BaseRefunds;
use app\common\model\CouponIssueUser;
use app\common\model\Users;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Log;

class Notify extends BaseController
{
    /**
     * 控制器中间件 [不需要鉴权set]
     * @var array
     */

	protected $middleware = [
    	Auth::class => ['except' 	=> ['pay_async_notice','refund'] ]
    ];

    //初始化
    protected function initialize()
    {
        parent::initialize();
    }
    
    // 微信异步支付结果通知
    public function pay_async_notice()
    {
        $model  = Request::param('model');// 模型
        $model  = ucfirst($model);
        $config = BasePaydata::wxConfig($model);
        $pay    = Pay::wechat($config);
        $payinfo= [];
        $setData = $pay->verify(); // 是的，验签就这么简单！  返回数据格式未集合 
        $this->create_file(date('Y-m-d',time())."_pay_log.log",'logs/',date("Y-m-d H:i:s",time()).'通知成功:'.$setData->out_trade_no);
        try{
            if($setData->result_code=='SUCCESS'){//支付成功
                $payinfo['appid']       =   $setData['appid'];//小程序ID
                $payinfo['mch_id']      =   $setData['mch_id'];//商户号
                $payinfo['result_code'] =   $setData['result_code'];//业务结果SUCCESS/FAIL
                $payinfo['openid']      =   $setData['openid'];//用户标识
                $payinfo['trade_type']  =   $setData['trade_type'];//交易类型
                $payinfo['total_fee']   =   $setData['total_fee'];//订单金额
                $payinfo['transaction_id']  =   $setData['transaction_id'];//微信支付订单号
                $payinfo['order_no']    =   substr($setData['out_trade_no'],3);//商户订单号
                $payinfo['time_end']    =   $setData['time_end'];//支付完成时间
                $payinfo['status']  =   1;//更改支付状态
                
                $res = BasePaydata::where(['order_no'=>$payinfo['order_no'],'model'=>$model,'status'=>0])-> order('id', 'desc')->find();//查询订单支付记录
                // 更新支付记录
                BasePaydata::where('order_no',$payinfo['order_no'])
                ->where('model',$model)
                ->where('status',0)
                ->data($payinfo)
                ->update();
                // 更新订单支付状态
                $Orderpay['payment_trade']  = $payinfo['transaction_id'];
                $Orderpay['payment_status'] = $payinfo['status'];//支付状态
                $Orderpay['payment_datetime'] = $payinfo['time_end'];
                $Orderpay['status']         = 4;//更改订单状态为已支付
                $Orderpay['payment_code']   = 1;//支付配置ID  1=小程序
                $Orderpay['payment_data_id']   = $res['id']; // 支付记录ID
                $Orderpay['update_time']    = time();
                Db::name('CouponOrder')
                ->where('order_no',$payinfo['order_no'])
                ->data($Orderpay)
                ->update();

                if($model=='Coupon'){
                    // 根据openid查询userinfo
                    $user = Users::where('openid',$payinfo['openid'])->where('status',1)->find();
                    // 获取消费券编号
                    $orderItem = Db::name('CouponOrderItem')->where('order_no',$payinfo['order_no'])->find();
                    // 生成用户领券记录
                    CouponIssueUser::issueUserCoupon($orderItem['coupon_uuno'],$user,$payinfo['order_no']);
                }
                $msg = '支付成功';
            }else{
                $msg = '支付失败！';
            }
            Log::debug('Wechat notify', $setData->all());
        } catch (\Exception $e) {
            $sss = $setData->out_trade_no ? $setData->out_trade_no : '';
            $this->create_file(date('Y-m-d',time())."_pay_error.log",'logs/',date("Y-m-d H:i:s",time()).'验签失败:'.$e->getMessage().$sss);
        }
        return $pay->success()->send();
    }
    
    public function refund()//退款验证
    {
        $model  = Request::param('model');//模型
        $model  = ucfirst($model);
        $config = BaseRefunds::wxConfig($model);
        $key = md5($config['key']);
        $pay = Pay::wechat($config);
        $refundinfo =   [];
        $setData    = $pay->verify(null,true); // 是的，验签就这么简单！  返回数据格式未集合 
        //$this->create_file(date('Y-m-d',time())."_refund_log.log",'logs/',date("Y-m-d H:i:s",time()).'退款通知成功'.$setData);
        
        try{
            if($setData->return_code=='SUCCESS'){//退款成功
                $refundinfo['return_code']  =   $setData->return_code ;//返回状态码
                $refundinfo['return_msg']   =   $setData->return_code ;//返回信息
                $refundinfo['appid']    =   $setData->appid;//公众账号ID
                $refundinfo['mch_id']   =   $setData->mch_id;//退款的商户号
                $refundinfo['req_info'] =   $setData->req_info;//加密信息
                $refundinfo['transaction_id']   = $setData->transaction_id; //微信订单号
                $refundinfo['out_trade_no']     = substr($setData->out_trade_no,3); //商户订单号 
                $refundinfo['refund_id']        = $setData->refund_id;//微信退款单号
                $refundinfo['out_refund_no']    = $setData->out_refund_no;//商户退款单号
                $refundinfo['refund_fee']       = $setData->refund_fee; //退款金额 
                $refundinfo['settlement_refund_fee'] = $setData->settlement_refund_fee;   //退款金额 
                $refundinfo['refund_status']    = $setData->refund_status;//退款状态
                $refundinfo['success_time']     = $setData->success_time;//退款成功时间
                $refundinfo['refund_recv_accout'] = $setData->refund_recv_accout;//退款入账账户

                // 查询退款交易数据记录
                $res = BaseRefunds::where(['order_no'=>$refundinfo['out_trade_no'],'refund_id'=>$refundinfo['refund_id'],'status'=>0,'model'=>$model])->order('id', 'desc')->find();
                    
                //更新订单支付状态
                if($refundinfo['refund_status']=='SUCCESS'){
                    // 更新订单退款状态 支付状态
                    Db::name('CouponOrder')
                    ->where('order_no',$refundinfo['out_trade_no'])
                    ->update(['is_refund'=>2,'payment_status'=>2,'update_time'=>time()]);
                    $payinfo['status']=1;//更改退款状态
                }else{
                    $payinfo['status']=2;//更改退款状态
                }
                
                $payinfo['settlement_refund_fee']   =   $refundinfo['settlement_refund_fee'];//退款金额 
                $payinfo['refund_status']   =   $refundinfo['refund_status'];//退款状态
                $payinfo['success_time']    =   $refundinfo['success_time'];//退款成功时间
                $payinfo['refund_recv_accout']  =   $refundinfo['refund_recv_accout'];//退款入账账户

                $this-> create_file("888.txt",'logs/',json_encode($payinfo));
                // 更新退款记录
                BaseRefunds::where('id',$res['id'])
                ->data($payinfo)
                ->update();
                $sql = BaseRefunds::getLastSql();
                $this->create_file("999.txt",'logs/',json_encode($sql));
                // 修改订单状态
                $msg='退款成功';
            }else{
                $msg= '退款失败！';
            }
            Log::debug('Wechat refund notify', $setData->all());
        } catch (\Exception $e) {
            $sss = $refundinfo['out_trade_no'] ? $refundinfo['out_trade_no'] : '';
            $this->create_file(date('Y-m-d',time())."_refund_error.log",'logs/',date("Y-m-d H:i:s",time()).'验签失败:'.$e->getMessage().$sss);
        }
        $refundinfo['msg'] = $msg;
        $refundinfo['ip']  = request()->ip();
        $vcr =  json_encode($refundinfo);
        $this->create_file("123.txt",'logs/',$vcr);
        return $pay->success()->send();
    }

    public function create_file($name,$path,$content){
        $toppath=$path.$name;
        $Ts=fopen($toppath,"a+");
        fputs($Ts,$content."\r\n");
        fclose($Ts);
    }
}