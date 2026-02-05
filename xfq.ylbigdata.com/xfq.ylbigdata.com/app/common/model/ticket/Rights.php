<?php
/**
 * 商家门票核销员模型
 */

namespace app\common\model\ticket;
// 引入框架内置类
use app\common\model\Base;
class Rights extends Base
{
    protected $table = 'tp_ticket_rights';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
}