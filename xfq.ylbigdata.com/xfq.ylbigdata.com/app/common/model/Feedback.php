<?php
/**
 * 投诉反馈模型
 * @author slomoo <1103398780@qq.com> 2022/09/05
 */
namespace app\common\model;

class Feedback extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    public function users()
    {
        return $this->belongsTo('Users', 'uid')->field('id,sex,email,last_login_time,last_login_ip,mobile,type_id,status,create_time,update_time,name,headimgurl,nickname,salt,uuid,openid');
    }
    

}