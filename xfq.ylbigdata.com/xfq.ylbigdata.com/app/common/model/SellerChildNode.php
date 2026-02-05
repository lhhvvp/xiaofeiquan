<?php
/**
 * 子商户列表模型
 * @author slomoo <1103398780@qq.com> 2022/09/06
 */
namespace app\common\model;

class SellerChildNode extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    public function seller()
    {
        return $this->belongsTo('Seller', 'mid')->field('id,create_time,update_time,status,login_time,login_ip,last_login_time,last_login_ip,loginnum,mobile,nickname,image,do_business_time,address,content,longitude,latitude,class_id,cart_number,card_name,card_deposit,mtype,credit_code,area,email_validated,email,business_license,permit_foroperation,social_liability_insurance');
    }
    

}