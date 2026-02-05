<?php
/**
 * @desc   小程序登录注册基础信息API
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

class Index extends BaseController
{
    /**
     * 控制器中间件 [账号登录、注册、小程序登录、注册 不需要鉴权]
     * @var array
     */
    
    // 上传验证规则
    protected $uploadValidate = [];

	protected $middleware = [
    	Auth::class => ['except' 	=> ['system','miniwxlogin','jia','jie','note_list','note_detail','note_index','getuserphonenumber','get_area_info','set_user_info','regeo'] ]
    ];

    // 构造方法
    public function __construct()
    {
        // 验证规则
        $this->uploadValidate = [
            'file' => $this->uploadVal()
        ];
    }

    // 测试token
    public function login()
    {
        // $token = JwtAuth::getToken(2);
        // 00f93492d2ecb6d06f472f62cc629f24
        // 插入库内
        //$expiry_time = strtotime("+7 days");
        //Db::name('users')->where(['id' => 2])->data(['signpass'=>md5($token),'expiry_time'=>$expiry_time])->update();
        $this->apiSuccess('请求成功');
    }

    // 上传验证规则
    private function uploadVal()
    {
        $file = [];
        // 文件限制
        $file['fileExt'] = 'xls,xlsx';
        // 限制文件大小(单位b)
        $file['fileSize'] = 5 * 1024 * 1024;
        return $file;
    }

    /**
     * @api {post} /index/system 系统设置
     * @apiDescription  返回系统信息
     */
    public function jia()
    {
        $str = 'MzI5OWdGUUFoRXY1TkdDeU95QjlWQURNazJydWgyOXRlR3dNaEV2OUtHU21NeE9zYw%3D%3D';
        $key = 'bbbbb';//set_salt();
        $aaa = symencryption($str,$key);
        $this->apiSuccess('请求成功',$aaa);
    }

    /**
     * @api {post} /index/system 系统设置
     * @apiDescription  返回系统信息
     */
    public function jie()
    {
        $str = 'R05RVElHUzVOUS1JLVVPU1pUZWZWQ01MV0NETGU9U2R6UU1nbUFlRVBOTzRhY2dmd1R3R21ZTm5YTmt0SUR3ODNYT09BTGQ3UlQ3YkExaT1IRFROM2FiY3gwOE0tQzlNOVdpPVpTUngt';
        $key = 'bbbbb';//set_salt();
        $aaa = symdecrypt($str,$key);
        // 校验如果不是一个正确的手机号或者身份证号则加密串有问题
        $this->apiSuccess('请求成功',$aaa);
    }

    /**
     * @api {post} /index/system 系统设置
     * @apiDescription  返回系统信息
     */
    public function system()
    {
       /* $returnSystem = Cache::get('cache_list_system_info');
        if($returnSystem) {
            $this->apiSuccess('请求成功',$returnSystem);
        }*/

        $system = \app\common\model\System::find(1);
        $returnSystem['service'] = $system['service'];
        $returnSystem['policy']  = $system['policy'];
        $returnSystem['name']    = $system['name'];
        $returnSystem['logo']    = $system['logo'];
        $returnSystem['copyright'] = $system['copyright'];
        $returnSystem['act_rule'] = $system['act_rule'];
        $returnSystem['tel'] = $system['tel'];

        // 是否前往体验页面
        $returnSystem['is_open_api'] = $system['is_open_api'];
        // 是否开启滑块验证码
        $returnSystem['message_code'] = $system['message_code'];
        // 排队时间
        $returnSystem['is_queue_number'] = $system['is_queue_number'];
        // 二维码有效时间
        $returnSystem['is_qrcode_number'] = $system['is_qrcode_number'];
        // 是否开启打卡
        $returnSystem['is_clock_switch'] = $system['is_clock_switch'];
        // 轮播
        $returnSystem['slide'] = \app\common\model\Slide::where('status',1)->where('tags','index')->find();

        //Cache::set('cache_list_system_info',$returnSystem,900);
        $this->apiSuccess('请求成功',$returnSystem);
    }

    /**
     * @api {post} /index/getuserphonenumber 小程序登录
     * @apiDescription 系统登录接口，返回 token 用于操作需验证身份的接口
     * @apiParam (请求参数：) {string}       code 临时登录凭证 code 
     * @apiParam (请求参数：) {string}       encryptedData 加密数据
     * @apiParam (请求参数：) {string}       iv iv

     * @apiParam (响应字段：) {string}       token    Token
     */
    public function getuserphonenumber()
    {
        $param  = get_params();
        // 小程序登录所需参数 缺一不可
        if(empty($param['code'])){
            $this->apiError('参数错误');
        }

        $wxInfo = accesstoken();
        if($wxInfo['code']==0  && $wxInfo['msg']=='ok'){
            $url  = "https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token=".$wxInfo['data']['access_token'];
            // 不加json 报47001
            $data['code'] = $param['code'];
            $res  = https_request($url,json_encode($data),'json');
            $tokenData = json_decode($res, true);
            if(!empty($tokenData['errcode'])){
                // 跟新token
                updateAccesstoken();
                $this->apiError("获取错误，错误码：".$tokenData['errcode']);
            }else{
                $this->apiSuccess('请求成功',$tokenData);
            }
        }else{
            $this->apiError("获取错误,请重试！".$wxInfo['msg']);
        }
    }

    /**
     * @api {post} /index/miniwxlogin 小程序登录
     * @apiDescription 系统登录接口，返回 token 用于操作需验证身份的接口
     * @apiParam (请求参数：) {string}       code 临时登录凭证 code 
     * @apiParam (请求参数：) {string}       encryptedData 加密数据
     * @apiParam (请求参数：) {string}       iv iv

     * @apiParam (响应字段：) {string}       token    Token
     * 
     * 2023-06-05 登录逻辑重写
     * 
     */
    public function miniwxlogin()
    {
        //$jwtAuth = JwtAuth::getInstance();
        $param  = get_params();
        // 小程序登录所需参数 缺一不可
        /*if(empty($param['code']) || empty($param['encryptedData']) || empty($param['iv'])) {
            $this->apiError('参数错误');
        }*/
        //2023-09-13 新登录
        if(empty($param['code'])) {
            $this->apiError('参数错误');
        }
        // 获取微信小程序参数
        $wechat = \app\common\model\System::find(1);
        // 微信登录地址
        $infourl  = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $wechat['appid'] . "&secret=" . $wechat['appsecret'] . "&js_code=" . $param['code'] . "&grant_type=authorization_code";
        $jsonData = http_curl_get($infourl, true);
        $jsonData = json_decode($jsonData,true);
        
        if(!isset($jsonData['session_key'])){
            $this->apiError('数据异常：sessionKey不存在');
        }
/*
        $sessionKey = $jsonData["session_key"];

        // iv长度
        if(strlen($param['iv']) != 24){
            $this->apiError("iv长度错误");
        }
        // 加密函数
        if(!function_exists('openssl_decrypt')){
            $this->apiError("openssl不支持");
        }
        // 解密
        $result  = openssl_decrypt(base64_decode($param['encryptedData']), "AES-128-CBC", base64_decode($sessionKey), 1, base64_decode($param['iv']));
        $rawData = json_decode($result, true);

        if($rawData == NULL){
            $this->apiError("获取错误,请重试！");
        }*/
        $data['openid']     =   isset($jsonData["openid"]) ? $jsonData["openid"] : NULL;

        // 检查当前用户是否存在
        $user = Db::name('users')->where(['openid'=>$data['openid']])->find();

        // 已经注册过
        if (!empty($user)) {
            if ($user['status'] == '-1') {
                $this->apiError('该用户禁止登录,请于平台联系');
            }

            $upData = [
                'openid'            => $data['openid'],
                'last_login_time'   => time(),
                'last_login_ip'     => request()->ip(),
            ];
            $res = Db::name('users')->where(['id' => $user['id']])->update($upData);
            if($res){
                //获取jwt的句柄
                //$jwtAuth = JwtAuth::getInstance();
                //$token   = $jwtAuth->setUid($user['id'])->encode()->getToken();
                $token = JwtAuth::getToken($user['id']);
                $expiry_time = time() + 3600 * 24 * 30;//strtotime("+30 day");
                // token入库
                Db::name('users')->where(['id' => $user['id']])->data(['signpass'=>md5($token),'expiry_time'=>$expiry_time])->update();
                $this->apiSuccess('登录成功',['token' => $token,'userinfo'=>$user]);
            }
        }
        $this->apiError('未注册',$data,4444);


        /*// 注册流程
        $inData['salt']         = set_salt(6); // 永久加密盐  用于手机号  身份证号加密
        $inData['create_time']  = time();
        $inData['mobile']       = $param['mobile'];
        $inData['mobile_validated'] = 1;
        $inData['openid']       = $data['openid'];
        $inData['nickname']     = $data['nickname'];
        $inData['headimgurl']   = $data['headimgurl'];
        $inData['sex']          = $data['sex'];
        $inData['create_ip']    = request()->ip();
        $inData['uuid']         = gen_uuid();
        $inData['last_login_ip']= request()->ip();
        $inData['last_login_time']= time();
        $uid  = Db::name('Users')->strict(false)->field(true)->insertGetId($inData);
        if($uid){
            //获取jwt的句柄
            //$jwtAuth = JwtAuth::getInstance();
            //$token   = $jwtAuth->setUid($uid)->encode()->getToken();
            $token = JwtAuth::getToken($uid);
            // token入库
            Db::name('users')->where(['id' => $uid])->data(['signpass'=>md5($token)])->update();
            // 查询用户信息
            $regUser = Db::name('users')->where(['id' => $uid])->find();
            $this->apiSuccess('注册成功',['token' => $token,'userinfo'=>$regUser]);
        }else{
            $this->apiError('注册失败');
        }*/
    }

    /**
     * @api {post} /index/note_list
     * @apiDescription  公告列表
     */
    public function note_list()
    {
        $param = get_params();
        $where = array();
        $where[] = ['status', '=', 1];
        $note = \app\common\model\Notice::where($where)
            ->order('create_time desc')
            ->select()
            ->toArray();
        $this->apiSuccess('请求成功',$note);
    }

    /**
     * @api {post} /index/note_detail
     * @apiDescription  公告详情
     */
    public function note_detail()
    {
        $param = get_params();

        $where = [];

        if(empty($param['id']) || $param['id'] == 0 || is_int($param['id'])){
            $this->apiError('参数错误');
        }

        $where[] = ['id','=',$param['id']];
        $where[] = ['status','=',1];

        $list = \app\common\model\Notice::where($where)
        -> find();
        // 访问量+1
        Db::name('Notice')
        ->where('id',$param['id'])
        ->Inc('hits', 1)
        ->update();
        $this->apiSuccess('请求成功',$list);
    }

    /**
     * @api {post} /index/note_index
     * @apiDescription  公告列表
     * 2022-08-28 调整为展示多条
     */
    public function note_index()
    {
        $where = [];
        $where[] = ['status','=',1];
        $list = \app\common\model\Notice::where($where)->order('sort desc')
        ->limit(3)
        -> select();
        $this->apiSuccess('请求成功',$list);
    }

    /**
     * @api {post} /index/transform 获取区域列表
     * @apiDescription  返回区域列表
     */
    public function transform()
    {   
        $param  = get_params();
        if(empty($param['longitude']) || empty($param['latitude'])){
            $this->apiError('参数错误');
        }
        $info = geocoder($param['longitude'],$param['latitude']);
        if ($info['status']!=0) $this->apiError($info['message']);
        $this->apiSuccess('请求成功',$info);
    }

    /**
     * [get_area_info 根据省份Id获取城市]
     * @return   [type]            [根据省份Id获取城市]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-04-17
     * @LastTime 2023-04-17
     * @version  [1.0.0]
     */
    public function get_area_info()
    {
        $param  = get_params();
        $where = [];
        $where[] = ['pid','=',$param['pid']];
        $list = \app\common\model\Area::where($where)->select();
        $this->apiSuccess('请求成功',$list);
    }

    public function set_user_info()
    {
        
        exit;
        $host = "https://dfidveri.market.alicloudapi.com";
        $path = "/verify_id_name";
        $method = "POST";
        $appcode = "1fb45072d6ea46d4b6f1db63bdb6b78b";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        //根据API的要求，定义相对应的Content-Type
        array_push($headers, "Content-Type".":"."application/x-www-form-urlencoded; charset=UTF-8");
        $querys = "";
                $bodys = "id_number=610822200807162011&name=蔚卓轩";
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
                print_r($res);
        exit;
        set_time_limit(0);
        $host = "https://dfidveri.market.alicloudapi.com";
        $path = "/verify_id_name";
        $method = "POST";
        $appcode = "1fb45072d6ea46d4b6f1db63bdb6b78b";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        //根据API的要求，定义相对应的Content-Type
        array_push($headers, "Content-Type".":"."application/x-www-form-urlencoded; charset=UTF-8");

        $sql = "SELECT u.id,u.`name`,u.idcard,u.credit_score FROM tp_write_off as a 
        LEFT JOIN tp_coupon_issue_user as b on a.coupon_issue_user_id = b.id 
        LEFT JOIN tp_users as u on b.uid = u.id 
        WHERE u.credit_score = -1 GROUP BY b.uid";

        $userList = Db::query($sql);

        //print_r($userList);die;

        try {
            foreach ($userList as $key => $value) {
                // code...
                $idcard =trim($value['idcard']);
                $name =trim($value['name']);
                //sleep(1);
                $querys = "";
                $bodys = "id_number=".$idcard."&name=".$name;
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
                
                    $resData = json_decode($res,true);
                    if($resData['status']=='OK' && $resData['state']==1){
                        // 桃花分=9999
                        Db::name('users')->where('id',$value['id'])->update(['credit_score'=>9999]);
                    }
            }
            $this->apiSuccess('成功');
        } catch (Exception $e) {
            $this->apiError('错误id:'.$value['id'].", message:".$e->getMessage());
        }
    }

    public function regeo()
    {
        exit();
        set_time_limit(0);

        $sql = "SELECT 
        a1.id,
        a1.uw_longitude,
        a1.uw_latitude,
        a1.province,
        a1.city,
        a1.district,
        a1.formatted_address

FROM tp_write_off as a1 WHERE a1.province is null AND a1.uw_longitude !=1 AND a1.uw_longitude !=0 AND uw_longitude !=2";

        $list = Db::query($sql);
        foreach ($list as $key => $value) {
            $curl = curl_init();
            $url = 'https://restapi.amap.com/v3/geocode/regeo?parameters&key=19667e1adaafae09037e859664af0cde&location='.$value['uw_longitude'].','.$value['uw_latitude'];

            curl_setopt_array($curl, [
              CURLOPT_URL => $url,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => "",
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 30,
              CURLOPT_SSL_VERIFYPEER => false,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => "GET",
              CURLOPT_POSTFIELDS => "-----011000010111000001101001--\r\n\r\n",
              CURLOPT_HTTPHEADER => [
                "content-type: multipart/form-data; boundary=---011000010111000001101001"
              ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            $resData = json_decode($response,true);
            if($resData['status']==1 && $resData['info']=='OK'){
                $upData = [
                    'province' => $resData['regeocode']['addressComponent']['province'] ? $resData['regeocode']['addressComponent']['province'] : NULL,
                    'city' => $resData['regeocode']['addressComponent']['city'] ? $resData['regeocode']['addressComponent']['city'] : NULL,
                    'district' => $resData['regeocode']['addressComponent']['district'] ? $resData['regeocode']['addressComponent']['district'] : NULL,
                    'formatted_address' => $resData['regeocode']['formatted_address'] ? $resData['regeocode']['formatted_address'] : NULL,
                ];
                Db::name('write_off')->where('id',$value['id'])->data($upData)->update();
            }
        }

        $this->apiSuccess('成功');
    }
}