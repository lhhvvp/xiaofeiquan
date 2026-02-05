<?php
/**
 * 旅行团管理模型
 * @author slomoo <1103398780@qq.com> 2022/08/14
 */
namespace app\common\model;
use think\facade\Request;
// 引入构建器
use app\common\facade\MakeBuilder;
class Tour extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 获取列表
    public static function getRewriteList(array $where = [], int $pageSize = 0, array $order = ['sort', 'id' => 'desc'])
    {
        if ($pageSize) {
            $list = self::where($where)
                ->with(['seller'])
                ->order($order)
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $pageSize,
                ]);
        } else {
            $list = self::where($where)->with(['seller'])
                ->order($order)
                ->select();
        }
        return MakeBuilder::changeTableData($list, 'Tour');
    }
    
    public function seller()
    {
        return $this->belongsTo('Seller', 'mid')->field('id,create_time,update_time,status,login_time,login_ip,last_login_time,last_login_ip,loginnum,mobile,nickname,image,do_business_time,address,content,longitude,latitude,class_id,cart_number,card_name,card_deposit,mtype,credit_code,area,email_validated,email,business_license,permit_foroperation,social_liability_insurance');
    }
    
    public function tourWriteOff()
    {
        return $this->hasMany('TourWriteOff', 'tid','id')->where('type','=', 1);
    }

    public function tourIssueUser()
    {
        return $this->hasMany('TourIssueUser', 'tid','id')->where('type','=', 2);
    }


    public function tourist()
    {
        return $this->hasMany('Tourist', 'tid','id');
    }

    public  function tourHotelUserRecord(){
        return $this->hasMany('TourHotelUserRecord', 'tid','id');
    }
}