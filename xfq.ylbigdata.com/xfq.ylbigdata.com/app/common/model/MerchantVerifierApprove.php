<?php
/**
 * 商户-核验-点模型
 * @author slomoo <1103398780@qq.com> 2023/07/10
 */
namespace app\common\model;

class MerchantVerifierApprove extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';


    public function admin()
    {
        return $this->belongsTo('Admin', 'admin_id');
    }


}