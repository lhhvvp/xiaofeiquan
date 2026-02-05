<?php
/**
 * @desc   小程序支付API
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

class Pay extends BaseController
{
    /**
     * 控制器中间件 [不需要鉴权set]
     * @var array
     */

	protected $middleware = [
    	Auth::class => ['except' 	=> ['aaa'] ]
    ];

    //初始化
    protected function initialize()
    {
        parent::initialize();
        $this->BasePaydata      = new \app\common\model\BasePaydata;
        $this->CouponIssueUser  = new \app\common\model\CouponIssueUser;
        $this->BaseRefunds      = new \app\common\model\BaseRefunds;
    }

    public function aaa(){

    }

    /**
     * @api {post} /pay/submit 提交订单
     * @apiDescription  提交订单
     */
    public function submit()
    {
        $userid     = Request::param('uid/d',0);    //用户uid
        $openid     = Request::param('openid');     //付款OPENID
        $coupon_uuno   = Request::param('coupon_uuno');   //消费券id
        $coupon_data= Request::param('data');       //消费券列表 【消费券uuno,number,price】
        $type       = Request::param('type');        //支付类型 app，wap，mp，miniapp
        
        if(!$openid){
            $this->apiError('当前用户信息异常');
        }

        // 校验用户信息 uid 关联的 openid
        $userInfo = \app\common\model\Users::where(['id'=>$userid])
        ->find();
        if($userInfo['openid']!=$openid){
            $this->apiError('当前用户信息异常，禁止提交');
        }
        
        //验证消费券信息
        $jsonData       = json_decode($coupon_data,true);//转数组

        if($jsonData['number']==0){
            $this->apiError('请至少购买一张消费券');
        }

        if($jsonData['price']<=0){
            $this->apiError('消费券面额至少大于0.01，否则无法调起支付');
        }

        // 查询消费券面额以及信息
        $couponIssueInfo = \app\common\model\CouponIssue::where(['uuno'=>$coupon_uuno])
        ->find();

        if($couponIssueInfo['sale_price']<=0){
            $this->apiError('消费券面额至少小于0.01，无法调起支付');
        }

        if (bccomp($couponIssueInfo['sale_price'], $jsonData['price'], 2) <> 0) {
            $this->apiError('消费券购买价异常，请查证');
        }

        // 校验购买次数
        if($couponIssueInfo['is_limit_total']==1){
            $total = $this->CouponIssueUser->where(['uid' => $userInfo['id'], 'issue_coupon_id' => $couponIssueInfo['id'], 'issue_coupon_class_id' => $couponIssueInfo['cid']])->count();
            if($couponIssueInfo['limit_total'] <= $total){
                $this->apiError('购买已达上限','',3);
            }
        }

        // 总数量
        $number_count    =   $jsonData['number'];
        // 订单总价
        $amount_price    =   bcmul(strval($number_count),$jsonData['price'], 2);  //订单实际支付总价格 =  实际单价 x 数量
        $origin_price    =   bcmul(strval($number_count),$jsonData['price'], 2);  //订单原总价 = 原单价 x 数量

        $order_no   = date('YmdHis').GetNumberCode(6);

        //创建订单数据
        $order = [
            'uuid'                  => $userInfo['uuid'],
            'openid'                => $userInfo['openid'],
            'order_no'              => $order_no,
            'order_out_no'          => 'XFQ'.$order_no,
            'origin_price'          => $origin_price,
            'amount_price'          => $amount_price,
            'number_count'          => $number_count,
            'order_remark'          => '用户购买消费券', // 后期可移植到coupon_data
            'status'                => 1,
            'create_time'           => time(),
            'update_time'           => time()
        ];
        
        // 开始事务
        Db::startTrans();
        // 订单添加
        $order_id = Db::name('CouponOrder')->insertGetId($order);
        if($order_id > 0)
        {
            // 添加订单详情数据
            $detail_ret = self::OrderDetailInsert($order,$userInfo,$couponIssueInfo);
            if(!$detail_ret){
                Db::rollback();//回滚数据
                $this->apiError('订单详情添加失败');
            }
            
            // 消费券剩余数量 - number
            $Deduct_ret = self::OrderInventoryDeduct($jsonData);
            if(!$Deduct_ret){
                Db::rollback();// 事务回滚
                $this->apiError('消费券库存扣除失败');
            }

            // 订单提交成功
            Db::commit();
            
            // 开始付款
            // 线上支付
            $payret = $this->BasePaydata->wxminipay($order['amount_price']*100,$order,$openid,$type,"Coupon");
            if($payret){
                $start = '{';
                $end   = '}';
                $data['pay']        = $type=='app' ? $start.getstripos($payret, '{' , '}').$end : $payret;
                $data['order_no']   = $order['order_no'];
                $data['amount_price']= $order['amount_price'];
                $this->apiSuccess('订单添加成功',$data);
            }
        }else {
            Db::rollback();
            $this->apiError('订单添加失败');
        }
    }

    // 添加订单详情数据
    private function OrderDetailInsert($order, $userInfo, $couponIssueInfo)
    {   
        $data=[];
        $data = [
            'uuid'              => $userInfo['uuid'],
            'order_no'          => $order['order_no'],
            'coupon_uuno'       => $couponIssueInfo['uuno'],
            'coupon_cid'        => $couponIssueInfo['cid'],
            'coupon_title'       => $couponIssueInfo['coupon_title'],
            'coupon_price'       => $couponIssueInfo['coupon_price'],
            'coupon_icon'       => $couponIssueInfo['coupon_icon'],
            'coupon_sale_price' => $couponIssueInfo['sale_price'],
            'total_market'      => bcmul(strval($order['number_count']), $couponIssueInfo['sale_price'], 2),
            'price_selling'       => $couponIssueInfo['sale_price'],
            'total_selling'       => bcmul(strval($order['number_count']), $couponIssueInfo['sale_price'], 2),            
            'stock_sales'       => intval($order['number_count']),
            'discount_amount'   => 0, // 优惠金额
            'create_time'       => time(),
            'update_time'       => time(),
        ];
        $addnum = Db::name('CouponOrderItem')->insertGetId($data);//返回成功数
        if($addnum >0 ){
            return true;
        }
        return false;
    }

    // 减少消费券库存
    private function OrderInventoryDeduct($jsonData)
    {   
        // 扣除消费券库存操作
        if(!Db::name('CouponIssue')->where(['uuno'=>$jsonData['uuno']])->Dec('remain_count',$jsonData['number'])){
            return false;
        }
        return true;
    }

    /**
     * @api {post} /pay/refund 退款
     * @apiDescription  提交退款
     */
    public function refund()
    {
        $userid     = Request::param('uid/d',0);    //用户uid
        $openid     = Request::param('openid');     //付款OPENID
        $order_remark     = Request::param('order_remark');   //退款理由
        $order_no         = Request::param('order_no');     //退款订单号
        $coupon_issue_user_id = Request::param('coupon_issue_user_id');     //领取记录ID

        if(!$coupon_issue_user_id){
            $this->apiError('领取记录不存在');
        }

        if ($order_no=='' || $order_remark=='') $this->apiError('退款信息错误');

        if(!$openid){
            $this->apiError('当前用户信息异常');
        }

        // 校验用户信息 uid 关联的 openid
        $userInfo = \app\common\model\Users::where(['id'=>$userid])
        ->find();
        if($userInfo['openid']!=$openid){
            $this->apiError('当前用户信息异常，禁止提交');
        }

        $order = Db::name('CouponOrder')->where('order_no',$order_no)->find();
        if (!$order) {
            $this->apiError('支付订单不存在!');
        }
        if (!$order['payment_trade']) {
                return jsonReturn('该订单未支付成功无法退款');
        }
        if ($order['is_refund'] > 0) {
            $this->apiError('订单正在退款中或者已经退款');
        }

        // 检查消费券是否使用
        $cInfo = $this->CouponIssueUser::find($coupon_issue_user_id);
        if($cInfo['status']==1){
            $this->apiError('该消费券已使用,无法退款');
        }

        // 处理退款
        $res = $this->OrderRefund($order,$order_remark,$cInfo);
        if ($res) {
            $this->apiSuccess('申请成功',$res);
        }
        return $this->apiError('申请失败');   
    }

    /**
     * 服务端后台 订单退款处理
     * @param array $order
     * @param str $order_remark
     * @return mixed
     */
    public function OrderRefund($order,$order_remark,$cInfo)
    {
        return Db::transaction(function () use ($order,$order_remark,$cInfo) {
            // 回退库存
            $this->regressionStock($order,$cInfo['issue_coupon_id']);
            switch ($order['payment_code']) {
                case '1':
                    //小程序退款
                    $payret = $this->BaseRefunds->refund($order['amount_price']*100,$order['amount_price']*100,$order['order_no'],$order_remark,'Coupon');
                    if($payret['code'] != 0){
                        return false;
                    }
                break;
            }
            // 设置领取记录为无效
            $this->CouponIssueUser::where('id',$cInfo['id'])->update(['is_fail'=>0]);
            return true;
        });
    }

    /**
     * 回退库存
     * ps: 增加消费券剩余领取数量
     * @param $order
     * @return bool
     */
    public function regressionStock($order,$issue_coupon_id)
    {
        $buy_number = $order['number_count'];
        $res = Db::name('CouponIssue')
        ->where('id',$issue_coupon_id)
        ->Inc('remain_count', (int)$buy_number)
        ->update();
        return $res;
    }
}