<?php
/**
 * 游客表模型
 * @author slomoo <1103398780@qq.com> 2022/09/08
 */
namespace app\common\model;

class Guest extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    public function seller()
    {
        return $this->belongsTo('Seller', 'mid');
    }
    public function sellerChildNode()
    {
        return $this->belongsTo('SellerChildNode', 'mid_sub');
    }
    public function users()
    {
        return $this->belongsTo('Users', 'uid')->field('id,sex,email,last_login_time,last_login_ip,mobile,type_id,status,create_time,update_time,name,headimgurl,nickname,salt,uuid,openid');
    }
    

}