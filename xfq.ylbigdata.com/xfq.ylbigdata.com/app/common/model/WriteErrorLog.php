<?php
/**
 * 散客-核销-日志模型
 * @author slomoo <1103398780@qq.com> 2023/07/17
 */
namespace app\common\model;
use think\facade\Request;

class WriteErrorLog extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    
    
    public function users()
    {
        return $this->belongsTo('users', 'uid');
    }
    public function seller()
    {
        return $this->belongsTo('Seller', 'mid');
    }
    
    // 核销错误日志记录
    public static function ErrorReportLog($paramData)
    {
        $incData['uid'] = (isset($paramData['userid']) && $paramData['userid']!=0) ? $paramData['userid'] : 0;
        $incData['mid'] = (isset($paramData['mid']) && $paramData['mid']!=0) ? $paramData['mid'] : 0;
        $incData['isd'] = (isset($paramData['coupon_issue_user_id']) && $paramData['coupon_issue_user_id']!=0) ? $paramData['coupon_issue_user_id'] : 0;
        $incData['title'] = (isset($paramData['title']) && $paramData['title']!='') ? $paramData['title'] : '';
        $incData['ip']    = Request::ip();
        $incData['user_agent']   = Request::server('HTTP_USER_AGENT');
        $incData['uw_longitude'] = isset($paramData['longitude']) ? $paramData['longitude'] : 0;
        $incData['uw_latitude']  = isset($paramData['latitude']) ? $paramData['latitude'] : 0;
        $incData['he_longitude'] = isset($paramData['vr_longitude']) ? $paramData['vr_longitude'] : 0;
        $incData['he_latitude']  = isset($paramData['vr_latitude']) ? $paramData['vr_latitude'] : 0;
        self::create($incData);
    }
}