<?php
/**
 * 门票分类模型
 * @author slomoo <1103398780@qq.com> 2022/07/26
 */
namespace app\common\model\appt;

use app\common\model\Base;

class Datetime extends Base
{
    protected $table = 'tp_ticket_appt_datetime';
    public function getTimeStartTextAttr($value,$data){
        return secondConvertClock($data['time_start']);
    }
    public function getTimeEndTextAttr($value,$data){
        return secondConvertClock($data['time_end']);
    }
    public function getStartAttr($value,$data){
        return date('Y-m-d\TH:i:s',(strtotime($data['date']) + $data['time_start']));
    }
    public function getEndAttr($value,$data){
        return date('Y-m-d\TH:i:s',(strtotime($data['date']) + $data['time_end']));
    }
}