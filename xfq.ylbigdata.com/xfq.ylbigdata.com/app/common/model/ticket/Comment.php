<?php

namespace app\common\model\ticket;
use app\common\model\Base;

class Comment extends Base
{
    protected $table = 'tp_ticket_comment';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    public static function getStatusList(){
        return [
            '1'=>'显示',
            '0'=>'隐藏'
        ];
    }


    public function getStatusTextAttr($value,$data){
        $list = self::getStatusList();
        return isset($list[$data['status']]) ? $list[$data['status']] : '-';
    }

    public function seller()
    {
        return $this->belongsTo(\app\common\model\Seller::class, 'seller_id');
    }

    public function users()
    {
        return $this->belongsTo(\app\common\model\Users::class, 'user_id');
    }

    public function order()
    {
        return $this->belongsTo(\app\common\model\ticket\Order::class, 'order_id');
    }
}