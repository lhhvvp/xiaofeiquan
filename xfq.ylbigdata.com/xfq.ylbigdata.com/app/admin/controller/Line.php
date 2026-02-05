<?php
/**
 * 线路产品控制器
 * @author slomoo <1103398780@qq.com> 2022/09/05
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\Session;
use think\facade\View;
use think\facade\Db;

class Line extends Base
{
    // 验证器
    protected $validate = 'Line';

    // 当前主表
    protected $tableName = 'line';

    // 当前主模型
    protected $modelName = 'Line';

    // 线路列表
    public function index()
    {
        // 获取主键
        $pk = MakeBuilder::getPrimarykey($this->tableName);
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();
            $orderByColumn = $param['orderByColumn'] ?? $pk;
            $isAsc         = $param['isAsc'] ?? 'desc';
            $where = [];
            if (@$param['title']!='') {
                $where[] = ['title','=',$param['title']];
            }
            $where[] = ['flag','=',1];
            // $where[] = ['mid','=',Session::get('admin.id')];
            $model  = '\app\common\model\\' . $this->modelName;
            $list = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
            return $list;
        }
        return View::fetch();
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

    // 旅投审核线路列表
    public function check()
    {
        // 获取主键
        $pk = MakeBuilder::getPrimarykey($this->tableName);
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();
            $orderByColumn = $param['orderByColumn'] ?? $pk;
            $isAsc         = $param['isAsc'] ?? 'desc';
            $where = [];
            if (@$param['title']!='') {
                $where[] = ['title','=',$param['title']];
            }
            $model  = '\app\common\model\\' . $this->modelName;
            $list = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
            return $list;
        }
        return View::fetch();
    }

    // 审核页面
    public function checkPost($id)
    {   
        // 审核操作
        if(Request::isPost()){
            $data = Request::except(['file'], 'post');
            // 线路ID
            $inData['line_id'] = $data['id'];
            $inData['remarks']  = isset($data['remark']) ? $data['remark'] : '';
            $inData['step']     = $data['step'];
            $inData['status']   = $data['status'];
            $inData['image']    = '';// 审核凭据
            $inData['group_id'] = Session::get('admin.group_id');
            $inData['admin_id'] = Session::get('admin.id');

            $result = $this->validate($inData,'LineRecord');
            if (true !== $result) {
                $this->error($result);
            }

            // 每一级审核不通过时 需要回退前一级状态为待审核
            $upData = [];
            $upData['update_time'] = time();
            // 运营审核
            if($inData['step']==2){
                // 无论是否通过，文旅均是待审核状态
                $upData['tourism_status'] = 2;
                // 如果不通过 则退回 需要修改资料
                $upData['status'] = $inData['status']==3 ? 4 : $inData['status'];
            }
            // 文旅审核
            if($inData['step']==3){
                $upData['status'] = $inData['status']==3 ? 2 : $inData['status'];
                $upData['tourism_status'] = $inData['status'];
            }
            // 事务操作
            Db::startTrans();
            try {
                // 修改审核状态
                $rs = \app\common\model\Line::where('id',$data['id'])->data($upData)->update();
                // 记录审核操作
                $inData['create_time'] = time();
                $inGid = \app\common\model\LineRecord::strict(false)->insertGetId($inData);
                if($rs > 0 && $inGid > 0){
                    // 提交事务
                    Db::commit();
                    $this->success('操作成功','index');
                }else{
                    // 回滚事务
                    Db::rollback();
                    $this->error('操作失败');
                }
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                // $this->error($e->getMessage());
            }
            $this->success('操作成功','index');
        }
        $model  = '\app\common\model\\' . $this->modelName;
        $result = $model::where('id',$id)->with(['seller'])->find();
        if($result){
            $result = $result->toArray();
        }
        // 格式化多图
        $result['photo'] = json_decode($result['photo'],true);
        //print_r($result->toArray());die;
        // 权限组输出
        View::assign(['line' => $result,'step'=>Session::get('admin.group_id')]);

        // 查询审核记录
        $reList  = \app\common\model\LineRecord::where('line_id',$id)
        ->with(['admin','authGroup'])
        ->order('create_time desc')
        ->select();
        /*->toArray();
        print_r($reList);die;*/
        View::assign(['reList' => $reList]);
        return View::fetch();
    }

    public function addPost()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');

            // 每次编辑将审核状态重置
            $data['status'] = 1;
            $data['tourism_status'] = 2;
            
            $result = $this->validate($data,$this->modelName);
            if (true !== $result) {
                $this->error($result);
            }
            $model = '\app\common\model\\' . $this->modelName;
            $result = $model::addPost($data);
            if ($result['error']) {
                $this->error($result['msg']);
            } else {
                $this->success($result['msg'], 'index');
            }
        }
    }

    // 编辑保存
    public function editPost()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            $where['id'] = $data['id'];

            // 每次编辑将审核状态重置
            $data['status'] = 1;
            $data['tourism_status'] = 2;
            
            $result = $this->validate($data,$this->modelName);
            if (true !== $result) {
                $this->error($result);
            }
            $model = '\app\common\model\\' . $this->modelName;
            $result = $model::editPost($data);
            if ($result['error']) {
                $this->error($result['msg']);
            } else {
                $this->success($result['msg'], 'index');
            }
        }
    }

    // 查看详情
    public function see($id)
    {
        if(Request::isPost()){
            $this->success('操作成功', 'index');
        }
        $model  = '\app\common\model\\' . $this->modelName;
        $result = $model::where('id',$id)->with(['seller'])->find();
        if($result){
            $result = $result->toArray();
        }
        // 格式化多图
        $result['photo'] = json_decode($result['photo'],true);
        //print_r($result->toArray());die;
        // 权限组输出
        View::assign(['line' => $result,'step'=>Session::get('admin.group_id')]);

        // 查询审核记录
        $reList  = \app\common\model\LineRecord::where('line_id',$id)
        ->with(['admin','authGroup'])
        ->order('create_time desc')
        ->select();
        /*->toArray();
        print_r($reList);die;*/
        View::assign(['reList' => $reList]);
        return View::fetch();
    }
}
