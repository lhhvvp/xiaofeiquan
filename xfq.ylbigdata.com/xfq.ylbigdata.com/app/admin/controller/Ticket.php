<?php
/**
 * 门票控制器
 * @author slomoo <1103398780@qq.com> 2022/07/26
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;

use app\common\model\ticket\Ticket as TicketModel;
use app\common\model\ticket\Category as CategoryModel;
use app\common\model\ticket\Rights as RightsModel;
use app\common\model\MerchantVerifier as MerchantVerifierModel;
use think\facade\View;
use think\facade\Db;

class Ticket extends Base
{
    // 验证器
    protected $validate = 'Ticket';

    // 当前主表
    protected $tableName = 'ticket';

    // 当前主模型
    protected $modelName = 'Ticket';

    // 列表
    public function index()
    {
        // 获取当前模块信息
        $model  = '\app\common\model\\' . $this->modelName;
        $module = \app\common\model\Module::where('table_name', $this->tableName)->find();
        // 搜索
        if (Request::param('getList') == 1) {
            $where         = MakeBuilder::getListWhere($this->tableName);
            $orderByColumn = Request::param('orderByColumn') ?? 'id';
            $isAsc         = Request::param('isAsc') ?? 'desc';
            return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
        }

        // 只获取商户分类为景区的商家
        $cateGory = \app\common\model\TicketCategory::field('id, title')->where('status',1)
            ->order('id asc')
            ->select()
            ->toArray();
        $view   = ['cate_gory' => $cateGory];
        View::assign($view);
        return View::fetch('ticket/index');
    }

    // 添加消费券时调用该列表
    public function list()
    {
        // 获取主键
        $pk = MakeBuilder::getPrimarykey($this->tableName);
        // 获取列表数据
        $columns = MakeBuilder::getListColumns($this->tableName);
        // 获取搜索数据
        $search = MakeBuilder::getListSearch($this->tableName);
        // 获取当前模块信息
        $model  = '\app\common\model\\' . $this->modelName;
        $module = \app\common\model\Module::where('table_name', $this->tableName)->find();
        // 搜索
        if (Request::param('getList') == 1) {
            $where         = MakeBuilder::getListWhere($this->tableName);
            $orderByColumn = Request::param('orderByColumn') ?? $pk;
            $isAsc         = Request::param('isAsc') ?? 'desc';
            return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
        }
        // 检测单页模式
        $isSingle = MakeBuilder::checkSingle($this->modelName);
        if ($isSingle) {
            return $this->jump($isSingle);
        }
        // 获取新增地址
        // 构建页面
        return TableBuilder::getInstance()
            ->setUniqueId($pk)                              // 设置主键
            ->addColumns($columns)                          // 添加列表字段数据
            ->setSearch($search)                            // 添加头部搜索
            ->addColumn('right_button', '操作', 'btn')      // 启用右侧操作列
            /*->addTopButton('info', [                      // 添加额外按钮
                'title' => '添加',
                'icon'  => 'fa fa-plus',
                'class' => 'btn btn-success btn-xs',
                'href'  => url('add', ['pid' => '__id__'])
            ])*/
            ->setPagination('false')                  // 关闭分页显示
            ->setParentIdField('pid')                 // 设置列表树父id
            ->fetch();
    }

    // 添加
    public function add()
    {
        return View::fetch('ticket/add');
    }

    // 修改
    public function edit($id)
    {
        $model = '\app\common\model\\' . $this->modelName;
        $info  = $model::edit($id)->toArray();
        View::assign(['info' => $info]);
        return View::fetch('ticket/edit');
    }
    /*
     * 添加/编辑票种
     * */
    public function post()
    {
        if (Request::isGet()) {
            $id = Request::get('id', '');
            $category_list = CategoryModel::where("status",1)->select()->toArray();
            $n_cate_list = [];
            foreach($category_list as $item){
                if(isset($n_cate_list[$item['seller_id']])){
                    $n_cate_list[$item['seller_id']][] = $item;
                }else{
                    $n_cate_list[$item['seller_id']] = [];
                    $n_cate_list[$item['seller_id']][] = $item;
                }

            }
            $verifier_list = MerchantVerifierModel::where(["status"=>1,"type"=>"ticket"])->select()->toArray();
            $n_verifier_list = [];
            foreach($verifier_list as $item){
                if(isset($n_verifier_list[$item['mid']])){
                    $n_verifier_list[$item['mid']][] = $item;
                }else{
                    $n_verifier_list[$item['mid']] = [];
                    $n_verifier_list[$item['mid']][] = $item;
                }
            }
            View::assign('category_list',$n_cate_list);
            View::assign('verifier_list',$n_verifier_list);
            if(!empty($id)){
                $vo = TicketModel::where('id',$id)->find();
                $rights_list = RightsModel::where("ticket_id",$vo['id'])->select();
                View::assign(['vo'=>$vo,'rights_list'=>$rights_list]);
            }
            return View::fetch('ticket/post');
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
