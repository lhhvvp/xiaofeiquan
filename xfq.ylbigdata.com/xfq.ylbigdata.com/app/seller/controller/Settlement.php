<?php
/**
 * 结算管理控制器
 * @author slomoo <1103398780@qq.com> 2023/08/16
 */
namespace app\seller\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\Session;
use think\facade\View;
use think\facade\Config;
use think\facade\Db;
use app\common\model\Ticket;
use app\common\model\TicketUser;
class Settlement extends Base
{
    // 验证器
    protected $validate = 'TicketSettlement';

    // 当前主表
    protected $tableName = 'ticket_settlement';

    // 当前主模型
    protected $modelName = 'TicketSettlement';

    /**
     * [tourist 结算记录]
     * @return   [type]            [结算记录]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-08-14
     * @LastTime 2023-08-14
     * @version  [1.0.0]
     */
    public function list() {
        if(Request::param('getList') == 1){
            $param = Request::param();
            $where = [];
            if (!empty($param['title'])) {
                $where[] = ['t.title', 'like', '%' . $param['title'] . '%'];
            }
            if (!empty($param['uuno'])) {
                $where[] = ['t.uuno', '=', $param['uuno']];
            }
            if (!empty($param['create_time'])) {
                $getDateran = get_dateran($param['create_time']);
                $where[] = ['t.create_time', 'between', $getDateran];
            }
            // 读取当前商家的核验人员
            $where[] = ['t.mid','=',session('seller.id')];

            $pageSize = $this->pageSize; // 每页展示数量
            $page = $param['page']; // 当前页，默认为第一页

            $query = \app\common\model\TicketSettlement::alias('t')
            ->field('t.*,s.nickname')
            ->join('seller s','s.id = t.mid')
            ->where($where)
            ->append(['status_text','audit_status_text']);

            $result = $query->paginate($pageSize, false, ['page' => $page]);

            $data = $result->items();
            // 2023-08-17 检查核算文件是否生成
            foreach ($data as $key => $value) {
                if($value['data_detail']) $value['data_detail'] = $_SERVER["DOCUMENT_ROOT"].$value['data_detail'];
                $data[$key]['is_files_exits'] = 0;
                if(file_exists($value['data_detail'])) {
                    $data[$key]['is_files_exits'] = 1;
                }
            }
            return [
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->listRows(),
                'total' => $result->total(),
                'data' => $data,
            ];
        }
        return View::fetch('settlement/list');
    }

    /**
     * [add 创建结算单界面]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-08-16
     * @LastTime 2023-08-16
     * @version  [1.0.0]
     */
    public function add()
    {
        // 搜索指定日期内 符合结算要求的订单
        if (Request::param('getList') == 1) {
            $param = Request::param();
            $orderByColumn = $param['orderByColumn'] ?? 'id';
            $isAsc         = $param['isAsc'] ?? 'desc';
            $where = [];

            if (!empty($param['create_time'])) {
                $getDateran = get_dateran($param['create_time']);
                $where[] = ['create_time', 'between', $getDateran];
            }else{
                $start_time = strtotime(date("Y-m-d 00:00:00"));
                $end_time   = strtotime(date("Y-m-d 23:59:59"));
                $where[] = ['create_time', 'between', [$start_time,$end_time]];
            }

            $where[] = ['mch_id','=',session('seller.id')];
            // 未结算的订单
            $where[] = ['settlement_status','=','unsettled'];
            // 线上订单
            $where[] = ['channel','=','online'];
            // 已使用的
            $where[] = ['order_status','=','used'];
            // 未退货的
            $where[] = ['refund_status','=','not_refunded'];
            $pageSize = $this->pageSize; // 每页展示数量
            $page = $param['page']; // 当前页，默认为第一页

            $query = \app\common\model\TicketOrder::where($where)
            ->append(['type_text']);

            $result = $query->paginate($pageSize, false, ['page' => $page]);

            $data = $result->items();
            return [
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->listRows(),
                'total' => $result->total(),
                'data' => $data,
            ];
        }
        return View::fetch('settlement/add_settlement');
    }

    /**
     * [confirm_form 创建结算单界面-第二个页面：确认结算单]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-08-16
     * @LastTime 2023-08-16
     * @version  [1.0.0]
     */
    public function confirm_form()
    {
        $param = Request::param();
        $where = [];
        $where[] = ['mch_id','=',session('seller.id')];
        // 未结算的订单
        $where[] = ['settlement_status','=','unsettled'];
        // 线上订单
        $where[] = ['channel','=','online'];
        // 已支付的、已使用的
        $where[] = ['order_status','=','used'];
        // 未退货的
        $where[] = ['refund_status','=','not_refunded'];
        // 搜索指定日期内 符合结算要求的订单
        if (Request::param('getList') == 1 && Request::param('dateStr') !='') {
            if (!empty($param['dateStr'])) {
                $getDateran = get_dateran($param['dateStr']);
                $where[] = ['create_time', 'between', $getDateran];
            }else{
                $start_time = strtotime(date("Y-m-d 00:00:00"));
                $end_time   = strtotime(date("Y-m-d 23:59:59"));
                $where[] = ['create_time', 'between', [$start_time,$end_time]];
            }
            $pageSize = $this->pageSize; // 每页展示数量
            $page = $param['page']; // 当前页，默认为第一页

            $query = \app\common\model\TicketOrder::where($where)
            ->append(['type_text']);

            $result = $query->paginate($pageSize, false, ['page' => $page]);

            $data = $result->items();
            return [
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->listRows(),
                'total' => $result->total(),
                'data' => $data,
            ];
        }
        // 统计指定日期内的总订单数、总核销金额
        if(Request::param('date') !=''){
            $getDateran = get_dateran(Request::param('date'));
            $where[] = ['create_time', 'between', $getDateran];
        }else{
            $start_time = strtotime(date("Y-m-d 00:00:00"));
            $end_time   = strtotime(date("Y-m-d 23:59:59"));
            $where[] = ['create_time', 'between', [$start_time,$end_time]];
        }
        $total = \app\common\model\TicketOrder::where($where)->count();
        $amount_price = \app\common\model\TicketOrder::where($where)->sum('amount_price');
        $sInfo = \app\common\model\Seller::find(session('seller.id'));
        // 订单ID加密串
        $idArr = \app\common\model\TicketOrder::where($where)->column('id');
        // 所有订单IDMD5
        $enStr = md5(join(',', $idArr));
        return View::fetch('settlement/confirm_form',['sInfo'=>$sInfo,'total'=>$total,'amount_price'=>$amount_price,'enStr'=>$enStr]);
    }

    /**
     * [addPostSettlement 结算单申请提交操作]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-08-17
     * @LastTime 2023-08-17
     * @version  [1.0.0]
     */
    public function addPostSettlement()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            $data['mid'] = session('seller.id');

            $sInfo = \app\common\model\Seller::find($data['mid']);

            $dateran = explode(" 至 ", $data['date']);
            $data['start_date'] = $dateran[0];
            $data['ent_date'] = $dateran[1];
            $data['nickname'] = $sInfo['nickname'];
            $data['class_id'] = $sInfo['class_id'];
            $data['area']     = $sInfo['area'];
            $data['period']   = 'week';
            $data['update_time'] = time();
            $data['create_time'] = time();
            // 门票结算
            $data['uuno']       = 'MPJS'.date("His").strtoupper(set_salt(5));
            // 查询加密串
            $where = [];
            if(!empty($data['date'])){
                $getDateran = get_dateran($data['date']);
                $where[] = ['create_time', 'between', $getDateran];
            }else{
                $this->error('禁止篡改数据');
            }
            // 具体某个商户
            $where[] = ['mch_id','=',session('seller.id')];
            // 未结算的订单
            $where[] = ['settlement_status','=','unsettled'];
            // 线上订单
            $where[] = ['channel','=','online'];
            // 已支付的、已使用的
            $where[] = ['order_status','in',['paid','used']];
            // 未退货的
            $where[] = ['refund_status','=','not_refunded'];
            // 校验加密串是否相等
            $orderList = \app\common\model\TicketOrder::where($where)->select();
            $idArr = array_column($orderList->toArray(), 'id');
            // 所有订单IDMD5
            $enStr = md5(join(',', $idArr));

            if($data['enstr'] !== $enStr){
                $this->error('禁止篡改数据');
            }

            // 获取订单所有内部编号: 获取订单详情列表
            $trade_no_arr = array_column($orderList->toArray(), 'trade_no');
            // 将所有的值分割成较小的块
            $chunks = array_chunk($trade_no_arr, 1000);
            // 删除临时表
            Db::execute('DROP TEMPORARY TABLE IF EXISTS tp_temp_ids');
            // 创建临时表
            Db::execute("CREATE TEMPORARY TABLE tp_temp_ids (trade_no varchar(40))");

            // 批量插入每个块的值到临时表
            foreach ($chunks as $chunk) {
                Db::name('temp_ids')->insertAll(array_map(function ($id) {
                    return ['trade_no' => $id];
                }, $chunk));
            }

            // 执行查询
            $orderDetailList = Db::name('ticket_order_detail')->alias('t')
                ->join('temp_ids ti', 't.trade_no = ti.trade_no')
                ->where('refund_status','not_refunded') // 未退款
                ->where('refund_progress','init')       // 未申请退款
                ->select();
            $order_detail_id_arr = array_column($orderDetailList->toArray(), 'id');
            // 删除临时表
            Db::execute('DROP TEMPORARY TABLE IF EXISTS tp_temp_ids');

            $result = $this->validate($data,  'TicketSettlement');
            if (true !== $result) {
                // 验证失败 输出错误信息
                $this->error($result);
            } else {
                // 结算数量
                $listData      = $orderList->toArray();
                $order_number  = $data['order_numbers'] = count($listData);
                // 结算金额
                $ticket_price_total = "0";
                $ticket_number       = 0;
                // 结算记录构建
                $recordData = [];
                foreach ($orderDetailList as $key => $value) {
                    // 结算记录构建
                    $recordData[$key]['uuno']     = $data['uuno'];
                    $recordData[$key]['trade_no'] = $value['trade_no'];
                    $recordData[$key]['slave_trade_no'] = $value['out_trade_no'];
                    $recordData[$key]['create_time'] = time();
                    $recordData[$key]['update_time'] = time();
                    $ticket_price_total = bcadd($ticket_price_total, bcmul($value['ticket_price'], $value['ticket_number'],2),2);
                    $ticket_number += $value['ticket_number'];
                }
                // 生成PDF文件
                $pageData['title'] = '游客门票购买业务';
                $pageData['addtitle'] = date('Y年m月d日 H:i:s',time());
                $pageData['uuno']       = $data['uuno'];
                // 订单总金额
                $pageData['sum_ticket_price'] = $data['amount'] = $ticket_price_total;
                // 订单总笔数
                $pageData['order_number']     = $order_number;
                // 门票总数量
                $pageData['ticket_number']  =  $data['ticiet_numbers']  = $ticket_number;
                $page_1 = '<style type="text/css">
                .jsxx {font-size: 18px;}
                .xxxx {font-size: 12px;}
                </style>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <table width="325" height="202" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr>
                    <td height="150" align="center" style="font-size: 30px; font-weight:bold;">
                    <p>商户结算申请对账单</p>
                    <p>&nbsp;</p></td>
                  </tr>
                </table>
                <table width="560" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxx">
                  <tr>
                    <td width="130" height="35" align="right">商户名称：</td>
                    <td>'.$data['nickname'].'</td>
                  </tr>
                  <tr>
                    <td height="35" align="right" >结算业务名称：</td>
                    <td>'.$pageData['title'].'</td>
                  </tr>
                  <tr>
                    <td height="35" align="right">申请结算时间：</td>
                    <td>'.$pageData['addtitle'].'</td>
                  </tr>
                  <tr>
                    <td height="35" align="right">结算单号：</td>
                    <td>'.$pageData['uuno'].'</td>
                  </tr>
                  <tr>
                    <td height="35" align="right">结算金额：</td>
                    <td>'.$pageData['sum_ticket_price'].'元</td>
                  </tr>
                  <tr>
                    <td height="35" align="right">结算订单数量：</td>
                    <td>'.$pageData['order_number'].'</td>
                  </tr>
                  <tr>
                    <td height="35" align="right">结算门票总数量：</td>
                    <td>'.$pageData['ticket_number'].'</td>
                  </tr>
                </table>
                <table width="325" height="115" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr>
                    <td height="115" align="center" style="font-size: 18px; font-weight:bold;"><p>&nbsp;</p>
                    <p>收款账户</p></td>
                  </tr>
                </table>
                <table width="560" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxx">
                  <tr>
                    <td width="130" height="35" align="right">账户名称：</td>
                    <td>'.$data['card_name'].'</td>
                  </tr>
                  <tr>
                    <td height="35" align="right" >收款账户：</td>
                    <td>'.$data['cart_number'].'</td>
                  </tr>
                  <tr>
                    <td height="35" align="right">开 户 行：</td>
                    <td>'.$data['card_deposit'].'</td>
                  </tr>
                </table>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <table width="308" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxx">
                  <tr>
                    <td width="308" height="30" align="right">公司（盖章）：</td>
                  </tr>
                  <tr>
                    <td width="308" height="30" align="right">法人签字 ：</td>
                  </tr>
                  <tr>
                    <td height="30" align="right">日期 ：</td>
                  </tr>
                </table>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <p>附件</p>
                ';

                $page_2 = '';
                $page_2 .= '<p>结算单号: '.$pageData['uuno'].'</p>';
                $page_2 .= '
                <table width="800" border="1" align="center" cellpadding="0" cellspacing="0" bordercolor="#999999" class="xxxx">
                  <tr>
                    <td width="40" height="25" align="center">序号</td>
                    <td align="center">订单编号</td>
                    <td width="80" align="center">游客</td>
                    <td width="80" align="center">手机号</td>
                    <td width="80" align="center">门票ID</td>
                    <td width="130" align="center">门票名称</td>
                    <td width="70" align="center">门票单价</td>
                    <td width="70" align="center">购买数量</td>
                    <td width="70" align="center">下单时间</td>
                    <td width="70" align="center">使用时间</td>
                  </tr>';
                  foreach ($orderDetailList as $k => $v) {
                      $page_2 .='<tr>
                        <td height="25" align="center">'.($k+1).'</td>
                        <td align="center">'.$v['trade_no'].'</td>
                        <td align="center">'.$v['tourist_fullname'].'</td>
                        <td align="center">'.$v['tourist_mobile'].'</td>
                        <td align="center">'.$v['ticket_id'].'</td>
                        <td align="center">'.$v['ticket_title'].'</td>
                        <td align="center">'.$v['ticket_price'].'</td>

                        <td align="center">'.$v['ticket_number'].'</td>
                        <td align="center">'.date("Y-m-d H:i:s",$v['create_time']).'</td>
                        <td align="center">'.date("Y-m-d H:i:s",$v['enter_time']).'</td>
                      </tr>';
                  }
                $page_2 .='</table>';

                $html = $page_1.$page_2;
                // 创建PDF文件
                $data_detail = createPdf($html,'普通商户门票结算申请对账单_'.$data['uuno'].'.pdf');
                if($data_detail['code']!=1){
                    $this->error($data_detail['msg']);
                }
                $data['data_detail'] = $data_detail['url'];

                // 开始事务
                Db::startTrans();
                try {
                    // 插入结算单
                    Db::name('ticket_settlement')->strict(false)->insertGetId($data);

                    // 插入计算记录【订单号&结算单】
                    Db::name('ticket_settlement_records')->insertAll($recordData);
                    // 触发事件-结算中
                    Db::name('ticket_order')->where('id','in',$idArr)
                        ->update(['settlement_status'=>'in_progress']);
                    Db::name('ticket_order_detail')->where('id','in',$order_detail_id_arr)
                        ->update(['settlement_status'=>'in_progress']);
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $this->error('申请失败'.$e->getMessage());
                }
                $this->success('申请成功，请点击结算记录进行下一步操作~', 'list');
            }
        }
        $this->error('非法请求');
    }

    // 查看结算单
    public function show()
    {
        $uuno = Request::param('uuno');
        $accInfo = \app\common\model\TicketSettlement::where('uuno',$uuno)->where('mid',session('seller.id'))->find();
        if(!$accInfo){
            $this->error('记录不存在');
        }
        // 区域
        $areaClass = Config::get('lang.area');
        $accInfo['area'] = $areaClass[$accInfo['area']];
        // 格式化附件
        $accInfo['data_url'] = json_decode($accInfo['data_url'],true);
        $view['accInfo'] = $accInfo;
        View::assign($view);

        // 审核记录
        $AuditRecord = \app\common\model\TicketSettlementAudit::where('uuno',$uuno)->select()->toArray();
        View::assign(['AuditRecord' => $AuditRecord]);
        return View::fetch('settlement/show');
    }

    // 结算列表
    public function order_detail_list(){
        $param = Request::param();
        $where = [];
        $where[] = ['o.mch_id','=',session('seller.id')];

        // 获取trade_no
        $trade_no = \app\common\model\TicketSettlementRecords::where('uuno',$param['uuno'])->group('trade_no')->column('trade_no');
        $pageSize = $this->pageSize; // 每页展示数量
        $page = $param['page']; // 当前页，默认为第一页

        $query = \app\common\model\TicketOrder::alias('o')->where($where)
        ->field('o.*')
        ->whereIn('trade_no',$trade_no)
        ->append(['type_text']);

        $result = $query->paginate($pageSize, false, ['page' => $page]);

        $data = $result->items();
        return [
            'current_page' => $result->currentPage(),
            'last_page' => $result->lastPage(),
            'per_page' => $result->listRows(),
            'total' => $result->total(),
            'data' => $data,
        ];
    }

    // 根据核算记录ID 修改附件信息
    public function settlement_data_url()
    {
        if(Request::isPost()){
            $data = Request::except(['file'], 'post');
            // 2022-08-28 待审核时 可撤销审核  从新提交
            if($data['audit_status']=='pending'){
                // 检查是否通过审核  通过审核则不允许撤销
                $accountingInfo = \app\common\model\TicketSettlement::where('uuno',$data['uuno'])->where('mid',session('seller.id'))->find();
                if(!$accountingInfo){
                    $this->error('当前记录不存在');
                }
                if($accountingInfo['audit_status']=='pass'){
                    $this->error('撤销失败,当前记录已经通过审核');
                }
                $res = \app\common\model\TicketSettlement::where('uuno',$data['uuno'])
                ->update(['update_time'=>time(),'audit_status'=>'uploaded','status'=>'pending']);
                if(!$res){
                    $this->error('撤销失败');
                }
                $this->success('撤销成功请重新提交资料', 'list');
            }
            if(!$data['uuno']){
                $this->error('参数错误请刷新页面重试');
            }
            if(!isset($data['images_'])){
                $this->error('请重新上传资料');
            }
            // 修改附件地址 修改状态未待审核
            // 2022-08-25 二次提交时将所有审核步骤变为待审核
            $res = \app\common\model\TicketSettlement::where('uuno',$data['uuno'])
                ->update(['data_url'=>json_encode($data['images_'],true),'update_time'=>time(),'status'=>'in_progress','audit_status'=>'pending']);
            if(!$res){
                $this->error('上传失败,请稍后重试');
            }
            $this->success('上传成功，请耐心等待审核结果', 'list');
        }
        $this->error('非法请求禁止访问');
    }
}
