<?php
/**
 * 门票分类控制器
 * @author slomoo <1103398780@qq.com> 2022/07/26
 */

namespace app\admin\controller;

// 引入框架内置类
use app\common\model\ticket\Comment as CommentModel;
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\View;
use think\facade\Db;
use app\common\model\Seller as SellerModel;

class TicketComment extends Base
{
    // 验证器
    protected $validate = 'TicketComment';

    // 当前主表
    protected $tableName = 'ticket_comment';

    // 当前主模型
    protected $modelName = 'TicketComment';

    public function index()
    {
        if (Request::isGet()) {
            $status_list = CommentModel::getStatusList();
            View::assign("status_list", $status_list);
            return View::fetch('ticket_comment/index');
        } else {
            $param        = Request::param();
            $where        = [];
            $where_users  = [];
            $where_seller = [];
            if (isset($param['users_nickname']) && $param['users_nickname'] != '') {
                $where_users[] = ['users.nickname', 'like', '%' . $param['users_nickname'] . '%'];
            }
            if (isset($param['seller_nickname']) && $param['seller_nickname'] != '') {
                $where_seller[] = ['seller.nickname', 'like', '%' . $param['seller_nickname'] . '%'];
            }
            if (isset($param['status']) && $param['status'] != '') {
                $where[] = ['comment.status', '=', $param['status']];
            }
            if (isset($param['ip']) && $param['ip'] != '') {
                $where[] = ['comment.ip', '=', $param['ip']];
            }
            if (isset($param['create_time_range']) && !empty($param['create_time_range'])) {
                $date_range = explode("至", $param['create_time_range']);
                $where[]    = ['comment.create_time', '>=', strtotime(trim($date_range[0]) . " 00:00:00")];
                $where[]    = ['comment.create_time', '<=', strtotime(trim($date_range[1]) . " 23:59:59")];
            }
            $orderby = $param['orderByColumn'] ?? 'id' . ' ' . $param['isAsc'] ?? 'desc';
            $list    = CommentModel::where($where)
                ->hasWhere("users", $where_users)
                ->hasWhere("seller", $where_seller)
                ->with(["users", "seller"])
                ->append(['status_text'])
                ->order($orderby)
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $this->pageSize,
                ]);
            $list->visible(['users' => ['nickname', 'headimgurl'], 'seller' => ['nickname', 'image']]);
            return ['total' => $list->total(), 'current_page' => $list->currentPage(), 'last_page' => $list->lastPage(), 'data' => $list->items()];
        }
    }

    // 查看评论
    public function see()
    {
        if (Request::isGet()) {
            $id = Request::get("id", "");
            if (empty($id)) {
                $this->error("参数错误！");
            }
            $row = CommentModel::where("id", $id)->with(['seller', 'users', 'order'])->append(['status_text'])->find();
            $row->visible(['seller' => ['nickname', 'image'], 'users' => ['headimgurl', 'nickname'], 'order' => ['trade_no']]);
            View::assign("row", $row->toArray());
            return View::fetch('ticket_comment/see');
        }
    }

    /*
     * 审核/退审
     * */
    public function audit()
    {
        if (Request::isPost()) {
            $id     = Request::post("id", "");
            $status = Request::post("status", "");
            if ($id == '' || $status == '') {
                $this->error("参数错误！");
            }
            Db::startTrans();
            try {
                $row         = CommentModel::where("id", $id)->find();
                $row->status = $status;
                $row->save();
                //更新商户评论数和评分
                $seller_info = SellerModel::where("id", $row['seller_id'])->find();

                if ($status == 1) {
                    $comment_num = $seller_info->comment_num + 1;
                } else {
                    $comment_num = ($seller_info->comment_num - 1) >= 0 ? ($seller_info->comment_num - 1) : 0;
                }
                if ($comment_num > 0) {
                    $comment_rate = ($seller_info->comment_rate * $seller_info->comment_num + $row['rate']) / $comment_num;
                } else {
                    $comment_rate = 5;
                }
                $comment_rate              = $comment_rate > 5 ? 5 : $comment_rate;
                $seller_info->comment_num  = $comment_num;
                $seller_info->comment_rate = number_format($comment_rate, 2);
                $seller_info->save();
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->error("操作失败！");
            }
            $this->success("操作成功！！");
        }
    }
}
