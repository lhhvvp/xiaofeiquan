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
use app\common\model\appt\Rule as RuleModel;

class Rule extends Base
{
    // 票种分类列表
    public function index()
    {
        if (Request::isGet()) {
            $list = RuleModel::where(['seller_id'=>session()['seller']['id']])->append(['time_start_end_text','weeks_text'])->select()->toArray();
            View::assign('list',$list);
            return View::fetch('appt/rule/index');
        }
    }
    // 添加票种
    public function post()
    {
        if (Request::isGet()) {
            $id = Request::get('id', '');
            if(!empty($id)){
                $vo = RuleModel::where('id',$id)->append(['time_start_text','time_end_text'])->find();
                View::assign('vo',$vo);
            }
            return View::fetch('appt/rule/post');
        } else {
            $data = Request::post("row");
            if(empty($data['time_start']) || empty($data['time_end'])){
                $this->error('开始时间和结束时间必填！');
            }
            $data['time_start'] = clockConvertSecond($data['time_start']);
            $data['time_end'] = clockConvertSecond($data['time_end']);
            if( $data['time_start'] > $data['time_end']){
                $this->error('开始时间必须小于结束时间!');
            }
            $where = [];
            $where[] = ['seller_id','=',$data['seller_id']];
            if(!empty($data['id'])){
                $where[] = ['id','<>',$data['id']];
            }
            $has_list  = RuleModel::where($where)->select()->toArray();
            $isContained = false;
            foreach ($has_list as $item) {
                $min = $item['time_start'];
                $max = $item['time_end'];
                if (!empty(array_intersect($data['weeks'], explode(",", $item['weeks']))) && !($data['time_end'] <= $min || $max <= $data['time_start'])) {
                    $isContained = true;
                }
            }
            if($isContained){
                $this->error('某个时间点已被添加，请检查后再试!');
            }
            $data['weeks'] =  implode(',', $data['weeks'] );
            if(empty($data['id'])){
                RuleModel::create($data);
            }else{
                RuleModel::where('id',$data['id'])->save($data);
            }
            $this->success('操作成功!');
        }
    }
    // 删除分类
    public function del(string $id)
    {
        $vo = RuleModel::where('id',$id)->find();
        $vo->delete();
        $this->success('删除成功!');
    }
}
