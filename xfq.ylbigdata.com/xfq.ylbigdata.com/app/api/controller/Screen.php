<?php
/**
 * @desc   大屏数据接口API
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
use think\facade\Cache;
use think\facade\Config;

class Screen extends BaseController
{
    /**
     * 控制器中间件 [账号登录、注册、小程序登录、注册 不需要鉴权]
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except'    => ['login','index'] ]
    ];

    /**
     * @api {post} /screen/login 登录访问大屏
     * @apiDescription 大屏登录接口，返回 token
     * @apiParam (请求参数：) {string}             password 登录密码
     * @apiParam (响应字段：) {string}             token    Token
     * @apiSuccessExample {json} 成功示例
     * {"code":0,"msg":"登录成功","time":1627374739,"data":{"token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJhcGkuZ291Z3VjbXMuY29tIiwiYXVkIjoiZ291Z3VjbXMiLCJpYXQiOjE2MjczNzQ3MzksImV4cCI6MTYyNzM3ODMzOSwidWlkIjoxfQ.gjYMtCIwKKY7AalFTlwB2ZVWULxiQpsGvrz5I5t2qTs"}}
     * @apiErrorExample {json} 失败示例
     * {"code":1,"msg":"帐号或密码错误","time":1627374820,"data":[]}
     */
    public function login()
    {
        $param = get_params();
        if(empty($param['password'])){
            $this->apiError('参数错误');
        }
        $system = \app\common\model\System::find(1);
        // 校验密码
        if (empty($system['screen_password'])) {
            $this->apiError('请前往运营后台设置大屏访问密码');
        }
        if ($system['screen_password'] !== $param['password']) {
            $this->apiError('密码错误');
        }
        //获取jwt的句柄
        //$token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJhcGkuZ291Z3VjbXMuY29tIiwiYXVkIjoiZ291Z3VjbXMiLCJpYXQiOjE2MjczNzQ3MzksImV4cCI6MTYyNzM3ODMzOSwidWlkIjoxfQ.gjYMtCIwKKY7AalFTlwB2ZVWULxiQpsGvrz5I5t2qTs';
        $token  = JwtAuth::getToken($system['screen_password']);
        $this->apiSuccess('登录成功',['token' => $token]);
    }

    /**
     * @api {post} /screen/index
     * @apiDescription  图形数据
     */
    public function index()
    {
        $dataList = [];
        // 商家入住数量--柱状图
        /*$dataList['colData']  = [
            ["id"=>1,"area" => "榆阳区","value"=> 0,"writeoff"=> 0],
            ["id"=>2,"area" => "横山区","value"=> 0,"writeoff"=> 0],
            ["id"=>3,"area" => "神木市","value"=> 0,"writeoff"=> 0],
            ["id"=>4,"area" => "府谷县","value"=> 0,"writeoff"=> 0],
            ["id"=>5,"area" => "靖边县","value"=> 0,"writeoff"=> 0],
            ["id"=>6,"area" => "定边县","value"=> 0,"writeoff"=> 0],
            ["id"=>7,"area" => "绥德县","value"=> 0,"writeoff"=> 0],
            ["id"=>8,"area" => "米脂县","value"=> 0,"writeoff"=> 0],
            ["id"=>9,"area" => "佳县","value"=> 0,"writeoff"=> 0],
            ["id"=>10,"area" => "吴堡县","value"=> 0,"writeoff"=> 0],
            ["id"=>11,"area" => "清涧县","value"=> 0,"writeoff"=> 0],
            ["id"=>12,"area" => "子洲县","value"=> 0,"writeoff"=> 0],
        ];
        // 入住总数
        $sellerlist = \app\common\model\Seller::field('area,count(1) as total')->group('area')->select();
        foreach ($dataList['colData'] as $key => $value) {
            foreach ($sellerlist as $kk => $vv) {
                if($vv['area']==$value['id']){
                    $dataList['colData'][$key]['value'] = $vv['total'];
                }
            }
        }
        sort($dataList['colData']);*/
        // 累计数据
        $dataList['toplist'] = [
            [
                'name'=>'累计发放金额',
                'number'=>0,
            ],[
                'name'=>'累计核销金额',
                'number'=>0,
            ],[
                'name'=>'今日核销金额',
                'number'=>0,
            ],
        ];
        // 查询累计发放金额=所有消费券的面额
        $coupon_issue_list = \app\common\model\CouponIssue::select();
        $len = count($coupon_issue_list);
        
        for ($i=0; $i < $len; $i++) { 
            $dataList['toplist'][0]['number'] += $coupon_issue_list[$i]['coupon_price'] * $coupon_issue_list[$i]['total_count'];
        }
        // 查询累计核销金额=所有核销记录中消费券的面额
        $dataList['toplist'][1]['number'] = \app\common\model\WriteOff::sum('coupon_price');
        $tour_write_off_price = \app\common\model\TourWriteOff::sum('coupon_price');
        $dataList['toplist'][1]['number'] = $dataList['toplist'][1]['number'] + $tour_write_off_price;

        // 查询今日核销记录=所有核销记录中今日消费券的面额
        $start_time = strtotime(date("Y-m-d 00:00:00"));
        $end_time   = strtotime(date("Y-m-d 23:59:59"));
        $dataList['toplist'][2]['number'] = \app\common\model\WriteOff::where('create_time','between',[$start_time,$end_time])->sum('coupon_price');

        $tour_write_off_price_time = \app\common\model\TourWriteOff::where('create_time','between',[$start_time,$end_time])->sum('coupon_price');
        $dataList['toplist'][2]['number'] = $dataList['toplist'][2]['number'] + $tour_write_off_price_time;

        // 地图展示
        $dataList['mapData']  = [
            ["id"=>1,"name" => "榆阳区","value"=> 0,"writeoff"=> 0],
            ["id"=>2,"name" => "横山区","value"=> 0,"writeoff"=> 0],
            ["id"=>3,"name" => "神木市","value"=> 0,"writeoff"=> 0],
            ["id"=>4,"name" => "府谷县","value"=> 0,"writeoff"=> 0],
            ["id"=>5,"name" => "靖边县","value"=> 0,"writeoff"=> 0],
            ["id"=>6,"name" => "定边县","value"=> 0,"writeoff"=> 0],
            ["id"=>7,"name" => "绥德县","value"=> 0,"writeoff"=> 0],
            ["id"=>8,"name" => "米脂县","value"=> 0,"writeoff"=> 0],
            ["id"=>9,"name" => "佳县","value"=> 0,"writeoff"=> 0],
            ["id"=>10,"name" => "吴堡县","value"=> 0,"writeoff"=> 0],
            ["id"=>11,"name" => "清涧县","value"=> 0,"writeoff"=> 0],
            ["id"=>12,"name" => "子洲县","value"=> 0,"writeoff"=> 0],
        ];
        // 商家核销数
        $writelist = Db::name('write_off')
        ->field('w.mid,s.nickname,s.latitude,s.longitude,s.area,count(w.id) as total')
        ->alias('w')
        ->join('seller s', 'w.mid = s.id')
        ->group('s.area')
        ->select();
        foreach ($dataList['mapData'] as $key => $value) {
            foreach ($writelist as $kk => $vv) {
                if($vv['area']==$value['id']){
                    $dataList['mapData'][$key]['value'] = $vv['total'];
                }
            }
        }
        sort($dataList['mapData']);

        // 地图上的点
        $dataList['spotData']  = [
            ["id"=>1,"name" => "榆阳区","value"=> []],
            ["id"=>2,"name" => "横山区","value"=> []],
            ["id"=>3,"name" => "神木市","value"=> []],
            ["id"=>4,"name" => "府谷县","value"=> []],
            ["id"=>5,"name" => "靖边县","value"=> []],
            ["id"=>6,"name" => "定边县","value"=> []],
            ["id"=>7,"name" => "绥德县","value"=> []],
            ["id"=>8,"name" => "米脂县","value"=> []],
            ["id"=>9,"name" => "佳县","value"=> []],
            ["id"=>10,"name" => "吴堡县","value"=> []],
            ["id"=>11,"name" => "清涧县","value"=> []],
            ["id"=>12,"name" => "子洲县","value"=> []],
        ];
        $sellerlist_spot = Db::name('seller')
        ->field('s.nickname,s.latitude,s.longitude,s.area')
        ->alias('s')
        ->select();
        foreach ($dataList['spotData'] as $key => $value) {
            foreach ($sellerlist_spot as $kk => $vv) {
                if($vv['area']==$value['id']){
                    $dataList['spotData'][$key]['value'][] = $vv;
                }
            }
        }
        sort($dataList['spotData']);

        // 基础数据
        // 消费券四个分类下各个的预计发放总量
        //$issuelist = \app\common\model\CouponIssue::field('cid,sum(total_count) as total')->group('cid')->select();
        /*$dataList['list'] = [
            [
                'id'   => 1,
                'name' => '畅游消费券',
                'number' => 0,
                'icons'=>'',
            ],[
                'id'   => 2,
                'name' => '剧院消费券',
                'number' => 0,
                'icons'=>'',
            ],[
                'id'   => 3,
                'name' => '旅行消费券',
                'number' => 0,
                'icons'=>'',
            ],[
                'id'   => 4,
                'name' => '清爽消费券',
                'number' => 0,
                'icons'=>'',
            ],[
                'id'   => 5,
                'name' => '入住商户',
                'number' => 0,
                'icons'=>'',
            ],[
                'id'   => 6,
                'name' => '核销人员',
                'number' => 0,
                'icons'=>'',
            ],
        ];
        //print_r($issuelist->toArray());die;
        for ($i=0; $i < count($issuelist); $i++) {
            if($i+1 == $issuelist[$i]['cid']){
                $dataList['list'][$i]['number'] = $issuelist[$i]['total'];
            }
        }*/
        $dataList['list'] = [
            [
                'id'   => 1,
                'name' => '发行数量',
                'number' => 0,
                'icons'=>'',
            ],[
                'id'   => 2,
                'name' => '核销数量',
                'number' => 0,
                'icons'=>'',
            ],[
                'id'   => 3,
                'name' => '入住商户',
                'number' => 0,
                'icons'=>'',
            ],[
                'id'   => 4,
                'name' => '核验人员',
                'number' => 0,
                'icons'=>'',
            ],
        ];
        // 发行数量
        $faxing_number = \app\common\model\CouponIssue::field('SUM(total_count) as total')->where('1=1')->find();
        $dataList['list'][0]['number'] = $faxing_number['total'];
        // 商户核销数量 = 散客核销数量 + 团体核销数量
        $sanke_number = \app\common\model\WriteOff::count();
        $tuanti_number = \app\common\model\TourWriteOff::count();
        $dataList['list'][1]['number'] = $sanke_number + $tuanti_number;
        // 入住的商户
        $dataList['list'][2]['number'] = \app\common\model\Seller::where('status',1)->count();
        // 核验人员
        $dataList['list'][3]['number'] = \app\common\model\MerchantVerifier::where('status',1)->count();

        // 用户属性统计--获取用户年龄分布
        $userlist = \app\common\model\Users::field('age')->select();
        $dataList['dataAge']  = [
            ["name" => "0~18岁","value"=> 0,],
            ["name" => "18~35岁","value"=> 0,],
            ["name" => "35~55岁","value"=> 0,],
            ["name" => "55以上岁","value"=> 0,]
        ];
        foreach ($userlist as $key => $value) {
            if($value['age'] > 0 && $value['age']<=18){
                $dataList['dataAge'][0]['value'] = $dataList['dataAge'][0]['value'] + 1;
            }else if($value['age'] > 18 && $value['age']<=35){
                $dataList['dataAge'][1]['value'] = $dataList['dataAge'][1]['value'] + 1;
            }else if($value['age'] > 35 && $value['age']<=55){
                $dataList['dataAge'][2]['value'] = $dataList['dataAge'][2]['value'] + 1;
            }else if($value['age'] > 55){
                $dataList['dataAge'][3]['value'] = $dataList['dataAge'][3]['value'] + 1;
            }
        }

        // 用户属性统计--总用户量&实名用户量
        $dataList['dataVali']  = [
            ["name" => "总用户量","value"=> 0,],
            ["name" => "实名用户量","value"=> 0,]
        ];
        $dataList['dataVali'][0]['value'] = \app\common\model\Users::count();
        $dataList['dataVali'][1]['value'] = \app\common\model\Users::where('email_validated',1)->count();
        // 用户属性统计--性别统计
        $dataList['dataSex']  = [
            ["name" => "男性","value"=> 0,],
            ["name" => "女性","value"=> 0,]
        ];
        $dataList['dataSex'][0]['value'] = \app\common\model\Users::where('sex',1)->count();
        $dataList['dataSex'][1]['value'] = \app\common\model\Users::where('sex',2)->count();

        // 2023-03-04 增加统计每一种券的发行、核销情况
        // 获取消费券信息
        $list  = Db::name('coupon_issue')->field('a.total_count,a.coupon_price,a.id,a.coupon_title,b.title')
            ->alias('a')
            ->join(['tp_coupon_class'=>'b'],'a.cid = b.id')
            ->select()
            ->toArray();

        // 核销数量
        $hexiao  = Db::name('write_off')->field('count(1) as total,a.coupon_issue_id')
            ->alias('a')
            ->group('a.coupon_issue_id')
            ->select()
            ->toArray();
        foreach ($list as $key => $value) {
            $list[$key]['faxing_price']   = $value['total_count'] * $value['coupon_price'];
            $list[$key]['writeoff_total'] = 0;
            $list[$key]['writeoff_price'] = 0;
            foreach ($hexiao as $kk => $vv) {
                if($value['id'] == $vv['coupon_issue_id']){
                    $list[$key]['writeoff_total'] = $vv['total'];
                    $list[$key]['writeoff_price'] = $vv['total'] * $value['coupon_price'];
                }
            }
        }
        $dataList['coupon_info_list']  = $list;
        // 访问量统计
        /*$cacheRs = Cache::get('cache_list_Trend');
        if($cacheRs) {
            $dataList['listTrend']  = $cacheRs;
        }else{
            $mmm = intval(date('m'));
            $ddd = intval(date('d')-7);
            $yyy = intval(date('Y'));
            $start_time = mktime(0,0,0,$mmm,$ddd,$yyy);
            $start_reg_time = strtotime(date('Y-m-d 00:00:00', $start_time));
            $now_time   = time() - 86400;
            for ($t = $start_reg_time; $t < $now_time; $t += 86400) {
                $d = date('Ymd', $t);
                $statMap[$d] = 0;
            }
            $seriesDataMember = ($statMap);
            $wxInfo = accesstoken();
            if($wxInfo['code']==0  && $wxInfo['msg']=='ok'){
                $url  = "https://api.weixin.qq.com/datacube/getweanalysisappiddailysummarytrend?access_token=".$wxInfo['data']['access_token'];
                // 数据填充
                foreach($seriesDataMember as $key=>$val){
                    $dateData   = json_encode(['begin_date' => "$key",'end_date'=> "$key"]);
                    $rsData = http_curl_post($url,$dateData);
                    $rsList = json_decode($rsData,true);
                    $dataList['listTrend'][$key] = @$rsList['list'];
                }
                Cache::set('cache_list_Trend',$dataList['listTrend'],86400);
            }
        }*/
        // 2023-03-04 获取最近7天的散客核销数量、金额
        $sqlQy = "SELECT d.date, IFNULL(r.num,0) AS num,IFNULL(r.price,0) AS price
                FROM (
                    SELECT CURDATE() AS date
                    UNION ALL
                    SELECT DATE_SUB(CURDATE(), INTERVAL 1 DAY) AS date
                    UNION ALL
                    SELECT DATE_SUB(CURDATE(), INTERVAL 2 DAY) AS date
                    UNION ALL
                    SELECT DATE_SUB(CURDATE(), INTERVAL 3 DAY) AS date
                    UNION ALL
                    SELECT DATE_SUB(CURDATE(), INTERVAL 4 DAY) AS date
                    UNION ALL
                    SELECT DATE_SUB(CURDATE(), INTERVAL 5 DAY) AS date
                    UNION ALL
                    SELECT DATE_SUB(CURDATE(), INTERVAL 6 DAY) AS date
                    UNION ALL
                    SELECT DATE_SUB(CURDATE(), INTERVAL 7 DAY) AS date
                ) d 
                LEFT JOIN (
                    SELECT from_unixtime(create_time,'%Y-%m-%d')  AS createTime, count(*) AS num,SUM(coupon_price) as price
                    FROM tp_write_off
                    GROUP BY createTime
                    ) r ON r.createTime = d.date
                GROUP BY d.date";
        $dataList['listTrend'] = Db::query($sqlQy);

        // 数据
        $this->apiSuccess('请求成功',$dataList);
    }

    /**
     * @api {post} /screen/index
     * @apiDescription  列表数据
     */
    public function list()
    {
        // 热门商家排名-根据核销数量排名
        $classname['top_seller_20'] = $list  = Db::name('write_off')->alias('aa')
            ->field('count(1) as total, SUM(aa.coupon_price) as price,aa.mid,bb.username,bb.nickname,cc.class_name')
            ->join(['tp_seller'=>'bb'],'aa.mid = bb.id')
            ->join(['tp_seller_class'=>'cc'],'bb.class_id = cc.id')
            ->group('aa.mid')
            ->order('total desc')
            ->select()
            ->toArray();

        // 消费券领取 top 20
        $classname['top_coupon_issue_user_20'] = \app\common\model\CouponClass::select();
        foreach ($classname['top_coupon_issue_user_20'] as $index => $item) {
            $list = [];
            if (isset($item['id'])) {
                switch ($item['id']) {
                    case 1:
                        $list = \app\common\model\CouponIssueUser::field('w.*,u.name')
                            ->alias('w')
                            ->leftJoin('users u', 'w.uid=u.id')
                            ->where('w.issue_coupon_class_id', 1)
                            ->order('w.create_time desc')
                            ->limit(20)
                            ->select();
                        break;
                    case 2:
                        $list = \app\common\model\CouponIssueUser::field('w.*,u.name')
                            ->alias('w')
                            ->leftJoin('users u', 'w.uid=u.id')
                            ->where('w.issue_coupon_class_id', 2)
                            ->order('w.create_time desc')
                            ->limit(20)
                            ->select();
                        break;
                    case 3:
                        $list = \app\common\model\TourIssueUser::field('w.*,u.name')
                            ->alias('w')
                            ->leftJoin('users u', 'w.uid=u.id')
                            ->where('w.type', 1)
                            ->order('w.create_time desc')
                            ->limit(20)
                            ->select();
                        break;
                    case 4:
                        $list = \app\common\model\CouponIssueUser::field('w.*,u.name')
                            ->alias('w')
                            ->leftJoin('users u', 'w.uid=u.id')
                            ->where('w.issue_coupon_class_id', 4)
                            ->order('w.create_time desc')
                            ->limit(20)
                            ->select();
                        break;
                }
            }
            $classname['top_coupon_issue_user_20'][$index]['list'] = $list;
        }

        // 商家最新核销 top 20
        $classname['top_write_off_20'] = \app\common\model\CouponClass::select();
        foreach ($classname['top_write_off_20'] as $index => $item) {
            $list = [];
            if (isset($item['id'])) {
                switch ($item['id']) {
                    case 1:
                        $list = \app\common\model\WriteOff::field('w.*,i.cid')
                            ->alias('w')
                            ->with('users')
                            ->leftJoin('coupon_issue i', 'w.coupon_issue_id=i.id')
                            ->where('i.cid', 1)
                            ->order('w.create_time desc')
                            ->limit(20)
                            ->select();
                        break;
                    case 2:
                        $list = \app\common\model\WriteOff::field('w.*,i.cid')
                            ->alias('w')
                            ->with('users')
                            ->leftJoin('coupon_issue i', 'w.coupon_issue_id=i.id')
                            ->where('i.cid', 2)
                            ->order('w.create_time desc')
                            ->limit(20)
                            ->select();
                        break;
                    case 3:
                        $list = \app\common\model\TourWriteOff::field('w.*,i.cid')
                            ->alias('w')
                            ->with('users')
                            ->leftJoin('coupon_issue i', 'w.coupon_issue_id=i.id')
                            ->where('w.type', 1)
                            ->order('w.create_time desc')
                            ->limit(20)
                            ->select();
                        break;
                    case 4:
                        $list = \app\common\model\WriteOff::field('w.*,i.cid')
                            ->alias('w')
                            ->with('users')
                            ->leftJoin('coupon_issue i', 'w.coupon_issue_id=i.id')
                            ->where('i.cid', 4)
                            ->order('w.create_time desc')
                            ->limit(20)
                            ->select();
                        break;
                }
            }
            $classname['top_write_off_20'][$index]['list'] = $list;
        }
        foreach ($classname['top_write_off_20'] as $key => $value) {
            if(!empty( $value['list'])){
                $temList = $value['list'];
                foreach ($value['list'] as $kk => $vv) {
                    $temList[$kk]['new_create_time'] = date("m月d H:i:s",strtotime($vv['create_time']));
                }
            }
            $classname['top_write_off_20'][$key]['list'] = $temList;
        }
        $this->apiSuccess('请求成功',$classname);
    }
}