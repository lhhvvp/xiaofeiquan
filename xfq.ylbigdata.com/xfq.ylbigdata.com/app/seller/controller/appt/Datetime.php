<?php
/**
 * 商户管理控制器
 * @author slomoo <1103398780@qq.com> 2022/07/22
 */

namespace app\seller\controller\appt;

// 引入框架内置类
use app\seller\controller\Base;
use app\common\facade\MakeBuilder;
use think\facade\Request;
use think\facade\View;
use app\common\model\appt\Datetime as DatetimeModel;
use app\common\model\appt\Rule as RuleModel;


class Datetime extends Base
{
    // 票种分类列表
    public function index()
    {
        $seller_id = session()['seller']['id'];
        if (Request::isGet()) {
            $list = DatetimeModel::where(['seller_id'=>$seller_id])->order("date asc")->group("date")->select()->toArray();

            View::assign('list',$list);
            return View::fetch('appt/datetime/index');
        } else {
            $param = Request::param();
            $where[] = ['seller_id','=',$seller_id];

            if (isset($param['start']) && $param['end']) {
                $where[] = ['date', 'between time',[$param['start'],$param['end']]];
            }
            $list = DatetimeModel::where($where)->field("id as pkid,seller_id,date,time_start,time_end,stock,total_stock")->append(['start','end','time_start_text','time_end_text'])->select()->toArray();
            /*$n_list = [];
            if(count($list) > 0){
                foreach($list as $item){
                    if(!isset($n_list[$item['date']])){
                        $n_list[$item['date']] = [];
                    }
                    $n_list[$item['date']][] = $item;
                }
            }*/
            return $list;
        }
    }
    // 修改日期价格
    public function post()
    {
        if (Request::isGet()) {
            $param = Request::get();
            if(!isset($param['id'])){
                $this->error('缺少参数！');
            }
            $vo = DatetimeModel::where(['id'=>$param['id']])->append(['time_start_text','time_end_text'])->findOrEmpty();
            if ($vo->isEmpty()) {
                $this->error('记录不存在！');
            }
            View::assign('vo',$vo->toArray());
            return View::fetch('appt/datetime/post');
        } else {
            $post = Request::post("row");
            $vo = DatetimeModel::where(['id'=>$post['id']])->findOrEmpty();
            if (!$vo->isEmpty()) {
                $stock = $post['total_stock'] - $vo['total_stock'] +  $vo['stock'];
                if($stock < 0){
                    $this->error('修改失败，不允许已售大于总库存！');
                }else{
                    $vo->stock =  $stock;
                    $vo->total_stock = $post['total_stock'];
                    $vo->save();
                }
            }else{
                $this->error('不允许添加！');
            }
            $this->success('修改成功!');
        }
    }

    public function add()
    {
        if (Request::isGet()) {
            $param = Request::get();
            if (!isset($param['date']) || empty($param['date'])) {
                $this->error('参数错误！');
            }
            $rule_list = RuleModel::where('seller_id',session()['seller']['id'])->append(['time_start_end_text','weeks_text'])->select()->toArray();
            View::assign('rule_list',$rule_list);
            View::assign('date',$param['date']);
            return View::fetch('appt/datetime/add');
        } else {
            $post = Request::post("row/a");
            $seller_id = session()['seller']['id'];
            if(empty($post['date'])){
                $this->error('缺少日期参数');
            }
            if(empty($post['rule_ids'])){
                $this->error('请选择时段');
            }
            $rule_list = RuleModel::where("id","in",$post['rule_ids'])->select()->toArray();
            if(empty($rule_list)){
                $this->error('时间段不存在，请刷新后再试');
            }
            $msg = '';
            $success = false;
            foreach($rule_list as $k=>$v){
                $find = DatetimeModel::where([['date','=',$post['date']],['time_start','<=',$v['time_end']],['time_end','>',$v['time_start']]])->find();
                if($find){
                    $msg .= $v['title']."时间段已有记录<br/>";
                }else{
                    $success = true;
                    $insertData = [
                        'seller_id' => $seller_id,
                        'date' => $post['date'],
                        'time_start' => $v['time_start'],
                        'time_end' => $v['time_end'],
                        'stock' => $v['stock'],
                        'total_stock' => $v['stock']
                    ];
                    if(DatetimeModel::insert($insertData)){
                        $msg .= $v['title']."时间段已添加成功<br/>";
                    }
                }
            }
            if($success){
                $this->success($msg);
            }else{
                $this->error($msg);
            }
        }
    }
    /*
     * 删除
     * */
    public function remove()
    {
        if (Request::isPost()) {
            $param = Request::post();
            if(!isset($param['id'])){
                $this->error('缺少参数！');
            }
            $vo = DatetimeModel::where(['id'=>$param['id']])->findOrEmpty();
            if(empty($vo)){
                $this->error('不存在！');
            }
            if($vo['total_stock'] > $vo['stock']){
                $this->error('已有人预定该时段！不允许删除，可以尝试修改库存。');
            }
            $vo->delete();
            $this->success('操作成功!');
        }
        $this->error('操作失败!');
    }
    // 添加票种
    public function build()
    {
        if (Request::isGet()) {

            $rule_list = RuleModel::where('seller_id',session()['seller']['id'])->append(['time_start_end_text','weeks_text'])->select()->toArray();
            View::assign('rule_list',$rule_list);
            return View::fetch('appt/datetime/build');
        } else {
            $post = Request::post("row");
            $seller_id = session()['seller']['id'];
            if(empty($post['daterange'])){
                $this->error('请选择日期');
            }
            if(empty($post['rule_ids'])){
                $this->error('请选择时段');
            }
            $rule_list = RuleModel::where("id","in",$post['rule_ids'])->select()->toArray();
            if(empty($rule_list)){
                $this->error('时间段不存在，请刷新后再试');
            }
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
            $datetime_insert_list = [];
            foreach($date_list as $key=>$date){
                if(DatetimeModel::where(['seller_id'=>$seller_id,'date'=>$date])->count()){
                    $this->error('请勿重复生成'.$date);
                }
                foreach($rule_list as $k=>$v){
                    $weeks_en = weeksToEn($v['weeks']);
                    $date_week = date("l",strtotime($date));
                    if(in_array($date_week,$weeks_en)){
                        $datetime_insert_list[] = [
                            'seller_id' => $seller_id,
                            'date' => $date,
                            'time_start' => $v['time_start'],
                            'time_end' => $v['time_end'],
                            'stock' => $v['stock'],
                            'total_stock' => $v['stock']
                        ];
                    }
                }
            }
            DatetimeModel::insertAll($datetime_insert_list);
            $this->success('批量生成成功!');
        }
    }
}
