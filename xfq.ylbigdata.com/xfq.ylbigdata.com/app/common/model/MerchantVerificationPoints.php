<?php
/**
 * 商户-核验-点模型
 * @author slomoo <1103398780@qq.com> 2023/07/10
 */
namespace app\common\model;

use app\common\facade\MakeBuilder;
use think\facade\Request;

class MerchantVerificationPoints extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    public function seller()
    {
        return $this->belongsTo('Seller', 'mid');
    }
}