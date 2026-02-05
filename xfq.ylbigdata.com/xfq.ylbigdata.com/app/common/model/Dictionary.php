<?php
/**
 * 字典数据模型
 * @author slomoo <slomoo@aliyun.com> 2022-07-19
 */
namespace app\common\model;

class Dictionary extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    public function dictionaryType()
    {
        return $this->belongsTo('DictionaryType', 'dict_type');
    }
    

}