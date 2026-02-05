<?php
/**
 * 门票-订单-内容模型
 * @author slomoo <1103398780@qq.com> 2022/10/28
 */
namespace app\common\model;
use think\facade\Db;
use think\facade\Request;
// 引入构建器
use app\common\facade\MakeBuilder;

class TicketOrder extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 获取列表
    public static function getRewriteList(array $where = [], int $pageSize = 0, array $order = ['sort', 'id' => 'desc'],$whereUid = [],$whereTravel = [],$whereSeller = [])
    {
        $query = $list = self::where($where)->hasWhere('users',$whereUid)->hasWhere('travel',$whereTravel)->hasWhere('seller',$whereSeller)->with(['users','travel','seller'])->order("id desc")->append(["channel_text","order_status_text","refund_status_text","type_text","payment_status_text"]);
        if ($pageSize > 0){
            $list = $query->paginate(['query'=> Request::get(),'list_rows' => $pageSize]);
        }else{
            $list = $query->select();
        }
        return $list;
    }

    public function users()
    {
        return $this->hasOne('Users', 'uuid','uuid')->joinType("LEFT")->field('id,sex,email,last_login_time,last_login_ip,mobile,idcard,type_id,status,create_time,update_time,name,headimgurl,nickname,salt,uuid,openid,auth_status,province,city,district');
    }

    public function travel()
    {
        return $this->hasOne('Travel', 'id','travel_id')->joinType("LEFT")->field('id,nickname,image');
    }
    public function seller()
    {
        return $this->hasOne('Seller', 'id','mch_id')->joinType("LEFT")->field('id,nickname,image');
    }
    public static function getOrderStatusList(){
        return [
            'created'   =>'待支付',
            'paid'      =>'已支付',
            'used'      =>'已使用',
            'cancelled' =>'已取消',
            'refunded'  =>'已退款'
        ];
    }
    // 定义订单状态字段的访问器
    public function getOrderStatusTextAttr($value,$data)
    {
        $list = self::getOrderStatusList();
        return isset($list[$data['order_status']]) ? $list[$data['order_status']] : '-';
    }

    public static function getRefundStatusList(){
        return [
            'not_refunded'          =>'未退货',
            'partially_refunded'=>'部分退货',
            'fully_refunded'=>'全部退货'
        ];
    }
    // 定义订单退款字段的访问器
    public function getRefundStatusTextAttr($value,$data)
    {

        $list = self::getRefundStatusList();
        return isset($list[$data['refund_status']]) ? $list[$data['refund_status']] : '-';
    }
    public static function getChannelList(){
        return [
            'online'=>'线上',
            'window'=>'窗口',
            'travel'=>'旅行社',
            'ota_xc'=>'携程',
            'ota_mt'=>'美团'
        ];
    }
    public static function getChannelTextAttr($value,$data){
        $list = self::getChannelList();
        return isset($list[$data['channel']]) ? $list[$data['channel']] : '-';
    }

    // 定义支付渠道字段的访问器
    public static function getTypeList()
    {
        return [
            'miniapp'=>'小程序',
            'weixin'  =>'微信',
            'cash'      =>'现金',
            'unionpay'     =>'银联',
            'alipay'         =>'支付宝',
            'ota_xc'=>'携程'
        ];
    }
    // 定义支付渠道字段的访问器
    public function getTypeTextAttr($value,$data)
    {
        $list = self::getTypeList();
        return isset($list[$data['type']]) ? $list[$data['type']] : '-';
    }

    public static function getPaymentStatusList()
    {
        return [
            '0'  =>'未支付',
            '1'  =>'已支付',
            '2'  =>'已退款'
        ];
    }
    public function getPaymentStatusTextAttr($value,$data)
    {
        $list = self::getPaymentStatusList();
        return isset($list[$data['payment_status']]) ? $list[$data['payment_status']] : '-';
    }
}