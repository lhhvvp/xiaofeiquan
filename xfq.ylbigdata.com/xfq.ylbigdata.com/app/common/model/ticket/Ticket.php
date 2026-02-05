<?php
/**
 * 门票模型
 * @author slomoo <1103398780@qq.com> 2022/07/26
 */

namespace app\common\model\ticket;

// 引入框架内置类
use app\common\model\Base;
use app\common\model\TicketPrice as TicketPriceModel;
use app\common\model\ticket\Rights as RightsModel;
class Ticket extends Base
{
    protected $table = 'tp_ticket';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    public function getCategoryTextAttr($value,$data){
        return Category::where('id',$data['category_id'])->value('title');
    }

    /*
     * 获取当天线上价格
     * */
    public function getMinPriceAttr($value,$data){
        return TicketPriceModel::where('ticket_id',$data['id'])->whereDay("date")->value("online_price");
    }

    /*
     * 获取权益（核销配置）列表
     * */
    public function getRightsListAttr($value,$data){
        $list = [];
        if($data['rights_num'] > 0){
            $list = RightsModel::where("ticket_id",$data['id'])->field("id,title,create_time")->select()->toArray();
        }
        return $list;
    }
}