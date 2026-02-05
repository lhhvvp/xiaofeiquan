<?php
/**
 * 会员管理模型
 * @author slomoo <slomoo@aliyun.com> 2022-04-15
 */
namespace app\common\model;

class Users extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    public function usersType()
    {
        return $this->belongsTo('UsersType', 'type_id');
    }
    
    // 是否绑定商户
    public function ismv()
    {
         return $this->hasOne('MerchantVerifier', 'uid','id')->field('*');
    }
}