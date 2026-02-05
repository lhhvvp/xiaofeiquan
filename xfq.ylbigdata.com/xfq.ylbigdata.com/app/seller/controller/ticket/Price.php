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
use think\facade\Validate;
use think\facade\View;
use app\common\model\ticket\Ticket as TicketModel;
use app\common\model\ticket\Category as CategoryModel;
use app\common\model\ticket\Price as PriceModel;
// 引入表格和表单构建器
// 引入导出的命名空间

class Price extends Base
{
    // 票种价格
    public function index()
    {
        if (Request::isGet()) {
            if(empty($ticket_id = Request::get('ticket_id',''))){
                $this->error('缺少参数！');
            }
            $data['ticket'] = TicketModel::where('id',$ticket_id)->find()->toArray();
            $data['list'] = PriceModel::where('ticket_id',$data['ticket']['id'])->select()->toArray();

            View::assign($data);
            return View::fetch('ticket/price/index');
        } else {
            $param = Request::param();
            $where = [];
            if (isset($param['ticket_id']) && $param['ticket_id'] != '') {
                $where[] = ['ticket_id', '=', $param['ticket_id']];
            }
            if (isset($param['start']) && isset($param['end'])) {
                $where[] = ['date', 'between time',[$param['start'],$param['end']]];
            }
            $list = PriceModel::where($where)
                ->order("date asc")
                ->select()->toArray();
            return $list;
        }
    }

    // 添加票种
    public function build()
    {
        if (Request::isGet()) {
            if(empty($ticket_id = Request::get('ticket_id',''))){
                $this->error('缺少参数！');
            }
            $data['ticket'] = TicketModel::where('id',$ticket_id)->find()->toArray();
            View::assign($data);
            return View::fetch('ticket/price/build');
        } else {
            $post = Request::post("row");
            $daterange = explode("至",trim($post['daterange']));
            $startDate = $daterange[0];
            $endDate = $daterange[1];

            $currentDate = strtotime($startDate);
            $endDate = strtotime($endDate);

            $today = strtotime(date("Y-m-d"));
            if($currentDate < $today){
                $this->error('请选择当日及以后的日期');
            }
            $halfYearLater = strtotime("+6 months", time());
            if($endDate > $halfYearLater){
                $this->error('只允许生成半年内的数据');
            }
            $date_list = [];
            while ($currentDate <= $endDate) {
                $date_list[] = date('Y-m-d', $currentDate);
                $currentDate = strtotime('+1 day', $currentDate);
            }
            if(!$ticket = TicketModel::where('id',$post['ticket_id'])->findOrEmpty()->toArray()){
                $this->error('票种不存在！');
            }
            $msg_str = '操作成功！';
            foreach($date_list as $key=>$date){
                if($info = PriceModel::where(['date'=>$date,'ticket_id'=>$post['ticket_id']])->find()){
                    $info->online_price = $post['online_price'];
                    $info->casual_price = $post['casual_price'];
                    $info->team_price = $post['team_price'];
                    $stock = $post['stock'] - $info->total_stock +  $info->stock;
                    if($stock < 0){
                        $msg_str .= '<br>'. $date.'天修改失败，不允许已售大于总库存';
                    }else{
                        $info->stock =  $stock;
                        $info->total_stock = $post['stock'];
                        $info->save();
                    }
                }else{
                    $insertData = [
                        'seller_id'=>$ticket['seller_id'],
                        'ticket_id'=>$post['ticket_id'],
                        'date'=>$date,
                        'online_price'=>$post['online_price'],
                        'casual_price'=>$post['casual_price'],
                        'team_price'=>$post['team_price'],
                        'stock'=>$post['stock'],
                        'total_stock'=>$post['stock']
                    ];
                    PriceModel::create($insertData);
                }
            }
            $this->success($msg_str);
        }
    }

    // 修改日期价格
    public function post()
    {
        if (Request::isGet()) {
            $param = Request::get();
            if(!isset($param['ticket_id']) || !isset($param['date'])){
                $this->error('缺少参数！');
            }
            if(!$ticket = TicketModel::where('id',$param['ticket_id'])->findOrEmpty()->toArray()){
                $this->error('票种不存在！');
            }
            $vo = PriceModel::where(['ticket_id'=>$param['ticket_id'],'date'=>$param['date']])->findOrEmpty()->toArray();
            View::assign('vo',$vo);
            View::assign('param',$param);
            View::assign('ticket',$ticket);
            return View::fetch('ticket/price/post');
        } else {
            $post = Request::post("row");
            $validate = Validate::rule([
                'total_stock'  => 'require|min:1'
            ]);
            $validate->message([
                'total_stock.require'  => '总库存不能为空！',
                'total_stock.min'  => '总库存不能小于1！'
            ]);
            if (!$validate->check($post)) {
                $this->apiError($validate->getError());
            }
            if(!$ticket = TicketModel::where('id',$post['ticket_id'])->findOrEmpty()->toArray()){
                $this->error('票种不存在！');
            }
            $post['seller_id'] = $ticket['seller_id'];
            $vo = PriceModel::where(['ticket_id'=>$post['ticket_id'],'date'=>$post['date']])->findOrEmpty();
            if (!$vo->isEmpty()) {
                //计算修改后的当前库存
                $stock = $post['total_stock'] - $vo->total_stock +  $vo->stock;
                if($stock < 0){
                    $this->error('不允许已售大于总库存！');
                }else{
                    $post['total_stock'] = $post['total_stock'];
                    $post['stock'] = $stock;
                    $vo->save($post);
                }
            }else{
                $post['total_stock'] = $post['total_stock'];
                $post['stock'] = $post['total_stock'];
                PriceModel::create($post);
            }
            $this->success('操作成功!');
        }
    }
}
