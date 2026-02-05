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
use app\common\model\ticket\Ticket as TicketModel;
use app\common\model\ticket\Category as CategoryModel;

// 引入表格和表单构建器
// 引入导出的命名空间

class Category extends Base
{
    // 票种分类列表
    public function index()
    {
        if (Request::isGet()) {
            return View::fetch('ticket/category/index');
        } else {
            $param = Request::param();
            $seller_id = session()['seller']['id'];
            $where = [];
            $where[] = ['title', 'like', '%' . $param['title'] . '%'];
            if (isset($param['title']) && $param['title'] != '') {
                $where[] = ['title', 'like', '%' . $param['title'] . '%'];
            }
            if (isset($param['status']) && $param['status'] != '') {
                $where[] = ['status', '=', $param['status']];
            }
            $order = $param['orderByColumn'] ?? 'id' . ' ' . $param['isAsc'] ?? 'desc';
            $list = CategoryModel::where($where)
                ->where(function($query) use($seller_id){
                    $query->whereOr([['seller_id',"=",$seller_id],['seller_id',"=",0]]);
                })
                ->order($order)
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $this->pageSize,
                ]);
            return ['total' => $list->total(), 'current_page' => $list->currentPage(), 'last_page' => $list->lastPage(), 'data' => $list->items()];
        }
    }

    // 添加票种
    public function post()
    {
        if (Request::isGet()) {
            $id = Request::get('id', '');
            if(!empty($id)){
                $vo = CategoryModel::where('id',$id)->find();
                View::assign('vo',$vo);
            }
            return View::fetch('ticket/category/post');
        } else {
            $data = Request::post();
            if(empty($data['id'])){
                CategoryModel::create($data);
            }else{
                CategoryModel::where('id',$data['id'])->save($data);
            }
            $this->success('操作成功!');
        }
    }
    // 删除分类
    public function del(string $id)
    {
        $vo = CategoryModel::where('id',$id)->find();
        //检查是否有票种使用了该分类
        if(TicketModel::where('category_id',$vo['id'])->count()){
            $this->error('该分类下有票种不允许删除!');
        }
        $vo->delete();
        $this->success('删除成功!');
    }
}
