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
use app\common\model\appt\Log as LogModel;

class Log extends Base
{
    // 分时预约记录列表
    public function index()
    {
        if (Request::isGet()) {
            return View::fetch('appt/log/index');
        } else {
            $param = Request::param();
            $where = [];
            if (isset($param['fullname']) && $param['fullname'] != '') {
                $where[] = ['fullname', 'like', '%' . $param['fullname'] . '%'];
            }
            if (isset($param['idcard']) && $param['idcard'] != '') {
                $where[] = ['idcard', 'like', '%' . $param['idcard'] . '%'];
            }
            if (isset($param['phone']) && $param['phone'] != '') {
                $where[] = ['phone', 'like', '%' . $param['phone'] . '%'];
            }
            if (isset($param['date']) && $param['date'] != '') {
                $where[] = ['date', '=',$param['date']];
            }
            if (isset($param['status']) && $param['status'] != '') {
                $where[] = ['status', '=', $param['status']];
            }
            if (isset($param['create_time_range']) && !empty($param['create_time_range'])) {
                $date_range = explode("至", $param['create_time_range']);
                $where[]    = ['create_time', '>=', strtotime(trim($date_range[0]) . " 00:00:00")];
                $where[]    = ['create_time', '<=', strtotime(trim($date_range[1]) . " 23:59:59")];
            }
            $order = ($param['orderByColumn'] ?? 'id') . ' ' . ($param['isAsc'] ?? 'desc');
            $list = LogModel::where($where)
                ->with(['users'])
                ->append(['time_start_text','time_end_text','start','end','status_text','tourist_list'])
                ->order($order)
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $this->pageSize,
                ]);
            return ['total' => $list->total(), 'current_page' => $list->currentPage(), 'last_page' => $list->lastPage(), 'data' => $list->items()];
        }
    }
    public function detail()
    {
        if (Request::isGet()) {
            $id = Request::get('id', '');
            if(!empty($id)){
                $vo = LogModel::where('id',$id)->with("users")->append(['time_start_text','time_end_text','start','end','status_text','tourist_list'])->find();
                $vo->visible(['users'=>['nickname','headimgurl']]);
                View::assign('vo',$vo);
            }
            return View::fetch('appt/log/detail');
        }
    }
}
