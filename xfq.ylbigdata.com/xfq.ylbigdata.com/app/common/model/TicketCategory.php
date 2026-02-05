<?php
/**
 * 门票分类模型
 * @author slomoo <1103398780@qq.com> 2023/06/28
 */
namespace app\common\model;
use think\model\concern\SoftDelete;

class TicketCategory extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    use SoftDelete;
    protected $deleteTime = 'delete_time';

    
    public function seller()
    {
        return $this->belongsTo('Seller', 'seller_id')->bind(['nickname'=>'nickname']);
    }
    

}