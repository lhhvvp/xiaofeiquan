<?php
namespace app\common\model;

class UsersTourist extends Base
{
    protected $table = 'tp_users_tourist';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    public static function getCertTypeList(){
        return [
            '1'=>'身份证',
            '2'=>'护照',
            '3'=>'台湾通行证',
            '4'=>'港澳通行证',
            '5'=>'回乡证'
        ];
    }
    public function getCertTypeTextAttr($value,$data){
        $list = self::getCertTypeList();
        return isset($list[$data['cert_type']]) ? $list[$data['cert_type']] : '-';
    }
}