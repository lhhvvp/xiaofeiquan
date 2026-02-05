<?php
/**
 * @desc   定时任务API
 * @author slomoo
 * @email slomoo@aliyun.com
 */
declare (strict_types = 1);
namespace app\api\controller;

use app\api\BaseController;
use app\api\middleware\Auth;
use app\api\service\JwtAuth;
use think\facade\Db;
use think\facade\Request;
use app\common\model\CouponIssue;

class System extends BaseController
{
    /**
     * 控制器中间件 [定时任务]
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except'    => ['rollback_remain_count','set_tour_invalid','cleanDb','tableTohtml','queryArea','rollback_remain_count_extend','remake','remake_lxs','XdataSummary','tableTohtml1','alert_push','notification','invalid_tour','remake_ticket','rollback_set_data'] ]
    ];

    /**
     * @api {post} /system/rollback_remain_count 回滚消费券剩余数量  待测试 
     * @apiDescription  回滚消费券剩余数量
     */
    public function rollback_remain_count()
    {
         
        // 1.获取消费券需要回滚的条数以及父级ID
        // is_rollback = 1 feild: id、pid 并且他的父级必须是限时的
        $coupons = CouponIssue::field('id,pid')
            ->where('is_rollback', 1)
            ->where('is_del', 0)
            ->select()
            ->toArray();

        foreach ($coupons as $coupon) {
            $info = CouponIssue::field('id,pid,limit_time,start_time,end_time,remain_count')
                ->where('id', $coupon['pid'])
                ->where('limit_time', 1)
                ->find()
                ->toArray();

            if (time() > $info['end_time']) {
                $updateData = [
                    'rollback_num' => Db::raw('rollback_num + '.$info['remain_count']),
                    'total_count' => Db::raw('total_count + '.$info['remain_count']),
                    'remain_count' => Db::raw('remain_count + '.$info['remain_count']),
                    'is_rollback' => 2,
                ];
                Db::name('coupon_issue')->where('id', '=', $coupon['id'])->update($updateData);
            }
        }
    }

    // 设置领取记录为待回滚状态
    public function rollback_set_data(){
        // 查询父级需要回滚的消费券
        $coupons = CouponIssue::whereIn('is_rollback', [1,2])
        ->where('is_del', 0)
        ->column('pid');
        // 根据父级ID查询该消费券领取记录且过期的，修改其状态为待回滚状态
        Db::name('coupon_issue_user')
        ->whereIn('issue_coupon_id',$coupons)
        ->where('is_rollback',1)
        ->where('status',2)
        ->update([
                'is_rollback'=>2,'update_time'=>time()
        ]);
        // 查询父级需要回滚的消费券
        /*$coupons = CouponIssue::field('id,pid')
            ->whereIn('is_rollback', [1,2])
            ->where('is_del', 0)
            ->select()
            ->toArray();
        // 根据父级ID查询该消费券领取记录且过期的，修改其状态为待回滚状态
        foreach ($coupons as $coupon) {
            Db::name('coupon_issue_user')
            ->where('issue_coupon_id',$coupon['pid'])
            ->where('is_rollback',1)
            ->where('status',2)
            ->update([
                    'is_rollback'=>2,'update_time'=>time()
            ]);
        }*/
    }
    
    // 回滚散客领券记录待回滚数据
    public function rollback_remain_count_extend(){
        // 2.回滚已经过期的券
        $issue_user = \app\common\model\CouponIssueUser::field('id,issue_coupon_id')
            ->where('is_rollback', 2)
            ->where('status', 2)->limit(1000)->order('id desc')->select();

        foreach ($issue_user as $key => $value) {
            $res = \app\common\model\CouponIssueUser::where('id',$value['id'])->where('is_rollback',2)->find();
            if($res){
                $rs = Db::name('coupon_issue')->where('pid',$value['issue_coupon_id'])
                ->whereIn('is_rollback',[1,2])
                ->update([
                    'total_count' =>    Db::raw('total_count+1'),
                    'remain_count' =>   Db::raw('remain_count+1'),
                    'rollback_num_extend' =>   Db::raw('rollback_num_extend+1')
                ]);

                Db::name('coupon_issue_user')->where('id',$value['id'])
                ->update([
                    'rollback_numbers' =>    Db::raw('rollback_numbers+1'),
                    'is_rollback'=>3,'update_time'=>time()
                ]);
            }
        }
    }

    /**
     * @api {post} /system/set_tour_invalid
     * @apiDescription  团状态等于确认团且不能是无效团的，团期超过当前时间 没有游客  没有导游 没有领券的 将团设置无效
     */
    public function set_tour_invalid()
    {
        // 1.团期超过当前时间
        $ids = \app\common\model\Tour::field('id,term')->where('status',4)->where('status','<>',6)->select();
        $idArr = [];
        foreach ($ids as $key => $value) {
            $termArr = explode(' - ',$value['term']);
            $term_start = strtotime($termArr[0]);
            $term_end   = strtotime($termArr[1]);
            // 当前时间戳大于团期的结束时间戳
            if(time() > $term_end)
                array_push($idArr,$value['id']);
        }
        // 2.如果数组不为空，则查询当前数组下，没有游客的
        $touristArr = [];
        if($idArr){
            $tourist = \app\common\model\Tourist::where('tid','in',$idArr)->column('tid');
            // 寻找两个数组的差集 = 旅行团没有游客的团
            $touristArr = array_filter($idArr, function ($v) use ($tourist) {
                return !in_array($v, $tourist);
            });
        }

        // 3.如果数组不为空，则查询当前数组下，没有导游的
        $guideArr = [];
        if($touristArr){
            $guide = \app\common\model\Guide::where('tid','in',$idArr)->column('tid');
            // 寻找两个数组的差集 = 旅行团没有导游的团
            $guideArr = array_filter($touristArr, function ($v) use ($guide) {
                return !in_array($v, $guide);
            });
        }

        // 4.如果数组不为空，则查询当前数组下，没有领券的
        $tourIssueUserArr = [];
        if($guideArr){
            $tourIssueUser = \app\common\model\TourIssueUser::where('tid','in',$idArr)->column('tid');
            // 寻找两个数组的差集 = 旅行团游客没有领券的团
            $tourIssueUserArr = array_filter($touristArr, function ($v) use ($tourIssueUser) {
                return !in_array($v, $tourIssueUser);
            });
        }

        // 5.如果数组不为空，则将所有的团设置为无效
        if($tourIssueUserArr){
            \app\common\model\Tour::where('id','in',$tourIssueUserArr)
            ->data(['status'=>6,'update_time'=>time()])
            ->update();
        }
    }

    public function cleanDb()
    {
        exit('success');
        try {
            $arr = [
                'tp_accounting'
                ,'tp_admin_log'
                ,'tp_audit_record'
                ,'tp_collection'
                ,'tp_coupon_issue'
                ,'tp_coupon_issue_user'
                //,'tp_examine_record'
                ,'tp_feedback'
                ,'tp_guide'
                ,'tp_guest'
                ,'tp_line'
                ,'tp_line_record'
                //,'tp_merchant_verifier'
                //,'tp_seller'
                //,'tp_seller_child_node'
                ,'tp_ticket'
                ,'tp_ticket_class'
                ,'tp_tour'
                ,'tp_tour_accounting'
                ,'tp_tour_audit_record'
                ,'tp_tour_coupon_group'
                ,'tp_tour_guest'
                ,'tp_tour_hotel_sign'
                ,'tp_tour_hotel_user_record'
                ,'tp_tour_issue_user'
                ,'tp_tour_write_off'
                ,'tp_tourist'
                //,'tp_users'
                ,'tp_verify_accounting_record'
                ,'tp_verify_collect'
                ,'tp_write_off'
                ,'tp_flow_type'
                ,'tp_flow'
                ,'tp_tour_hotel'
                ,'tp_base_paydata'
                ,'tp_base_refunds'
                ,'tp_coupon_order'
                ,'tp_coupon_order_item'
            ];
            $len = count($arr);
            for ($i=0; $i < $len; $i++) { 
                Db::execute('truncate table '.$arr[$i]);
            }
        } catch (Exception $e) {
            $this->apiError($e->getMessage());
        }
        $this->apiSuccess('本次截断'.$len.'张表');
    }

    public function tableTohtml()
    {
        exit('success');
        try {
            $arr = [
                'tp_accounting'=>'消费券核算表'
                ,'tp_admin'=>'管理员列表'
                ,'tp_admin_log'=>'管理员操作日志表'
                ,'tp_auth_group'=>'角色组管理表'
                ,'tp_auth_group_access'=>'用户组明细表'
                ,'tp_auth_rule'=>'规则节点表'
                ,'tp_config'=>'系统配置表'
                ,'tp_audit_record'=>'普通商户核算审核表'
                ,'tp_collection'=>'用户收藏商家表'
                ,'tp_coupon_class'=>'消费券分类表'
                ,'tp_coupon_issue'=>'消费券表'
                ,'tp_coupon_issue_user'=>'用户领取消费券记录表'
                ,'tp_examine_record'=>'商户注册审核表'
                ,'tp_dictionary'=>'字典'
                ,'tp_dictionary_type'=>'字典类型'
                ,'tp_field'=>'模型字段表'
                ,'tp_field_group'=>'模型字段类型表'
                ,'tp_module'=>'模型表'
                ,'tp_feedback'=>'投诉反馈表'
                ,'tp_ticket'=>'门票'
                ,'tp_ticket_class'=>'门票分类'
                ,'tp_guide'=>'导游表'
                ,'tp_line'=>'线路表'
                ,'tp_line_record'=>'线路审核表'
                ,'tp_merchant_verifier'=>'商户核验人员表'
                ,'tp_seller'=>'商户表'
                ,'tp_seller_child_node'=>'商户子机构表'
                ,'tp_tour'=>'旅行团表'
                ,'tp_tour_accounting'=>'旅行团结算表'
                ,'tp_tour_audit_record'=>'旅行团核算审核记录表'
                ,'tp_tour_coupon_group'=>'旅行团团体消费券'
                ,'tp_tour_guest'=>'旅行团游客表'
                ,'tp_tour_hotel_sign'=>'导游生成酒店打卡记录表'
                ,'tp_tour_hotel_user_record'=>'游客酒店打卡记录'
                ,'tp_tour_issue_user'=>'旅行团用户消费券领取记录'
                ,'tp_tour_write_off'=>'团体券核销记录表'
                ,'tp_tourist'=>'旅行团游客表'
                ,'tp_users'=>'用户表'
                ,'tp_write_off'=>'散客核销记录表'
                ,'tp_guest'=>'游客表'
                ,'tp_ticket_category'=>'门票分类'
                ,'tp_ticket'=>'商户门票'
                ,'tp_ticket_appt_datetime'=>'预约时间表'
                ,'tp_ticket_appt_log'=>'预约记录表'
                ,'tp_ticket_appt_log_tourist'=>'游客预约记录表'
                ,'tp_ticket_appt_rule'=>'预约时间段'
                ,'tp_ticket_comment'=>'门票评论表'
                ,'tp_ticket_order'=>'票务-订单-主表'
                ,'tp_ticket_order_detail'=>'票务-订单-从表'
                ,'tp_ticket_order_detail_rights'=>'订单游客权益表'
                ,'tp_ticket_order_ota'=>'门票-ota订单'
                ,'tp_ticket_order_ota_item'=>'门票-ota订单-项目'
                ,'tp_ticket_pay'=>'基础-支付-交易数据'
                ,'tp_ticket_pay_notify'=>'票务-支付回调-验证记录表'
                ,'tp_ticket_price'=>'日期价格表'
                ,'tp_ticket_refunds'=>'票务-退款-交易数据'
                ,'tp_ticket_refunds_notify'=>'票务-退款回调-验证记录表'
                ,'tp_ticket_rights'=>'门票权益（门票多次核销+核销人绑定）'
                ,'tp_ticket_settlement'=>'门票-订单-结算'
                ,'tp_ticket_settlement_audit'=>'门票-结算-审核记录'
                ,'tp_ticket_settlement_records'=>'门票-结算-记录'
                ,'tp_ticket_user'=>'票务-商户-售票员'
                ,'tp_ticket_user_tourist'=>'同行游客表'
                ,'tp_ticket_write_off'=>'门票核销表'

            ];

            $sql = "SELECT
                     COLUMN_NAME 列名,
                     DATA_TYPE 字段类型,
                     COLUMN_COMMENT 备注,
                     COLUMN_DEFAULT 默认值
                    FROM
                     information_schema. COLUMNS
                    WHERE
                     TABLE_SCHEMA = 'xfq_com' 
                    AND
                     TABLE_NAME = ";

            $strArr = [];


            $html = '<style type="text/css">
                        <!--
                        .jsxx {font-size: 18px;}
                        .xxxx {font-size: 12px;}
                        .title{font-size: 14px;}
                        .jsxxs {font-size: 16px;}
                        -->
                        </style>';
            $strArr = [];
            foreach ($arr as $key => $value) {
                $sql = "SELECT
                     COLUMN_NAME 列名,
                     DATA_TYPE 字段类型,
                     COLUMN_COMMENT 备注,
                     COLUMN_DEFAULT 默认值
                    FROM
                     information_schema. COLUMNS
                    WHERE
                     TABLE_SCHEMA = 'xfq_com' 
                    AND
                     TABLE_NAME = "."'".$key."'";
                $strArr  = Db::query($sql);
                $html .='<table width="600" border="1" align="center" cellpadding="0" cellspacing="0" bordercolor="#999999" class="xxxx">
                          <tr>
                            <td height="25" colspan="5">
                            表名：'.$value.'
                            </td>
                          </tr>
                          <tr>
                            <td width="40" height="25" align="center">序号</td>
                            <td width="100" align="center">列名</td>
                            <td width="120" align="center">字段类型</td>
                            <td  align="center">备注</td>
                            <td width="70" align="center">默认值</td>
                          </tr>';

                        foreach ($strArr as $k => $v) {
                                $html .= '<tr>
                                    <td height="25" align="center">'.($k+1).'</td>
                                    <td align="center">'.$v['列名'].'</td>
                                    <td align="center">'.$v['字段类型'].'</td>
                                    <td align="center">'.$v['备注'].'</td>
                                    <td align="center">'.$v['默认值'].'</td>
                                  </tr>';
                        }
                        $html .= '</table>
                        <p>&nbsp;</p>';
                        //break;
                
            }
           echo $html;die;
        } catch (Exception $e) {
            $this->apiError($e->getMessage());
        }
        $this->apiSuccess('本次截断'.$len.'张表');
    }

    public function tableTohtml1()
    {
        exit('success');
        try {
            $arr = [
                'tcyz_admin'=>'后台管理员表'
                ,'tcyz_admin_access'=>'组规则表'
                ,'tcyz_admin_group'=>'用户组表'
                ,'tcyz_admin_log'=>'用户登录记录表'
                ,'tcyz_admin_message'=>'系统-消息-内容'
                ,'tcyz_admin_notice'=>'管理员消息表'
                ,'tcyz_admin_rules'=>'菜单权限表'
                ,'tcyz_area'=>'系统-地域-省市区'
                ,'tcyz_attachment'=>'附件表'
                ,'tcyz_company'=>'单位信息表'
                ,'tcyz_config'=>'系统配置表'
                ,'tcyz_crontab'=>'系统-定时器-任务表'
                ,'tcyz_crontab_log'=>'系统-定时器-任务执行日志表'
                ,'tcyz_department'=>'部门管理表'
                ,'tcyz_dictionary'=>'字典数据表'
                ,'tcyz_hall'=>'展馆-基础-信息'
                ,'tcyz_jobs'=>'岗位管理'
                ,'tcyz_media'=>'文物-多媒体-指标'
                ,'tcyz_media_cate'=>'文物-多媒体-分类'
                ,'tcyz_patrol'=>'系统-巡查-对象'
                ,'tcyz_patrol_log'=>'系统-巡查-记录'
                ,'tcyz_patrol_manage'=>'文物-巡查-点'
                ,'tcyz_patrol_type'=>'文物-巡查-类型'
                ,'tcyz_patrol_type_problem'=>'文物-巡查-条目'
                ,'tcyz_patrol_user'=>'文物-巡查-人员'
                ,'tcyz_process_flow'=>'巡查记录-问题处理-处理过程'
                ,'tcyz_process_problem'=>'巡查记录-问题处理-问题列表'
                ,'tcyz_publish'=>'文物-出版-记录'
                ,'tcyz_relic'=>'文物-基础-信息'
                ,'tcyz_relic_accident'=>'文物-事故-登记'
                ,'tcyz_relic_appraisal'=>'文物-管理-鉴定'
                ,'tcyz_relic_clue'=>'文物-线索-收集'
                ,'tcyz_relic_repair'=>'文物-修复-记录'
                ,'tcyz_relic_shift'=>'文物-管理-移动记录'
                ,'tcyz_relic_sources'=>'文物-管理-来源记录'
                ,'tcyz_relic_storehouse'=>'文物-库房-信息'
                ,'tcyz_system_log'=>'系统日志表'
                ,'tcyz_unit'=>'文物-文保-单位管理'
            ];

            $sql = "SELECT
                     COLUMN_NAME 列名,
                     DATA_TYPE 字段类型,
                     COLUMN_COMMENT 备注,
                     COLUMN_DEFAULT 默认值
                    FROM
                     information_schema. COLUMNS
                    WHERE
                     TABLE_SCHEMA = 'gcww_com' 
                    AND
                     TABLE_NAME = ";

            $strArr = [];


            $html = '<style type="text/css">
                        <!--
                        .jsxx {font-size: 18px;}
                        .xxxx {font-size: 12px;}
                        .title{font-size: 14px;}
                        .jsxxs {font-size: 16px;}
                        -->
                        </style>';
            $strArr = [];
            $i = 1;
            foreach ($arr as $key => $value) {
                $i ++;
                $sql = "SELECT
                     COLUMN_NAME 列名,
                     DATA_TYPE 字段类型,
                     COLUMN_COMMENT 备注,
                     COLUMN_DEFAULT 默认值
                    FROM
                     information_schema. COLUMNS
                    WHERE
                     TABLE_SCHEMA = 'gcww_com' 
                    AND
                     TABLE_NAME = "."'".$key."'";
                $strArr  = Db::query($sql);
                $html .='<table width="600" border="1" align="center" cellpadding="0" cellspacing="0" bordercolor="#999999" class="xxxx">
                          <tr>
                            <td height="25" colspan="5">
                             '.$i.' - 表名：'.$value.'
                            </td>
                          </tr>
                          <tr>
                            <td width="40" height="25" align="center">序号</td>
                            <td width="100" align="center">列名</td>
                            <td width="120" align="center">字段类型</td>
                            <td  align="center">备注</td>
                            <td width="70" align="center">默认值</td>
                          </tr>';

                        foreach ($strArr as $k => $v) {
                                $html .= '<tr>
                                    <td height="25" align="center">'.($k+1).'</td>
                                    <td align="center">'.$v['列名'].'</td>
                                    <td align="center">'.$v['字段类型'].'</td>
                                    <td align="center">'.$v['备注'].'</td>
                                    <td align="center">'.$v['默认值'].'</td>
                                  </tr>';
                        }
                        $html .= '</table>
                        <p>&nbsp;</p>';
                        //break;
                
            }
           echo $html;die;
        } catch (Exception $e) {
            $this->apiError($e->getMessage());
        }
        $this->apiSuccess('本次截断'.$len.'张表');
    }

    // 拉去第三方库
    public function queryArea()
    {
        $rs = Db::name('area_code')->where('city','')->where('district','like','%县')->select();
        print_r(count($rs));die;
        $host = "https://jisuidcard.market.alicloudapi.com";
        $path = "/idcard/query";
        $method = "GET";
        $appcode = "f640ff87596e4ccaaabdf5be712910a2";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        //根据API的要求，定义相对应的Content-Type
        array_push($headers, "Content-Type".":"."application/json; charset=UTF-8");
        foreach ($rs as $key => $value) {
            $querys = "idcard=".$value['areaCode'];
            $bodys = "null";
            $url = $host . $path . "?" . $querys;

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            if (1 == strpos("$".$host, "https://"))
            {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
            $res = curl_exec($curl);
            $arr = json_decode($res,true);
            // 返回结果不包含县的就补市  并修改数据   否则跳过
            if($arr['status']==0){
                $city = $arr['result']['city'];
                $aaa = preg_split('/(?<!^)(?!$)/u', $city );
                // 不包含县的补市 并修改
                if (!in_array('县',$aaa)) {
                    $new_city = $city.'市';
                    Db::name('area_code')->where('areaCode', $value['areaCode'])->data(['city'=>$new_city])->update();
                }
            }

        }
        
    }

    // 2023-03-20 重新生成pdf文件
    public function remake(){
        ini_set('memory_limit', '1280M');
        ini_set('max_execution_time', '300');
        ini_set("pcre.backtrack_limit", "10000000");
        $id     = Request::param('id/d',0);
        $data = \app\common\model\Accounting::where(['id'=>$id])->find();
        if(!$data){
            $this->apiError('当前记录不存在');
        }
        if($data->tags == 1){
            // 查询团领取记录
            $tourInfo = \app\common\model\TourWriteOff::where('id','in',explode(',',$data['write_off_ids']))->with(['tourIssueUser'])->select();
            // 结算金额
            $coupon_price_total  = 0;
            // 结算数量
            $listData       = $tourInfo->toArray();
            $coupon_number  = count($listData);
            foreach ($listData as $key => $value) {
                $coupon_price_total += $value['coupon_price'];
                // 查询散客
                $listData[$key]['users'] = \app\common\model\Users::where('id',$value['tourIssueUser']['uid'])->find()->toArray();
            }
            // 生成PDF文件
            $pageData['title'] = '团体消费券业务';
            $pageData['addtitle'] = date('Y年m月d日 H:i:s',time());
            $pageData['no']       = $data['no'];
            $pageData['sum_coupon_price'] = $coupon_price_total;
            $pageData['writeoff_total']   = $coupon_number;
            $page_1 = '<style type="text/css">
            .jsxx {font-size: 18px;}
            .xxxx {font-size: 12px;}
            </style>
            <p>&nbsp;</p>
            <p>&nbsp;</p>
            <p>&nbsp;</p>
            <table width="325" height="202" border="0" align="center" cellpadding="0" cellspacing="0">
              <tr>
                <td height="150" align="center" style="font-size: 30px; font-weight:bold;">
                <p>商户结算申请对账单</p>
                <p>&nbsp;</p></td>
              </tr>
            </table>
            <table width="560" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxx">
              <tr>
                <td width="130" height="35" align="right">商户名称：</td>
                <td>'.$data['nickname'].'</td>
              </tr>
              <tr>
                <td height="35" align="right" >结算业务名称：</td>
                <td>'.$pageData['title'].'</td>
              </tr>
              <tr>
                <td height="35" align="right">申请结算时间：</td>
                <td>'.$pageData['addtitle'].'</td>
              </tr>
              <tr>
                <td height="35" align="right">结算单号：</td>
                <td>'.$pageData['no'].'</td>
              </tr>
              <tr>
                <td height="35" align="right">结算金额：</td>
                <td>'.$pageData['sum_coupon_price'].'元</td>
              </tr>
              <tr>
                <td height="35" align="right">结算核销数量：</td>
                <td>'.$pageData['writeoff_total'].'</td>
              </tr>
            </table>
            <table width="325" height="115" border="0" align="center" cellpadding="0" cellspacing="0">
              <tr>
                <td height="115" align="center" style="font-size: 18px; font-weight:bold;"><p>&nbsp;</p>
                <p>收款账户</p></td>
              </tr>
            </table>
            <table width="560" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxx">
              <tr>
                <td width="130" height="35" align="right">账户名称：</td>
                <td>'.$data['card_name'].'</td>
              </tr>
              <tr>
                <td height="35" align="right" >收款账户：</td>
                <td>'.$data['cart_number'].'</td>
              </tr>
              <tr>
                <td height="35" align="right">开 户 行：</td>
                <td>'.$data['card_deposit'].'</td>
              </tr>
            </table>
            <p>&nbsp;</p>
            <p>&nbsp;</p>
            <p>&nbsp;</p>
            <table width="308" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxx">
              <tr>
                <td width="308" height="30" align="right">公司（盖章）：</td>
              </tr>
              <tr>
                <td width="308" height="30" align="right">法人签字 ：</td>
              </tr>
              <tr>
                <td height="30" align="right">日期 ：</td>
              </tr>
            </table>
            <p>&nbsp;</p>
            <p>&nbsp;</p>
            <p>附件</p>';

            $page_2 = '';
            $page_2 .= '<p>结算单号: '.$pageData['no'].'</p>';
            $page_2 .= '
            <table width="800" border="1" align="center" cellpadding="0" cellspacing="0" bordercolor="#999999" class="xxxx">
              <tr>
                <td width="40" height="25" align="center">序号</td>
                <td align="center">消费券名称</td>
                <td width="80" align="center">领取人</td>
                <td width="130" align="center">身份证号</td>
                <td width="70" align="center">手机号</td>
                <td width="70" align="center">领取时间</td>
                <td width="70" align="center">消费券面额</td>
                <td width="70" align="center">核销地点</td>
                <td width="70" align="center">核销时间</td>
              </tr>';
              foreach ($listData as $k => $v) {
                  $page_2 .='<tr>
                    <td height="25" align="center">'.($k+1).'</td>
                    <td align="center">'.$v['coupon_title'].'</td>
                    <td align="center">'.$v['users']['name'].'</td>
                    <td align="center">'.$v['users']['idcard'].'</td>
                    <td align="center">'.$v['users']['mobile'].'</td>
                    <td align="center">'.$v['tourIssueUser']['create_time'].'</td>
                    <td align="center">'.$v['coupon_price'].'</td>
                    <td align="center">已上报</td>
                    <td align="center">'.$v['create_time'].'</td>
                  </tr>';
              }
            $page_2 .='</table>';

            $html = $page_1.$page_2;
            // 创建PDF文件
            $data_detail = createPdf($html,'普通商户团体券结算申请对账单_'.$data['no'].'.pdf');
            if($data_detail['code']!=1){
                $this->apiError($data_detail['msg']);
            }
            $this->apiSuccess($data_detail['msg'],$data);
        }

        // 根据散客领取记录
        $tourInfo = \app\common\model\WriteOff::where('id','in',explode(',',$data['write_off_ids']))->where('mid',session('seller')['id'])->with(['couponIssueUser','couponIssue'])->select();

        // 结算金额
        $coupon_price_total  = 0;
        // 结算数量
        $listData       = $tourInfo->toArray();
        $coupon_number  = count($listData);
        foreach ($listData as $key => $value) {
            $coupon_price_total += $value['coupon_price'];
            // 查询散客
            $listData[$key]['users'] = \app\common\model\Users::where('id',$value['couponIssueUser']['uid'])->find()->toArray();
        }
        // 生成PDF文件
        $pageData['title'] = '散客消费券业务';
        $pageData['addtitle'] = date('Y年m月d日 H:i:s',time());
        $pageData['no']       = $data['no'];
        $pageData['sum_coupon_price'] = $coupon_price_total;
        $pageData['writeoff_total']   = $coupon_number;
        $page_1 = '<style type="text/css">
        .jsxx {font-size: 18px;}
        .xxxx {font-size: 12px;}
        </style>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <table width="325" height="202" border="0" align="center" cellpadding="0" cellspacing="0">
          <tr>
            <td height="150" align="center" style="font-size: 30px; font-weight:bold;">
            <p>商户结算申请对账单</p>
            <p>&nbsp;</p></td>
          </tr>
        </table>
        <table width="560" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxx">
          <tr>
            <td width="130" height="35" align="right">商户名称：</td>
            <td>'.$data['nickname'].'</td>
          </tr>
          <tr>
            <td height="35" align="right" >结算业务名称：</td>
            <td>'.$pageData['title'].'</td>
          </tr>
          <tr>
            <td height="35" align="right">申请结算时间：</td>
            <td>'.$pageData['addtitle'].'</td>
          </tr>
          <tr>
            <td height="35" align="right">结算单号：</td>
            <td>'.$pageData['no'].'</td>
          </tr>
          <tr>
            <td height="35" align="right">结算金额：</td>
            <td>'.$pageData['sum_coupon_price'].'元</td>
          </tr>
          <tr>
            <td height="35" align="right">结算核销数量：</td>
            <td>'.$pageData['writeoff_total'].'</td>
          </tr>
        </table>
        <table width="325" height="115" border="0" align="center" cellpadding="0" cellspacing="0">
          <tr>
            <td height="115" align="center" style="font-size: 18px; font-weight:bold;"><p>&nbsp;</p>
            <p>收款账户</p></td>
          </tr>
        </table>
        <table width="560" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxx">
          <tr>
            <td width="130" height="35" align="right">账户名称：</td>
            <td>'.$data['card_name'].'</td>
          </tr>
          <tr>
            <td height="35" align="right" >收款账户：</td>
            <td>'.$data['cart_number'].'</td>
          </tr>
          <tr>
            <td height="35" align="right">开 户 行：</td>
            <td>'.$data['card_deposit'].'</td>
          </tr>
        </table>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <table width="308" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxx">
          <tr>
            <td width="308" height="30" align="right">公司（盖章）：</td>
          </tr>
          <tr>
            <td width="308" height="30" align="right">法人签字 ：</td>
          </tr>
          <tr>
            <td height="30" align="right">日期 ：</td>
          </tr>
        </table>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>附件</p>';

        $page_2 = '';
        $page_2 .= '<p>结算单号: '.$pageData['no'].'</p>';
        $page_2 .= '
        <table width="800" border="1" align="center" cellpadding="0" cellspacing="0" bordercolor="#999999" class="xxxx">
          <tr>
            <td width="40" height="25" align="center">序号</td>
            <td align="center">消费券名称</td>
            <td width="80" align="center">领取人</td>
            <td width="130" align="center">身份证号</td>
            <td width="70" align="center">手机号</td>
            <td width="70" align="center">领取时间</td>
            <td width="70" align="center">消费券面额</td>
            <td width="70" align="center">核销地点</td>
            <td width="70" align="center">核销时间</td>
          </tr>';
          foreach ($listData as $k => $v) {
              $page_2 .='<tr>
                <td height="25" align="center">'.($k+1).'</td>
                <td align="center">'.$v['couponIssue']['coupon_title'].'</td>
                <td align="center">'.$v['users']['name'].'</td>
                <td align="center">'.$v['users']['idcard'].'</td>
                <td align="center">'.$v['users']['mobile'].'</td>
                <td align="center">'.$v['couponIssueUser']['create_time'].'</td>
                <td align="center">'.$v['coupon_price'].'</td>
                <td align="center">已上报</td>
                <td align="center">'.$v['create_time'].'</td>
              </tr>';
          }
        $page_2 .='</table>';

        $html = $page_1.$page_2;
        // 创建PDF文件
        $data_detail = createPdf($html,'普通商户散客结算申请对账单_'.$data['no'].'.pdf');
        if($data_detail['code']!=1){
            $this->apiError($data_detail['msg']);
        }
        $this->apiSuccess($data_detail['msg'],$data);
    }

    // 2023-03-20 重新生成pdf文件 旅行社端
    public function remake_lxs(){
        ini_set('memory_limit', '1280M');
        ini_set('max_execution_time', '300');
        ini_set("pcre.backtrack_limit", "10000000");
        $id     = Request::param('id/d',0);
        $data = \app\common\model\TourAccounting::where(['id'=>$id])->find();
        if(!$data){
            $this->apiError('当前记录不存在');
        }
        // 根据团ID查询信息
        $tourInfo = \app\common\model\Tour::where('id','in',explode(',',$data['write_off_ids']))->with(['tourist','tour_hotel_user_record'])->select();
        // 统计游客数量
        $tourist_total  = 0;
        $listData       = $tourInfo->toArray();
        foreach ($listData as $key => $value) {
            $tourist_total += count($value['tourist']);
        }

        // 2023-03-22 将酒店打卡次数更改到游客表上的numbers中
        foreach ($listData as &$order) {
             // 获取tour_hotel_user_record数组中的所有uid
            $vo_uids = array_column($order['tour_hotel_user_record'], 'uid');
            foreach ($order['tourist'] as &$item) {
                if (in_array($item['uid'], $vo_uids)) {
                    if (!isset($item['numbers'])) {
                        $item['numbers'] = 0;
                    }
                    $item['numbers'] += array_count_values($vo_uids)[$item['uid']];
                } else {
                    $item['numbers'] = 0;
                }
            }
            unset($order['tour_hotel_user_record']); // 释放除酒店打卡记录
            unset($item); // 注意要释放$item的引用
        }
        unset($order); // 注意要释放$order的引用

        // 生成PDF文件
        $pageData['title'] = '旅游消费券';
        $pageData['addtitle'] = date('Y年m月d日 H:i:s',time());
        $pageData['no']       = $data['no'];
        $pageData['sum_coupon_price'] = $data['sum_coupon_price'];
        $pageData['writeoff_total']   = $data['writeoff_total'];
        $page_1 = '<style type="text/css">
        .jsxx {font-size: 18px;}
        .xxxx {font-size: 12px;}
        </style>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <table width="325" height="202" border="0" align="center" cellpadding="0" cellspacing="0">
          <tr>
            <td height="150" align="center" style="font-size: 30px; font-weight:bold;">
            <p>旅行社结算申请对账单</p>
            <p>&nbsp;</p></td>
          </tr>
        </table>
        <table width="560" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxx">
          <tr>
            <td width="130" height="35" align="right">商户名称：</td>
            <td>'.$data['nickname'].'</td>
          </tr>
          <tr>
            <td height="35" align="right" >结算业务名称：</td>
            <td>'.$pageData['title'].'</td>
          </tr>
          <tr>
            <td height="35" align="right">申请结算时间：</td>
            <td>'.$pageData['addtitle'].'</td>
          </tr>
          <tr>
            <td height="35" align="right">结算单号：</td>
            <td>'.$pageData['no'].'</td>
          </tr>
          <tr>
            <td height="35" align="right">结算金额：</td>
            <td>'.$pageData['sum_coupon_price'].'元</td>
          </tr>
          <tr>
            <td height="35" align="right">旅行团数量：</td>
            <td>'.$pageData['writeoff_total'].'</td>
          </tr>
          <tr>
            <td height="35" align="right">游客数量：</td>
            <td>'.$tourist_total.'</td>
          </tr>
        </table>
        <table width="325" height="115" border="0" align="center" cellpadding="0" cellspacing="0">
          <tr>
            <td height="115" align="center" style="font-size: 18px; font-weight:bold;"><p>&nbsp;</p>
            <p>收款账户</p></td>
          </tr>
        </table>
        <table width="560" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxx">
          <tr>
            <td width="130" height="35" align="right">账户名称：</td>
            <td>'.$data['card_name'].'</td>
          </tr>
          <tr>
            <td height="35" align="right" >收款账户：</td>
            <td>'.$data['cart_number'].'</td>
          </tr>
          <tr>
            <td height="35" align="right">开 户 行：</td>
            <td>'.$data['card_deposit'].'</td>
          </tr>
        </table>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <table width="308" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxx">
          <tr>
            <td width="308" height="30" align="right">公司（盖章）：</td>
          </tr>
          <tr>
            <td width="308" height="30" align="right">法人签字：</td>
          </tr>
          <tr>
            <td height="30" align="right">日期：</td>
          </tr>
        </table>
        <p>&nbsp;</p>
        <p>&nbsp;</p>';

        $page_2 = '';
        foreach ($listData as $key => $value) {
            $page_2 .= '<p>团号: '.$value['no'].'</p>
                        <p>名称: '.$value['name'].'</p>
            <table width="800" border="1" align="center" cellpadding="0" cellspacing="0" bordercolor="#999999" class="xxxx">
              <tr>
                <td width="40" height="25" align="center">序号</td>
                <td align="center">游客姓名</td>
                <td width="120" align="center">身份证号</td>
                <td width="90" align="center">手机号</td>
                <td width="80" align="center">领取时间</td>
                <td width="70" align="center">消费券面额</td>
                <td width="70" align="center">发票</td>
                <td width="70" align="center">景区打卡照片</td>
                <td width="70" align="center">酒店打卡次数</td>
                <td width="80" align="center">核销时间</td>
              </tr>';
              foreach ($value['tourist'] as $k => $v) {
                  $page_2 .='<tr>
                    <td height="25" align="center">'.($k+1).'</td>
                    <td align="center">'.$v['name'].'</td>
                    <td align="center">'.$v['idcard'].'</td>
                    <td align="center">'.$v['mobile'].'</td>
                    <td align="center">'.date("Y-m-d H:i:s",$v['tour_receive_time']).'</td>
                    <td align="center">'.$v['tour_price'].'</td>
                    <td align="center">已上报</td>
                    <td align="center">已上报</td>
                    <td align="center">'.$v['numbers'].'</td>
                    <td align="center">'.date("Y-m-d H:i:s",$v['tour_writeoff_time']).'</td>
                  </tr>';
              }
            $page_2 .='</table>';
        }

        $html = $page_1.$page_2;
        // 创建PDF文件
        $data_detail = createPdf($html,'旅行社结算申请对账单_'.$data['no'].'.pdf');
        if($data_detail['code']!=1){
            $this->apiError($data_detail['msg']);
        }
        $this->apiSuccess($data_detail['msg'],$data);
    }

    /**
     * [XdataSummary 消费券数据汇总]
     * @api    {post} /api/system/XdataSummary
     * @Author      slomoo@aliyun.com
     * @DateTime    2023-03-21
     * @version     [1.0.0]
     */
    public function XdataSummary(){
        $id     = Request::param('id/d',0);
        $coupon_issue = Db::name('coupon_issue')->field('total_count as issue_total,coupon_title,id as coupon_id,coupon_price,cid as class_cid,rollback_num,rollback_num_extend,start_time as issue_date')->select()->toArray();

        $where = [];
        // 获取消费券信息
        $listTour  = Db::name('tour_issue_user')->field('count(1) as receive_total,a.issue_coupon_id,b.cid as class_cid,c.title as class_name')
            ->alias('a')
            ->join(['tp_coupon_issue'=>'b'],'a.issue_coupon_id = b.id')
            ->join(['tp_coupon_class'=>'c'],'b.cid = c.id')
            ->where($where)
            ->group('a.issue_coupon_id')
            ->select()
            ->toArray();

        // 核销数量
        $hexiao  = Db::name('tour_write_off')->field('count(1) as total,a.coupon_issue_id,a.coupon_title')
            ->alias('a')
            ->join(['tp_coupon_issue'=>'b'],'a.coupon_issue_id = b.id')
            ->where($where)
            ->group('a.coupon_issue_id')
            ->select()
            ->toArray();

        foreach ($coupon_issue as $key => $value) {
            // 领取
            $coupon_issue[$key]['receive_total'] = 0;
            foreach ($listTour as $kk => $vv) {
                if($value['coupon_id'] == $vv['issue_coupon_id']){
                    $coupon_issue[$key]['receive_total'] = $vv['receive_total'];
                }
            }
            // 核销
            $coupon_issue[$key]['writeoff_total'] = 0;
            foreach ($hexiao as $kk => $vv) {
                if($value['coupon_id'] == $vv['coupon_issue_id']){
                    $coupon_issue[$key]['writeoff_total'] = $vv['total'];
                }
            }
        }


        // 每个券的核销比
        /*foreach ($coupon_issue as $key => $value) {

            $floor = $value['receive_total']>0 ? ($value['writeoff_total'] / $value['receive_total'] * 100) : 0;
            $coupon_issue[$key]['writeoff_ratio'] = sprintf("%.2f",$floor);// 核销比率
            // 发行金额 = 发行数量 * 面额
            $coupon_issue[$key]['issue_price'] = bcmul(strval($value['issue_total']), $value['coupon_price'],2);
            // 核销金额 = 核销数量 * 面额
            $coupon_issue[$key]['writeoff_price'] = bcmul(strval($value['writeoff_total']), $value['coupon_price'],2);
            // 多发数量 = 实际领取 - 发行数量
            $tempDel = $value['receive_total'] - $value['issue_total'];
            $coupon_issue[$key]['overstep'] = $tempDel > 0 ? $tempDel : 0;

            // 1=散客发行
            $coupon_issue[$key]['tags'] = 2;
        }
        $ration = array_column($coupon_issue, 'writeoff_ratio');
        // 按照核销比率排序
        array_multisort($ration,SORT_DESC,$coupon_issue);

        foreach ($coupon_issue as $key => $value) {
            // 校验ID是否存在 存在则更新  不存在 则新增
            $xEdit = \app\common\model\DataSummary::where('coupon_id',$value['coupon_id'])->find();
            if($xEdit==NULL){
                \app\common\model\DataSummary::create($value);
            }else{
                $xEdit->save($value);
            }
        }

        // 统计散客
        $coupon_issue_sk = Db::name('coupon_issue')->field('total_count as issue_total,coupon_title,id as coupon_id,coupon_price,cid as class_cid,rollback_num,rollback_num_extend,start_time as issue_date')->select()->toArray();*/

        // 获取消费券信息
        $list  = Db::name('coupon_issue_user')->field('count(1) as receive_total,a.issue_coupon_id,b.cid as class_cid,c.title as class_name')
            ->alias('a')
            ->join(['tp_coupon_issue'=>'b'],'a.issue_coupon_id = b.id')
            ->join(['tp_coupon_class'=>'c'],'b.cid = c.id')
            ->where($where)
            ->group('a.issue_coupon_id')
            ->select()
            ->toArray();
        // 核销数量
        $hexiao_list  = Db::name('write_off')->field('count(1) as total,a.coupon_issue_id,a.coupon_title')
            ->alias('a')
            ->join(['tp_coupon_issue'=>'b'],'a.coupon_issue_id = b.id')
            ->where($where)
            ->group('a.coupon_issue_id')
            ->select()
            ->toArray();

        foreach ($coupon_issue as $key => $value) {
            // 领取
            foreach ($list as $kk => $vv) {
                if($value['coupon_id'] == $vv['issue_coupon_id']){
                    $coupon_issue[$key]['receive_total'] = $vv['receive_total'];
                }
            }
            // 核销
            foreach ($hexiao_list as $kk => $vv) {
                if($value['coupon_id'] == $vv['coupon_issue_id']){
                    $coupon_issue[$key]['writeoff_total'] = $vv['total'];
                }
            }
        }

        // 每个券的核销比
        foreach ($coupon_issue as $key => $value) {
            $floor = $value['receive_total']>0 ? ($value['writeoff_total'] / $value['receive_total'] * 100) : 0;
            $coupon_issue[$key]['writeoff_ratio'] = sprintf("%.2f",$floor);// 核销比率
            // 发行金额 = 发行数量 * 面额
            $coupon_issue[$key]['issue_price'] = bcmul(strval($value['issue_total']), $value['coupon_price'],2);
            // 核销金额 = 核销数量 * 面额
            $coupon_issue[$key]['writeoff_price'] = bcmul(strval($value['writeoff_total']), $value['coupon_price'],2);
            // 多发数量 = 实际领取 - 发行数量
            $tempDel = $value['receive_total'] - $value['issue_total'];
            $coupon_issue[$key]['overstep'] = $tempDel > 0 ? $tempDel : 0;

            // 1=散客发行
            $coupon_issue[$key]['tags'] = 1;
        }
        $ration = array_column($coupon_issue, 'writeoff_ratio');
        // 按照核销比率排序
        array_multisort($ration,SORT_DESC,$coupon_issue);


        foreach ($coupon_issue as $key => $value) {
            // 校验ID是否存在 存在则更新  不存在 则新增
            $xEdit = \app\common\model\DataSummary::where('coupon_id',$value['coupon_id'])->find();
            if($xEdit==NULL){
                \app\common\model\DataSummary::create($value);
            }else{
                $xEdit->save($value);
            }
        }

        $this->apiSuccess('操作成功');
    }

    /**
     * [alert_push 核销预警推送]
     * @return   [type]            [核销预警推送]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-07-17
     * @LastTime 2023-07-17
     * @version  [1.0.0]
     */
    public function alert_push()
    {

        // 核销统计报告：散客累计核销23086元（728 张），团体累计核销67510元（3430 张），核销预警金额23086元。
        // 散客核销总金额、数量
        $singleCustomerPrice = \app\common\model\WriteOff::sum('coupon_price');
        $singleCustomerCnt = \app\common\model\WriteOff::count();
        // 团体核销总金额
        $tourCustomerPrice = \app\common\model\TourWriteOff::sum('coupon_price');
        $tourCustomerCnt = \app\common\model\TourWriteOff::count();
        // 团体券金额统计
        $tourExtendCustomerPrice = Db::table('tp_tour_coupon_group')
        ->alias('g')
        ->leftJoin('tour t', 'g.tid = t.id')
        ->leftJoin('coupon_issue i', 'i.id = g.coupon_issue_id')
        ->where('t.status', '<>', 6)
        ->fieldRaw('SUM(t.numbers * i.coupon_price) as total')->find();
        
        $total = bcadd(strval($singleCustomerPrice), strval($tourExtendCustomerPrice['total']),2);

        $host = "https://gyytz.market.alicloudapi.com";
        $path = "/sms/smsSend";
        $method = "POST";
        $appcode = "906db11f83294674a094e1b5ef327209";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);

        $mobiles = [
            '18992207739',// 李鹏飞
            '19891237999',// 常江伟
            //'17795977666',// 闫总
            '15619123472',// 王猛
            '18792683064', // 李琦
            //'19909122099',// 闫总
        ];

        // 当前日期
        $dateTimeNow = '截至'.date('m月d日',time());

        foreach ($mobiles as $mobile) {
            //smsSignId（短信前缀）和templateId（短信模板），可登录国阳云控制台自助申请。参考文档：http://help.guoyangyun.com/Problem/Qm.html
            $params = urlencode('**time**:'.$dateTimeNow.'**p_p**:'.$singleCustomerPrice.',**p**:'.$singleCustomerCnt.',**tour_p**:'.$tourCustomerPrice.',**tour**:'.$tourCustomerCnt.',**Warning**:'.$total);
            $querys = "mobile=".$mobile."&param=".$params."&smsSignId=66c7dd175a6c416b9ba72bbce18013d6&templateId=7aca6a0fdae146699a75a6477fda2281";
            $bodys = "";
            $url = $host . $path . "?" . $querys;

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);  //如果只想获取返回参数，可设置为false
            if (1 == strpos("$".$host, "https://"))
            {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            }
            $res = curl_exec($curl);
            $res = json_decode($res,true);
            if($res['code']==0){
                // 注册流程
                $inData['uid']          = 2;
                $inData['mobile']       = $mobile;
                $inData['sms_code']     = 999999;
                $inData['template']     = '';
                $inData['create_time']  = time();
                $inData['smsid']        = $res['smsid'];
                $inData['code']         = $res['code'];
                $inData['balance']      = $res['balance'];
                $inData['msg']          = $res['msg'];
                $inData['expire_time'] = time();
                $user_sms  = Db::name('users_sms_log')->strict(false)->field(true)->insertGetId($inData);
            }
        }
    }

    /**
     * [notification 每个小时核销数超过20、50发送预警提示]
     * @return   [type]            [每个小时核销数超过20、50发送预警提示]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-07-25
     * @LastTime 2023-07-25
     * @version  [1.0.0]
     */
    public function notification()
    {   
        $no = 20;
        $currentTime = time() - 3600;

        $result = Db::table('tp_write_off')->alias('w')
        ->leftJoin('tp_seller s', 'w.mid = s.id')
        ->where('w.create_time', '>=', $currentTime)
        ->group('w.mid')
        ->having('COUNT(1) > '.$no)
        ->field('w.mid, s.nickname, COUNT(1) AS cnt')
        ->select()
        ->toArray();
        
        if(!$result){
            return 0;
        }

        $number = 0;
        $str    = '**list**:';
        // 参数拼接
        foreach ($result as $row) {
            $str    .= $row['nickname'].':'.$row['cnt'].PHP_EOL;
            $number += $row['cnt'];
        }

        $time = date('Y-m-d H:i:s',$currentTime);
        $params = urlencode('**time**:'.$time.',**number**:'.$number.$str);

        $host = "https://gyytz.market.alicloudapi.com";
        $path = "/sms/smsSend";
        $method = "POST";
        $appcode = "906db11f83294674a094e1b5ef327209";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        $mobiles = [
            //'17795977666',
            //'19909122099',
            '15619123472',// 王猛
            '18792683064', // 李琦
        ];

        foreach ($mobiles as $mobile) {
            //smsSignId（短信前缀）和templateId（短信模板），可登录国阳云控制台自助申请。参考文档：http://help.guoyangyun.com/Problem/Qm.html
            
            $querys = "mobile=".$mobile."&param=".$params."&smsSignId=66c7dd175a6c416b9ba72bbce18013d6&templateId=e024c31d905a42a58e40b24ba593623d";
            $bodys = "";
            $url = $host . $path . "?" . $querys;

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);  //如果只想获取返回参数，可设置为false
            if (1 == strpos("$".$host, "https://"))
            {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            }
            $res = curl_exec($curl);
            $res = json_decode($res,true);
            if($res['code']==0){
                // 注册流程
                $inData['uid']          = 3;
                $inData['mobile']       = $mobile;
                $inData['sms_code']     = 888888;
                $inData['template']     = '';
                $inData['create_time']  = time();
                $inData['smsid']        = $res['smsid'];
                $inData['code']         = $res['code'];
                $inData['balance']      = $res['balance'];
                $inData['msg']          = $res['msg'];
                $inData['expire_time'] = time();
                $user_sms  = Db::name('users_sms_log')->strict(false)->field(true)->insertGetId($inData);
            }
        }
    }

    public function invalid_tour()
    {
        Db::query('UPDATE tp_tour SET `status` = 6 WHERE is_locking = 0 AND `status` !=6');
    }

    // 2023-08-17 重新生成pdf文件 商户门票结算
    public function remake_ticket(){
        ini_set('memory_limit', '1280M');
        ini_set('max_execution_time', '300');
        ini_set("pcre.backtrack_limit", "10000000");
        $uuno     = Request::param('uuno/s','');
        $data = \app\common\model\TicketSettlement::where(['uuno'=>$uuno])->find();
        if(!$data){
            $this->apiError('当前记录不存在');
        }
        // 根据散客领取记录
        $orderDetailList = Db::name('ticket_settlement_records')->alias('r')
        ->join('ticket_order_detail d','d.out_trade_no = r.slave_trade_no')
        ->where('r.uuno','=',$data['uuno'])
        ->field('d.*')
        ->select();

        // 结算数量
        $order_number  = $data['order_numbers'];
        // 结算金额
        $ticket_price_total  = $data['amount'];
        // 结算门票数量
        $ticket_number       = $data['ticiet_numbers'];
        // 生成PDF文件
        $pageData['title'] = $data['title'];
        $pageData['addtitle'] = $data['create_time'];
        $pageData['uuno']       = $data['uuno'];
        // 订单总金额
        $pageData['sum_ticket_price'] = $ticket_price_total;
        // 订单总笔数
        $pageData['order_number']     = $order_number;
        // 门票总数量
        $pageData['ticket_number']    = $ticket_number;
        $page_1 = '<style type="text/css">
        .jsxx {font-size: 18px;}
        .xxxx {font-size: 12px;}
        </style>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <table width="325" height="202" border="0" align="center" cellpadding="0" cellspacing="0">
          <tr>
            <td height="150" align="center" style="font-size: 30px; font-weight:bold;">
            <p>商户门票申请对账单</p>
            <p>&nbsp;</p></td>
          </tr>
        </table>
        <table width="560" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxx">
          <tr>
            <td width="130" height="35" align="right">商户名称：</td>
            <td>'.$data['nickname'].'</td>
          </tr>
          <tr>
            <td height="35" align="right" >结算业务名称：</td>
            <td>'.$pageData['title'].'</td>
          </tr>
          <tr>
            <td height="35" align="right">申请结算时间：</td>
            <td>'.$pageData['addtitle'].'</td>
          </tr>
          <tr>
            <td height="35" align="right">结算单号：</td>
            <td>'.$pageData['uuno'].'</td>
          </tr>
          <tr>
            <td height="35" align="right">结算金额：</td>
            <td>'.$pageData['sum_ticket_price'].'元</td>
          </tr>
          <tr>
            <td height="35" align="right">结算订单数量：</td>
            <td>'.$pageData['order_number'].'</td>
          </tr>
          <tr>
            <td height="35" align="right">结算门票总数量：</td>
            <td>'.$pageData['ticket_number'].'</td>
          </tr>
        </table>
        <table width="325" height="115" border="0" align="center" cellpadding="0" cellspacing="0">
          <tr>
            <td height="115" align="center" style="font-size: 18px; font-weight:bold;"><p>&nbsp;</p>
            <p>收款账户</p></td>
          </tr>
        </table>
        <table width="560" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxx">
          <tr>
            <td width="130" height="35" align="right">账户名称：</td>
            <td>'.$data['card_name'].'</td>
          </tr>
          <tr>
            <td height="35" align="right" >收款账户：</td>
            <td>'.$data['cart_number'].'</td>
          </tr>
          <tr>
            <td height="35" align="right">开 户 行：</td>
            <td>'.$data['card_deposit'].'</td>
          </tr>
        </table>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <table width="308" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxx">
          <tr>
            <td width="308" height="30" align="right">公司（盖章）：</td>
          </tr>
          <tr>
            <td width="308" height="30" align="right">法人签字 ：</td>
          </tr>
          <tr>
            <td height="30" align="right">日期 ：</td>
          </tr>
        </table>
        <p>&nbsp;</p>
        <p>&nbsp;</p>
        <p>附件</p>
        ';

        $page_2 = '';
        $page_2 .= '<p>结算单号: '.$pageData['uuno'].'</p>';
        $page_2 .= '
        <table width="800" border="1" align="center" cellpadding="0" cellspacing="0" bordercolor="#999999" class="xxxx">
          <tr>
            <td width="40" height="25" align="center">序号</td>
            <td align="center">订单编号</td>
            <td width="80" align="center">游客</td>
            <td width="80" align="center">手机号</td>
            <td width="80" align="center">门票ID</td>
            <td width="130" align="center">门票名称</td>
            <td width="70" align="center">门票单价</td>
            <td width="70" align="center">购买数量</td>
            <td width="70" align="center">下单时间</td>
            <td width="70" align="center">使用时间</td>
          </tr>';
          foreach ($orderDetailList as $k => $v) {
              $page_2 .='<tr>
                <td height="25" align="center">'.($k+1).'</td>
                <td align="center">'.$v['trade_no'].'</td>
                <td align="center">'.$v['tourist_fullname'].'</td>
                <td align="center">'.$v['tourist_mobile'].'</td>
                <td align="center">'.$v['ticket_id'].'</td>
                <td align="center">'.$v['ticket_title'].'</td>
                <td align="center">'.$v['ticket_price'].'</td>

                <td align="center">'.$v['ticket_number'].'</td>
                <td align="center">'.date("Y-m-d H:i:s",$v['create_time']).'</td>
                <td align="center">'.date("Y-m-d H:i:s",$v['enter_time']).'</td>
              </tr>';
          }
        $page_2 .='</table>';

        $html = $page_1.$page_2;
        // 创建PDF文件
        $data_detail = createPdf($html,'普通商户门票结算申请对账单_'.$data['uuno'].'.pdf');
        if($data_detail['code']!=1){
            $this->apiError($data_detail['msg']);
        }
        $this->apiSuccess($data_detail['msg'],$data);
    }
}