<?php
namespace app\common\model\appt;

use app\common\model\Base;

class Rule extends Base
{
    protected $table = 'tp_ticket_appt_rule';
    public function getTimeStartEndTextAttr($value,$data){
        return secondConvertClock($data['time_start']) .' ~ '. secondConvertClock($data['time_end']);
    }
    public function getTimeStartTextAttr($value,$data){
        return secondConvertClock($data['time_start']);
    }
    public function getTimeEndTextAttr($value,$data){
        return secondConvertClock($data['time_end']);
    }

    public function getWeeksTextAttr($value,$data){
        $str = "";
        if(!empty($data['weeks'])){
            foreach(explode(",",$data['weeks']) as $item){
                switch ($item){
                    case 1:
                        $str .= ",星期一";
                        break;
                    case 2:
                        $str .= ",星期二";
                        break;
                    case 3:
                        $str .= ",星期三";
                        break;
                    case 4:
                        $str .= ",星期四";
                        break;
                    case 5:
                        $str .= ",星期五";
                        break;
                    case 6:
                        $str .= ",星期六";
                        break;
                    case 7:
                        $str .= ",星期日";
                        break;
                    default:
                        break;
                }
            }
            $str = substr($str, 1);
        }
        return $str;
    }
}