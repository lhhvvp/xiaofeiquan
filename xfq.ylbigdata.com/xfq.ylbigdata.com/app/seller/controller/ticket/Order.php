<?php
/**
 * 商户管理控制器
 * @author slomoo <1103398780@qq.com> 2022/07/22
 */

namespace app\seller\controller\ticket;

// 引入框架内置类
use app\seller\controller\Base;
use app\common\facade\MakeBuilder;
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
    // 票种分类列表
    public function index()
    {
        if (Request::isGet()) {
            $view['channel_list']       = OrderModel::getChannelList();
            $view['orderStatus_list']   = OrderModel::getOrderStatusList();
            $view['paymentStatus_list'] = OrderModel::getPaymentStatusList();
            View::assign($view);
            return View::fetch('ticket/order/index');
        } else {
            $param     = Request::param();
            $where     = [];
            $seller_id = session()['seller']['id'];
            $where[]   = ['mch_id', '=', $seller_id];
            if (isset($param['title']) && $param['title'] != '') {
                $where[] = ['title', 'like', '%' . $param['title'] . '%'];
            }
            if (isset($param['channel']) && $param['channel'] != '') {
                $where[] = ['channel', '=', $param['channel']];
            }
            if (isset($param['order_status']) && $param['order_status'] != '') {
                $where[] = ['order_status', '=', $param['order_status']];
            }
            if (isset($param['payment_status']) && $param['payment_status'] != '') {
                $where[] = ['payment_status', '=', $param['payment_status']];
            }
            if (isset($param['uuid']) && $param['uuid'] != '') {
                $where[] = ['uuid', '=', $param['uuid']];
            }
            if (isset($param['contact_man']) && $param['contact_man'] != '') {
                $where[] = ['contact_man', '=', $param['contact_man']];
            }
            if (isset($param['contact_phone']) && $param['contact_phone'] != '') {
                $where[] = ['contact_phone', '=', $param['contact_phone']];
            }
            if (isset($param['contact_certno']) && $param['contact_certno'] != '') {
                $where[] = ['contact_certno', '=', $param['contact_certno']];
            }
            if (isset($param['create_time_range']) && !empty($param['create_time_range'])) {
                $date_range = explode("至", $param['create_time_range']);
                $where[]    = ['create_time', '>=', strtotime(trim($date_range[0]) . " 00:00:00")];
                $where[]    = ['create_time', '<=', strtotime(trim($date_range[1]) . " 23:59:59")];
            }
            $order = ($param['orderByColumn'] ?? 'id') . ' ' . ($param['isAsc'] ?? 'desc');
            $list  = OrderModel::where($where)
                ->append(['channel_text', 'payment_status_text', 'order_status_text'])
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
        $id = Request::get("id", "");
        $trade_no = Request::get("trade_no", "");

        if ($id == '' && $trade_no == '') {
            $this->error("参数错误！");
        }
        $seller_id  = session()['seller']['id'];

        if($id != ''){
            $vo         = OrderModel::where(['id' => $id, 'mch_id' => $seller_id])->append(['order_status_text','payment_status_text','channel_text','refund_status_text','refund_list'])->find()->toArray();
        }else{
            $vo         = OrderModel::where(['trade_no' => $trade_no, 'mch_id' => $seller_id])->append(['order_status_text','payment_status_text','channel_text','refund_status_text','refund_list'])->find()->toArray();
        }
        if(!$vo){
            $this->error("未找到订单！");
        }
        $vo['users']         = UserModel::where('uuid',$vo['uuid'])->field("nickname,uuid,headimgurl")->findOrEmpty()->toArray();
        $view['vo'] = $vo;
        $detail_list = OrderDetailModel::where("trade_no",$vo['trade_no'])->append(['tourist_cert_type_text','refund_status_text','refund_progress_text','rights_list'])->select()->toArray();
        foreach($detail_list as &$item){
            $item['refund_list'] =  TicketRefundsModel::where("order_detail_no","=",$item['out_trade_no'])->append(['status_text'])->order("id desc")->select()->toArray();
            $item['writeoff_list'] =  WriteOffModel::with(["rights"])->where("order_detail_id","=",$item['id'])->append(['status_text'])->select()->toArray();
        }
        $view['detail_list'] = $detail_list;
        View::assign($view);
        return View::fetch('ticket/order/see');

    }
}
