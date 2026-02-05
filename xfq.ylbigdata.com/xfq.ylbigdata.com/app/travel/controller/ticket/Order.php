<?php
namespace app\travel\controller\ticket;

// 引入框架内置类
use app\travel\controller\Base;
use think\facade\Db;
use think\facade\Request;
use think\facade\View;
use app\common\model\Users as UserModel;
use app\common\model\ticket\Order as OrderModel;
use app\common\model\ticket\OrderDetail as OrderDetailModel;
use app\common\model\TicketRefunds as TicketRefundsModel;
use app\common\model\ticket\WriteOff as WriteOffModel;
// 引入表格和表单构建器
// 引入导出的命名空间

class Order extends Base
{
    // 门票订单列表
    public function index()
    {

        if (Request::isGet()) {

            $view['channel_list']       = OrderModel::getChannelList();
            $view['orderStatus_list']   = OrderModel::getOrderStatusList();
            $view['paymentStatus_list'] = OrderModel::getPaymentStatusList();
            View::assign($view);

            return View::fetch();
        } else {
            $param     = Request::param();
            $where     = [];
            $travel_id = session()['travel']['id'];
            $where[]   = ['travel_id', '=', $travel_id];
            $where[]   = ['channel', '=', "travel"];
            $sellerWhere = [];
            if (isset($param['seller_nickname']) && $param['seller_nickname'] != '') {
                $sellerWhere[] = ['nickname', 'like', '%' . $param['seller_nickname'] . '%'];
            }
            if (isset($param['order_status']) && $param['order_status'] != '') {
                $where[] = ['order.order_status', '=', $param['order_status']];
            }
            if (isset($param['trade_no']) && $param['trade_no'] != '') {
                $where[] = ['order.trade_no', '=', $param['trade_no']];
            }
            if (isset($param['payment_status']) && $param['payment_status'] != '') {
                $where[] = ['order.payment_status', '=', $param['payment_status']];
            }
            if (isset($param['create_time_range']) && !empty($param['create_time_range'])) {
                $date_range = explode("至", $param['create_time_range']);
                $where[]    = ['order.create_time', '>=', strtotime(trim($date_range[0]) . " 00:00:00")];
                $where[]    = ['order.create_time', '<=', strtotime(trim($date_range[1]) . " 23:59:59")];
            }
            $order = ($param['orderByColumn'] ?? 'id') . ' ' . ($param['isAsc'] ?? 'desc');
            $list  = OrderModel::where($where)
                ->hasWhere("seller",$sellerWhere)
                ->with(['seller'])
                ->append(['channel_text', 'payment_status_text', 'order_status_text','qrcode_str','rights_qrcode_list'])
                ->order($order)
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $this->pageSize,
                ]);
            return ['total' => $list->total(), 'current_page' => $list->currentPage(), 'last_page' => $list->lastPage(), 'data' => $list->items()];
        }
    }

    public function see()
    {
        $id       = Request::get("id", "");
        $trade_no = Request::get("trade_no", "");

        if ($id == '' && $trade_no == '') {
            $this->error("参数错误！");
        }
        $travel_id = session()['travel']['id'];
        if ($id != '') {
            $vo = OrderModel::where(['id' => $id, 'travel_id' => $travel_id])->append(['order_status_text', 'payment_status_text', 'channel_text', 'refund_status_text','rights_qrcode_list','refund_list'])->find()->toArray();
        } else {
            $vo = OrderModel::where(['trade_no' => $trade_no, 'travel_id' => $travel_id])->append(['order_status_text', 'payment_status_text', 'channel_text', 'refund_status_text','rights_qrcode_list','refund_list'])->find()->toArray();
        }
        $view['vo']  = $vo;
        $detail_list = OrderDetailModel::where("trade_no", $vo['trade_no'])->append(['tourist_cert_type_text', 'refund_status_text', 'refund_progress_text', 'rights_list'])->select()->toArray();
        foreach ($detail_list as &$item) {
            $item['refund_list']   = TicketRefundsModel::where("order_detail_no", "=", $item['out_trade_no'])->append(['status_text'])->order("id desc")->select()->toArray();
            $item['writeoff_list'] = WriteOffModel::with(["rights"])->where("order_detail_id", "=", $item['id'])->append(['status_text'])->select()->toArray();
        }
        $view['detail_list'] = $detail_list;
        View::assign($view);
        return View::fetch('ticket/order/see');
    }
    /*
     *
     * 取消订单
     *
     * */
    public function cancel()
    {
        if(!Request::isAjax())
        {
            $this->error("请求方式错误！");
        }
        $id = Request::post("id/d",0);
        if($id === 0){
            $this->error("参数错误！");
        }
        $travel_id  = session()['travel']['id'];
        $order_info = OrderModel::where(["id"=>$id,'travel_id'=>$travel_id])->find();
        if(!$order_info){
            $this->error("记录不存在！");
        }
        if($order_info['order_status'] != 'created'){
            $this->error("订单状态不允许取消！");
        }
        $order_info->order_status = 'cancelled';
        $order_info->save();
        $this->success("取消成功！");
    }

    /*
     *
     * 退款
     *
     * */
    public function refund()
    {
        if(!Request::isAjax())
        {
            $this->error("请求方式错误！");
        }
        $id = Request::post("id/d",0);
        $refund_desc = Request::post("refund_desc/s","");
        if($id === 0){
            $this->error("参数错误！");
        }
        if($refund_desc === ""){
            $this->error("退款原因必填！");
        }
        $travel_id  = session()['travel']['id'];
        $order_info = OrderModel::where(["id"=>$id,'channel'=>'travel','travel_id'=>$travel_id])->find();
        if(!$order_info){
            $this->error("记录不存在！");
        }
        switch ($order_info['order_status']) {
            case 'created':
                $this->error('未支付订单无法退款!');
                break;
            case 'used':
                $this->error('已使用订单无法退款!');
                break;
            case 'cancelled':
                $this->error('已取消订单无法退款!');
                break;
            case 'refunded':
                $this->error('该订单已经全额退款!');
                break;
            default:
                // code...
                break;
        }
        if ($order_info['refund_status'] == 'fully_refunded') {
            $this->error('该订单已经全额退款!');
        }
        //
        // 查询待退款的全部订单
        $order_detail_list = OrderDetailModel::where('trade_no', $order_info['trade_no'])
            ->where('refund_status', 'not_refunded') // 退款状态 未退款的
            ->where(function($query){
                $query->where('refund_progress','init')->whereOr('refund_progress','refuse');
            })       // 退款进度 为初始化状态或已拒绝的
            ->select()
            ->toArray();
        if(!$order_detail_list){
            $this->error('当前订单下未检测到可退款的子单!');
        }
        // 开启事务
        Db::startTrans();
        try {
            // 生成一条退款交易数据
            $refundData = [
                'mch_id'        => $order_info['mch_id'],
                'trade_no'      => $order_info['trade_no'],
                'out_refund_no' => 'BIG'.date('YmdHis') . GetNumberCode(6),
                'total_fee'     => $order_info['amount_price'],
                'refund_fee'    => 0.00, // 退款总金额
                'refund_desc'   => $refund_desc,
                'refund_ip'     => Request::ip(),
                'transaction_id' => $order_info['transaction_id'],
                'create_time'   => time()
            ];
            $refundInfo = TicketRefundsModel::create($refundData);
            // 计算退款总金额
            $refund_fee = 0.00; // 退款总金额
            foreach ($order_detail_list as $key => $value) {
                $refund_fee = bcadd(strval($refund_fee), $value['ticket_price'], 2);
                // 修改当前订单退款信息
                OrderDetailModel::where('id', $value['id'])->update([
                    'refund_progress' => 'pending_review', // 已提交
                    'is_full_refund' => 1,                // 是否整单退 1=是 0=否
                    'refund_id'     => $refundInfo['id']
                ]);
            }
            // 修改总退款金额
            $refundInfo->refund_fee = $refund_fee;
            $refundInfo->save();
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            // 返回失败信息
            $this->error( $e->getMessage());
        }
        $this->success("申请成功，请耐心等待审核！");
    }
}
