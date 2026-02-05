<?php
/**
 * 基础-支付-配置模型
 * @author slomoo <1103398780@qq.com> 2022/10/24
 */
namespace app\common\model;

class BasePayment extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    //支付配置缓存
    public function pay_cache()
    {
        // 1=小程序支付
        $data = $this->where('id', 1)->find();
        if ($data) {
            $data = $data->toArray();
        } else {
            return;
        }
        $Cache = array();
        $Cache = $data;
        cache("pay_cache", $Cache);
        return $data;
    }
    

}