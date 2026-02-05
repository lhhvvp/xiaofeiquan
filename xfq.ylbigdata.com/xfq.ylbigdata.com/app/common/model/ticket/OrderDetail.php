<?php
/**
 * 门票模型
 * @author slomoo <1103398780@qq.com> 2022/07/26
 */

namespace app\common\model\ticket;

// 引入框架内置类
use app\common\model\Base;
use app\common\model\ticket\OrderDetailRights as OrderDetailRightsModel;
class OrderDetail extends Base
{
    protected $table = 'tp_ticket_order_detail';
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
    public static function getRefundProgressList(){
        return [
            'init'=>'初始化状态',
            'pending_review'=>'已提交、待审核 ',
            'refuse'=>'拒绝',
            'approved'=>'通过',
            'completed'=>'完成退款'
        ];
    }
    public function getRefundProgressTextAttr($value,$data){
        $list = self::getRefundProgressList();
        return isset($list[$data['refund_progress']]) ? $list[$data['refund_progress']] : '-';
    }

    public function getTouristCertTypeTextAttr($value,$data){
        $list = self::getCertTypeList();
        return isset($list[$data['tourist_cert_type']]) ? $list[$data['tourist_cert_type']] : '-';
    }

    public static function getRefundStatusList(){
        return [
            'not_refunded'=>'未退款',
            'fully_refunded'=>'已退款'
        ];
    }

    public static function getRefundStatusTextAttr($value,$data){
        $list = self::getRefundStatusList();
        return isset($list[$data['refund_status']]) ? $list[$data['refund_status']] : '-';
    }
    /*
     * 门票核销串
     * */
    public function getQrcodeStrAttr($value,$data){
        $str = '';
        if($data['ticket_rights_num'] < 1){
            $str = "detail&".$data['ticket_code']."&".(time()+600);
            $str = sys_encryption($str,$data['id']);
        }
        return $str;
    }
    /*
     * 获取权益数组
     * */
    public function getRightsListAttr($value,$data){
        return OrderDetailRightsModel::where("detail_id",$data['id'])->append(['qrcode_str'])->select()->toArray();
    }

}