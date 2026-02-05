<?php
namespace app\common\model\appt;

use app\common\model\Base;
use app\common\model\appt\LogTourist as LogTouristModel;
class Log extends Base
{
    protected $table = 'tp_ticket_appt_log';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    public function getTimeStartTextAttr($value,$data){
        return secondConvertClock($data['time_start']);
    }
    public function getTimeEndTextAttr($value,$data){
        return secondConvertClock($data['time_end']);
    }
    public function getStartAttr($value,$data){
        return date('Y-m-d H:i',(strtotime($data['date']) + $data['time_start']));
    }
    public function getEndAttr($value,$data){
        return date('Y-m-d H:i',(strtotime($data['date']) + $data['time_end']));
    }
    public function getStatusTextAttr($value,$data){
        $status_list = [
            '0'=>'待核销',
            '1'=>'已核销',
            '2'=>'已取消'
        ];
        return isset($status_list[$data['status']]) ? $status_list[$data['status']] : '-';
    }
    /*
     * 核销码的字符串
     * */
    public function getQrcodeStrAttr($value,$data){
        $str = 'log&'.$data['code'].'&'.(time()+600);
        return sys_encryption($str,$data['id']);
    }
    public function getTouristListAttr($value,$data){
        return LogTouristModel::where("log_id",$data['id'])->append(['tourist_cert_type_text'])->select()->toArray();
    }
    public function seller()
    {
        return $this->belongsTo(\app\common\model\Seller::class,"seller_id");
    }

    public function users()
    {
        return $this->belongsTo(\app\common\model\Users::class,"user_id")->field("id,nickname,headimgurl");
    }
}