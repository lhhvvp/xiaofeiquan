<?php
/**
 * 游客控制器
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

class Guest extends Base
{
    // 验证器
    protected $validate = 'Guest';

    // 当前主表
    protected $tableName = 'guest';

    // 当前主模型
    protected $modelName = 'Guest';

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
            $where[] = ['mid','=',Session::get('travel.id')];
            $model  = '\app\common\model\\' . $this->modelName;
            $list = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
            return $list;
        }
        return View::fetch();
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
        //print_r($result->toArray());die;
        // 权限组输出
        View::assign(['guest' => $result]);
        return View::fetch();
    }
}
