<?php
/**
 * 门票模型
 * @author slomoo <1103398780@qq.com> 2023/06/29
 */
namespace app\common\model;
use app\common\model\ticket\Rights as RightsModel;
use think\model\concern\SoftDelete;

class Ticket extends Base
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

    public function ticketCategory()
    {
        return $this->belongsTo('TicketCategory', 'category_id')->bind(['cate_title'=>'title']);
    }

    /*
     * 获取权益（核销配置）列表
     * */
    public function getRightsListAttr($value,$data){
        $list = [];
        if($data['rights_num'] > 0){
            $list = RightsModel::where("ticket_id",$data['id'])->select()->toArray();
        }
        return $list;
    }

}