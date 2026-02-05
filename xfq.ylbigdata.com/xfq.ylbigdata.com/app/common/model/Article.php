<?php
/**
 * 文章模块模型
 * @author slomoo <slomoo@aliyun.com> 2022-07-19
 */
namespace app\common\model;

class Article extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    public function cate()
    {
        return $this->belongsTo('Cate', 'cate_id');
    }
    public function usersType()
    {
        return $this->belongsTo('UsersType', 'view_auth');
    }
    

}