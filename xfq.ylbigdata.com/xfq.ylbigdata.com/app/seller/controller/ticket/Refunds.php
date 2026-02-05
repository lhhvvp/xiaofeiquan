<?php
/**
 * 商户管理控制器
 * @author slomoo <1103398780@qq.com> 2022/07/22
 */
namespace app\seller\controller\ticket;

// 引入框架内置类
use app\seller\controller\Base;
use think\facade\Db;
use think\facade\Request;
use think\facade\View;
use app\common\model\TicketRefunds as TicketRefundsModel;
// 引入表格和表单构建器
// 引入导出的命名空间

class Refunds extends Base
{
    // 票种列表
    public function index()
    {
        if (Request::isGet()) {
            View::assign("status_list",TicketRefundsModel::getStatusList());
            return View::fetch('ticket/refunds/index');
        } else {
            $param = Request::param();
            $where = [];
            $where[] = ['mch_id','=',session()['seller']['id']];
            if (isset($param['trade_no']) && $param['trade_no'] != '') {
                $where[] = ['trade_no', '=',$param['trade_no']];
            }
            if (isset($param['order_detail_no']) && $param['order_detail_no'] != '') {
                $where[] = ['order_detail_no', '=',$param['order_detail_no']];
            }
            if (isset($param['status']) && $param['status'] != '') {
                $where[] = ['status', '=', $param['status']];
            }
            if (isset($param['create_time_range']) && !empty($param['create_time_range'])) {
                $date_range = explode("至", $param['create_time_range']);
                $where[] = ['create_time', '>=', strtotime(trim($date_range[0]) . " 00:00:00")];
                $where[] = ['create_time', '<=', strtotime(trim($date_range[1]). " 23:59:59")];
            }
            $order = ($param['orderByColumn'] ?? 'id') . ' ' . ($param['isAsc'] ?? 'desc');
            $list = TicketRefundsModel::where($where)
                ->append(['status_text'])
                ->order($order)
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $this->pageSize,
                ]);
            return ['total' => $list->total(), 'current_page' => $list->currentPage(), 'last_page' => $list->lastPage(), 'data' => $list->items()];
        }
    }

    /**
     * 同意退款--执行订单退款
     * @param $refunds
     */
    public function execute()
    {
        if (!Request::isPost()) {
            $this->error('请求异常');
        }
        $refund_id   = Request::param('refund_id/d',0);
        if(!$refund_id){
            $this->error('参数错误');
        }
        $orderDetail = $this->wxrefund($refund_id);
        if($orderDetail === true){
            $this->success('操作成功', 'index');
        }
        $this->error('操作失败'.$orderDetail);
    }

    // 退款验证
    public function wxrefund($refund_id)
    {
        // 退款记录
        $detail = \app\common\model\TicketRefunds::find($refund_id);
        if(!$detail) return '订单不存在';
        // 检查是否是申请退款的订单？
        if($detail->status!=0) return '当前退款记录存在异常';
        // 退款记录包含的订单主表
        $detail['order'] = \app\common\model\TicketOrder::where('trade_no',$detail->trade_no)->find();
        switch ($detail['order']['order_status']) {
            case 'created':
                return '未支付订单无法退款!';
                break;
            case 'used':
                return '已使用订单无法退款!';
                break;
            case 'cancelled':
                return '已取消订单无法退款!';
                break;
            case 'refunded':
                return '该订单已经全额退款!';
                break;
            default:
                // code...
                break;
        }
        // 退款包含的从表
        $detail['order_detail'] = \app\common\model\TicketOrderDetail::where('trade_no',$detail->trade_no)->where('refund_id',$refund_id)->select();
        foreach ($detail['order_detail'] as $key => $value) {
            switch ($value['refund_status']) {
                case 'fully_refunded':
                    return '该子单已经全额退款!'.$value['out_trade_no'];
                    break;
                default:
                    // code...
                    break;
            }
            switch ($value['refund_progress']) {
                case 'init':
                    return '该子单未提交退款'.$value['out_trade_no'];
                    break;
                case 'approved':
                    return '该子单已经通过退款审核，请稍后查看'.$value['out_trade_no'];
                    break;
                case 'completed':
                    return '该子单已经完成退款'.$value['out_trade_no'];
                    break;
                default:
                    // code...
                    break;
            }
        }
        // 退费验证
        $bj = bccomp((string)$detail['order']['amount_price'], (string)$detail->refund_fee, 2);
        if ($bj < 0) {
            return '退款金额大于支付金额,请仔细核对';
        }
        // 开始退款逻辑
        return $this->refundBatch($detail);
    }

    /**
     * 订单退款处理
     * @param int $type
     * @param $order
     * @param array $refundData
     * @return mixed
     */
    public function refundBatch($detail)
    {
        $this->ref = new \app\common\model\TicketRefunds();
        // 开启事务
        Db::startTrans();
        try {
            foreach ($detail['order_detail'] as $key => $value) {
                // 回退库存
                Db::name('ticket_price')->where('ticket_id',$value['ticket_id'])->where('date',$value['ticket_date'])->inc('stock',1)->update();
                // 修改订单详情状态
                Db::name('ticket_order_detail')->where('id',$value['id'])
                    ->data([
                        'refund_progress' => 'approved',
                        'refund_status' => 'fully_refunded',
                        'refund_time'   =>  time(),
                        'update_time'   =>  time()
                    ])
                    ->update();
            }
            // 退金额
            if ($detail['refund_fee'] > 0) {
                //小程序退款
                $payret = $this->ref->refund($detail);
                if($payret['code'] != 0){
                    return '微信退款失败'.$payret;
                }
            }
            // 提交事务
            Db::commit();
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();

            // 返回失败信息
            return $e->getMessage();
        }
    }
    /*
     * 审核拒绝
     * */
    public function refuse(){
        if(!Request::isPost()){
            $this->error("请求方式错误！");
        }
        $id = Request::post("id/d",0);
        $refuse_desc = Request::post("refuse_desc/s","");
        if($id === 0){
            $this->error("参数错误！");
        }
        if($refuse_desc === ""){
            $this->error("拒绝原因必填！");
        }
        $row = TicketRefundsModel::where("id",$id)->find();
        if(!$row){
            $this->error("未找到该记录！");
        }
        $row->status = 2;
        $row->refuse_desc = $refuse_desc;
        // slomoo@aliyun.com 2023-07-26 修改订单详情进度为已拒绝
        Db::name('ticket_order_detail')
            ->where('refund_id', $id)
            ->update([
                'refund_progress' => 'refuse', // 已拒绝
                'refund_status'   => 'not_refunded',
                'update_time'     => time()
            ]);
        if($row->save()){
            $this->success("操作成功！");
        }
        $this->error("操作失败！");
    }
}
