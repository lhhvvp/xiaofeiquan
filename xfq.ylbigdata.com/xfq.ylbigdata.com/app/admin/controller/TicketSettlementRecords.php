<?php
/**
 * 门票-结算-记录控制器
 * @author slomoo <1103398780@qq.com> 2023/08/16
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;

class TicketSettlementRecords extends Base
{
    // 验证器
    protected $validate = 'TicketSettlementRecords';

    // 当前主表
    protected $tableName = 'ticket_settlement_records';

    // 当前主模型
    protected $modelName = 'TicketSettlementRecords';
}
