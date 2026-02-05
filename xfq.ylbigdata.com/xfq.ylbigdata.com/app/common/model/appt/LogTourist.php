<?php
namespace app\common\model\appt;

use app\common\model\Base;

class LogTourist extends Base
{
    protected $table = 'tp_ticket_appt_log_tourist';


    public function getStatusTextAttr($value,$data){
        $status_list = [
            '0'=>'待核销',
            '1'=>'已核销',
            '2'=>'已取消'
        ];
        return isset($status_list[$data['status']]) ? $status_list[$data['status']] : '-';
    }

    public static function getCertTypeList(){
        return [
            '1'=>'身份证',
            '2'=>'护照',
            '3'=>'台湾通行证',
            '4'=>'港澳通行证',
            '5'=>'回乡证'
        ];
    }
    public function getTouristCertTypeTextAttr($value,$data){
        $list = self::getCertTypeList();
        return isset($list[$data['tourist_cert_type']]) ? $list[$data['tourist_cert_type']] : '-';
    }
}