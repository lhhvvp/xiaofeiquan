<?php
/**
 * 票务-订单-主表控制器
 * @author slomoo <1103398780@qq.com> 2023/07/24
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\View;

use app\common\model\Seller as SellerModel;
use app\common\model\Users as UserModel;
use app\common\model\ticket\Order as OrderModel;
use app\common\model\ticket\OrderDetail as OrderDetailModel;
use app\common\model\TicketRefunds as ticketRefundsModel;
use app\common\model\ticket\WriteOff as WriteOffModel;

class TicketOrder extends Base
{
    // 验证器
    protected $validate = 'TicketOrder';

    // 当前主表
    protected $tableName = 'ticket_order';

    // 当前主模型
    protected $modelName = 'TicketOrder';

    // 列表
    public function index()
    {
        if (Request::param('getList') == 1) {
            $param = Request::param();
            $orderByColumn = $param['orderByColumn'] ?? 'id';
            $isAsc         = $param['isAsc'] ?? 'desc';
            $where = [];
            // 模型条件查询
            $uidwhere = [];
            if(isset($param['name']) && $param['name'] !=''){
                $uidwhere[] = ['name','=',$param['name']];
            }
            $whereTravel = [];
            if(isset($param['travel_name']) && $param['travel_name'] !=''){
                $whereTravel[] = ['nickname','=',$param['travel_name']];
            }
            $whereSeller = [];
            if(isset($param['seller_name']) && $param['seller_name'] !=''){
                $whereSeller[] = ['nickname','=',$param['seller_name']];
            }
            if(isset($param['order_status']) && $param['order_status'] !=''){
                $where[] = ['order_status','=',$param['order_status']];
            }
            if(isset($param['payment_status']) && $param['payment_status'] !=''){
                $where[] = ['payment_status','=',$param['payment_status']];
            }
            if(isset($param['channel']) && $param['channel'] !=''){
                $where[] = ['channel','=',$param['channel']];
            }
            $model  = '\app\common\model\\' . $this->modelName;
            $list = $model::getRewriteList($where, $this->pageSize, [$orderByColumn => $isAsc],$uidwhere,$whereTravel,$whereSeller);
            return $list;
        }
        // 商户分类
        $SellerClass = \app\common\model\SellerClass::field('id, class_name')
            ->order('sort asc')
            ->select()
            ->toArray();
        $view   = [
            'class_list' => $SellerClass,
            'channel_list'=>\app\common\model\TicketOrder::getChannelList(),
            'order_status_list'=>\app\common\model\TicketOrder::getOrderStatusList(),
            'payment_status_list'=>\app\common\model\TicketOrder::getPaymentStatusList()
        ];
        View::assign($view);
        return View::fetch('ticket_order/index');
    }

    public function see()
    {
        $id = Request::get("id", "");
        $trade_no = Request::get("trade_no", "");

        if ($id == '' && $trade_no == '') {
            $this->error("参数错误！");
        }
        if($id != ''){
            $vo         = OrderModel::where(['id' => $id])->append(['order_status_text','payment_status_text','channel_text','refund_status_text','refund_list'])->findOrEmpty()->toArray();
        }else{
            $vo         = OrderModel::where(['trade_no' => $trade_no])->append(['order_status_text','payment_status_text','channel_text','refund_status_text','refund_list'])->findOrEmpty()->toArray();
        }
        if(!$vo){
            $this->error("未找到订单！");
        }
        $vo['seller']         = SellerModel::where('id',$vo['mch_id'])->field("id,nickname,image")->findOrEmpty()->toArray();
        if($vo['channel'] == 'travel' && $vo['travel_id'] > 0){
            $vo['travel'] = SellerModel::where('id',$vo['travel_id'])->field("id,nickname,image")->findOrEmpty()->toArray();
        }
        if($vo['uuid'] != '0'){
            $vo['users'] = UserModel::where('uuid',$vo['uuid'])->field("nickname,uuid,headimgurl")->findOrEmpty()->toArray();
        }
        $view['vo'] = $vo;
        $detail_list = OrderDetailModel::where("trade_no",$vo['trade_no'])->append(['tourist_cert_type_text','refund_status_text','refund_progress_text'])->select()->toArray();
        foreach($detail_list as &$item){
            $item['refund_list'] =  ticketRefundsModel::where("order_detail_no","=",$item['out_trade_no'])->append(['status_text'])->order("id desc")->select()->toArray();
            $item['writeoff_list'] =  WriteOffModel::with(["rights"])->where("order_detail_id","=",$item['id'])->append(['status_text'])->select()->toArray();
        }
        $view['detail_list'] = $detail_list;
        View::assign($view);
        return View::fetch('ticket_order/see');

    }
}
