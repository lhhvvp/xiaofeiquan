<?php
/**
 * 线路分类控制器
 * @author slomoo <1103398780@qq.com> 2022/09/05
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;

class LineCategory extends Base
{
    // 验证器
    protected $validate = 'LineCategory';

    // 当前主表
    protected $tableName = 'line_category';

    // 当前主模型
    protected $modelName = 'LineCategory';

    // ajax请求 -- 消费券时调用该列表
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
        $where         = MakeBuilder::getListWhere($this->tableName);
        $orderByColumn = Request::param('orderByColumn') ?? $pk;
        $isAsc         = Request::param('isAsc') ?? 'desc';
        return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
    }
}
