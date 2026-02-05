<?php
/**
 * @desc   窗口售票员登录基础信息API
 * @author slomoo
 * @email slomoo@aliyun.com
 */
declare (strict_types = 1);
namespace app\window\controller;

use app\window\BaseController;
use app\window\middleware\Auth;
use app\window\service\JwtAuth;
use think\facade\Db;
use think\facade\Request;
use think\facade\Cache;
use think\captcha\facade\Captcha;
use app\common\model\TicketUser;
use app\common\model\Seller;

class Index extends BaseController
{
    /**
     * 控制器中间件 [账号登录、不需要鉴权]
     * @var array
     */
    
    // 上传验证规则
    protected $uploadValidate = [];

	protected $middleware = [
    	Auth::class => ['except' 	=> ['system','winlogin','captcha'] ]
    ];

    // 构造方法
    public function __construct()
    {
        // 验证规则
        $this->uploadValidate = [
            'file' => $this->uploadVal()
        ];
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
    public function system()
    {
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

        $this->apiSuccess('请求成功',$returnSystem);
    }

    // 验证码
    public function captcha(){
        return Captcha::create();
    }

    /**
     * @api {post} /index/winlogin 小程序登录
     * @apiDescription 系统登录接口，返回 token 用于操作需验证身份的接口
     * @apiParam (响应字段：) {string}       token    Token
     * 2023-08-07 PC售票员登录
     */
    public function winlogin()
    {
        $username        = Request::param('username/s', '');       // 用户账号
        $password      = Request::param('password/s', '');       // 密码
        $pubkey     = Request::param('pubkey/s', '');          // sm2加密
        $code     = Request::param('code/s', '');            // 验证码

        // 批量非空校验
        $requiredParams = [
            'username'      => $username,
            'password'      => $password,
            'pubkey'        => $pubkey,
            'code'        => $code,
        ];

        foreach ($requiredParams as $param => $value) {
            if (empty($value)) {
                $this->apiError($param . '不能为空');
            }
        }

        /*// 解密
        $a = sm2($postData['pubkey']);
        if(!is_array($a)){
            return apiError('参数异常');
        }*/
        
        if (!Captcha::check($code)) {
            $this->apiError('验证码错误');
        }

        // 查询账户是否存在
        $result = TicketUser::where('username', $username)->find();
        if (empty($result)) {
            $this->apiError('帐号或密码错误');
        }

        // 错误超过一定次数校验
        if (!empty($result['lock_time']) && time() - strtotime($result['lock_time']) < 10*60 ) {
            $this->apiError('该账号已被锁定、请10分钟后重试');
        }

        // 密码校验
        $saltedPassword = $password.$result['salt'];
        if (md5($saltedPassword) != $result['password']) {
            // 超过3次锁定
            if ($result['err_num'] >= 2) {
                TicketUser::where('id', $result['id'])
                    ->update(['lock_time' => date('Y-m-d H:i:s'), 'err_num' => 0]);
                $this->apiError('账号密码错误次数超过3次、请10分钟后重试');
            } else {
                // 错误提示
                TicketUser::where('id', $result['id'])->inc('err_num')->update();   // 错误次数+1
                $remainingAttempts = 3 - ($result['err_num'] + 1);
                $this->apiError('账号密码错误、剩余'.$remainingAttempts.'次、请稍后重试');
            }
        }
        TicketUser::where('id', $result['id'])->update(['err_num' => 0]);

        // 校验状态
        if ($result['status'] == 1) {
            // 更新登录IP和登录时间
            TicketUser::where('id', '=', $result['id'])
                ->update(['loginnum'=>$result['loginnum'] + 1,'last_login_time'=>time(),'last_login_ip'=>Request::ip(),'login_time' => time(), 'login_ip' => Request::ip()]);

            // 重新查询要赋值的数据[原因是toArray必须保证find的数据不为空，为空就报错]
            $result = TicketUser::with('seller')->find($result['id']);

            $salt  = $result['uuid'].$result['salt'];
            $token = JwtAuth::getToken($salt);
            $expiry_time = time() + 3600 * 24 * 30;//strtotime("+30 day");
            
            // token入库
            TicketUser::where(['id' => $result['id']])
            ->data(['signpass'=>md5($token.$result['uuid']),'expiry_time'=>$expiry_time])
            ->update();

            // 查询商户信息
            
            $returnData = [
                'id'         => $result['id'],
                'uuid'       => $result['uuid'],
                'username'   => $result['username'],
                'login_time' => date('Y-m-d H:i:s', $result['login_time']),
                'login_ip'   => $result['login_ip'],
                'nickname'   => $result['nickname'],
                'loginnum'   => $result['loginnum'],
                'token'      => $token,
                'm_nickname' => $result['seller']['nickname'],
                'm_id'       => $result['seller']['id'],
                'businesstr' => sys_encryption($result['seller']['id'],'mid'),
            ];
            $this->apiSuccess('登录成功', $returnData);
        }
        $this->apiError('用户已被禁用,请于平台联系');
    }
}