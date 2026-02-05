<?php
/**
 * 会员管理控制器
 * @author slomoo <slomoo@aliyun.com> 2022-04-15
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\View;

class Users extends Base
{
    // 验证器
    protected $validate = 'Users';

    // 当前主表
    protected $tableName = 'users';

    // 当前主模型
    protected $modelName = 'Users';

    // 用户分布情况
    public function distribute(){
        
        $param = Request::param();
        $where = [];
        $list  = \app\common\model\Users::field('id,name,nickname,province,city,district,count(1) as total')
            ->where($where)
            ->group('city')
            ->order('total desc')
            ->select()
            ->toArray();
        View::assign('list',$list);
        return View::fetch();
    }
}
