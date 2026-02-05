<?php
/**
 * 投诉反馈控制器
 * @author slomoo <1103398780@qq.com> 2022/09/05
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\View;
class Feedback extends Base
{
    // 验证器
    protected $validate = 'Feedback';

    // 当前主表
    protected $tableName = 'feedback';

    // 当前主模型
    protected $modelName = 'Feedback';


    // 列表
    public function index(){
        // 获取主键
        $pk = MakeBuilder::getPrimarykey($this->tableName);
        // 获取列表数据
        $columns = MakeBuilder::getListColumns($this->tableName);
        // 获取搜索数据
        $search = MakeBuilder::getListSearch($this->tableName);
        // 获取当前模块信息
        $model = '\app\common\model\\' . $this->modelName;
        $module = \app\common\model\Module::where('table_name', $this->tableName)->find();
        // 搜索
        if (Request::param('getList') == 1) {
            $where = MakeBuilder::getListWhere($this->tableName);
            $orderByColumn = Request::param('orderByColumn') ?? $pk;
            $isAsc = Request::param('isAsc') ?? 'desc';
            return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
        }
        // 构建页面
        return TableBuilder::getInstance()
            ->setUniqueId($pk)                              // 设置主键
            ->addColumns($columns)                         // 添加列表字段数据
            ->setSearch($search)                            // 添加头部搜索
            ->addColumn('right_button', '操作', 'btn')      // 启用右侧操作列
            ->addRightButtons($module->right_button)        // 设置右侧操作列
            ->addTopButtons($module->top_button)            // 设置顶部按钮组
            ->addRightButton('info', [                      // 添加额外按钮
                'title' => '查看',
                'icon'  => 'fa fa-search',
                'class' => 'btn btn-success btn-xs',
                'href'  => url('see', ['parentId' => '__id__'])
            ])
            ->fetch();
    }

    // 查看详情
    public function see($parentId)
    {
        $orderByColumn = $param['orderByColumn'] ?? 'id';
        $isAsc         = $param['isAsc'] ?? 'desc';

        $map = [];

        $map[] = ['id','=',$parentId];
        $model  = '\app\common\model\\' . $this->modelName;
        $detail = $model::where($map)->find();
        View::assign(['row' => $detail]);
        return View::fetch();
    }
}
