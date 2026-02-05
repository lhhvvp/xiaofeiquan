<?php
/**
 * 栏目规则权限控制器
 * @author slomoo <1103398780@qq.com> 2022/07/20
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;

class CateRule extends Base
{
    // 验证器
    protected $validate = 'CateRule';

    // 当前主表
    protected $tableName = 'cate_rule';

    // 当前主模型
    protected $modelName = 'CateRule';

    // 模块入口文件
    public function index(){
        
    }
}
