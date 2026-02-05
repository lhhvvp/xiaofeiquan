<?php
/**
 * 审核类型模型
 * @author slomoo <1103398780@qq.com> 2022/09/08
 */
namespace app\common\model;

class FlowType extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    // 获取父ID选项信息
    public static function getPidOptions($order = ['sort', 'id' => 'desc'])
    {
        $list   = self::order($order)
            ->select()
            ->toArray();
        $list   = tree($list);
        $result = [];
        foreach ($list as $v) {
            $result[$v['id']] = $v['ltitle'];
        }
        return $result;
    }

}