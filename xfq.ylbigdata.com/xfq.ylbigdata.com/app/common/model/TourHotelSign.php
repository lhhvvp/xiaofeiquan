<?php
/**
 * 导游生成酒店打卡记录模型
 * @author slomoo <1103398780@qq.com> 2022/08/31
 */
namespace app\common\model;

class TourHotelSign extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    public function users()
    {
        return $this->belongsTo('Users', 'uid')->field('id,sex,email,last_login_time,last_login_ip,mobile,type_id,status,create_time,update_time,name,headimgurl,nickname,salt,uuid,openid');
    }
    public function tour()
    {
        return $this->belongsTo('Tour', 'tid');
    }
    

}