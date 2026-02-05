<?php
/**
 * 门票分类模型
 * @author slomoo <1103398780@qq.com> 2022/07/26
 */
namespace app\common\model\ticket;

use app\common\model\Base;

class Category extends Base
{
    protected $table = 'tp_ticket_category';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

}