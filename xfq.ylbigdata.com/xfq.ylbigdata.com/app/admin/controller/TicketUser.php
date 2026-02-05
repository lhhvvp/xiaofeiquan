<?php
/**
 * 票务-商户-售票员控制器
 * @author slomoo <1103398780@qq.com> 2023/08/07
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;

class TicketUser extends Base
{
    // 验证器
    protected $validate = 'TicketUser';

    // 当前主表
    protected $tableName = 'ticket_user';

    // 当前主模型
    protected $modelName = 'TicketUser';
}
