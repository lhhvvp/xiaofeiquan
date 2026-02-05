<?php
/**
 * 图片模块表模型
 * @author slomoo <1103398780@qq.com> 2022/07/19
 */
namespace app\common\model;

class Picture extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    public function cate()
    {
        return $this->belongsTo('Cate', 'cate_id');
    }
    

}