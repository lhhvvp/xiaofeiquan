<?php
/**
 * 字段分组模型
 * @author slomoo <slomoo@aliyun.com> 2022-07-18
 */
namespace app\common\model;

class FieldGroup extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    public function module()
    {
        return $this->belongsTo('Module', 'module_id');
    }
    

}