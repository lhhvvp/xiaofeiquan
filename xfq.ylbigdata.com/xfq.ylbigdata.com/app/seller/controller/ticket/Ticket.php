<?php
/**
 * 商户管理控制器
 * @author slomoo <1103398780@qq.com> 2022/07/22
 */
namespace app\seller\controller\ticket;

// 引入框架内置类
use app\seller\controller\Base;
use think\facade\Request;
use think\facade\View;
use app\common\model\ticket\Ticket as TicketModel;
use app\common\model\ticket\Category as CategoryModel;
use app\common\model\ticket\Rights as RightsModel;
use think\facade\Db;
use think\exception;
// 引入表格和表单构建器
// 引入导出的命名空间

class Ticket extends Base
{
    // 票种列表
    public function index()
    {
        if (Request::isGet()) {
            return View::fetch('ticket/ticket/index');
        } else {
            $param = Request::param();
            $where = [];
            if (isset($param['title']) && $param['title'] != '') {
                $where[] = ['title', 'like', '%' . $param['title'] . '%'];
            }
            if (isset($param['status']) && $param['status'] != '') {
                $where[] = ['status', '=', $param['status']];
            }
            $order = $param['orderByColumn'] ?? 'id' . ' ' . $param['isAsc'] ?? 'desc';
            $list = TicketModel::where($where)
                ->append(['category_text'])
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
            View::assign('category_list',CategoryModel::where("status",1)->select()->toArray());
            if(!empty($id)){
                $vo = TicketModel::where('id',$id)->find();
                $rights_list = RightsModel::where("ticket_id",$vo['id'])->select();
                View::assign(['vo'=>$vo,'rights_list'=>$rights_list]);
            }
            return View::fetch('ticket/ticket/post');
        } else {
            $post = Request::post('row/a');
            $rights_list = (isset($post['rights_list']) && !empty($post['rights_list'])) ? $post['rights_list'] : [];
            unset($post['rights_list']);
            if(count($rights_list) > 0 && count($rights_list) < 2){
                $this->error("核销配置至少配置两个，否则无需配置");
            }
            Db::startTrans();
            try {
                if(empty($post['id'])){
                    $post['code'] = uniqidNumber(20,'T');
                    $info = TicketModel::create($post);
                }else{
                    $info = TicketModel::where('id',$post['id'])->find();
                    $info->save($post);
                }
                //更新/添加核销配置
                if(!empty($rights_list)){
                    $v_ids = [];
                    foreach($rights_list as $item){
                        if(empty($item['verifier_ids'])){
                            throw new Exception("`".$item['title']."`未设置核销员！请修改后再试");
                        }
                        $vData = [
                            'title'=>$item['title'],
                            'verifier_ids'=>$item['verifier_ids'],
                            'ticket_id'=>$info['id'],
                            'seller_id'=>$info['seller_id']
                        ];
                        if(isset($item['id']) && !empty($item['id'])){
                            $v_info = RightsModel::where("id",$item['id'])->find();
                            $v_info->save($vData);
                            array_push($v_ids, $v_info->id);
                        }else{
                            $v_info = RightsModel::create($vData);
                            array_push($v_ids, $v_info->id);
                        }
                    }
                    //开始删除没有的。
                    if(count($v_ids)>0){
                        RightsModel::where([['ticket_id',"=",$info['id']],['id',"not in",$v_ids]])->delete();
                    }
                }
                $info->rights_num = count($rights_list);
                $info->save();
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->error($e->getMessage());
            }

            $this->success('操作成功!');
        }
    }
}
