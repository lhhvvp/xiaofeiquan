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
use app\common\model\ticket\Comment as CommentModel;

// 引入表格和表单构建器
// 引入导出的命名空间

class Comment extends Base
{
    // 票种分类列表
    public function index()
    {
        if (Request::isGet()) {
            $status_list = CommentModel::getStatusList();
            View::assign("status_list",$status_list);
            return View::fetch('ticket/comment/index');
        } else {
            $param = Request::param();
            $where = [];
            $where_users = [];
            $seller_id = session()['seller']['id'];
            $where[] = ['seller_id','=',$seller_id];
            if (isset($param['nickname']) && $param['nickname'] != '') {
                $where_users[] = ['users.nickname', 'like', '%' . $param['nickname'] . '%'];
            }
            if (isset($param['status']) && $param['status'] != '') {
                $where[] = ['comment.status', '=', $param['status']];
            }
            if (isset($param['ip']) && $param['ip'] != '') {
                $where[] = ['comment.ip', '=', $param['ip']];
            }
            if (isset($param['create_time_range']) && !empty($param['create_time_range'])) {
                $date_range = explode("至", $param['create_time_range']);
                $where[] = ['comment.create_time', '>=', strtotime(trim($date_range[0]) . " 00:00:00")];
                $where[] = ['comment.create_time', '<=', strtotime(trim($date_range[1]). " 23:59:59")];
            }
            $orderby = $param['orderByColumn'] ?? 'id' . ' ' . $param['isAsc'] ?? 'desc';
            $list = CommentModel::where($where)
                ->hasWhere("users",$where_users)
                ->with(["users"])
                ->append(['status_text'])
                ->order($orderby)
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $this->pageSize,
                ]);
            $list->visible(['users'=>['nickname','headimgurl']]);
            return ['total' => $list->total(), 'current_page' => $list->currentPage(), 'last_page' => $list->lastPage(), 'data' => $list->items()];
        }
    }
    // 票种分类列表
    public function see()
    {
        if (Request::isGet()) {
            $id = Request::get("id","");
            if(empty($id)){
                $this->error("参数错误！");
            }
            $row = CommentModel::where("id",$id)->with(['users','order'])->append(['status_text'])->find();
            $row->visible(['users'=>['headimgurl','nickname'],'order'=>['trade_no']]);
            View::assign("row",$row->toArray());
            return View::fetch('ticket/comment/see');
        }
    }
}
