<?php
/**
 * 游客酒店打卡记录模型
 * @author slomoo <1103398780@qq.com> 2022/08/31
 */
namespace app\common\model;
use think\facade\Request;
use app\common\facade\MakeBuilder;

class TourHotelUserRecord extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    public function tourHotelSign()
    {
        return $this->belongsTo('TourHotelSign', 'sign_id');
    }
    public function users()
    {
        return $this->belongsTo('Users', 'uid')->field('id,sex,email,last_login_time,last_login_ip,mobile,type_id,status,create_time,update_time,name,headimgurl,nickname,salt,uuid,openid');
    }
    public function tour()
    {
        return $this->belongsTo('Tour', 'tid');
    }
    
    // 获取列表
    public static function getRewriteList(array $where = [], int $pageSize = 0, array $order = ['sort', 'id' => 'desc'],$whereUid)
    {
        if ($pageSize) {
            $list = self::where($where)
                ->hasWhere('users',$whereUid)
                ->with(['users','tour','tourHotelSign'])
                ->order($order)
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $pageSize,
                ]);
        } else {
            $list = self::where($where)->hasWhere('users',$whereUid)->with(['users','tour','tourHotelSign'])
                ->order($order)
                ->select();
        }
        return MakeBuilder::changeTableData($list, 'CouponIssueUser');
    }
}