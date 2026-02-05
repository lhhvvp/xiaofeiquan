<?php
/**
 * 数据汇总控制器
 * @author slomoo <1103398780@qq.com> 2023/08/14
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
class Summary extends Base
{
    // 验证器
    protected $validate = 'TicketOrderDetail';

    // 当前主表
    protected $tableName = 'ticket_order_detail';

    // 当前主模型
    protected $modelName = 'TicketOrderDetail';

    /**
     * [ticket 商户门票销售情况列表]
     * @return   [type]            [表格全部门票的每天销售额、数量、单门票统计某个时段的销售额、数量]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-08-14
     * @LastTime 2023-08-14
     * @version  [1.0.0]
     */
    public function ticket() {
        if(Request::param('getList') == 1){
            $param = Request::param();
            
            $where = [];
            $map   = [];
            if(!empty($param['ticket_id'])){
                $where[] = ['t.id', '=', $param['ticket_id']];
                $map[]   = ['id','=',$param['ticket_id']];
            }

            // 获取该商户所有的门票
            $ticketList = Ticket::where('seller_id',session('seller.id'))->where($map)->where('status',1)->select();

            // 读取当前商家的核验人员
            $where[] = ['t.seller_id','=',session('seller.id')];

            // 获取日期范围:默认最近7天
            $startDate = isset($param['start_date']) ? $param['start_date'] : date('Y-m-d', strtotime("-6 days"));
            $endDate = isset($param['end_date']) ? $param['end_date']  : date('Y-m-d');

            $where[] = ['tod.create_time','between',[strtotime($startDate),strtotime($endDate)]];

            $data = Db::name('ticket')
                ->alias('t')
                ->join('ticket_order_detail tod', 't.id = tod.ticket_id')
                ->leftJoin('ticket_order ord', 'ord.trade_no = tod.trade_no')
                ->field('t.id, t.title, DATE(FROM_UNIXTIME(ord.create_time)) AS date, tod.ticket_price, 
                          SUM(tod.ticket_number) AS total_quantity, SUM(tod.ticket_number * tod.ticket_price) AS total_sales')
                ->where($where)
                ->whereNotNull('ord.create_time')
                ->group('DATE(FROM_UNIXTIME(ord.create_time))')
                ->select();

            // 构建日期范围数组
            $dateRange = [];
            $currentDate = $startDate;
            while ($currentDate <= $endDate) {
                $dateRange[] = $currentDate;
                $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
            }

            // 构建返回数据格式
            $returnData = [];
            foreach ($dateRange as $date) {
                $returnData[$date] = [
                    'ref_date' => $date,
                    'total_quantity' => 0,
                    'total_sales' => '0.00',
                    'ticket_number' => 0,
                    'list' => [],
                ];
            }

            // 将数据集赋值给日期
            foreach ($data as $item) {
                $returnData[$item['date']]['total_quantity'] = $item['total_quantity'];
                $returnData[$item['date']]['total_sales']    = number_format($item['total_sales'], 2);
                $returnData[$item['date']]['ticket_number'] += 1;
                $returnData[$item['date']]['list'][$item['id']] = [
                    'id' => $item['id'],
                    'title' => $item['title'],
                    'total_quantity' => $item['total_quantity'],
                    'total_sales' => number_format($item['total_sales'], 2),
                ];
            }

            // 数据补全：检查每一天是否有数据，如果没有则初始化为0
            foreach ($returnData as $date => $value) {
                foreach ($ticketList as $ticket) {
                    if (!isset($value['list'][$ticket['id']])) {
                        $returnData[$date]['list'][$ticket['id']] = [
                            'id' => $ticket['id'],
                            'title' => $ticket['title'],
                            'total_quantity' => 0,
                            'total_sales' => '0.00',
                        ];
                    }
                }
            }

            // 按照日期排序
            ksort($returnData);

            // 构建最终返回结果
            $result = [];
            foreach ($returnData as $date => $value) {
                $value['list'] = array_values($value['list']); // 重新索引 "list" 数组的键值
                $result[] = $value;
            }

            return json($result);
        }
        // 获取该商户所有的门票
        $ticketList = Ticket::where('seller_id',session('seller.id'))->where('status',1)->select();
        return View::fetch('summary/ticket',['list'=>$ticketList]);
    }

    /**
     * [ticket_seller 统计商户售票员销售情况]
     * @return   [type]            [格商户下所有售票员销售额、数量、单售票员]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-08-14
     * @LastTime 2023-08-14
     * @version  [1.0.0]
     */
    public function ticket_seller() {
        if(Request::param('getList') == 1){
            $param = Request::param();

            $where = [];
            if (!empty($param['name'])) {
                $where[] = ['t.name', 'like', '%' . $param['name'] . '%'];
            }

            if(!empty($param['ticket_id'])){
                $where[] = ['t.id', '=', $param['ticket_id']];
            }

            // 读取当前商家的核验人员
            $where[] = ['t.mid','=',session('seller.id')];

            // 获取日期范围:默认最近7天
            $startDate = isset($param['start_date']) ? $param['start_date'] : date('Y-m-d', strtotime("-6 days"));
            $endDate = isset($param['end_date']) ? $param['end_date']  : date('Y-m-d');

            $where[] = ['tod.create_time','between',[strtotime($startDate),strtotime($endDate)]];

            $data = Db::name('ticket_user')
            ->alias('t')
            ->join('ticket_order_detail tod', 't.uuid = tod.uuid')
            ->leftJoin('ticket_order ord', 'ord.trade_no = tod.trade_no')
            ->field('t.id, t.name, DATE(FROM_UNIXTIME(ord.create_time)) AS date, tod.ticket_price, 
                      SUM(tod.ticket_number) AS total_quantity, SUM(tod.ticket_number * tod.ticket_price) AS total_sales')
            ->where($where)
            ->whereNotNull('ord.create_time')
            ->group('DATE(FROM_UNIXTIME(ord.create_time))')
            ->select();

            // 构建日期范围数组
            $dateRange = [];
            $currentDate = $startDate;
            while ($currentDate <= $endDate) {
                $dateRange[] = $currentDate;
                $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
            }

            // 构建返回数据格式
            $returnData = [];
            foreach ($dateRange as $date) {
                $returnData[$date] = [
                    'ref_date' => $date,
                    'total_quantity' => 0,
                    'total_sales' => '0.00',
                ];
            }

            foreach ($data as $item) {
                $returnData[$item['date']] = [
                    'ref_date' => $item['date'],
                    'total_quantity' => $item['total_quantity'],
                    'total_sales' => number_format($item['total_sales'], 2),

                ];
            }

            return json($returnData);
        }
        // 获取该商户所有的销售人
        $ticketUserList = TicketUser::field('id,name,username')->where('mid',session('seller.id'))->where('status',1)->select();
        return View::fetch('summary/ticket_seller',['list'=>$ticketUserList]);
    }

    /**
     * [tourist 表格游客统计]
     * @return   [type]            [表格游客统计]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-08-14
     * @LastTime 2023-08-14
     * @version  [1.0.0]
     */
    public function tourist() {
        if(Request::param('getList') == 1){
            $param = Request::param();
            $where = [];
            if (!empty($param['name'])) {
                $where[] = ['t.tourist_fullname', 'like', '%' . $param['name'] . '%'];
            }
            // 读取当前商家的核验人员
            $where[] = ['ord.mch_id','=',session('seller.id')];

            $pageSize = $this->pageSize; // 每页展示数量
            $page = $param['page']; // 当前页，默认为第一页

            $query = Db::name('ticket_order_detail')
            ->alias('t')
            ->field('t.tourist_fullname,t.tourist_cert_type,t.tourist_cert_id,t.tourist_mobile,u.name,u.mobile as umobile')
            ->join('ticket_user u','u.uuid = t.uuid')
            ->join('ticket_order ord','ord.trade_no = t.trade_no')
            ->where($where)
            ->group('t.tourist_cert_id,t.tourist_fullname');

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
        return View::fetch('summary/tourist');
    }
}
