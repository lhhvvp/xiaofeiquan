<?php
/**
 * 配置信息模型
 * @author slomoo <slomoo@aliyun.com> 2022-07-19
 */
namespace app\common\model;

class Config extends Base
{
    //不需要使用自动时间戳
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    //protected $createTime = 'create_time';
    //protected $updateTime = 'update_time';

}