<?php
/**
 * 消费券领取记录模型
 * @author slomoo <1103398780@qq.com> 2022/08/27
 */
namespace app\common\model;
use think\facade\Event;
use think\facade\Db;
use think\facade\Request;
// 引入构建器
use app\common\facade\MakeBuilder;
class CouponIssueUser extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 获取列表
    public static function getRewriteList(array $where = [], int $pageSize = 0, array $order = ['sort', 'id' => 'desc'], $whereUid = [])
    {
        if ($pageSize) {
            $list = self::where($where)
                ->hasWhere('users',$whereUid)
                ->with(['users','couponIssue','couponClass'])
                ->order($order)
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $pageSize,
                ]);
        } else {
            $list = self::where($where)
                ->hasWhere('users',$whereUid)
                ->with(['users','couponIssue','couponClass'])
                ->order($order)
                ->select();
        }
        return MakeBuilder::changeTableData($list, 'CouponIssueUser');
    }
    
    public function users()
    {
        return $this->belongsTo('Users', 'uid')->field('id,sex,email,last_login_time,last_login_ip,mobile,idcard,type_id,status,create_time,update_time,name,headimgurl,nickname,salt,uuid,openid,auth_status,province,city,district');
    }
    public function couponIssue()
    {
        return $this->belongsTo('CouponIssue', 'issue_coupon_id');
    }
    public function couponClass()
    {
        return $this->belongsTo('CouponClass', 'issue_coupon_class_id');
    }
    
    /**
    * 查询当前用户未使用的消费券
    * @return array|\think\response\Json
    * @throws \think\Exception
    */
    public static function selectLog($id)
    {
        $list = self::where('uid',$id)->distinct(true)->where('status',0)->column('issue_coupon_id');
        if($list){
            // 触发检查消费券是否过期事件
            Event::trigger('CouponIssueCheck', $list);
        }
    }

    // 用户购买消费券成功之后 生成领取记录
    // id = 消费券ID  user = 用户信息
    public static function issueUserCoupon($uuno, $user,$order_no)
    {
        $uid = $user['id'];
        $issueCouponInfo = Db::name('CouponIssue')->where('uuno',$uuno)->find();
        // 事务操作
        Db::startTrans();
        try {
            // 领取存储的数据
            $saveData = [
                'uid' => $uid, 
                'issue_coupon_id' => $issueCouponInfo['id'], 
                'issue_coupon_class_id' => $issueCouponInfo['cid'], 
                'create_time'   => time(),
                'coupon_title'  => $issueCouponInfo['coupon_title'],
                'coupon_price'  => $issueCouponInfo['coupon_price'],
                'use_min_price' => $issueCouponInfo['use_min_price'],
                'coupon_create_time'  => strtotime($issueCouponInfo['create_time']),
                'time_start'    => $issueCouponInfo['start_time'],
                'time_end'      => $issueCouponInfo['end_time'],
                'is_fail'       => 1,
                'is_limit_total' => $issueCouponInfo['is_limit_total'],
            ];

            // 保存领取记录
            $issueId = Db::name('CouponIssueUser')->strict(false)->insertGetId($saveData);
            
            // 整条数据加密，盐值从个人用户获取唯一盐值
            //$saveData['enstr_salt'] = symencryption(json_encode($saveData,JSON_UNESCAPED_UNICODE),$user->salt);
            // 2022-07-29 改为不可逆加密 md5
            $saltData = [
                'id'  => $issueId,
                'uid' => $uid,
                'create_time' => $saveData['create_time'],
                'issue_coupon_id' => $issueCouponInfo['id'], 
                'issue_coupon_class_id' => $issueCouponInfo['cid'], 
                'coupon_title'  => $issueCouponInfo['coupon_title'],
                'coupon_price'  => $issueCouponInfo['coupon_price'],
                'use_min_price' => $issueCouponInfo['use_min_price'],
                'coupon_create_time'  => strtotime($issueCouponInfo['create_time']),
                'time_start'    => $issueCouponInfo['start_time'],
                'time_end'      => $issueCouponInfo['end_time'],
                'is_limit_total'=> $issueCouponInfo['is_limit_total'],
            ];
            // 领取记录加密串 = 领取部分数据记录 + 用户盐值
            $enstr_salt = md5(json_encode($saltData,JSON_UNESCAPED_UNICODE).$user['salt']);
            // 生成领取二维码并修改
            //$qrcode_url = Qrcode($issueId);'qrcode_url'=>$qrcode_url,
            Db::name('CouponIssueUser')
            ->where('id',$issueId)
            ->update(['enstr_salt'=>$enstr_salt]);
            // 消费券剩余领取数量 - 1  total_count > 0证明限制总量
            if ($issueCouponInfo['total_count'] > 0) {
                $issueCouponInfo['remain_count'] -= 1;

                $issueCouponData['remain_count'] = $issueCouponInfo['remain_count'];
                $issueCouponData['update_time']  = time();
                Db::name('CouponIssue')->where('uuno',$uuno)->update($issueCouponData);
            }

            // 将领取记录ID 更新到订单表
            Db::name('CouponOrder')
            ->where('order_no',$order_no)
            ->update(['issue_coupon_user_id'=>$issueId]);
            // 提交事务
            Db::commit();
            //$this->apiSuccess('领取成功','data success');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            //$this->apiSuccess('领取成功',$e->getMessage());
        }
    }

    public static function getListExport(array $where = [], int $pageSize = 0, array $order = ['sort', 'id' => 'desc'],$limit){
        $model = new static();
        $model = $model->alias($model->getName());

        // 获取with关联
        $moduleId  = \app\common\model\Module::where('model_name', $model->getName())->value('id');
        $fileds    = \app\common\model\Field::where('module_id', $moduleId)
            ->select()
            ->toArray();
        $listInfo  = [];  // 字段根据关联信息重新赋值
        $withInfo  = [];  // 模型关联信息(用于设置关联预载入)
        $fieldInfo = [];  // 字段包含.的时候从关联模型中获取数据
        foreach ($fileds as $filed) {
            // 数据源为模型数据时设置关联信息
            if ($filed['data_source'] == 2) {
                $listInfo[] = [
                    'field'          => $filed['field'],                   // 字段名称
                    'relation_model' => lcfirst($filed['relation_model']), // 关联模型
                    'relation_field' => $filed['relation_field'],          // 展示字段
                    'type'           => $filed['type'],                    // 字段类型
                    'setup'          => string2array($filed['setup']),     // 字段其他设置
                ];
                $withInfo[] = lcfirst($filed['relation_model']);
            }
            // 字段包含.的时候从关联模型中获取数据
            if (strpos($filed['field'], '.') !== false) {
                // 拆分字段名称为数组
                $filedArr    = explode('.', $filed['field']);
                $fieldInfo[] = [
                    'field'          => $filed['field'],       // 字段名称
                    'relation_model' => lcfirst($filedArr[0]), // 关联模型
                    'relation_field' => $filedArr[1],          // 展示字段
                    'type'           => $filed['type'],        // 字段类型
                ];
            }
        }

        // 关联预载入
        if ($withInfo) {
            $model = $model->with($withInfo);
        }

        // 筛选条件
        if ($where) {
            $whereNew = [];
            $whereHas = [];
            foreach ($where as $v) {
                if (strpos($v[0], '.') === false) {
                    $whereNew[] = $v;
                } else {
                    // 关联模型搜索
                    $filedArr = explode('.', $v[0]);

                    $whereHas[lcfirst($filedArr[0])][] = [
                        'field'        => $filedArr[1],
                        'field_option' => $v[1],
                        'field_value'  => $v[2],
                    ];
                }
            }
            // 关联模型搜索
            if ($whereHas) {
                foreach ($whereHas as $k => $v) {
                    $model = $model->hasWhere($k, function ($query) use ($v) {
                        foreach ($v as $vv) {
                            $query->where($vv['field'], $vv['field_option'], $vv['field_value']);
                        }
                    });
                }
            }
            // 当前模型搜索
            if ($whereNew) {
                $model = $model->where($where);
            }
        }

        // 查询/分页查询
        if ($pageSize) {
            $list = $model->order($order)
                ->limit($pageSize,$limit)->select();
        } else {
            $list = $model->order($order)
                ->select();
        }
        return MakeBuilder::changeTableData($list, $model->getName());
    }

    /*public function getStatusAttr($value)
    {
        $status = [0=>'未使用',1=>'已使用',2=>'已过期'];
        return $status[$value];
    }*/

    //查询快递信息【四小时允许更新一次】
    public static function syncTrackingResult($trackingNumber, $coupon_issue_user_id)
    {
        $model  = '\app\common\model\LogisticsInformation';
        $logisticsInformationArr = $model::where([['coupon_issue_user_id','=',$coupon_issue_user_id]])->find();

        if (!empty($logisticsInformationArr)) {
            //存在物流信息
            if ($trackingNumber == $logisticsInformationArr['tracking_number']) {
                //判断更新时间是否大于4小时,更新物流信息、未签收、状态码为200或205
                if ((time() - strtotime($logisticsInformationArr['update_time']) > 4 * 3600)
                    && ($logisticsInformationArr['delivery_status'] != 3)
                    && (in_array($logisticsInformationArr['code'], [200, 205]))) {
                    //更新物流：大于4小时,更新物流信息、未签收、状态码为200或205
                    $trackingResult = self::getLogisticsInformation($trackingNumber);
                    self::saveLogisticsInformation($trackingNumber, $coupon_issue_user_id,  $trackingResult, 1);
                } else {
                    //小于4小时、已签收、状态码异常,直接返回物流信息
                    $logisticsInformationArr['data'] = json_decode($logisticsInformationArr['data']);//格式化快递信息
                    $trackingResult = $logisticsInformationArr;
                }
            } else {
                //物流编号变更
                $trackingResult = self::getLogisticsInformation($trackingNumber);
                self::saveLogisticsInformation($trackingNumber, $coupon_issue_user_id,  $trackingResult, 1);
            }
        } else {
            //如果不存在物流信息，则查询物流信息：返回物流信息后进行存储
            $trackingResult = self::getLogisticsInformation($trackingNumber);
            self::saveLogisticsInformation($trackingNumber, $coupon_issue_user_id,  $trackingResult, 0);
        }

        return $trackingResult;
    }

    // 获得快递信息
    public static function getLogisticsInformation($trackingNumber)
    {
        $system = \app\common\model\System::find(1);
        if (!$system || !$system['tracking_app_code']) {
            return [
                'code' => 404,
                'msg' => '未找到物流信息',
                'data' => '',
            ];
        }

        $url = "https://wuliu.market.alicloudapi.com/kdi?no=" . $trackingNumber;
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $system['tracking_app_code']);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        if (1 == strpos("$" . $url, "https://")) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $out_put = curl_exec($curl);

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        list($header, $body) = explode("\r\n\r\n", $out_put, 2);

        if ($httpCode == 200) {
            //请求成功，处理数据
            $bodyContent = json_decode($body);
            switch ($bodyContent->status) {
                case 0://正常
                    $trackingResult['code'] = 200;
                    $trackingResult['msg'] = 'success';
                    $trackingResult['data'] = $bodyContent->result->list;//进度
                    $trackingResult['delivery_status'] = $bodyContent->result->deliverystatus;//0：快递收件(揽件)1.在途中 2.正在派件 3.已签收 4.派送失败 5.疑难件 6.退件签收进度
                    $trackingResult['issign'] = $bodyContent->result->issign;//是否本人签收:1是/0否 [不准，请勿用该字段判断，请使用delivery_status=3]
                    $trackingResult['exp_type'] = $bodyContent->result->type;//快递公司en
                    $trackingResult['exp_name'] = $bodyContent->result->expName;//快递公司名称
                    $trackingResult['courier'] = $bodyContent->result->courier;//快递员
                    $trackingResult['courierPhone'] = $bodyContent->result->courierPhone;//快递员电话
                    break;
                case 201://快递单号错误
                    $trackingResult = [
                        'code' => 201,
                        'msg' => '快递单号错误',
                        'data' => '',
                    ];
                    break;
                case 203://快递公司不存在
                    $trackingResult = [
                        'code' => 203,
                        'msg' => '快递公司不存在',
                        'data' => '',
                    ];
                    break;
                case 204://快递公司识别失败
                    $trackingResult = [
                        'code' => 204,
                        'msg' => '快递公司识别失败',
                        'data' => '',
                    ];
                    break;
                case 205://没有信息
                    $trackingResult = [
                        'code' => 205,
                        'msg' => '没有信息',
                        'data' => '',
                    ];
                    break;
                case 207://IP限制
                    $trackingResult = [
                        'code' => 207,
                        'msg' => 'IP限制',
                        'data' => '',
                    ];
                    break;
                default:
                    //默认处理
                    $trackingResult = [
                        'code' => 500,
                        'msg' => '未知错误，请刷新重试',
                        'data' => '',
                    ];
                    break;
            }

        } else {
            if ($httpCode == 400 && strpos($header, "Invalid Param Location") !== false) {
                $trackingResult = [
                    'code' => 400,
                    'msg' => '参数错误',
                    'data' => '',
                ];
            } elseif ($httpCode == 400 && strpos($header, "Invalid AppCode") !== false) {
                $trackingResult = [
                    'code' => 400,
                    'msg' => 'AppCode错误',
                    'data' => '',
                ];
            } elseif ($httpCode == 400 && strpos($header, "Invalid Url") !== false) {
                $trackingResult = [
                    'code' => 400,
                    'msg' => '请求的 Method、Path 或者环境错误',
                    'data' => '',
                ];
            } elseif ($httpCode == 403 && strpos($header, "Unauthorized") !== false) {
                $trackingResult = [
                    'code' => 403,
                    'msg' => '服务未被授权（或URL和Path不正确）',
                    'data' => '',
                ];
            } elseif ($httpCode == 403 && strpos($header, "Quota Exhausted") !== false) {
                $trackingResult = [
                    'code' => 403,
                    'msg' => '套餐包次数用完',
                    'data' => '',
                ];
            } elseif ($httpCode == 403 && strpos($header, "Api Market Subscription quota exhausted") !== false) {
                $trackingResult = [
                    'code' => 403,
                    'msg' => '套餐包次数用完，请续购套餐',
                    'data' => '',
                ];
            } elseif ($httpCode == 500) {
                $trackingResult = [
                    'code' => 500,
                    'msg' => 'API网关错误',
                    'data' => '',
                ];
            } elseif ($httpCode == 0) {
                $trackingResult = [
                    'code' => 0,
                    'msg' => 'URL错误',
                    'data' => '',
                ];
            } else {
                $trackingResult = [
                    'code' => 400,
                    'msg' => '参数名错误 或 其他错误',
                    'data' => '',
                ];
            }
        }
        return $trackingResult;
    }

    /**
     * 存储或更新快递信息
     * @param $trackingResult   //物流数据
     * @param $coupon_issue_user_id  //消费卷领取记录id
     * @param $actionValue      //0新增、1变更[根据优惠劵id]
     * @return void
     */
    public static function saveLogisticsInformation($trackingNumber, $coupon_issue_user_id,  $trackingResult, $actionValue)
    {
        $model  = '\app\common\model\LogisticsInformation';
        $trackingResult['tracking_number'] = $trackingNumber;
        $trackingResult['data'] = json_encode($trackingResult['data']);//快递信息转为json串存储

        if ($actionValue == 0) {
            //新增
            $trackingResult['coupon_issue_user_id'] = $coupon_issue_user_id;
            $model::create($trackingResult);
        } else {
            //更新
            $model::update($trackingResult, ['coupon_issue_user_id' => $coupon_issue_user_id]);
        }
    }

    //系统核销处理
    public function systemWriteOff($userid, $coupon_issue_user_id, $trackingResult)
    {
        if (($trackingResult['code'] == 200) && ($trackingResult['msg'] == 'success')) {
            //查看领取记录是否核销，若未核销，则需要进行核销处理
            $cInfo = self::find($coupon_issue_user_id);
            if($cInfo && ($cInfo['status'] != 1)){
                //存在卷，且未使用可进行核销操作
                $iInfo = \app\common\model\CouponIssue::find($cInfo['issue_coupon_id']);
                if($iInfo){
                    $uInfo = \app\common\model\Users::find($userid);// 获取用户信息

                    // 事务操作
                    Db::startTrans();
                    try {
                        // 记录核销操作
                        $data['orderid']                = 0;
                        $data['create_time']            = time();
                        $data['coupon_issue_user_id']   = $coupon_issue_user_id;
                        $data['mid']                    = $iInfo->write_off_seller;
                        $data['uuno']                   = $iInfo->uuno;
                        $data['coupon_issue_id']        = $iInfo->id;
                        $data['coupon_title']           = $cInfo->coupon_title;
                        $data['coupon_price']           = $cInfo->coupon_price;
                        $data['use_min_price']          = $cInfo->use_min_price;
                        $data['time_start']             = $cInfo->time_start;
                        $data['time_end']               = $cInfo->time_end;
                        $data['qrcode_url']             = $cInfo->qrcode_url;
                        $data['userid']                 = $userid;//核验人id，领卷人id
                        //用户经纬度
                        $data['uw_longitude']           = '0.000000';
                        $data['uw_latitude']            = '0.000000';
                        // 核销点的经纬度
                        $data['poi_longitude']          = '0.000000';
                        $data['poi_latitude']           = '0.000000';
                        // 核验人的经纬度
                        $data['uid']                    = $uInfo->id;
                        $data['he_longitude']           = '0.000000';
                        $data['he_latitude']            = '0.000000';
                        // 核销加密串 = 领取记录加密串 + 核销记录md5串 + 核销用户盐值
                        $data['enstr_salt']             = md5($cInfo['enstr_salt'].json_encode($data,JSON_UNESCAPED_UNICODE).$uInfo->salt);
                        $inId = Db::name('write_off')->insertGetId($data);

                        // 修改领取记录状态 = 已经使用
                        Db::name('CouponIssueUser')
                            ->where('id',$cInfo['id'])
                            ->update(['is_fail'=>0,'status'=>1,'time_use'=>time()]);
                        // 提交事务
                        Db::commit();
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                    }
                }
            }
        }
    }

    //自动核销已签收快递
    public function autoWriteOff()
    {
        // $list = self::field('w.uid,l.*')
        //     ->alias('w')
        //     ->leftJoin('LogisticsInformation l', 'w.id=l.coupon_issue_user_id')
        //     ->where('w.delivery_address',  '<>', '')
        //     ->where('w.tracking_number',  '<>', '')
        //     ->where('l.delivery_status',  '<>', 3)
        //     ->select()
        //     ->toArray();
        
        //查询所有未核销礼包券
        $list = self::field('id,uid,tracking_number')
            ->where('issue_coupon_class_id', '=', 6)
            ->where('status', '<>', 1)
            ->where('delivery_address',  '<>', '')
            ->where('tracking_number',  '<>', '')
            ->select()
            ->toArray();

        foreach ($list as $info) {
            $trackingResult = self::syncTrackingResult($info['tracking_number'], $info['id']);

            //返回200,success,已签收,则可以进行核销操作
            self::systemWriteOff($info['uid'], $info['id'], $trackingResult);
        }
    }
}