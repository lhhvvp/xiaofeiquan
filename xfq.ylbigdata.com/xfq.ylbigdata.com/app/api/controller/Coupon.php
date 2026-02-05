<?php
/**
 * @desc   消费券API
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
use ip2region\XdbSearcher;
use think\facade\Event;


class Coupon extends BaseController
{

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except'    => ['line_detail','line_list','tempApi','index','detail','list','applicabletoV2','applicableto'] ]
    ];

    //初始化
    protected function initialize()
    {
        parent::initialize();
        $this->CouponIssue      = new \app\common\model\CouponIssue;
        $this->Users            = new \app\common\model\Users;
        $this->CouponIssueUser  = new \app\common\model\CouponIssueUser;
        $this->CouponClass      = new \app\common\model\CouponClass;
        $this->Seller           = new \app\common\model\Seller;
        $this->WriteOff         = new \app\common\model\WriteOff;
        $this->CouponReceiveCondition = new \app\common\model\CouponReceiveCondition;
        $this->CouponConditionDetails = new \app\common\model\CouponConditionDetails;
        $this->SellerMarkQcUserRecord = new \app\common\model\SellerMarkQcUserRecord;
    }

    public function index()
    {
		$map      = [];//查询条件

        $class_id = Request::param('class_id/d',0);     // 栏目类型 1=通用 2=门票 3=线路 4=商品
		$type 	  = Request::param('type/d', 0);        // 消费券类型 1=通用 2=品类券 3=商品券
        $use_store= Request::param('use_store/d', 0);   // 使用门店  1=通用  2=景区  3=旅行社 4=剧院 5=影院
		$limit    = Request::param('limit/d', 10);      // 分页 每页展示几条
		$page     = Request::param('page/d', 1);        // 分页 第几页
		$userid   = Request::param('userid/d',0);       // 用户ID
		$productId = Request::param('productId/d',0);   // 商品ID
        $tag       = Request::param('tag/d',0);         // 是否在首页展示  1=是  0=否

        // 获取用户信息
        // if(empty($userid)){
        //     $this->apiError('用户不能为空！');
        // }
        // $uInfo = \app\common\model\Users::find($userid);
        // if(!$uInfo){
        //     $this->apiError('用户不存在');
        // }
        // if($uInfo['status'] != 1){
        //     $this->apiError('用户已被禁用');
        // }

        // 获取消费券分类
        $CouponClass = $this->CouponClass::field('id, title,class_icon')
            ->where('status',1)
            ->order('sort asc')
            ->select()
            ->toArray();
		$info = $this->getIssueCouponList($userid,$type,$page,$limit,$productId,$tag,$use_store,$class_id);
        // 按照消费券分类
        foreach ($CouponClass as $key => $value) {
            $CouponClass[$key]['list'] = [];
            // 2023-03-02 每个分类下只显示4个
            $i = 0;
            foreach ($info['list'] as $kk => $vv) {
                if($value['id']==$vv['cid'] && $i<4){
                    $CouponClass[$key]['list'][] = $vv;
                    $i = $i+1;
                }
            }
        }
        $this->apiSuccess('查询成功',$CouponClass);
	}

    // 体验接口
    public function tempApi()
    {
        $map      = [];//查询条件

        $class_id = Request::param('class_id/d',0);     // 栏目类型 1=通用 2=门票 3=线路 4=商品
        $type     = Request::param('type/d', 0);        // 消费券类型 1=通用 2=品类券 3=商品券
        $use_store= Request::param('use_store/d', 0);   // 使用门店  1=通用  2=景区  3=旅行社 4=剧院 5=影院
        $limit    = Request::param('limit/d', 10);      // 分页 每页展示几条
        $page     = Request::param('page/d', 1);        // 分页 第几页
        $userid   = Request::param('userid/d',0);       // 用户ID
        $productId = Request::param('productId/d',0);   // 商品ID
        $tag       = Request::param('tag/d',0);         // 是否在首页展示  1=是  0=否

        // 获取用户信息
        if(empty($userid)){
            $this->apiError('用户不能为空！');
        }
        $uInfo = \app\common\model\Users::find($userid);
        if(!$uInfo){
            $this->apiError('用户不存在');
        }
        if($uInfo['status'] != 1){
            $this->apiError('用户已被禁用');
        }

        // 获取消费券分类
        $CouponClass = $this->CouponClass::field('id, title,class_icon')
            ->where('status',1)
            ->order('sort asc')
            ->select()
            ->toArray();
        $info = $this->getIssueCouponList($userid,$type,$page,$limit,$productId,$tag,$use_store,$class_id);
        // 按照消费券分类
        foreach ($CouponClass as $key => $value) {
            $CouponClass[$key]['list'] = [];
            foreach ($info['list'] as $kk => $vv) {
                if($value['id']==$vv['cid']){
                    $CouponClass[$key]['list'][] = $vv;
                }
            }
        }
        $this->apiSuccess('查询成功',$CouponClass);
    }

	/**
     * 获取消费券列表
     * @param array $type
     * @return array
     */
    public function getIssueCouponList($uid,$type,$page,$limit,$productId,$tag,$use_store,$class_id)
    {
    	$typeId = 0;
    	$cateId = 0;
        $list = $this->getIssueCouponUsed($uid,(int)$type, $typeId, $page, $limit,$productId,$tag,$use_store,$class_id);

        //echo $this->CouponIssue::getLastSql();die;
        foreach ($list as &$v) {
            $v['coupon_price'] = floatval($v['coupon_price']);
            $v['use_min_price'] = floatval($v['use_min_price']);
            $v['is_use'] = $uid ? isset($v['used']) : false;
            if ($v['coupon_time_end']) {
                $v['coupon_time_start'] = date('Y/m/d', $v['coupon_time_start']);
                $v['coupon_time_end'] = $v['coupon_time_end'] ? date('Y/m/d', $v['coupon_time_end']) : date('Y/m/d', time() + 86400);
            }
            if ($v['start_time']) {
                $v['start_time'] = date('Y/m/d', $v['start_time']);
                $v['end_time'] = date('Y/m/d', $v['end_time']);
            }
        }
        $data['list'] = $list;
        //$data['count'] = $this->getIssueCouponCount($productId, $cateId,$tag);
        return $data;
    }

    /**
     * 获取消费券数量
     * @param int $productId
     * @param int $cateId
     * @return mixed
     */
    private function getIssueCouponCount($productId = 0, $cateId = 0,$tag)
    {
        $model = function ($query) {
            $query->where('status', 1)
                ->where('is_del', 0)
                ->where('remain_count > 0 OR is_permanent = 1')
                ->where(function ($query) {
                    $query->where('receive_type', 1);
                })->where(function ($query) {
                    $query->where(function ($query) {
                        $query->where('start_time', '<', time())->where('end_time', '>', time());
                    })->whereOr(function ($query) {
                        $query->where('start_time', 0)->where('end_time', 0);
                    });
                });
        };

        $map   = [];
        if($tag)
            $map[] = ['tag','=',$tag];

        $count[0] = $this->CouponIssue::where($model)->where('type', 0)->where($map)->count();
        $count[1] = $this->CouponIssue::where($model)->where('type', 1)->where($map)->when($cateId != 0, function ($query) use ($cateId) {
            if ($cateId) $query->where('category_id', 'in', $cateId);
        })->count();
        $count[2] = $this->CouponIssue::where($model)->where('type', 2)->where($map)->when($productId != 0, function ($query) use ($productId) {
            $where[] = ['','EXP',$this->CouponIssue::raw("FIND_IN_SET($productId,product_id)")];
            if ($productId) $query->where($where);
        })->count();
        return $count;
    }

    /**
     * 关联模型检测用户是否拥有当前消费券
     * @param int $type 0通用，1分类，2商品
     * @param int $typeId 分类ID或商品ID
     * @param int $page
     * @param int $limit
     * @return array
     */
    private function getIssueCouponUsed(int $uid,int $type, $typeId, int $page, int $limit,$productId,$tag,$use_store,$class_id)
    {
        $map   = [];

        // $idCardWhere = ['receive_crowd', '=', 1];
        // if(!empty($uid)) {
        //   // 登录，限制展示
        //   $uInfo = \app\common\model\Users::find($uid);
        //   //领取人群 1=全部  2=本地[6108、6127] 3=外地。判断用户idcard的前四位为本地或者外地
        //     if (!empty($uInfo['idcard'])){
        //         $fourIdCard = substr($uInfo['idcard'], 0, 4);
        //         $receive_crowd_arr = [1, 3];//默认全部和外地人可领
        //         if (in_array($fourIdCard, [6108, 6127])) {
        //             //本地,则全部和本地人可领
        //             $receive_crowd_arr = [1, 2];
        //         }
        //         $idCardWhere = ['receive_crowd', 'in', $receive_crowd_arr];
        //     }
        // }
        // $map[] = $idCardWhere;

        // 是否在首页展示
        if($tag) {
            $map[] = ['tag','=',$tag];
        }

        return $this->CouponIssue::where('status', 1)
            ->where('is_del', 0)
            //->where('remain_count > 0 OR is_permanent = 1')
            ->where(function ($query) {
                $query->where('receive_type', 1); // 消费券发送方式： 1=手动领取
            })
            /*->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('start_time', '<', time())->where('end_time', '>', time());
                })->whereOr(function ($query) {
                    $query->where('start_time', 0)->where('end_time', 0);
                });
            })*/
            // 2023-02-27 取消用户是否领取
            /*->with(['used' => function ($query) use ($uid) {
                $query->where('uid', $uid);
            }])*/
            ->where('coupon_type','in',[1,2])
            ->where($map)
            //->where('type', $type)
            //->where('use_store', $use_store)
            //->where('class_id', $class_id)
            /*->when($type == 2, function ($query) use ($typeId) {
                if ($typeId) $query->where('category_id', 'in', $typeId);
            })
            ->when($type == 3, function ($query) use ($productId) {
                $where[] = ['','EXP',$this->CouponIssue::raw("FIND_IN_SET($productId,product_id)")];
                if ($productId) $query->where($where);
            })*/
            /*->when(in_array($use_store,[1,2,3,4,5]), function ($query) use ($use_store) {
                if ($use_store) $query->where('use_store', '=', $use_store);
            })*/
            ->order('sort desc,id desc')->select()->toArray();
    }

    /**
     * 领取消费券
     * @param Request $request
     * @return mixed
     */
    public function receive()
    {
		$userid = Request::param('userid/d',0);
		$couponId = Request::param('couponId/d',0);

        //读取后台配置 白名单IP
        $system = \app\common\model\System::find(1);
        // IP黑名单检测
        if (is_safe_ip('',$system)) $this->apiError("禁止领取");
        //if (is_safe_ip('',$system)) $this->apiError("禁止领取",[],1);
        // 限制区域访问
        /*if($system['is_safe_area'] !=''){
            $is_safe_area = explode(',',$system['is_safe_area']);
            if(is_array($is_safe_area)){
                foreach ($is_safe_area as $key => $value) {
                    $ipAreaArr = get_ip_area($system['apis_ip_key'],'');
                    if(isset($ipAreaArr['province']) && $ipAreaArr['province'] == $value)
                        $this->apiError("当前区域已被限制访问");
                    if(isset($ipAreaArr['city']) && $ipAreaArr['city'] == $value)
                        $this->apiError("当前区域已被限制访问");
                    if(isset($ipAreaArr['district']) && $ipAreaArr['district'] == $value)
                        $this->apiError("当前区域已被限制访问");
                }
            }
        }*/
        // ip库  只能匹配到市级别。
        if($system['is_safe_area'] !=''){
            $is_safe_area = explode(',',$system['is_safe_area']);
            if(is_array($is_safe_area)){
                $ip = get_client_ip();
                $xdb = './ip2region/ip2region.xdb';
                //try {
                    // 加载整个 xdb 到内存。
                    $cBuff = XdbSearcher::loadContentFromFile($xdb);
                    if (null === $cBuff) {
                        throw new \RuntimeException("failed to load content buffer from '$xdb'");
                    }
                    // 使用全局的 cBuff 创建带完全基于内存的查询对象。
                    $searcher = XdbSearcher::newWithBuffer($cBuff);

                    foreach ($is_safe_area as $key => $value) {
                        // 查询
                        $region = $searcher->search($ip);
                        $regionArr = explode('|',$region);
                        if(in_array($value,$regionArr))
                            $this->apiError("当前区域已被限制访问");
                    }
                //} catch (\Exception $e) {
                //    $this->apiError("区域获取错误".$e->getMessage());
                //}
            }
        }
        
        // 限制时段内可领取
        if($system['is_effective_start'] !=0 && $system['is_effective_end'] !=0){
            if(!is_effective_time($system['is_effective_start'],$system['is_effective_end'])){
                $this->apiError("当前时段无法领取");
            }
        }

        // 获取用户信息
        $memInfo = $this->Users::find($userid);
        if (!$memInfo) $this->apiError("用户不存在");

        if ($memInfo->auth_status!=1)  $this->apiError('您还没有认证！暂时无法领取！');

        // 2023-03-22 桃花分小于1000不允许领取消费券
        //if($memInfo->credit_score > 0 && $memInfo->credit_score < 1000) $this->apiError("当前用户无法领取");

        // 限制领取间隔
        if($system['message_send_mail']==1){
            /// 检查用户ID领取间隔 规则：N 秒之内超过2条 则禁止刷票
            $latestComment = Db::name('CouponIssueUser')
                -> where('uid','=',$memInfo->id)
                -> where('create_time','>',time()-$system['is_interval_time'])
                -> order('create_time','desc')
                -> count();
            if ($latestComment >= 2) {
                $this->apiError("操作过于频繁");
            }
        }
        
        // 根据后台设置的基数 领取时直接毙掉部分用户
        // odds = 概率 后台设置  总基数(概率=100)
        if($system['is_random_number_extend'] > 0) {
            $msgArr = array(
                '0' => array('id'=>1,'msg'=>'可以领取','odds'=>(100 - $system['is_random_number_extend'])),
                '1' => array('id'=>2,'msg'=>'抢券中','odds'=>$system['is_random_number_extend']),
            );
            foreach ($msgArr as $key => $val) {
                $arr[$val['id']] = $val['odds'];
            }
            $rid = get_rand_msg($arr); // 根据后台设置的概率获取对应的提醒

            // 结果等于2的话 就是不允许领取  抛出提示语
            if($rid==2) $this->apiError($msgArr[$rid-1]['msg'],[],998);
        }
        // 根据后台设置的概率进行领取  
        // odds = 概率 后台设置  总基数(概率=100)
        if($system['is_random_number'] > 0) {
            $msgArr = array(
                '0' => array('id'=>1,'msg'=>'可以领取','odds'=>(100 - $system['is_random_number'])),
                '1' => array('id'=>2,'msg'=>'排队中','odds'=>$system['is_random_number']),
            );
            foreach ($msgArr as $key => $val) {
                $arr[$val['id']] = $val['odds'];
            }
            $rid = get_rand_msg($arr); // 根据后台设置的概率获取对应的提醒

            // 结果等于2的话 就是不允许领取  抛出提示语
            if($rid==2) $this->apiError($msgArr[$rid-1]['msg'],[],2);
        }

        if (!$couponId || !is_numeric($couponId)) $this->apiError("参数错误");

        return $this->issueUserCoupon($couponId, $memInfo);
    }

    // 数据校验&&消费券领取动作
    private function issueUserCoupon($id, $user)
    {
        $issueCouponInfo = $this->CouponIssue->getInfo((int)$id);

        if($issueCouponInfo->coupon_type==3){
            $this->apiError('禁止领取团体券');
        }

        if($issueCouponInfo->is_get==0){
            $this->apiError($issueCouponInfo->tips);
        }

        //领取人群 1=全部  2=本地[6108、6127] 3=外地。判断用户idcard的前四位为本地或者外地
        $fourIdCard = substr($user->idcard, 0, 4);
        $receive_crowd_arr = [1, 3];//默认全部和外地人可领
        if (in_array($fourIdCard, [6108, 6127])) {
            //本地，则全部和本地人可领
            $receive_crowd_arr = [1, 2];
        }
        if (!in_array($issueCouponInfo['receive_crowd'], $receive_crowd_arr)) {
            $this->apiError('该消费券不支持该地区领取!');
        }

        $temp_total_count = 0;
        if($issueCouponInfo['limit_time'] == 1){
            $nowTime = time() - $issueCouponInfo['start_time'];
            if($nowTime <= 10){
                $temp_total_count = $issueCouponInfo['total_count'] - $issueCouponInfo['total_count'] * 0.5;
            }else if($nowTime <= 180){
                $temp_total_count = $issueCouponInfo['total_count'] - $issueCouponInfo['total_count'] * 0.8;
            }else if($nowTime <= 300){
                $temp_total_count = $issueCouponInfo['total_count'] - $issueCouponInfo['total_count'] * 0.95;
            }
        }
        //if (!$issueCouponInfo) $this->apiError("领取的优惠劵已领完或已过期");
        // 2023-03-02 需根据消费券信息 返回对应的提示
        if($issueCouponInfo['status'] ==0) $this->apiError('未开启消费券');
        if($issueCouponInfo['status'] ==-1) $this->apiError('无效消费券');
        if($issueCouponInfo['is_del'] ==1) $this->apiError('当前消费券已被删除');
        if($issueCouponInfo['limit_time']==1){
            if($issueCouponInfo['start_time'] > time()) $this->apiError('活动未开启');
            if($issueCouponInfo['end_time'] < time()) $this->apiError('已过领取时间');
        }
        if($issueCouponInfo['status'] ==2) $this->apiError('已领完');
        if($issueCouponInfo['remain_count'] <= $temp_total_count ) $this->apiError('库存不足,稍后再试');

        if($issueCouponInfo['is_limit_total']==1){
            $total = $this->CouponIssueUser->where(['uid' => $user->id, 'issue_coupon_id' => $id, 'issue_coupon_class_id' => $issueCouponInfo['cid']])->count();
            if($issueCouponInfo['limit_total'] <= $total){
                $this->apiError('已领取过该优惠劵!','',3);
            }
        }

        if($issueCouponInfo['cid'] == 6) {
            //判断用户是否领取超过1次
            $offlineCouponTotal = \app\common\model\CouponIssueUser::field('w.*,u.name')
                ->alias('w')
                ->leftJoin('coupon_issue ce', 'w.issue_coupon_id=ce.id')
                ->where('ce.cid', 6)
                ->where('w.uid', $user->id)
                ->count();
            if ($offlineCouponTotal !== 0) {
                $this->apiError('大礼包券每人限领1次！');
            }
        }
        
        //线上核销卷
        if ($issueCouponInfo['use_type'] == 1) {
            //判断规则是否满足
            $recordConditionStatus = $this->CouponIssue->getRecordConditionStatus($user->id, $id);
            if (!$recordConditionStatus['can_receive']) {
                $this->apiError('不满足打卡规则，领取失败！');
            }
        }

        if ($issueCouponInfo->remain_count <= 0 && !$issueCouponInfo->is_permanent){
            // 修改消费券状态
            Db::name('CouponIssue')->where('id',$id)->update(['status'=>2,'update_time'=>time()]);
            $this->apiError('抱歉消费券已经领取完了！');
        }

        $uid = $user->id;


        // 2023-03-04 经纬度增加
        $param = get_params();
        $longitude = $param['longitude'];
        $latitude  = $param['latitude'];

        // 事务操作
        Db::startTrans();
        // 库存校验
        $remain_count = $this->CouponIssue->remainCount((int)$id);
        if($remain_count[0]['remain_count'] <= 0 ) $this->apiError('已抢完');
        if($remain_count[0]['provide_count'] >= $issueCouponInfo['total_count']) $this->apiError('已领完');

        try {
            // 2023-03-10 根据不同类型计算券的到期时间
            $expire_time_count = 0;
            switch ($issueCouponInfo['is_permanent']) {
                case 1:
                    // 永久
                    $expire_time_count = 4070880000; // 2099-01-01
                    break;
                case 2:
                    // 期限
                    $expire_time_count = $issueCouponInfo['coupon_time_end'];
                    break;
                case 3:
                    // 按天$day = 7;
                    $day_time = $issueCouponInfo['day'];
                    $expire_time_count = strtotime('+'.$day_time.' day');
                    break;
                default:
                    // code...
                    break;
            }
            
            // 领取存储的数据
            $saveData = [
                'uid' => $uid, 
                'issue_coupon_id' => $id, 
                'issue_coupon_class_id' => $issueCouponInfo['cid'], 
                'create_time'   => time(),
                'expire_time'   => $expire_time_count,
                'coupon_title'  => $issueCouponInfo['coupon_title'],
                'coupon_price'  => $issueCouponInfo['coupon_price'],
                'use_min_price' => $issueCouponInfo['use_min_price'],
                'coupon_create_time'  => strtotime($issueCouponInfo['create_time']),
                'time_start'    => $issueCouponInfo['start_time'],
                'time_end'      => $issueCouponInfo['end_time'],
                'is_fail'       => 1,
                'ips'           => Request::ip(),
                'longitude'     => $longitude,
                'latitude'      => $latitude,
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
                'issue_coupon_id' => $id, 
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
            $enstr_salt = md5(json_encode($saltData,JSON_UNESCAPED_UNICODE).$user->salt);
            // 生成领取二维码并修改
            //$qrcode_url = Qrcode($issueId);'qrcode_url'=>$qrcode_url,
            Db::name('CouponIssueUser')
            ->where('id',$issueId)
            ->update(['enstr_salt'=>$enstr_salt]);
            // 消费券剩余领取数量 - 1  total_count > 0证明限制总量
            /*$cccInfo = \app\common\model\CouponIssue::find($id);

            $nnn = new \app\common\model\CouponIssue();
            $nnn->beforeUpdate($cccInfo);
            Db::name('CouponIssue')
            ->where('id',$id)
            ->dec('remain_count')
            ->update();*/
            $rs = Db::name('CouponIssue')
            ->where('id',$id)
            ->whereColumn('provide_count','<=','total_count')
            ->where('remain_count','>',0)
            ->dec('remain_count')
            ->update();
            if($rs <= 0){
                Db::rollback();
                //throw new \think\Exception("领取失败".$e->getMessage());
                $this->apiError('领取失败');
            }
            // 提交事务
            Db::commit();
            //$this->apiSuccess('领取成功','data success');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            //throw new \think\Exception("领取失败".$e->getMessage());
            $this->apiError('领取失败');
        }
        $this->apiSuccess('领取成功');
    }

    public function detail()
    {
        $couponId = Request::param('couponId/d',0);
        $uid      = Request::param('userid/d',0);

        // 获取消费券分类
        $CouponIssue = $this->CouponIssue::where('id',$couponId)
            ->with(['couponClass'])
            ->find();

        if(!$CouponIssue){
            $this->apiError('请求错误');
        }

        // if (empty($uid)) {
        //     $this->apiError('请求错误');
        // }

        if($CouponIssue->limit_time == 0) $CouponIssue->tips = '领取时间：不限时';
        if($CouponIssue->limit_time == 1) $CouponIssue->tips = '领取时间：'.date('Y年m月d日 H:i',$CouponIssue->start_time).'至'.date('m月d日 H:i',$CouponIssue->end_time);

        if($CouponIssue->is_permanent == 1) $CouponIssue->tips_extend = '有效期：永久有效';
        if($CouponIssue->is_permanent == 2) $CouponIssue->tips_extend = '有效期：消费券需在'.date('Y年m月d日 H:i',$CouponIssue->coupon_time_end).'前使用';
        if($CouponIssue->is_permanent == 3) $CouponIssue->tips_extend = '有效期：自领取之日起'.$CouponIssue->day.'日内有效';

        //消费卷领取规则处理
        if ($CouponIssue->use_type == 1) {
            //线上核销
            $recordConditionStatus = $this->CouponIssue->getRecordConditionStatus($uid, $couponId);
            $condition          = $recordConditionStatus['condition'];//无领取规则
            $user_record_data   = $recordConditionStatus['user_record_data'];//用户打卡数据
            //$can_receive        = $recordConditionStatus['can_receive'];//默认卷可领
            $can_receive = true; // 由于打卡后回来需要领券，默认可领取
            
        } else {
            //线下核销
            $condition = "";//无领取规则
            $user_record_data = "";//无领取规则
            $can_receive = true;//默认卷可领
        }
        $CouponIssue->receive_condition = $condition;//领卷规则列表
        $CouponIssue->user_record_data = $user_record_data;//用户打卡数据
        $CouponIssue->can_receive = $can_receive;//打卡是否满足领卷规则

        $this->apiSuccess('查询成功',$CouponIssue);
    }

    // 线路列表 通用时 返回运营创建的所有线路
    public function line_list()
    {
        $map    = [];//查询条件
        $couponId    = Request::param('couponId/d',0); // 消费券ID
        $flag        = Request::param('flag/d',0);   // 获取类别
        $limit  = Request::param('limit/d', 10);  // 分页 每页展示几条
        $page   = Request::param('page/d', 1);    // 分页 第几页
        if(!$couponId) $this->apiError('参数错误');

        // 查询消费券信息
        if($flag==1){
            $couponIssue = $this->CouponIssue::where('id',$couponId)
            ->find()->toArray();
        }

        $where = [];

        // 条件
        if(@$couponIssue['class_id']==3){
            switch ($couponIssue['type']) {
                case 1:
                    $where[] = ['flag','=',1];
                    break;
                case 2:
                    $where[] = ['category_id','=',$couponIssue['category_id']];
                    break;
                case 3:
                    $where[] = ['id','in',explode(',',$couponIssue['product_id'])];
                    break;
                default:
                    // code...
                    break;
            }
        }else{
            // 通用：运营创建的
            $where[] = ['flag','=',$flag];
            if($flag==2){
                $where[] = ['mid','=',$couponId];
            }
        }

        $list = \app\common\model\Line::where('status', 1)
            ->where('tourism_status', 1)
            ->where('delete_time',0)
            ->where($where)
            ->when($page != 0, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })
            ->order('access_count desc')->select();
        $this->apiSuccess('查询成功',$list);
    }

    // 线路列表详情
    public function line_detail()
    {
        $line_id    = Request::param('line_id/d',0); // 消费券ID
        if(!$line_id) $this->apiError('参数错误');

        $detail = \app\common\model\Line::where('id', $line_id)->with(['lineCategory'])->find();
        Db::name('line')->where('id', $line_id)
        ->inc('access_count',1)
        ->update();
        // 每次访问 线路访问次数+1
        
        $this->apiSuccess('查询成功',$detail);
    }

    // 查询消费券适用于商家
    public function applicabletoV2()
    {
        $param = get_params();
        if(!isset($param['id']) || $param['id']==0){
            $this->apiError('消费券ID错误');
        }
        $cInfo = $this->CouponIssue::where('id',$param['id'])
            ->find();
        if(!$cInfo){
            $this->apiError('未找到消费券信息');
        }
        if(!isset($param['latitude']) || !isset($param['longitude']) || $param['latitude']=='' || $param['longitude']==''){
            $this->apiError('经纬度异常');
        }

        $where = 'status = 1';


        // 全部商家
        if($cInfo['use_store']==1){
            $where .= '';
            // 对应的某个分类下的商家
        }else if(in_array($cInfo['use_store'],[2,3,4,5,6,7]) && ($cInfo['use_stroe_id']==0 || $cInfo['use_stroe_id']=='')){
            $where .= ' and class_id = '.$cInfo['use_store'];
        }else{
            // 指定的商家
            $where .= ' and id in ('.$cInfo['use_stroe_id'].')';
        }

        $latitude = $param["latitude"];
        $longitude = $param["longitude"];
        $SQRT       = 'SQRT(
                                    POW( SIN( PI()*( '.$latitude.' - latitude )/ 360 ), 2 )+ COS( PI()* 29.504164 / 180 )* COS( '.$latitude.' * PI()/ 180 )* POW( SIN( PI()*( '.$longitude.' - longitude )/ 360 ), 2 ))';
        // 商家信息
        $sql = "SELECT
                    id,status,nickname,image,mobile,do_business_time,address,content,longitude,latitude,class_id,distance
                FROM
                    (
                    SELECT
                        *,
                        round((
                                2 * 6378.137 * ASIN($SQRT)) * 1000 
                        ) AS distance 
                    FROM
                        tp_seller
                    ) a 
                WHERE ".$where."
                ORDER BY
                    distance ASC limit 1";
        $list = Db::query($sql);

        if(!empty($list)){
            foreach ($list as $key => $value) {
                // 2023-03-18 经纬度未获取到返回 0
                $list[$key]['distance'] = 0;
                if($latitude!=1)
                    $list[$key]['distance'] = $value['distance'] / 1000;
            }
        }
        $this->apiSuccess('请求成功',$list);
    }

    // 查询消费券适用于商家
    public function applicableto()
    {
        $param = get_params();
        if(!isset($param['id']) || $param['id']==0){
            $this->apiError('消费券ID错误');
        }
        $cInfo = $this->CouponIssue::where('id',$param['id'])
            ->find();
        if(!$cInfo){
            $this->apiError('未找到消费券信息');
        }
        if(!isset($param['latitude']) || !isset($param['longitude']) || $param['latitude']=='' || $param['longitude']==''){
            $this->apiError('经纬度异常');
        }

        if(!isset($param['page']) || !isset($param['limit']) || $param['page']=='' || $param['limit']==''){
            $this->apiError('分页参数异常');
        }

        $where = 'status = 1';

        if(isset($param['keyword']) && $param['keyword'] !='')
            $where .= " and nickname like '%".$param['keyword']."%'";

        // 全部商家
        if($cInfo['use_store']==1){
            $where .= '';
            // 对应的某个分类下的商家
        }else if(in_array($cInfo['use_store'],[2,3,4,5,6,7]) && ($cInfo['use_stroe_id']==0 || $cInfo['use_stroe_id']=='')){
            $where .= ' and class_id = '.$cInfo['use_store'];
        }else{
            // 指定的商家
            $where .= ' and id in ('.$cInfo['use_stroe_id'].')';
        }

        $latitude = $param["latitude"];
        $longitude = $param["longitude"];
        $SQRT       = 'SQRT(
                                    POW( SIN( PI()*( '.$latitude.' - latitude )/ 360 ), 2 )+ COS( PI()* 29.504164 / 180 )* COS( '.$latitude.' * PI()/ 180 )* POW( SIN( PI()*( '.$longitude.' - longitude )/ 360 ), 2 ))';
        // 商家信息
        $sql = "SELECT
                    id,status,nickname,image,mobile,do_business_time,address,content,longitude,latitude,class_id,distance
                FROM
                    (
                    SELECT
                        *,
                        round((
                                2 * 6378.137 * ASIN($SQRT)) * 1000 
                        ) AS distance 
                    FROM
                        tp_seller
                    ) a 
                WHERE ".$where."
                ORDER BY
                    distance ASC limit ".$param['page'].",".$param['limit']."";
        $list = Db::query($sql);

        if(!empty($list)){
            foreach ($list as $key => $value) {
                // 2023-03-18 经纬度未获取到返回 0
                $list[$key]['distance'] = 0;
                if($latitude!=1)
                    $list[$key]['distance'] = $value['distance'] / 1000;
            }
        }
        $this->apiSuccess('请求成功',$list);
    }

    /**
     * 根据分类获取消费券列表
     */
    public function list()
    {
        $map      = [];//查询条件
        $cid = Request::param('cid/d',0);
        $uid = Request::param('userid/d',0);
        $limit  = Request::param('limit/d', 10);      // 分页 每页展示几条
        $page   = Request::param('page/d', 1);        // 分页 第几页
        //  || !$uid
        if(!$cid) {
            $this->apiError('参数错误！');
        }
        $map[] = ['cid','=',$cid];

        // 获取用户信息
        // $idCardWhere = ['receive_crowd', '=', 1];
        // if(!empty($uid)) {
        //     $uInfo = \app\common\model\Users::find($uid);
        //     if ($uInfo && ($uInfo['status'] == 1) && !empty($uInfo['idcard'])) {
        //         //领取人群 1=全部  2=本地[6108、6127] 3=外地。判断用户idcard的前四位为本地或者外地
        //         $fourIdCard = substr($uInfo['idcard'], 0, 4);
        //         $receive_crowd_arr = [1, 3];//默认全部和外地人可领
        //         if (in_array($fourIdCard, [6108, 6127])) {
        //             //本地，则全部和本地人可领
        //             $receive_crowd_arr = [1, 2];
        //         }
        //         $idCardWhere = ['receive_crowd', 'in', $receive_crowd_arr];
        //     }
        // }
        // $map[] = $idCardWhere;

        $list = $this->CouponIssue::where('status', 1)
            ->where('is_del', 0)
            //->where('remain_count > 0 OR is_permanent = 1')
            ->where(function ($query) {
                $query->where('receive_type', 1); // 消费券发送方式： 1=手动领取
            })
            /*->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('start_time', '<', time())->where('end_time', '>', time());
                })->whereOr(function ($query) {
                    $query->where('start_time', 0)->where('end_time', 0);
                });
            })*/
            /*->with(['used' => function ($query) use ($uid) {
                $query->where('uid', $uid);
            }])*/
            ->where('coupon_type','<>',3)
            ->where($map)
            ->when($page != 0, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->order('sort desc,id desc')->select()->toArray();
            
        //echo $this->CouponIssue::getLastSql();die;
        foreach ($list as &$v) {
            $v['coupon_price'] = floatval($v['coupon_price']);
            $v['use_min_price'] = floatval($v['use_min_price']);
            $v['is_use'] = $uid ? isset($v['used']) : false;
            if ($v['coupon_time_end']) {
                $v['coupon_time_start'] = date('Y/m/d', $v['coupon_time_start']);
                $v['coupon_time_end'] = $v['coupon_time_end'] ? date('Y/m/d', $v['coupon_time_end']) : date('Y/m/d', time() + 86400);
            }
            if ($v['start_time']) {
                $v['start_time'] = date('Y/m/d', $v['start_time']);
                $v['end_time'] = date('Y/m/d', $v['end_time']);
            }
        }
        $this->apiSuccess('查询成功',$list);
    }

    private function reportError($title,$param) {
        $param['title'] = $title;
        Event::trigger('ErrorReportEvent', $param);
        $this->apiError($title);
    }

    /**
     * 扫码核销消费券
     * @param Request $request
     * @return mixed
     */
    public function writeoff()
    {
        $param = get_params();
        if (!Request()->isPost()) {
            $this->reportError('禁止访问',$param);
        }

        $userid = Request::param('userid/d',0);                // 用户ID
        $mid    = Request::param('mid/d',0);                   // 商户ID
        $coupon_issue_user_id = Request::param('coupon_issue_user_id/d',0);  // 领取记录ID
        $use_min_price = Request::param('use_min_price/d',0);   // 消费金额
        $orderid       = Request::param('orderid/d',0);
        $qrcode_url    = Request::param('qrcode_url/s',0);

        // 2022-10-19 用户核销时的经纬度
        $longitude = $param['longitude'];
        $latitude  = $param['latitude'];

        if($longitude == 1 && $latitude == 1){
            $this->reportError('当前游客未开启定位',$param);
        }

        $verifier_realtime_longitude = $param['vr_longitude'];
        $verifier_realtime_latitude  = $param['vr_latitude'];

        if($verifier_realtime_longitude == 1 && $verifier_realtime_latitude == 1){
            $this->reportError('当前核验人未开启定位',$param);
        }
        
        // 检查基础参数
        if(!$userid){
            $this->reportError('没有登录',$param);
        }
        if(!$mid){
            $this->reportError('商户信息错误',$param);
        }
        if(!$coupon_issue_user_id){
            $this->reportError('消费券不存在',$param);
        }

        // 检查核销人员
        $uInfo = $this->Users::find($userid);// 获取用户信息
        if(!$uInfo){
            $this->reportError('用户不存在',$param);
        }

        if($uInfo['status']!=1){
            $this->reportError('用户已被禁用',$param);
        }

        // 校验核验人
        $mvInfo = \app\common\model\MerchantVerifier::where('uid',$userid)->where('mid',$mid)->where("type","coupon")->find();// 获取用户信息
        if(!$mvInfo){
            $this->reportError('核验人不存在',$param);
        }

        if($mvInfo['status']!=1){
            $this->reportError('当前核验人已被禁用',$param);
        }

        // 检查商户
        $mInfo = $this->Seller::find($mid);// 获取商户信息
        if(!$mInfo){
            $this->reportError('商户不存在',$param);
        }
        if($mInfo['status']!=1){
            $this->reportError('商户已被禁用',$param);
        }

        // 2023-07-10 增加商户核销散客消费券时位置三要素（核销点位置、核验人位置、游客位置）比对，设置核销成功的有效范围为半径为200m
        // 核验人实时位置
        

        // 检验核验人与用户之间的距离
        $distance = calculateDistance($latitude, $longitude, $verifier_realtime_latitude, $verifier_realtime_longitude);
        if(round($distance, 2) > 400) {
            $this->reportError('用户与核销人位置太远,距离：'.round($distance, 2).'米',$param);
        }
        // 查询核验人所在点
        $poiInfo = \app\common\model\MerchantVerificationPoints::where('mid',$mid)->where('status',1)->select();
        $dist_verif_pt_user = []; // 所有核销点与用户的距离计算
        $dist_verif_pt      = []; // 所有核销点与核销人的距离计算
        foreach ($poiInfo as $key => $value) {
            // 计算用户与核销点位置之间的距离
            $dist_verif_pt_user[$value['id']][] = calculateDistance($latitude, $longitude, $value['latitude'], $value['longitude']);
            // 计算核验人与核销点位置之间的距离
            $dist_verif_pt[$value['id']][] = calculateDistance($verifier_realtime_latitude, $verifier_realtime_longitude, $value['latitude'], $value['longitude']);        
        }

        #========================================================================================
        $min_user_distance = ''; // 最小距离
        $min_user_key = '';      // 最小距离所属ID
        // 取用户与所有点距离最近的一个点
        foreach ($dist_verif_pt_user as $key => $subArray) {
            $currentMin = min($subArray);
            if ($min_user_distance === '' || $currentMin < $min_user_distance) {
                $min_user_distance = $currentMin;
                $min_user_key = $key;
            }
        }

        if($min_user_key==''){
            $this->reportError('用户与核销点位置异常',$param);
        }
        if(round($min_user_distance, 2) > 200) {
            $this->reportError('用户距离核销点太远'.round($min_user_distance, 2).'米',$param);
        }
        
        #========================================================================================
        $min_verification_distance = ''; // 最小距离
        $min_verification_key = '';      // 最小距离所属ID
        // 取核验人与所有点距离最近的一个点
        foreach ($dist_verif_pt as $key => $subArray) {
            $currentMin = min($subArray);
            if ($min_verification_distance === '' || $currentMin < $min_verification_distance) {
                $min_verification_distance = $currentMin;
                $min_verification_key = $key;
            }
        }
        if(round($min_verification_distance, 2) > 200) {
            $this->reportError('核验人距离核销点太远'.round($min_verification_distance, 2).'米',$param);
        }
        // 获取最近的点的经纬度
        $min_points = \app\common\model\MerchantVerificationPoints::where('id',$min_verification_key)->find();
        #========================================================================================

        // 2023-06-30 用户核销时经纬度与商户位置的距离计算、并校验
        /*$distance = calculateDistance($latitude, $longitude, $mInfo['latitude'], $mInfo['longitude']);
        if(round($distance, 2) > $mInfo['verification_scope']) {
            $this->apiError('已超出可核销范围');
        }*/

        // 检查领取记录
        $cInfo = $this->CouponIssueUser::find($coupon_issue_user_id);
        if(!$cInfo){
            $this->reportError('该核销码异常',$param);// 领取记录不存在
        }
        if($cInfo['status']==1){
            $this->reportError('该消费券已使用',$param);
        }
        if($cInfo['status']==2){
            $this->reportError('该消费券已过期',$param);
        }
        if($cInfo['is_fail']==0){
            $this->reportError('该消费券无效',$param);
        }

        if($cInfo['uid']==$userid){
            $this->reportError('核销异常',$param);
        }



        // 检查加密串是否相等 不相等 直接报二维码无效
        if ($cInfo['qrcode_url'] != $qrcode_url){
            $this->reportError('二维码已失效',$param);
        }

        if($cInfo['code_time_expire']<time()){
            $this->reportError('二维码已过期',$param);
        }

        // 检查消费券信息
        $iInfo = $this->CouponIssue::find($cInfo['issue_coupon_id']);
        if(!$iInfo){
            // 消费券在后台不存在
            $this->reportError('该消费券已无法使用',$param);
        }
        // if($cInfo['issue_coupon_id']==27){
        //     $this->apiError('系统繁忙');
        // }
        /*if($iInfo['status']=='-1'){
            $this->apiError('该消费券已无法使用'); // 消费券在后台设置为无效
        }*/
        if($iInfo['is_threshold']==1 && ($iInfo['use_min_price'] <= $use_min_price)){
            $this->reportError('最低消费需要满'.$iInfo['use_min_price'].'才可使用',$param);
        }
        if($iInfo['is_permanent']==2){
            if($iInfo['coupon_time_start'] > time()){
                $this->reportError('该消费券还未到使用时段',$param);
            }
            if($iInfo['coupon_time_end'] < time()){
                $this->reportError('该消费券已过使用时段',$param);
            }
        }
        // 2022-08-27 增加有效期天数 截至领取时间往后推N天
        if($iInfo['is_permanent']==3){
            $yxtime = strtotime($cInfo['create_time']) + $iInfo['day'] * 86400;
            if(time() > $yxtime){
                $this->reportError('该消费券已经过期',$param);
            }
        }
        $system = \app\common\model\System::find(1);
        // 禁止某些商户核销
        if($system['banned_seller'] !=''){
            $banned_seller = explode(',',$system['banned_seller']);
            if(is_array($banned_seller) && in_array($mid, $banned_seller)){
                $this->reportError("系统繁忙,请稍后再试",$param);
            }
        }

        // 消费类型
        switch ($iInfo['type']) {
            case 1:
                // code...
                break;
            case 2:
                // code...
                break;
            case 3:
                // code...
                break;
            default:
                // code...
                break;
        }
        // 门店
        if($iInfo['use_store']!=1){
            // 商户分类ID 与 消费券可使用的门店类型不一致
            if($mInfo['class_id'] != $iInfo['use_store']){
                $this->reportError('该消费券无法在该门店类型下使用',$param);
            }

            // 商户ID 不在消费券指定的门店内  即无法使用
            if($iInfo['use_stroe_id']){
                $use_stroe_id = explode(',',$iInfo['use_stroe_id']);
                if(!in_array($mInfo['id'],$use_stroe_id)){
                    $this->reportError('该消费券无法在该门店下使用',$param);
                }
            }
        }

        // 获取商户订单信息
        // ...

        // 事务操作
        Db::startTrans();
        try {
            // 记录核销操作
            $data['orderid']                = $orderid;
            $data['create_time']            = time();
            $data['coupon_issue_user_id']   = $coupon_issue_user_id;
            $data['mid']                    = $mid;
            $data['uuno']                   = $iInfo->uuno;
            $data['coupon_issue_id']        = $iInfo->id;
            $data['coupon_title']           = $cInfo->coupon_title;
            $data['coupon_price']           = $cInfo->coupon_price;
            $data['use_min_price']          = $cInfo->use_min_price;
            $data['time_start']             = $cInfo->time_start;
            $data['time_end']               = $cInfo->time_end;
            $data['qrcode_url']             = $cInfo->qrcode_url;
            $data['userid']                 = $userid;
            // 用户经纬度
            $data['uw_longitude']           = $longitude;
            $data['uw_latitude']            = $latitude;
            // 核销点的经纬度
            $data['poi_longitude']           = $min_points->longitude;
            $data['poi_latitude']            = $min_points->latitude;
            // 核验人的经纬度
            $data['he_longitude']           = $verifier_realtime_longitude;
            $data['he_latitude']            = $verifier_realtime_latitude;
            $data['uid']                    = $cInfo['uid'];
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
            $this->apiSuccess('核销失败',$e->getMessage());
        }
        $this->apiSuccess('核销成功','data success');
    }

    /**
     * 核销列表
     * @param Request $request
     * @return mixed
     */
    public function writeofflog()
    {
        if (!Request()->isPost()) {
            $this->apiError('禁止访问！');
        }
        $userid = Request::param('userid/d',0);  // 用户ID
        $mid    = Request::param('mid/d',0);     // 商户ID
        $limit    = Request::param('limit/d', 10);      // 分页 每页展示几条
        $page     = Request::param('page/d', 1);        // 分页 第几页
        // 检查基础参数
        if(!$userid){
            $this->apiError('没有登录');
        }
        if(!$mid){
            $this->apiError('商户信息错误');
        }

        // 检查核销人员
        $uInfo = $this->Users::find($userid);// 获取用户信息
        if(!$uInfo){
            $this->apiError('用户不存在');
        }
        if($uInfo['status']!=1){
            $this->apiError('用户已被禁用');
        }

        // 检查商户
        $mInfo = $this->Seller::find($mid);// 获取商户信息
        if(!$mInfo){
            $this->apiError('商户不存在');
        }
        if($mInfo['status']!=1){
            $this->apiError('商户已被禁用');
        }

        $data = $this->WriteOff::where('userid',$userid)->where('mid',$mid)->when($page != 0, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->order('sort desc,id desc')->select()->toArray();
        $this->apiSuccess('查询成功',$data);
    }

    /**
     * 核销详情
     * @param Request $request
     * @return mixed
     */
    public function writeoffdetail()
    {
        if (!Request()->isPost()) {
            $this->apiError('禁止访问！');
        }
        $userid = Request::param('userid/d',0);  // 用户ID
        $mid    = Request::param('mid/d',0);     // 商户ID
        $id     = Request::param('id/d',0);      // 核销ID
        // 检查基础参数
        if(!$userid){
            $this->apiError('没有登录');
        }
        if(!$mid){
            $this->apiError('商户信息错误');
        }

        // 检查核销人员
        $uInfo = $this->Users::find($userid);// 获取用户信息
        if(!$uInfo){
            $this->apiError('用户不存在');
        }
        if($uInfo['status']!=1){
            $this->apiError('用户已被禁用');
        }

        // 检查商户
        $mInfo = $this->Seller::find($mid);// 获取商户信息
        if(!$mInfo){
            $this->apiError('商户不存在');
        }
        if($mInfo['status']!=1){
            $this->apiError('商户已被禁用');
        }

        $data = $this->WriteOff::where('id',$id)->find();
        if($data){
            $this->apiSuccess('查询成功',$data);
        }else{
            $this->apiError('数据异常');
        }
    }

    /**
     * 核销详情
     * @param Request $request
     * @return mixed
     */
    public function couponissueuser()
    {
        if (!Request()->isPost()) {
            $this->apiError('禁止访问！');
        }
        $userid = Request::param('userid/d',0);  // 用户ID
        $id     = Request::param('id/d',0);      // 领取ID
        // 检查基础参数
        if(!$userid){
            $this->apiError('没有登录');
        }
        if(!$id){
            $this->apiError('领取信息错误');
        }

        // 检查核销人员
        $uInfo = $this->Users::find($userid);// 获取用户信息
        if(!$uInfo){
            $this->apiError('用户不存在');
        }
        if($uInfo['status']!=1){
            $this->apiError('用户已被禁用');
        }

        $data = $this->CouponIssueUser::where('id',$id)->find();
        if($data){
            $this->apiSuccess('查询成功',$data);
        }else{
            $this->apiError('数据异常');
        }
    }

    /**
     * 领取ID换取消费券详情
     * @param Request $request
     * @return mixed
     */
    public function idtocoupon()
    {
        if (!Request()->isPost()) {
            $this->apiError('禁止访问！');
        }
        $id     = Request::param('cuid/d',0);      // 消费券领取记录ID
        if(!$id){
            $this->apiError('参数异常');
        }
        // 检查领取记录
        $issue = $this->CouponIssueUser::find($id);
        if(!$issue){
            $this->apiError('记录不存在');
        }

        // 查询消费券
        $cInfo = $this->CouponIssue::find($issue['issue_coupon_id']);
        if(!$cInfo){
            $this->apiError('消费券信息不存在');
        }
        // 返回核销信息
        $cInfo['writeoff'] = \app\common\model\WriteOff::where('coupon_issue_user_id',$id)->with('users')->find();

        //线上核销，返回收货信息
        if ($cInfo['use_type'] == 1) {
            $cInfo['delivery'] = [
                'delivery_user'     => $issue['delivery_user'],
                'delivery_phone'    => $issue['delivery_phone'],
                'delivery_address'  => $issue['delivery_address'],
                'tracking_number'   => $issue['tracking_number'],
            ];
        }

        $this->apiSuccess('查询成功',$cInfo);
    }

    /**
     * 领取加密串换取对称加密代码-用于检测二维码是否过期 || 生成二维码加密串内容
     * @param Request $request
     * @return mixed
     */
    public function encryptAES()
    {
        if (!Request()->isPost()) {
            $this->apiError('禁止访问！');
        }
        $id       = Request::param('id/d',0);   // 消费券领取记录ID
        $salt     = Request::param('salt/s',0); // 消费券领取记录加密串
        $uid      = Request::param('uid/d',0);   // 用户ID
        if(!$id || !$salt){
            $this->apiError('参数异常');
        }
        $system = \app\common\model\System::find(1);

        // 检查领取记录
        $issue = $this->CouponIssueUser::where(['id'=>$id,'enstr_salt'=>$salt,'uid'=>$uid])->find();
        if(!$issue){
            $this->apiError('数据异常，禁止访问');
        }

        // 检查当前领取记录是否核销
        $write_off_info = \app\common\model\WriteOff::where('coupon_issue_user_id',$id)->find();
        $write_off_status = $write_off_info ? 1 : 0;

        // 2023-03-01 返回当前用户的 姓名 身份证号
        $uInfo = $this->Users::field('name,idcard')->find($uid);
        $uInfo->idcard = substr($uInfo->idcard,0,4)."************".substr($uInfo->idcard,14,4);
        $len = mb_strlen($uInfo->name);
        switch ($len) {
            case 1:
                $uInfo->name = desensitize($uInfo->name,1,1,'*');
                break;
            case 2:
                $uInfo->name = desensitize($uInfo->name,1,1,'*');
                break;
            default:
                $uInfo->name = desensitize($uInfo->name,1,$len-2,'*');
                break;
        }
        $uInfo->idcard = desensitize($uInfo->idcard,4,12,'*');

        // 判断当前二维码内容是否过期
        if ($issue['code_time_expire'] < time()) {
            // 生成二维码内容
            $str = $issue['enstr_salt'];
            $key = $issue['id'];//set_salt();
            $qrcode_url = symencryption($str,$key);
            // 更新二维码内容
            $upData['qrcode_url'] = $qrcode_url;
            $upData['code_time_create'] =  time();
            $upData['code_time_expire'] =  $upData['code_time_create'] + $system['is_qrcode_number'];//60 * 5; // 过期时间5分钟
            $this->CouponIssueUser::where('id',$id)->data($upData)->update();
            $returnData['id']   = $id;
            $returnData['write_off_status'] = $write_off_status;
            $returnData['qrcode_url']   = $upData['qrcode_url'];
            $returnData['uinfo'] = $uInfo;
            $this->apiSuccess('success',$returnData);
        }else{
            $upData['write_off_status'] = $write_off_status;
            $upData['qrcode_url']       = $issue['qrcode_url'];
            $upData['id']               = $issue['id'];
            $upData['uinfo']            = $uInfo;
            $this->apiSuccess('success',$upData);
        }
    }

    /**
     * @api {post} /user/tour_write_off_list
     * @apiDescription  旅行团--核销记录列表
     */
    public function tourwriteofflog()
    {
        if (!Request()->isPost()) {
            $this->apiError('禁止访问！');
        }
        $userid = Request::param('userid/d',0);  // 用户ID
        $mid    = Request::param('mid/d',0);     // 商户ID
        $limit    = Request::param('limit/d', 10);      // 分页 每页展示几条
        $page     = Request::param('page/d', 1);        // 分页 第几页
        // 检查基础参数
        if(!$userid){
            $this->apiError('没有登录');
        }
        if(!$mid){
            $this->apiError('商户信息错误');
        }

        // 检查核销人员
        $uInfo = $this->Users::find($userid);// 获取用户信息
        if(!$uInfo){
            $this->apiError('用户不存在');
        }
        if($uInfo['status']!=1){
            $this->apiError('用户已被禁用');
        }

        // 检查商户
        $mInfo = $this->Seller::find($mid);// 获取商户信息
        if(!$mInfo){
            $this->apiError('商户不存在');
        }
        if($mInfo['status']!=1){
            $this->apiError('商户已被禁用');
        }

        $data = \app\common\model\TourWriteOff::where('userid',$userid)->where('mid',$mid)->when($page != 0, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->order('sort desc,id desc')->select()->toArray();
        $this->apiSuccess('查询成功',$data);
    }

    //获得该用户在该卷的打卡记录
    public function getUserCouponRecordList()
    {
        $uid      = Request::param('userid/d',0);
        $couponId = Request::param('couponId/d',0);

        // 获取消费券分类
        $CouponIssue = $this->CouponIssue::where('id',$couponId)
            ->with(['couponClass'])
            ->find();

        if(!$CouponIssue){
            $this->apiError('请求错误');
        }

        if (empty($uid)) {
            $this->apiError('请求错误');
        }

        $userRecordData = \app\common\model\SellerMarkQcUserRecord::field('id, seller_id, class_id, create_time')
            ->where('uid', $uid)
            ->where('coupon_id', $couponId)
            ->with(['Seller'])
            ->order('id desc')
            ->select();

        $this->apiSuccess('查询成功',$userRecordData);
    }
}
