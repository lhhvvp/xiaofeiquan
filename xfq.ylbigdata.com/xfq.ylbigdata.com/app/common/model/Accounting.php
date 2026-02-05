<?php
/**
 * 消费券核算表模型
 * @author slomoo <1103398780@qq.com> 2022/08/05
 */
namespace app\common\model;

class Accounting extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    public function seller()
    {
        return $this->belongsTo('Seller', 'mid')->field('id,create_time,update_time,status,login_time,login_ip,last_login_time,last_login_ip,loginnum,mobile,nickname,image,do_business_time,address,content,longitude,latitude,class_id,cart_number,card_name,card_deposit,mtype,credit_code,area,email_validated,email,business_license,permit_foroperation,social_liability_insurance,no,name');
    }
    public function sellerClass()
    {
        return $this->belongsTo('SellerClass', 'class_id');
    }
    
    public function auditRecord()
    {
        return $this->hasOne('AuditRecord','aid')->where('step',1)->where('status',1)->order('create_time desc');
    }
}