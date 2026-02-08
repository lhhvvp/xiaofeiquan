<?php
/**
 * @desc   个人中心模块API
 * @author slomoo
 * @email  slomoo@aliyun.com
 */
declare (strict_types = 1);
namespace app\api\controller;

use AlicFeng\IdentityCard\IdentityCard;
use app\admin\validate\Users;
use app\api\BaseController;
use app\api\middleware\Auth;
use app\api\service\JwtAuth;
use app\common\model\UsersTourist as UsersTouristModel;
use think\facade\Db;
use think\facade\Request;
use think\facade\Validate;

class User extends BaseController
{
    /**
     * 控制器中间件 [不需要鉴权]
     * @var array
     */
	protected $middleware = [
    	Auth::class => ['except' => ['addguest','miniwxregister','getCertTypeList','auth_info']]
    ];
	
    /**user
     * @api {post} /user/index
     * @apiDescription  个人中心
     */
    public function index()
    {   
        $param = get_params();
        if(empty($param['uid'])){
            $this->apiError('用户ID不能为空！');
        }

        $uid = $param['uid'];
        // 获取用户信息
        $user = \app\common\model\Users::field('id,uuid,headimgurl,nickname,name,mobile,idcard,credit_score,credit_rating,update_credit,auth_status')
        ->where(['id'=>$param['uid']])
        ->with(['ismv' => function ($query) use ($uid) {
                $query->where('uid', $uid)->where('status',1);
            }])
        -> find();
        if (empty($user)) {
            $this->apiError('当前用户不存在');
        }

        // 隐藏手机号中间4位
        $user['mobile'] = substr_replace($user['mobile'],'****',3,4);

        $user['idcard'] = substr_replace($user['idcard'],'****',2,14);

        // 是否为导游
        $guide = \app\common\model\Guide::where(['uid'=>$uid])
        -> find();
        $user['guide'] = $guide ? $guide['id'] : false;

        // 检测当前用户是否拥有景区打卡任务
        $tourwriteoff = \app\common\model\TourWriteOff::where(['uid'=>$uid,'type'=>2])
        -> find();
        // 2022-08-30 检测当前用户是否有酒店打卡任务
        $tourhoteluserrecord = \app\common\model\TourHotelUserRecord::where(['uid'=>$uid])
        -> find();
        $user['is_clock'] = ($tourwriteoff || $tourhoteluserrecord) ? true : false;
        $this->apiSuccess('请求成功',$user);
    }

    /**
     * [auth_info 认证信息]
     * @return   [type]            [认证信息]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-06-05
     * @LastTime 2023-06-05
     * @version  [1.0.0]
     */
    public function auth_info()
    {   
        $param = get_params();
        if(empty($param['uid'])){
            $this->apiError('用户ID不能为空！');
        }

        $uid = $param['uid'];
        // 获取用户信息
        $user = \app\common\model\Users::field('name,mobile,idcard,auth_status')
        ->where(['id'=>$param['uid']])
        ->find();
        if (empty($user)) {
            $this->apiError('当前用户不存在');
        }
        $this->apiSuccess('请求成功',$user);
    }

    /**user
     * @api {post} /user/edit
     * @apiDescription  用户修改资料
     */
    public function edit()
    {
        $param = get_params();
        $param['update_time'] = time();

        if(isset($param['idcard'])){

            // 校验数据正确性
            if(!isCreditNo($param['idcard'])){ 
                $this->apiError('请输入正确的身份证号码');
            }

            # 获取周岁 | 
            $param['age'] = IdentityCard::age($param['idcard']);
            # 获取生日
            $param['birthday'] = IdentityCard::birthday($param['idcard']);
            # 获取性别 | {男为M | 女为F}
            $sex = IdentityCard::sex($param['idcard']);
            $param['sex'] = $sex=='M' ? 1 : 2;
            # 获取生肖
            $param['zodiac'] = IdentityCard::constellation($param['idcard']);
            # 获取星座
            $param['starsign'] = IdentityCard::star($param['idcard']);
            # 获取省份
            /*$province = Area::province($param['idcard'], $default='');
            # 获取省份
            $city = Area::city($param['idcard'], $default='');
            # 获取省份
            $area = Area::area($param['idcard'], $default='');*/

            $get_area_code_info = get_area_code_info($param['idcard']);

            $param['province'] = isset($get_area_code_info['province']) ? $get_area_code_info['province'] : '';
            $param['city']     = isset($get_area_code_info['city']) ? $get_area_code_info['city'] : '';
            $param['district'] = isset($get_area_code_info['district']) ? $get_area_code_info['district'] : '';

            $param['email_validated'] = 1; // 输入了身份证号表明实名认证
        }

        if(empty($param['id'])){
            $this->apiError('用户ID不能为空！');
        }

        if(isset($param['nickname'])){
            // 过滤特殊字符 & 处理XSS跨站攻击
            $param['nickname'] = @$param['nickname'] ? removeXSS(filterText($param['nickname'])) : '';

            if($param['nickname']==''){
                $this->apiError('请输入内容');
            }
        }

        if(isset($param['name'])){
            // 过滤特殊字符 & 处理XSS跨站攻击
            //$param['name'] = @$param['name'] ? removeXSS(filterText($param['name'])) : '';

            if(!checkNameFilter($param['name'])){
                $this->apiError('请输入正确的姓名！');
            }
        }

        if(isset($param['mobile'])){
            // 过滤特殊字符 & 处理XSS跨站攻击
            $param['name'] = @$param['name'] ? removeXSS(filterText($param['name'])) : '';

            if(!check_phone($param['mobile'])){
                $this->apiError('手机号错误');
            }
        }
        
        // 内容检测--待接入微信内容审核

        // 获取用户信息
        $user = Db::name('users')->field('name,nickname,headimgurl')->where(['id'=>$param['id']])->find();
        if (empty($user)) {
            $this->apiError('当前用户不存在');
        }

        $res = \app\common\model\Users::where(['id' => $param['id']])->strict(false)->field(true)->update($param);
        if ($res !== false) {
            $this->apiSuccess('保存成功',$res);
        } else {
            $this->apiError('保存失败');
        }
    }

    /**user
     * @api {post} /user/coupon_issue_user
     * @apiDescription  消费券--我的领取记录
     */
    public function coupon_issue_user()
    {
        $param = get_params();
        if(empty($param['uid'])){
            $this->apiError('参数异常');
        }
        // 获取用户信息
        $uInfo = \app\common\model\Users::find($param['uid']);
        if(!$uInfo){
            $this->apiError('用户不存在');
        }
        if($uInfo['status']!=1){
            $this->apiError('用户已被禁用');
        } 

        // 检测过期票券
        \app\common\model\CouponIssueUser::selectLog($param['uid']);

        $where = [];
        $where[] = ['uid','=',$param['uid']];

        if(isset($param['status']) && $param['status']!=''){
            $where[] = ['status','=',$param['status']];
        }
        $rows = empty($param['limit']) ? 10 : $param['limit'];
        $list = \app\common\model\CouponIssueUser::where($where)->with(['couponIssue'])->order('id desc')
        -> paginate($rows, false, ['query' => $param]);
        $this->apiSuccess('请求成功',$list);
    }

    /**user
     * @api {post} /user/collection
     * @apiDescription  用户收藏商家列表
     */
    public function collection()
    {
        $param = get_params();
        if(empty($param['uid'])){
            $this->apiError('参数异常');
        }

        if(!isset($param['latitude']) || !isset($param['longitude'])){
            $this->apiError('参数异常');
        }

        if(!isset($param['page']) || !isset($param['limit'])){
            $this->apiError('参数异常');
        }

        // 获取用户信息
        $uInfo = \app\common\model\Users::find($param['uid']);
        if(!$uInfo){
            $this->apiError('用户不存在');
        }
        if($uInfo['status']!=1){
            $this->apiError('用户已被禁用');
        }

        // 查询收藏的数据
        $uInfo = \app\common\model\Collection::where(['uid'=>$param['uid']])->column('mid');
        if(!$uInfo){
            $this->apiError('没有收藏任何商家');
        }
        $ids = implode(',',$uInfo);
        $where = 'id in ('.$ids.')';
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
                $list[$key]['distance'] = $value['distance'] / 1000;
            }
        }
        $this->apiSuccess('请求成功',$list);
    }

    /**user
     * @api {post} /user/collection_action
     * @apiDescription  取消&收藏
     */
    public function collection_action()
    {
        $param = get_params();
        if(empty($param['uid']) || empty($param['mid']) || empty($param['action'])){
            $this->apiError('参数异常');
        }

        if($param['action']=='add'){
            $cInfo = \app\common\model\Collection::where('uid',$param['uid'])->where('mid',$param['mid'])->find();
            if(!$cInfo){
                // 检查是否存在
                $inData['uid'] = $param['uid'];
                $inData['mid'] = $param['mid'];
                $inData['create_time'] = time();
                Db::name('Collection')->strict(false)->field(true)->insertGetId($inData);
            }
            $this->apiSuccess('收藏成功','data success');
        }

        if($param['action']=='del'){
            // 检查是否存在
            Db::name('Collection')->where('uid',$param['uid'])->where('mid',$param['mid'])->delete();
            $this->apiSuccess('取消成功');
        }
    }

    /**user
     * @api {post} /user/guide_tour
     * @apiDescription  导游-我的团
     */
    public function guide_tour()
    {
        $param = get_params();
        if(empty($param['uid'])){
            $this->apiError('用户ID不能为空！');
        }

        $uid = $param['uid'];
        // 获取导游信息
        $tour = \app\common\model\Guide::field('id,create_time,update_time,sort,status,name,mobile,certificates,tid,mid,uid')
        ->where(['uid'=>$uid])
        ->where('status',1)
        ->with(['tour'])
        ->order('create_time desc')
        ->select();
        foreach ($tour as $key => $value) {
            $tour[$key]['tourist'] = \app\common\model\Tourist::field('id,create_time,update_time,sort,status,name,mobile,tid,mid,uid,contract,insurance,tour_receive_time,numbers,tour_writeoff_time')
            ->where('tid',$value['tid'])
            ->select();
        }
        $this->apiSuccess('请求成功',$tour);
    }

    /**user
     * @api {post} /user/hotel_tour
     * @apiDescription  导游-我的团-酒店打卡记录
     */
    public function hotel_tour()
    {
        $param = get_params();
        if(empty($param['tid'])){
            $this->apiError('团ID不能为空！');
        }

        $tid = $param['tid'];
        // 根据团获取下面所有酒店打卡记录
        $tour = \app\common\model\TourHotelSign::where(['tid'=>$tid])
        ->order('create_time desc')
        ->select();
        foreach ($tour as $key => $value) {
            $tour[$key]['tour_hotel_user_record'] = \app\common\model\TourHotelUserRecord::where('sign_id',$value['id'])->with(['users'])->select();
        }
        $this->apiSuccess('请求成功',$tour);
    }

    /**user
     * @api {post} /user/tour_coupon
     * @apiDescription  导游-我的团-团体券
     */
    public function tour_coupon()
    {
        $param = get_params();
        if(empty($param['tid'])){
            $this->apiError('团ID不能为空！');
        }

        $tid = $param['tid'];
        // 获取团体券信息
        $tour = \app\common\model\TourCouponGroup::where(['tid'=>$tid])
        ->where('cid','<>',3) // 旅行券不用展示
        ->where('is_receive',1)
        ->with(['tour','couponIssue','couponClass'])
        ->select();
        $this->apiSuccess('请求成功',$tour);
    }

    /**user
     * @api {post} /user/tour_coupon_group
     * @apiDescription  导游-我的团-团体券-详情
     */
    public function tour_coupon_group()
    {
        $param = get_params();
        if(empty($param['id'])){
            $this->apiError('ID不能为空！');
        }

        // 获取团体券信息
        $tour = \app\common\model\TourCouponGroup::where(['id'=>$param['id']])
        ->with(['tour','couponIssue','couponClass'])
        ->find();

        // 查询团所属旅行社
        $tour['seller'] = \app\common\model\Seller::where('id',$tour['tour']['mid'])->field('nickname,image')->find();

        // 获取核销记录
        $tour['tour_write_off'] = \app\common\model\TourWriteOff::where(['tour_coupon_group_id'=>$param['id']])
        ->with(['user'])
        ->select();

        // 获取团下面所有游客数量
        $tour['tourist'] = \app\common\model\Tourist::field('id,create_time,update_time,sort,status,name,mobile,tid,mid,uid,contract,insurance,tour_receive_time,tour_price,numbers,tour_writeoff_time')->where(['tid'=>$tour['tid']])
        ->select();
        $this->apiSuccess('请求成功',$tour);
    }

    /**
     * 团体券核销展示二维码 - 用于检测二维码是否过期 || 生成二维码加密串内容
     * @param Request $request
     * @return mixed
     */
    public function encryptAES()
    {
        if (!Request()->isPost()) {
            $this->apiError('禁止访问！');
        }
        $id       = Request::param('id/d',0);   // 团体券主键ID
        $salt     = Request::param('salt/s',0); // 消费券领取记录加密串
        if(!$id || !$salt){
            $this->apiError('参数异常');
        }
        // 检查领取记录
        $issue = \app\common\model\TourCouponGroup::where(['id'=>$id,'enstr_salt'=>$salt])->find();
        if(!$issue){
            $this->apiError('数据异常，禁止访问');
        }
        // 检查当前领取记录是否核销
        $write_off_status = $issue['status']==1 ? 1 : 0;

        // 判断当前二维码内容是否过期
        if ($issue['code_time_expire'] < time()) {
            // 生成二维码内容
            $str = $issue['enstr_salt'];
            $key = $issue['id'];//set_salt();
            $qrcode_url = symencryption($str,$key);
            // 更新二维码内容
            $upData['qrcode_url'] = $qrcode_url;
            $upData['code_time_create'] =  time();
            $upData['code_time_expire'] =  $upData['code_time_create'] + 60 * 5; // 过期时间5分钟
            \app\common\model\TourCouponGroup::where('id',$id)->data($upData)->update();
            $returnData['id']   = $id;
            $returnData['qrcode_url']   = $upData['qrcode_url'];
            $returnData['write_off_status'] = $write_off_status;
            $this->apiSuccess('success',$returnData);
        }else{
            $upData['qrcode_url']       = $issue['qrcode_url'];
            $upData['write_off_status'] = $write_off_status;
            $upData['id']               = $issue['id'];
            $this->apiSuccess('success',$upData);
        }
    }

    /**
     * 扫码核销团体消费券
     * @param Request $request
     * @return mixed
     */
    public function writeoff_tour()
    {
        if (!Request()->isPost()) {
            $this->apiError('禁止访问！');
        }
        $userid = Request::param('userid/d',0);                // 用户ID
        $mid    = Request::param('mid/d',0);                   // 商户ID
        $coupon_issue_user_id = Request::param('coupon_issue_user_id/d',0);  // 团体券ID
        $use_min_price = Request::param('use_min_price/d',0);   // 消费金额
        $orderid       = Request::param('orderid/d',0);
        $qrcode_url    = Request::param('qrcode_url/s',0);

        // 2022-10-19 用户核销时的经纬度
        $param = get_params();
        $longitude = $param['longitude'];
        $latitude  = $param['latitude'];

        $vr_longitude = $param['vr_longitude'];
        $vr_latitude  = $param['vr_latitude'];

        // 检查基础参数
        if(!$userid){
            $this->apiError('没有登录');
        }
        if(!$mid){
            $this->apiError('商户信息错误');
        }
        if(!$coupon_issue_user_id){
            $this->apiError('团体券不存在');
        }

        // 检查核销人员
        $uInfo = \app\common\model\Users::find($userid);// 获取用户信息
        if(!$uInfo){
            $this->apiError('用户不存在');
        }
        if($uInfo['status']!=1){
            $this->apiError('用户已被禁用');
        }

        // 检查商户
        $mInfo = \app\common\model\Seller::find($mid);// 获取商户信息
        if(!$mInfo){
            $this->apiError('商户不存在');
        }
        if($mInfo['status']!=1){
            $this->apiError('商户已被禁用');
        }

        // 检查团体券
        $cInfo = \app\common\model\TourCouponGroup::find($coupon_issue_user_id);
        if(!$mInfo){
            $this->apiError('该核销码异常'); // 领取记录不存在
        }
        if($cInfo['status']==1){
            $this->apiError('该消费券已使用');
        }
        if($cInfo['status']==2){
            $this->apiError('该消费券已过期');
        }

        // 检查加密串是否相等 不相等 直接报二维码无效
        if ($cInfo['qrcode_url'] != $qrcode_url){
            $this->apiError('二维码已失效');
        }

        if($cInfo['code_time_expire']<time()){
            $this->apiError('二维码已过期');
        }

        // 检查消费券信息
        $iInfo = \app\common\model\CouponIssue::find($cInfo['coupon_issue_id']);
        if(!$iInfo){
            $this->apiError('该消费券已无法使用'); // 消费券在后台不存在
        }
        if($iInfo['status']=='-1'){
            $this->apiError('该消费券已无法使用'); // 消费券在后台设置为无效
        }
        if($iInfo['is_threshold']==1 && ($iInfo['use_min_price'] >= $use_min_price)){
            $this->apiError('最低消费需要满'.$iInfo['use_min_price'].'才可使用');
        }
        if($iInfo['is_permanent']==2){
            if($iInfo['coupon_time_start'] > time()){
                $this->apiError('该消费券还未到使用时段');
            }
            if($iInfo['coupon_time_end'] < time()){
                $this->apiError('该消费券已过使用时段');
            }
        }
        // 2022-08-27 增加有效期天数 截至领取时间往后推N天
        if($iInfo['is_permanent']==3){
            $yxtime = $cInfo['receive_time'] + $iInfo['day'] * 86400;
            if(time() > $yxtime){
                $this->apiError('该消费券已经过期');
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
                //$this->apiError('该消费券无法在该门店类型下使用');
            }

            // 商户ID 不在消费券指定的门店内  即无法使用
            if($iInfo['use_stroe_id']){
                $use_stroe_id = explode(',',$iInfo['use_stroe_id']);
                if(!in_array($mInfo['id'],$use_stroe_id)){
                    $this->apiError('该消费券无法在该门店下使用');
                }
            }
        }

        // 获取商户订单信息
        // ...

        // 查询该团体下所有游客
        $tourist = \app\common\model\TourIssueUser::where('tid',$cInfo['tid'])->where('type',2)->where('issue_coupon_id',$cInfo['coupon_issue_id'])->select();
        if(!$tourist->toArray()){
            $this->apiError('该旅行团没有游客无法核销'); // 领取记录不存在
        }
        $writeoff_tour = [];
        // 批量格式化数据
        foreach ($tourist as $key => $value) {
            // 记录核销操作
            $writeoff_tour[$key]['orderid']                = $orderid;
            $writeoff_tour[$key]['create_time']            = time();
            $writeoff_tour[$key]['tour_issue_user_id']     = $value['id'];  // 游客领取ID
            $writeoff_tour[$key]['tour_coupon_group_id']   = $coupon_issue_user_id; // 团体券ID
            $writeoff_tour[$key]['tid']                    = $value['tid'];
            $writeoff_tour[$key]['mid']                    = $mid;
            $writeoff_tour[$key]['type']                   = 2;
            $writeoff_tour[$key]['uuno']                   = $iInfo->uuno;
            $writeoff_tour[$key]['coupon_issue_id']        = $iInfo->id;
            $writeoff_tour[$key]['coupon_title']           = $iInfo->coupon_title;
            $writeoff_tour[$key]['coupon_price']           = $iInfo->coupon_price;
            $writeoff_tour[$key]['use_min_price']          = $iInfo->use_min_price;
            $writeoff_tour[$key]['time_start']             = $iInfo->time_start ? $iInfo->time_start : 0;
            $writeoff_tour[$key]['time_end']               = $iInfo->time_end ? $iInfo->time_end : 0;
            $writeoff_tour[$key]['userid']                 = $userid;
            $writeoff_tour[$key]['uid']                    = $value['uid'];
            $writeoff_tour[$key]['uw_longitude']           = $longitude;
            $writeoff_tour[$key]['uw_latitude']            = $latitude;
            $writeoff_tour[$key]['he_longitude']           = $vr_longitude;
            $writeoff_tour[$key]['he_latitude']            = $vr_latitude;
            $data = $writeoff_tour;
            // 核销加密串 = 领取记录加密串 + 核销记录md5串 + 核销用户盐值
            $writeoff_tour[$key]['enstr_salt']             = md5($cInfo['enstr_salt'].json_encode($data,JSON_UNESCAPED_UNICODE).$uInfo->salt);
        }
        // 事务操作
        Db::startTrans();
        try {
            Db::name('tour_write_off')->insertAll($writeoff_tour);
            // 修改团体券状态
            Db::name('tour_coupon_group')
            ->where('id',$coupon_issue_user_id)
            ->update(['status'=>1,'write_use'=>time()]);
            // 2023-03-23 团体券核销时，需同步将团体领券记录更新为已使用
            Db::name('tour_issue_user')
            ->where('tid',$cInfo['tid'])
            ->where('type',2)
            ->where('issue_coupon_id',$cInfo['coupon_issue_id'])
            ->update(['time_use'=>time(),'status'=>1]);

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
     * @api {post} /user/clock_list
     * @apiDescription  获取用户核销记录 = 打卡记录
     */
    public function clock_list()
    {   
        $param = get_params();
        if(empty($param['uid'])){
            $this->apiError('用户ID不能为空！');
        }

        // 获取用户信息
        /*$tour_write_off = \app\common\model\TourWriteOff::where(['uid'=>$param['uid']])
        ->where('type',2)
        ->with(['tour'])
        ->order('create_time desc')
        ->select();*/

        // 2023-03-01 是否开启打卡，不开启时只获取酒店打卡记录
        $system = \app\common\model\System::find(1);
        if($system['is_clock_switch']==1){
            // 2022-08-30 打卡任务包含酒店、景区两个打卡记录  需要用标识区分开
            $sql = "SELECT
                ccc.*,
                bb.`name` AS tour_name 
            FROM
                (
                SELECT
                    `id`,
                    1 AS tags,
                    `clock_time`,
                    `create_time`,
                    `images`,
                    `is_clock`,
                    `coupon_title`,
                    `descs`,
                    `address`,
                    `tid` 
                FROM
                    `tp_tour_write_off` 
                WHERE
                    `uid` = ".$param["uid"]."
                    AND `type` = 2 UNION ALL
                    (
                    SELECT
                        id,
                        2 AS tags,
                        clock_time,
                        create_time,
                        images,
                        is_clock,
                        spot_name AS coupon_title,
                        descs,
                        address,
                        tid 
                    FROM
                        tp_tour_hotel_user_record 
                    WHERE
                        uid = ".$param["uid"]."
                    )
                    ORDER BY
                        create_time DESC 
                    ) AS ccc
                JOIN `tp_tour` AS bb ON bb.id = ccc.tid";
        }else{
            $sql = "SELECT
                cc.id,
                2 AS tags,
                cc.clock_time,
                cc.create_time,
                cc.images,
                cc.is_clock,
                cc.spot_name AS coupon_title,
                cc.descs,
                cc.address,
                cc.tid,
                bb.`name` AS tour_name 
            FROM
                tp_tour_hotel_user_record AS cc
                JOIN `tp_tour` AS bb ON bb.id = cc.tid 
            WHERE
                uid = ".$param["uid"]."";
        }
        
        $tour_write_off = Db::query($sql);

        if (!$tour_write_off) {
            $this->apiError('还没有领取记录');
        }

        $this->apiSuccess('请求成功',$tour_write_off);
    }

    /**
     * @api {post} /user/clock
     * @apiDescription  游客打卡
     */
    public function clock()
    {   
        if (!Request()->isPost()) {
            $this->apiError('禁止访问！');
        }
        $param = get_params();
        if(empty($param['clock_uid'])){
            $this->apiError('参数异常');
        }
        if(empty($param['tour_issue_user_id'])){
            $this->apiError('参数异常');
        }
        if(empty($param['spot_name']) || empty($param['images']) || empty($param['address']) || empty($param['longitude']) || empty($param['latitude'])){
            $this->apiError('参数异常');
        }

        // 2022-09-23 导游代打卡
        if(isset($param['agency_user_id']) && $param['agency_user_id'] > 0){
            $upData['gid'] = $param['agency_user_id'];
        }

        // 获取用户信息
        $tour_issue_user = \app\common\model\TourWriteOff::where(['uid'=>$param['clock_uid']])
        ->where('id',$param['tour_issue_user_id'])
        ->where('type',2)
        ->find();
        if (!$tour_issue_user) {
            $this->apiError('信息不存在');
        }

        // 事务操作
        Db::startTrans();
        try {
            // 打卡
            $upData['clock_time'] = time();
            $upData['is_clock']   = 1;
            $upData['spot_name']  = $param['spot_name'];
            $upData['images']     = $param['images'];
            $upData['address']    = $param['address'];
            $upData['descs']      = @$param['descs'] ? @$param['descs'] : '';
            $upData['longitude']  = $param['longitude'];
            $upData['latitude']   = $param['latitude'];
            $res = \app\common\model\TourWriteOff::where(['id' => $param['tour_issue_user_id'],'uid'=>$param['clock_uid']])
            ->strict(false)
            ->field(true)
            ->update($upData);
            // 增加打卡次数 +1
            Db::name('Tourist')
            ->where('uid',$param['clock_uid'])
            ->where('tid',$tour_issue_user['tid'])
            ->inc('numbers')
            ->update();

            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->apiError('打卡失败',$e->getMessage());
        }
        $this->apiSuccess('打卡成功','data success');
    }

    // 导游生成酒店打卡记录 & 游客打卡记录
    public function add_sign_record()
    {
        if (!Request()->isPost()) {
            $this->apiError('禁止访问！');
        }
        $param = get_params();
        if(empty($param['uid']) || empty($param['tid'])){
            $this->apiError('参数异常');
        }

        if(empty($param['latitude']) || empty($param['longitude'])){
            $this->apiError('地理坐标不能为空');
        }

        if(empty($param['hotel_name'])){
            $this->apiError('请输入酒店名称');
        }

        $param['no'] = gen_uuid();
        $param['create_time'] = time();

        // 获取团下所有游客-》用户信息
        $tourist = \app\common\model\Tourist::where(['tid'=>$param['tid']])
        ->select();
        if(!$tourist->toArray()){
            $this->apiError('旅行团下还没有游客，请前去生成');
        }
        $touristUid = array_column($tourist->toArray(),'uid');
        if(in_array(0,$touristUid)){
            $this->apiError('还有游客未绑定用户信息，暂时无法生成');
        }
        // 2023-03-13 添加酒店打卡任务 限制12小时内只能生成一条记录
        $latestCointSign = Db::name('tour_hotel_sign')
            -> where('tid','=',$param['tid'])
            -> where('create_time','>',time() - 43200)
            -> order('create_time','desc')
            -> count();
        if ($latestCointSign >= 1) {
            $this->apiError("12小时内只能生成一次打卡记录");
        }

        // 事务操作
        Db::startTrans();
        try {
            $param['tourist_numbers'] = count($tourist->toArray()); // 游客数量
            // 添加导游生成酒店打卡记录
            $sign_id = Db::name('tour_hotel_sign')->strict(false)->field(true)->insertGetId($param);
            // 生成游客打卡记录
            $signData = [];
            foreach ($tourist as $key => $value) {
                $signData[$key]['create_time'] = time();
                $signData[$key]['sign_id']     = $sign_id;
                $signData[$key]['spot_name']   = $param['hotel_name'];
                $signData[$key]['uid'] = $value['uid'];
                $signData[$key]['tid'] = $param['tid'];
                $signData[$key]['guid'] = $param['uid']; // 导游ID
            }
            $limit = Db::name('tour_hotel_user_record')->replace()->insertAll($signData);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->apiError('创建失败',$e->getMessage());
        }
        $this->apiSuccess('创建成功','data success');
    }

    /**
     * @api {post} /user/hotel_clock
     * @apiDescription  游客酒店打卡
     */
    public function hotel_clock()
    {   
        if (!Request()->isPost()) {
            $this->apiError('禁止访问！');
        }
        $param = get_params();
        if(empty($param['id'])){
            $this->apiError('参数异常');
        }
        if(empty($param['images']) || empty($param['address']) || empty($param['longitude']) || empty($param['latitude'])){
            $this->apiError('参数异常');
        }

        // 2022-09-23 导游代打卡
        if(isset($param['agency_user_id']) && $param['agency_user_id'] > 0){
            $upData['gid'] = $param['agency_user_id'];
        }

        // 获取打卡信息
        $tourHotelUserRecord = \app\common\model\TourHotelUserRecord::where(['id'=>$param['id']])
        ->find();
        if (!$tourHotelUserRecord) {
            $this->apiError('信息不存在');
        }

        if($tourHotelUserRecord['is_clock']==1){
            $this->apiError('您已经打过卡了，无需操作');
        }

        // 事务操作
        Db::startTrans();
        try {
            // 打卡
            $upData['clock_time'] = time();
            $upData['is_clock']   = 1;
            $upData['images']     = $param['images'];
            $upData['address']    = $param['address'];
            $upData['descs']      = @$param['descs'] ? @$param['descs'] : '';
            $upData['longitude']  = $param['longitude'];
            $upData['latitude']   = $param['latitude'];
            $res = \app\common\model\TourHotelUserRecord::where(['id' => $param['id']])->where('is_clock',0)
            ->strict(false)
            ->field(true)
            ->update($upData);
            // 增加打卡次数 +1
            if($res > 0){
                Db::name('tour_hotel_sign')
                ->where('id',$tourHotelUserRecord['sign_id'])
                ->inc('need_numbers')
                ->update();
            }

            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->apiError('打卡失败',$e->getMessage());
        }
        $this->apiSuccess('打卡成功','data success');
    }

    /**
     * @api {post} /user/feed_back 投诉建议反馈
     * @apiDescription  返回是否成功
     */
    public function feed_back()
    {
        if (!Request()->isPost()) {
            $this->apiError('禁止访问！');
        }
        $param = get_params();
        if(empty($param['uid'])){
            $this->apiError('参数异常');
        }
        if(empty($param['content'])){
            $this->apiError('请输入内容');
        }

        /// 检查ip发布间隔 规则：10分钟之内超过3条 则禁止在刷屏
        $feedback = \app\common\model\Feedback::where('create_ip','=',request()->ip())
            -> where('create_time','>',time()-600)
            -> where('uid',$param['uid'])
            -> count();
        if ($feedback >= 3) {
            $this->apiError('对不起, 您的操作过于频繁, 请休息一会儿在来！');
        }
        // 查询当前用户信息
        $users = \app\common\model\Users::where('id',$param['uid'])->find();
        if(!$users){
            $this->apiError('未查询到用户信息，请先登录');
        }

        // 生成数据
        $param['create_ip']       = request()->ip();
        $param['name']            = $users['name'];
        $param['mobile']          = $users['mobile'];
        $param['create_time']     = time();
        $aid = Db::name('feedback')->strict(false)->field(true)->insertGetId($param);
        if($aid){
            $this->apiSuccess('发布成功');
        }else{
            $this->apiError('发布失败');
        }
    }

    /**
     * @api {post} /user/addguest 游客报名
     * @apiDescription  返回是否成功
     */
    public function addguest()
    {
        if (!Request()->isPost()) {
            $this->apiError('禁止访问！');
        }
        $param = get_params();
        if(empty($param['uid'])){
            $this->apiError('请登录');
        }
        if(empty($param['mid'])){
            $this->apiError('旅行社信息异常');
        }

        $where = [];
        if(!empty($param['mid_sub'])){
            $where[] =['mid_sub','=',@$param['mid_sub']];
        }

        // 查询是否报名
        $guest = \app\common\model\Guest::where('uid',$param['uid'])
        ->where('mid',$param['mid'])
        ->where($where)
        ->find();
        if($guest){
            $this->apiError('您已经报名了');
        }

        // 查询用户信息
        $users = \app\common\model\Users::where('id',$param['uid'])
        ->find();
        if(!$users){
            $this->apiError('请先注册');
        }

        // 生成数据
        $param['create_ip']       = request()->ip();
        $param['name']            = $users['name'];
        $param['mobile']          = $users['mobile'];
        $param['openid']          = $users['openid'];
        $param['headimgurl']      = $users['headimgurl'];
        $param['idcard']          = $users['idcard'];
        $param['nickname']        = $users['nickname'];
        $param['create_time']     = time();

        $gid = \app\common\model\Guest::strict(false)->field(true)->insertGetId($param);
        if($gid){
            $this->apiSuccess('报名成功');
        }else{
            $this->apiError('报名失败');
        }
    }

    /**user
     * @api {post} /user/coupon_order
     * @apiDescription  用户订单
     */
    public function coupon_order()
    {   
        $param = get_params();
        if(empty($param['uid'])){
            $this->apiError('用户ID不能为空！');
        }
        // 获取用户信息
        $uInfo = \app\common\model\Users::find($param['uid']);
        if(!$uInfo){
            $this->apiError('用户不存在');
        }
        if($uInfo['status']!=1){
            $this->apiError('用户已被禁用');
        }

        $where = [];
        $where[] = ['uuid','=',$uInfo['uuid']];

        if(isset($param['status']) && $param['status']!=''){
            $where[] = ['payment_status','=',$param['status']];
        }

        $rows = empty($param['limit']) ? 10 : $param['limit'];
        // 查询订单详情
        $orderInfo = Db::name('coupon_order')
        -> where($where)
        -> order('create_time desc')
        -> paginate($rows, false, ['query' => $param])
        -> each(function ($item, $key) {
            $item['detail'] = Db::name('coupon_order_item')->where('order_no',$item['order_no'])->find();
            return $item;
        });

        $this->apiSuccess('请求成功',$orderInfo);
    }

    /**user
     * @api {post} /user/coupon_order_detail
     * @apiDescription  用户订单详情
     */
    public function coupon_order_detail()
    {   
        $param = get_params();
        if(empty($param['uid'])){
            $this->apiError('用户ID不能为空！');
        }

        if(empty($param['order_no'])){
            $this->apiError('订单ID不能为空');
        }
        // 获取用户信息
        $uInfo = \app\common\model\Users::find($param['uid']);
        if(!$uInfo){
            $this->apiError('用户不存在');
        }
        if($uInfo['status']!=1){
            $this->apiError('用户已被禁用');
        }

        $where = [];
        $where[] = ['uuid','=',$uInfo['uuid']];
        $where[] = ['order_no','=',$param['order_no']];

        // 查询订单详情
        $orderInfo = Db::name('coupon_order')
        -> where($where)
        -> find();

        if($orderInfo){
            $orderInfo['detail'] = Db::name('coupon_order_item')->where('order_no',$orderInfo['order_no'])->find();
        }
        $this->apiSuccess('请求成功',$orderInfo);
    }

    /**user
     * @api {post} /user/get_user_coupon_id
     * @apiDescription  获取用户所领取的消费券ID集
     * @date 2023-04-30 11:00
     */
    public function get_user_coupon_id()
    {   
        $param = get_params();
        if(empty($param['uid'])){
            $this->apiError('用户ID不能为空！');
        }


        $where = [];
        $where[] = ['uid','=',$param['uid']];

        // 查询订单详情
        $couponInfo = Db::name('coupon_issue_user')
        -> where($where)
        -> column('issue_coupon_id');

        $this->apiSuccess('请求成功',$couponInfo);
    }

    /**
     * [auth_identity 身份三网认证接口]
     * @return   [type]            [description]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-05-31
     * @LastTime 2023-05-31
     * @version  [1.0.0]
     */
    public function auth_identity()
    {
        $param = get_params();
        $requiredParams = ['mobile', 'name', 'idcard', 'uid'];

        foreach ($requiredParams as $paramName) {
            if (!isset($param[$paramName])) {
                $this->apiError('参数错误');
            }
        }

        // 变更手机号时需要提供验证码
        if($param['tags']==1){
            $smsCode = Db::name('users_sms_log')
            -> where('uid',$param['uid'])
            -> where('mobile',$param['mobile'])
            -> where('sms_code',$param['smsCode'])
            -> order('create_time desc')
            -> find();

            if(!$smsCode){
                $this->apiError('验证码错误');
            }

            if(time() > $smsCode['expire_time']){
                $this->apiError('验证码已过期');
            }
        }

        if (isset($param['name'])) {
            $param['name'] = @$param['name'] ? removeXSS(filterText($param['name'])) : '';

            if (!checkNameFilter($param['name'])) {
                $this->apiError('请输入正确的姓名！');
            }
        }

        if (isset($param['mobile'])) {
            $param['mobile'] = @$param['mobile'] ? removeXSS(filterText($param['mobile'])) : '';

            if (!check_phone($param['mobile'])) {
                $this->apiError('手机号错误');
            }
        }

        if(isset($param['idcard'])){
            // 校验数据正确性
            if(!isCreditNo($param['idcard'])){ 
                $this->apiError('请输入正确的身份证号码');
            }
            # 获取周岁 | 
            $param['age'] = $inData['age'] = IdentityCard::age($param['idcard']);
            # 获取生日
            $param['birthday'] = $inData['birthday'] = IdentityCard::birthday($param['idcard']);
            # 获取性别 | {男为M | 女为F}
            $sex = IdentityCard::sex($param['idcard']);
            $param['sex'] = $inData['sex'] = $sex=='M' ? 1 : 2;
            # 获取生肖
            $param['zodiac'] = $inData['zodiac'] = IdentityCard::constellation($param['idcard']);
            # 获取星座
            $param['starsign'] = $inData['starsign'] = IdentityCard::star($param['idcard']);

            $get_area_code_info = get_area_code_info($param['idcard']);

            $param['province'] = $inData['province'] = isset($get_area_code_info['province']) ? $get_area_code_info['province'] : '';
            $param['city']     = $inData['city']     = isset($get_area_code_info['city']) ? $get_area_code_info['city'] : '';
            $param['district'] = $inData['district'] = isset($get_area_code_info['district']) ? $get_area_code_info['district'] : '';
        }

        // 2023-06-01 证明我儿子是我儿子
        $uInfo = \app\common\model\Users::find($param['uid']);
        if(!$uInfo){
            $this->apiError('用户不存在');
        }

        if($uInfo['auth_status']==1){
            $this->apiError('当前用户已经认证');
        }

        // 如果桃花分已经存在，则无需认证，直接改成认证成功
        if($uInfo['credit_score'] > 0 && $uInfo['name'] == $param['name'] && $uInfo['idcard']==$param['idcard']){
            Db::name('users')->where('id',$uInfo['id'])
                ->update(['auth_status'=>1,'mobile'=>$param['mobile']]);
            $this->apiSuccess('认证成功','无需2要素认证');
        }

        // 校验手机号是否存在
        $mobile = Db::name('users')
        ->where('mobile', $param['mobile'])
        //->where('openid','<>','')
        ->whereNotNull('openid')
        ->where('id','<>',$param['uid'])
        ->find();
        if ($mobile) {
            $this->apiError('当前手机号已经绑定其他微信'.$mobile['name']);
        }

        // 校验身份证号是否存在
        $idcard = Db::name('users')
                ->where('idcard', $param['idcard'])
                // ->where('idcard','<>','')
                ->whereNotNull('idcard')
                ->where('id','<>',$param['uid'])
                ->find();
        if ($idcard) {
            $this->apiError('当前身份证号已经绑定其他手机号:'.$idcard['mobile']);
        }

        // Dev-only: offline mock identity verification for golden replay.
        if (env('rewrite.mock_identity')) {
            $jsonData = [
                'status' => 'OK',
                'state' => 1,
                'request_id' => 'mock-request-id',
                'result_message' => 'mock ok',
            ];
            $this->user_auth_log($jsonData, $param);
            // 跟新信息（与真实分支保持一致）
            Db::name('users')
                ->where(['id' => $uInfo['id']])
                ->strict(false)->field(true)
                ->update($param);
            $this->apiSuccess('认证成功', $jsonData['result_message']);
        }

        
        $system = \app\common\model\System::find(1);
        if($system['app_code']==''){
            $this->apiError('请配置认证代码');
        }

        $host = "https://dfidveri.market.alicloudapi.com";
        $path = "/verify_id_name";
        $method = "POST";
        $appcode = $system['app_code'];//"1fb45072d6ea46d4b6f1db63bdb6b78b";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        //根据API的要求，定义相对应的Content-Type
        array_push($headers, "Content-Type".":"."application/x-www-form-urlencoded; charset=UTF-8");
        $bodys = "id_number=".$param['idcard']."&name=".$param['name'];
        $url = $host . $path;

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
        curl_close($curl);
        $jsonData = json_decode($res,true);
        // 记录认证信息
        $this->user_auth_log($jsonData,$param);
        if ($jsonData['status'] == 'OK' && $jsonData['state']==1) {
            // 跟新信息
            Db::name('users')
                ->where(['id' => $uInfo['id']])
                ->strict(false)->field(true)
                ->update($param);
            $this->apiSuccess('认证成功',$jsonData['result_message']);
        } elseif ($jsonData['status'] == 'OK' && $jsonData['state']==2) {
            // 认证不通过
            $this->apiError('姓名和身份证号不匹配');
        } elseif ($jsonData['status'] == 'RATE_LIMIT') {
            $this->apiError('同一名字30分钟内只能认证10次');
        } elseif ($jsonData['status'] == 'INVALID_ARGUMENT') {
            $this->apiError('认证失败');
        } else {
            $this->apiError('身份认证无法通过');
        }
    }

    /**
     * [user_auth_log 用户认证日志]
     * @param    int|integer       $returnData   [返回代码]
     * @param    array             $param   [用户信息]
     * @return   [type]                    [description]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-05-30
     * @LastTime 2023-05-30
     * @version  [1.0.0]
     */
    private function user_auth_log($returnData,$param)
    {   
        //print_r($returnData);die;
        $order_no = '';
        $auth_status = -1;
        if($returnData['status'] =='OK'){
            $order_no = $returnData['request_id'];
            $auth_status = $returnData['state']; //验证结果： 1 : 查询成功, 二要素一致。  返回值为 2 : 查询成功, 二要素不一致 
        }
        $data = [
            'uid'      => $param['uid'],
            'name'     => $param['name'],
            'mobile'   => $param['mobile'],
            'idcard'   => $param['idcard'],
            'order_no' => $order_no,
            'status'     => $returnData['status'],
            'result'     => $auth_status,
            'msg'        => isset($returnData['result_message']) ? $returnData['result_message'] : '',
            'return_data'=> json_encode($returnData),
            'create_time'=> time()
        ];

        // 事务操作
        Db::startTrans();
        try {
            if($returnData['status'] =='OK' && $auth_status==1){
                // 修改认证状态
                Db::name('users')->where('id',$param['uid'])
                ->update(['auth_status'=>1,'name'=>$param['name'],'mobile'=>$param['mobile'],'idcard'=>$param['idcard']]);
            }
            // 记录认证记录
            Db::name('users_auth_log')->strict(false)->insert($data);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->apiSuccess('认证失败',$e->getMessage());
        }
    }

    /**
     * [miniwxregit 微信用户注册]
     * @return   [type]            [微信用户注册]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-06-05
     * @LastTime 2023-06-05
     * @version  [1.0.0]
     */
    public function miniwxregister()
    {
        $param  = get_params();
        if(!isset($param['openid'], $param['mobile'], $param['name'], $param['idcard'])) {
            $this->apiError('参数错误');
        }

        if(isset($param['name'])){
            // 过滤特殊字符 & 处理XSS跨站攻击
            //$param['name'] = @$param['name'] ? removeXSS(filterText($param['name'])) : '';

            if(!checkNameFilter($param['name'])){
                $this->apiError('请输入正确的姓名！');
            }
        }

        if(isset($param['mobile'])){
            // 过滤特殊字符 & 处理XSS跨站攻击
            $param['name'] = @$param['name'] ? removeXSS(filterText($param['name'])) : '';

            if(!check_phone($param['mobile'])){
                $this->apiError('手机号错误');
            }
        }

        if(isset($param['idcard'])){
            // 校验数据正确性
            if(!isCreditNo($param['idcard'])){ 
                $this->apiError('请输入正确的身份证号码');
            }
            # 获取周岁 | 
            $param['age'] = $inData['age'] = IdentityCard::age($param['idcard']);
            # 获取生日
            $param['birthday'] = $inData['birthday'] = IdentityCard::birthday($param['idcard']);
            # 获取性别 | {男为M | 女为F}
            $sex = IdentityCard::sex($param['idcard']);
            $param['sex'] = $inData['sex'] = $sex=='M' ? 1 : 2;
            # 获取生肖
            $param['zodiac'] = $inData['zodiac'] = IdentityCard::constellation($param['idcard']);
            # 获取星座
            $param['starsign'] = $inData['starsign'] = IdentityCard::star($param['idcard']);

            $get_area_code_info = get_area_code_info($param['idcard']);

            $param['province'] = $inData['province'] = isset($get_area_code_info['province']) ? $get_area_code_info['province'] : '';
            $param['city']     = $inData['city']     = isset($get_area_code_info['city']) ? $get_area_code_info['city'] : '';
            $param['district'] = $inData['district'] = isset($get_area_code_info['district']) ? $get_area_code_info['district'] : '';
        }

        // 校验微信是否存在
        if (Db::name('users')->where('openid', $param['openid'])->find()) {
            $this->apiError('当前微信已经注册');
        }

        // 校验手机号是否存在
        $mobile = Db::name('users')->where('mobile', $param['mobile'])->find();
        if ($mobile) {
            if($mobile['openid'] && !is_null($mobile['openid'])) {
                $this->apiError('当前手机号已经绑定其他账号：'.$mobile['name']);
            }

            if($param['idcard']==$mobile['idcard'] && is_null($mobile['openid'])){
                Db::name('users')->where(['mobile' => $param['mobile']])
                ->strict(false)->field(true)
                ->update(['openid'=>$param['openid']]);
                $token = JwtAuth::getToken($mobile['id']);

                $expiry_time = time() + 3600 * 24 * 30;//strtotime("+30 day");
                // token入库
                Db::name('users')->where(['id' => $mobile['id']])->data(['signpass'=>md5($token),'expiry_time'=>$expiry_time])->update();
                // 查询用户信息
                $this->apiSuccess('登录成功',['token' => $token,'userinfo'=>$mobile]);
            }
            $this->apiError('当前手机号已经绑定其他身份证号：'.$mobile['idcard']);
        }

        // 校验身份证号是否存在
        $idcard = Db::name('users')
                ->where('idcard', $param['idcard'])
                //->where('idcard','<>','')
                ->whereNotNull('idcard')
                ->find();
        if ($idcard) {
            $this->apiError('当前身份证号已经绑定其他手机号：'.$idcard['mobile']);
        }

        // 注册流程
        $inData['salt']         = set_salt(6); // 永久加密盐  用于手机号  身份证号加密
        $inData['create_time']  = time();
        $inData['mobile']       = $param['mobile'];
        $inData['openid']       = $param['openid'];
        $inData['name']         = $param['name'];
        $inData['idcard']       = $param['idcard'];
        $inData['create_ip']    = request()->ip();
        $inData['uuid']         = gen_uuid();
        $inData['last_login_ip']= request()->ip();
        $inData['last_login_time']= time();
        $uid  = Db::name('Users')->strict(false)->field(true)->insertGetId($inData);
        if($uid){
            $token = JwtAuth::getToken($uid);
            $expiry_time = time() + 3600 * 24 * 30;//strtotime("+30 day");
            // token入库
            Db::name('users')->where(['id' => $uid])->data(['signpass'=>md5($token),'expiry_time'=>$expiry_time])->update();
            // 查询用户信息
            $regUser = Db::name('users')->where(['id' => $uid])->find();
            $this->apiSuccess('注册成功',['token' => $token,'userinfo'=>$regUser]);
        }else{
            $this->apiError('注册失败');
        }
    }
    /**
     * 获取证件类型
     */
    public function getCertTypeList()
    {
        $this->apiSuccess('',UsersTouristModel::getCertTypeList());
    }
    /**
     * 获取同行游客列表
     */
    public function getTouristList()
    {
        $param = Request::get();
        $page_size = isset($param['page_size']) ? intval($param['page_size']) : 10;
        $user_id = Request::header('Userid');
        $list = UsersTouristModel::where('user_id',$user_id)->append(['cert_type_text'])->paginate([
            'list_rows'=>$page_size,
            'page'=>$param['page']
        ]);
        $this->apiSuccess('',$list);
    }
    /*
     * 添加/编辑同行游客
     * */
    public function postTourist()
    {
        if(Request::isPost()){
            $post = Request::post();
            $cert_tyle_list = UsersTouristModel::getCertTypeList();
            $validate = Validate::rule([
                'fullname'  => 'require|chs|max:10',
                'mobile'     => 'require|mobile',
                'cert_type'  => 'require|in:'.implode(",",array_keys($cert_tyle_list)),
                'cert_id'  => 'require',
            ]);
            $validate->message([
                'fullname.require'  => '姓名不能为空！',
                'fullname.chs'  => '姓名只能是汉字！',
                'fullname.max'  => '姓名长度超出！',
                'mobile.require'      => '手机号不能为空！',
                'mobile.mobile'     => '手机号格式不符',
                'cert_type.require'       => '证件类型不能为空！',
                'cert_type.in'       => '证件类型不存在！',
                'cert_id.require'  => '证件号不能为空'
            ]);
            if (!$validate->check($post)) {
                $this->apiError($validate->getError());
            }
            $user_id = Request::header('Userid');
            $msg = '添加成功！';
            $saveData = [
                'mobile'=>$post['mobile']
            ];
            if(isset($post['id']) && !empty($post['id'])){
                $model = UsersTouristModel::where('id',$post['id'])->find();
                $msg = '修改成功！';
            }else{
                //验证证件号是否匹配
                if($post['cert_type'] == 1){
                    //转成大写
                    $post["cert_id"] = strtoupper($post['cert_id']);
                    //类型是身份证的时候验证身份证号是否正确
                    $pattern = '/(^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$)|(^[1-9]\d{5}\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}$)/'; // 身份证号码的正则表达式
                    if (!preg_match($pattern, $post['cert_id'])) {
                        $this->apiError("身份证号格式不符！");
                    }
                    //先查看用户表是否有认证过的。
                    $has_u = \app\common\model\Users::where(["name"=>$post["fullname"],"idcard"=>$post["cert_id"],"auth_status"=>1])->find();
                    if(!$has_u){
                        //联网认证
                        $system = \app\common\model\System::find(1);
                        if($system['app_code']==''){
                            $this->apiError('请配置认证代码');
                        }
                        $options[CURLOPT_HTTPHEADER] = ['Authorization:APPCODE '.$system['app_code'],'Content-Type:application/x-www-form-urlencoded; charset=UTF-8'];
                        $options[CURLOPT_FAILONERROR] = false;
                        $result = \app\common\libs\Http::post("https://dfidveri.market.alicloudapi.com/verify_id_name",http_build_query(["id_number"=>$post['cert_id'],"name" =>$post['fullname']]),$options);
                        if($result === ""){
                            $this->apiError('验证身份失败！');
                        }
                        $jsonData = json_decode($result,true);
                        // 记录认证信息
                        //$this->user_auth_log($jsonData,$param);
                        if ($jsonData['status'] == 'OK' && $jsonData['state']==1) {
                            //认证通过，什么不干
                        } elseif ($jsonData['status'] == 'OK' && $jsonData['state']==2) {
                            // 认证不通过
                            $this->apiError('姓名和身份证号不匹配');
                        } elseif ($jsonData['status'] == 'RATE_LIMIT') {
                            $this->apiError('同一名字30分钟内只能认证10次');
                        } elseif ($jsonData['status'] == 'INVALID_ARGUMENT') {
                            $this->apiError('认证失败');
                        } else {
                            $this->apiError('身份认证无法通过');
                        }
                    }
                }
                $saveData['user_id'] = $user_id;
                $saveData['fullname'] = $post['fullname'];
                $saveData['cert_type'] = $post['cert_type'];
                $saveData['cert_id'] =$post['cert_id'];
                $saveData['status'] = 1;
                $model = new UsersTouristModel;
            }
            if(!$model->save($saveData)){
                $this->apiError('操作失败');
            }
            $this->apiSuccess($msg);
        }
        $this->apiError('请求方式错误！');
    }
    /*
     * 删除同行人
     * */
    public function delTourist()
    {
        $post = Request::post();
        $validate = Validate::rule([
            'ids'  => 'require'
        ]);
        $validate->message([
            'ids.require'  => '参数错误！'
        ]);
        if (!$validate->check($post)) {
            $this->apiError($validate->getError());
        }
        $user_id = Request::header('Userid');
        foreach(explode(",",$post['ids']) as $id){
            $model = UsersTouristModel::where(['id'=>$id,'user_id'=>$user_id])->find();
            if(!empty($model)){
                $model->delete();
            }
        }
        $this->apiSuccess('删除成功');
    }
    /**
     * [smsVerification 实名认证时，更换手机号需要收集验证码]
     * @return   [type]            [实名认证时，更换手机号需要收集验证码]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-07-10
     * @LastTime 2023-07-10
     * @version  [1.0.0]
     */
    public function smsVerification()
    {
        $param = get_params();
        $requiredParams = ['mobile', 'uid'];

        foreach ($requiredParams as $paramName) {
            if (!isset($param[$paramName])) {
                $this->apiError('参数错误');
            }
        }

        if (isset($param['mobile'])) {
            $param['mobile'] = @$param['mobile'] ? removeXSS(filterText($param['mobile'])) : '';

            if (!check_phone($param['mobile'])) {
                $this->apiError('手机号错误');
            }
        }

        // Dev-only: offline mock SMS send for golden replay.
        // Put before rate-limit checks so replay stays stable across repeated runs.
        if (env('rewrite.mock_sms')) {
            $code = 123456;
            $minute = 5;
            $inData = [
                'uid'         => $param['uid'],
                'mobile'      => $param['mobile'],
                'sms_code'    => strval($code),
                'template'    => '',
                'create_time' => time(),
                'smsid'       => 'mock-smsid',
                'code'        => '0',
                'balance'     => 9999,
                'msg'         => 'mock ok',
                'expire_time' => time() + $minute * 60,
            ];
            Db::name('users_sms_log')->strict(false)->field(true)->insert($inData);
            $this->apiSuccess('发送成功');
        }

        // 一小时超过3条 提示
        $smsLog = Db::name('users_sms_log')
            -> where('create_time','>',time()-3600)
            -> where('uid',$param['uid'])
            -> order('create_time','desc')
            -> count();
        if ($smsLog >= 3) {
            $this->apiError('对不起, 超出发送频率,过一会儿再试！');
        }

        $host = "https://gyytz.market.alicloudapi.com";
        $path = "/sms/smsSend";
        $method = "POST";
        $appcode = "906db11f83294674a094e1b5ef327209";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);

    //smsSignId（短信前缀）和templateId（短信模板），可登录国阳云控制台自助申请。参考文档：http://help.guoyangyun.com/Problem/Qm.html

        $code   = rand(100000, 999999);
        $minute = 5;
        $params = urlencode('**code**:'.$code.',**minute**:'.$minute);
        $querys = "mobile=".$param['mobile']."&param=".$params."&smsSignId=66c7dd175a6c416b9ba72bbce18013d6&templateId=b51f51beb84a4b4c8d4c3f016c326312";
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
            $inData['uid']          = $param['uid'];
            $inData['mobile']       = $param['mobile'];
            $inData['sms_code']     = $code;
            $inData['template']     = '';
            $inData['create_time']  = time();
            $inData['smsid']        = $res['smsid'];
            $inData['code']         = $res['code'];
            $inData['balance']      = $res['balance'];
            $inData['msg']          = $res['msg'];
            $inData['expire_time'] = time() + $minute * 60;
            $user_sms  = Db::name('users_sms_log')->strict(false)->field(true)->insertGetId($inData);
            if($user_sms){
                $this->apiSuccess('发送成功');
            }
        }

        $this->apiError('发送失败:'.$res['msg']);

    }

    //用户扫商户码进行打卡操作
    public function userClock()
    {
        $param = get_params();
        if (!Request()->isPost()) {
            $this->reportError('禁止访问',$param);
        }

        if(!isset($param['latitude']) || !isset($param['longitude']) || $param['latitude']=='' || $param['longitude']==''){
            $this->apiError('打卡位置异常');
        }

        // 获取用户信息
        if(empty($param['uid'])){
            $this->apiError('用户不能为空！');
        }
        $uInfo = \app\common\model\Users::find($param['uid']);
        if(!$uInfo){
            $this->apiError('用户不存在');
        }
        if($uInfo['status']!=1){
            $this->apiError('用户已被禁用');
        }
        if ($uInfo['auth_status']!=1) {
            $this->apiError('您还没有认证！暂时无法打卡！');
        }

        //消费卷判断
        if(empty($param['qrcode_url'])){
            $this->apiError('二维码信息为空！');
        }
        $sellerMarkQc = \app\common\model\SellerMarkQc::where('qrcode_url', $param['qrcode_url'])->find();
        if (empty($sellerMarkQc)) {
            $this->apiError('打卡二维码有误！');
        }

        //消费卷信息校验
        if(empty($param['couponId'])){
            $this->apiError('消费卷信息为空！');
        }
        $issueCouponInfo = \app\common\model\CouponIssue::find($param['couponId']);
        if (empty($issueCouponInfo)) {
            $this->apiError('消费卷不存在！');
        }

        //二维码解密
        $data = symdecrypt($param['qrcode_url'], "cyylewmjjmcode");
        if (empty($data)) {
            $this->apiError('打卡二维码有误！');
        }

        //判断被打卡二维码商户是否正确
        $dataArr = explode("_", $data);
        if ($dataArr[0] != $sellerMarkQc['seller_id']) {
            $this->apiError('打卡二维码有误！');
        }

        //判断商户
        $sellerInfo = \app\common\model\Seller::find($sellerMarkQc['seller_id']);// 获取商户信息
        if(!$sellerInfo){
            $this->apiError('商户不存在');
        }
        if($sellerInfo['status']!=1){
            $this->apiError('商户已被禁用');
        }

        //今日的开始及结束
        $todayStart = strtotime(date('Y-m-d 00:00:00'));
        $todayEnd   = strtotime(date('Y-m-d 23:59:59'));

        //判断商户今日打卡上限
        $sellerRecordCountNum = \app\common\model\SellerMarkQcUserRecord::field("count('id') as num")
            ->where('seller_id', $sellerMarkQc['seller_id'])
            ->whereBetween('create_time', [$todayStart, $todayEnd])
            ->find();
        if (!empty($sellerRecordCountNum) && ($sellerRecordCountNum['num'] >= $sellerMarkQc['day_threshold_value'])) {
            $this->apiError('该商户今日打卡已上限，请明日再试');
        }

        //判断该用户在该商户每天只允许打卡一次
        $userSellerRecordData = \app\common\model\SellerMarkQcUserRecord::field('id')
            ->where('uid', $param['uid'])
            ->where('seller_id', $sellerMarkQc['seller_id'])
            ->whereBetween('create_time', [$todayStart, $todayEnd])
            ->find();
        if (!empty($userSellerRecordData)) {
            $this->apiError('今日已在该商户打卡，请明日再试');
        }

        // 检验用户与商户之间的距离
        $distance = calculateDistance($param['latitude'], $param['longitude'], $sellerInfo['latitude'], $sellerInfo['longitude']);
        if(round($distance, 2) > $sellerMarkQc['range']) {
            $this->apiError('商户位置太远,距离：'.round($distance, 2).'米');
        }

        //存储打卡信息
        $recordData['coupon_id']      = $param['couponId'];
        $recordData['seller_id']      = $sellerMarkQc['seller_id'];
        $recordData['class_id']       = $sellerInfo['class_id'];
        $recordData['uid']            = $param['uid'];
        $recordData['qc_id']          = $sellerMarkQc['id'];
        $recordData['qrcode']         = $param['qrcode_url'];
        $recordData['mark_location']  = $sellerInfo['address'];
        $recordData['longitude']      = $param['longitude'];
        $recordData['latitude']       = $param['latitude'];
        $recordData['update_time']    = time();
        $recordData['create_time']    = time();
        $newId = Db::name('seller_mark_qc_user_record')->insertGetId($recordData);
        if ($newId) {
            $this->apiSuccess('打卡成功','data success');
        } else {
            $this->apiError('打卡失败，请重新尝试！');
        }

    }

    //提交快递单号
    public function saveDelivery()
    {
        $param = get_params();
        if (!Request()->isPost()) {
            $this->reportError('禁止访问',$param);
        }

        // 获取用户信息
        if(empty($param['uid'])){
            $this->apiError('用户不能为空！');
        }
        $uInfo = \app\common\model\Users::find($param['uid']);
        if(!$uInfo){
            $this->apiError('用户不存在');
        }
        if($uInfo['status']!=1){
            $this->apiError('用户已被禁用');
        }

        if(!isset($param['delivery_user']) || ($param['delivery_user']=='')){
            $this->apiError('收货姓名不能为空');
        }

        if(!isset($param['delivery_address']) || ($param['delivery_address']=='')){
            $this->apiError('收货地址不能为空');
        }

        if(!isset($param['delivery_phone']) || ($param['delivery_phone']=='')){
            $this->apiError('收货手机号不能为空');
        }

        // 检查领取记录
        if(empty($param['coupon_issue_user_id'])){
            $this->apiError('用户领取记录有误！');
        }

        $couponIssueUser = \app\common\model\CouponIssueUser::find($param['coupon_issue_user_id']);// 获取商户信息
        if(!$couponIssueUser){
            $this->apiError('该消费券异常');// 领取记录不存在
        }
        if($couponIssueUser['status']==1){
            $this->apiError('该消费券已使用');
        }
        if($couponIssueUser['status']==2){
            $this->apiError('该消费券已过期');
        }
        if($couponIssueUser['is_fail']==0){
            $this->apiError('该消费券无效');
        }

        // 检查消费券信息
        $iInfo = \app\common\model\CouponIssue::find($couponIssueUser['issue_coupon_id']);
        if(!$iInfo){
            $this->apiError('该消费券已无法使用');
        }
        if($iInfo['is_permanent']==2){
            if($iInfo['coupon_time_start'] > time()){
                $this->apiError('该消费券还未到使用时段');
            }
            if($iInfo['coupon_time_end'] < time()){
                $this->apiError('该消费券已过使用时段');
            }
        }
        // 截至领取时间往后推N天
        if($iInfo['is_permanent']==3){
            $yxtime = strtotime($couponIssueUser['create_time']) + $iInfo['day'] * 86400;
            if(time() > $yxtime){
                $this->apiError('该消费券已经过期',$param);
            }
        }

        //更新快递信息
        $deliveryData['delivery_user']      = $param['delivery_user'];
        $deliveryData['delivery_address']   = $param['delivery_address'];
        $deliveryData['delivery_phone']     = $param['delivery_phone'];
        $deliveryData['delivery_input_time']= time();

        Db::name('CouponIssueUser')
            ->where('id',$couponIssueUser['id'])
            ->update($deliveryData);

        $this->apiSuccess('提交成功','data success');
    }

    //根据用户、领券id、物流编号获得物流信息
    public function getLogisticsInformation()
    {
        $uid = Request::param('uid/d',0);
        $coupon_issue_user_id = Request::param('coupon_issue_user_id/d',0);
        $tracking_number = Request::param('tracking_number/s',0);

        // 获取用户信息
        if(empty($uid)){
            $this->apiError('用户不能为空！');
        }
        $uInfo = \app\common\model\Users::find($uid);
        if(!$uInfo){
            $this->apiError('用户不存在');
        }
        if($uInfo['status'] != 1){
            $this->apiError('用户已被禁用');
        }

        if(empty($tracking_number)){
            $this->apiError('快递单号不能为空');
        }

        if(empty($coupon_issue_user_id)){
            $this->apiError('用户领取记录有误！');
        }

        //获得快递信息
        $trackingResult = \app\common\model\CouponIssueUser::syncTrackingResult($tracking_number, $coupon_issue_user_id);

        //返回200,success,已签收,则可以进行核销操作
        (new \app\common\model\CouponIssueUser)->systemWriteOff($uid, $coupon_issue_user_id, $trackingResult);//系统核销用户，需要进行修改

        $this->apiSuccess('查询成功',$trackingResult);
    }
}
