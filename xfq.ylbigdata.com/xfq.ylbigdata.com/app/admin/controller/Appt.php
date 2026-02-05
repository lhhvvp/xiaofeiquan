<?php
/**
 * 门票控制器
 * @author slomoo <1103398780@qq.com> 2022/07/26
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;
use app\common\facade\MakeBuilder;
use think\facade\View;
use app\common\model\appt\Log as ApptLogModel;
class Appt extends Base
{
    public function sellerList()
    {
        // 获取主键
        $pk = MakeBuilder::getPrimarykey('seller');
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();
            $orderByColumn = $param['orderByColumn'] ?? $pk;
            $isAsc         = $param['isAsc'] ?? 'desc';
            $where = [];
            if (@$param['nickname']!='') {
                $where[] = ['nickname','like',"%".$param['nickname']."%"];
            }
            if (@$param['class_id']!='') {
                $where[] = ['class_id','=',$param['class_id']];
            }
            if (@$param['area']!='') {
                $where[] = ['area','=',$param['area']];
            }
            if (@$param['status']!='') {
                $where[] = ['status','=',$param['status']];
            }else{
                $where[] = ['status','<>',4];
            }
            $model  = '\app\common\model\\Seller';
            $list = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
            for ($i=0; $i < count($list['data']); $i++) {
                @$list['data'][$i]['area'] = $this->app->config->get('lang.area')[$list['data'][$i]['area']];
            }
            return $list;
        }
        // 商户分类
        $SellerClass = \app\common\model\SellerClass::field('id, class_name')
            ->order('sort asc')
            ->select()
            ->toArray();
        $view   = [
            'class_list' => $SellerClass
        ];
        View::assign($view);
        return View::fetch('appt/seller');
    }
    // 分时预约记录列表
    public function logList()
    {
        if (Request::isGet()) {
            return View::fetch('appt/log');
        } else {
            $param = Request::param();
            $where = [];
            $where_seller = [];
            if (isset($param['seller_id']) && $param['seller_id'] != '') {
                $where[] = ['seller_id', '=',  $param['seller_id']];
            }
            if (isset($param['id']) && $param['id'] != '') {
                $where[] = ['seller_id', '=',  $param['seller_id']];
            }
            if (isset($param['seller_nickname']) && $param['seller_nickname'] != '') {
                $where_seller[] = ['nickname', 'like', '%' . $param['seller_nickname'] . '%'];
            }
            if (isset($param['idcard']) && $param['idcard'] != '') {
                $where[] = ['idcard', 'like', '%' . $param['idcard'] . '%'];
            }
            if (isset($param['phone']) && $param['phone'] != '') {
                $where[] = ['phone', 'like', '%' . $param['phone'] . '%'];
            }
            if (isset($param['status']) && $param['status'] != '') {
                $where[] = ['status', '=', $param['status']];
            }

            $order = ($param['orderByColumn'] ?? 'id') . ' ' . ($param['isAsc'] ?? 'desc');
            $list  = ApptLogModel::where($where)
                ->with(['seller','users'])
                ->append(['time_start_text', 'time_end_text', 'start', 'end', 'status_text'])
                ->order($order)
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $this->pageSize,
                ]);
            return ['total' => $list->total(), 'current_page' => $list->currentPage(), 'last_page' => $list->lastPage(), 'data' => $list->items()];
        }
    }
    public function logDetail()
    {
        if (Request::isGet()) {
            $id = Request::get('id', '');
            if(!empty($id)){
                $vo = ApptLogModel::where('id',$id)->with(['seller','users'])->append(['status_text','tourist_list'])->find();
                $vo->visible(['seller'=>['nickname','image'],'users'=>['nickaname','headimgurl']]);
                View::assign('vo',$vo);
            }
            return View::fetch('appt/logDetail');
        }
    }
}