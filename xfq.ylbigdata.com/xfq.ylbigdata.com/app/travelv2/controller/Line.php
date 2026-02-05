<?php
/**
 * 线路产品控制器
 * @author slomoo <1103398780@qq.com> 2022/09/05
 */
namespace app\travel\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\Session;
use think\facade\View;
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
            $where[] = ['flag','=',2];
            $where[] = ['mid','=',Session::get('travel.id')];
            $model  = '\app\common\model\\' . $this->modelName;
            $list = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
            return $list;
        }
        return View::fetch();
    }

    // 添加页面
    public function add()
    {
        return View::fetch();
    }

    // 编辑保存
    public function addPost()
    {
        if (Request::isPost()) {
            //$data = Request::except(['file'], 'post');
            $data = MakeBuilder::changeFormData(Request::except(['file'], 'post'), $this->tableName);
            $result = $this->validate($data,$this->modelName);
            if (true !== $result) {
                $this->error($result);
            }
            $model = '\app\common\model\\' . $this->modelName;
            $data['flag'] = 2;
            // 父级商户ID
            $data['mid']  = Session::get('travel.id');
            $result = $model::addPost($data);
            if ($result['error']) {
                $this->error($result['msg']);
            } else {
                $this->success($result['msg'], 'index');
            }
        }
    }

    // 编辑页面
    public function edit($id)
    {
        $model  = '\app\common\model\\' . $this->modelName;
        $result = $model::where('id',$id)->find();
        // 格式化多图
        $result['photo'] = json_decode($result['photo'],true);
        //print_r($result->toArray());die;
        View::assign(['line' => $result]);

        $line_record = \app\common\model\LineRecord::where('line_id',$id)
            ->order('create_time desc')
            ->with(['admin','authGroup'])
            ->select();
        View::assign(['LineRecord' => $line_record]);
        return View::fetch();
    }

    // 编辑保存
    public function editPost()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            $where['id'] = $data['id'];

            // 每次编辑将审核状态重置
            $data['status'] = 2;
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

    // 删除
    public function del($id)
    {
        if (Request::isPost()) {
            if (strpos($id, ',') !== false) {
                return $this->selectDel($id);
            }
            $model = '\app\common\model\\' .$this->modelName;
            return $model::del($id);
        }
    }

    // 批量删除
    public function selectDel(string $id){
        if (Request::isPost()) {
            $model = '\app\common\model\\' .$this->modelName;
            return $model::selectDel($id);
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
