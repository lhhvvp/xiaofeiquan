<?php
/**
 * 后台注册控制器
 * @author slomoo <slomoo@aliyun.com> 2022-08-08
 */
namespace app\seller\controller;
use think\App;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\captcha\facade\Captcha;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;
use think\facade\Cache;
use think\facade\Config;
use think\Response;
use think\Validate;

class Forgetpwd
{
    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;
    /**
     * 构造方法
     * @access public
     * @param App $app 应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;
    }

    /**
     * 验证数据
     * @access protected
     * @param array        $data     数据
     * @param string|array $validate 验证器名或者验证规则数组
     * @param array        $message  提示信息
     * @param bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                list($validate, $scene) = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        $result = $v->failException(false)->check($data);
        if (true !== $result) {
            return $v->getError();
        } else {
            return $result;
        }
    }

    // 注册
    public function index()
    {
        if(isset(session('seller')['id']))
            $seller = \app\common\model\Seller::where('id',session('seller')['id'])->find();
        else
            $seller = [];

        // 已登录自动跳转并且验证完成才能登录
        if (!empty($seller) && $seller['email_validated'] ==1 && $seller['status'] ==1){
            return redirect((string)url('Index/index'));
        }

        if (!empty($seller) && $seller['status']==3){
            // 查找失败原因
            $logexam = \app\common\model\ExamineRecord::where('sid',$seller['id'])->where('step',3)->order('create_time desc')->find(1);
            if($logexam){
                $view['examine'] = $logexam->toArray();
            }
        }
        // 保存商户基本信息
        if (Request::isPost()) {
            $data   = Request::except(['file'], 'post');
            // 插入图片
            if(isset($data['image']) && $data['image']!='' && $data['id']!=''){
                \app\common\model\Seller::where('id',$data['id'])->update(['image'=>$data['image']]);
            }

            $data['email'] = session('seller')['email'];

            // 表单信息校验
            // ...
            $data['status'] = 2; 

            $result = $this->validate($data, 'Seller');
            if (true !== $result) {
                // 验证失败 输出错误信息
                $this->error($result);
            } else {
                $model  = '\app\common\model\\' . 'Seller';
                $result = $model::editPost($data);
                if ($result['error']) {
                    $this->error($result['msg']);
                } else {
                    $this->success($result['msg'], 'index');
                }
            }
        }

        // 查找系统设置
        $system = \app\common\model\System::find(1);

        $view['mobile'] = Request::isMobile();
        $view['system'] = $system;
        $view['seller'] = $seller;


        // 商户分类
        $SellerClass = \app\common\model\SellerClass::field('id, class_name')
            ->order('sort asc')
            ->select()
            ->toArray();
        // 区域分类
        $areaClass = Config::get('lang.area');
        $view['class_list']  = $SellerClass;
        $view['areaClass']   = $areaClass;
        View::assign($view);
        return View::fetch('index');
    }

    // 验证码
    public function captcha(){
        return Captcha::create();
    }

    public function checkPwd(){
        if(isset(session('seller')['id'])){
            $seller = \app\common\model\Seller::where('id',session('seller')['id'])->find();
        } else {
            return redirect((string)url('forgetpwd/index'));
        }

        if(Request::isPost() && !empty($seller)){
            $result = [
                'error' => 0,
                'msg'  => '',
                'data' => '',
                'url'  => '',
                'wait' => 0,
            ];
            $newpassword  = Request::param("newpassword");
            $password  = Request::param("password");
            // 密码长度不能低于6位
            if (strlen($password) < 6) {
                $result['error'] = 1;
                $result['msg']   = lang('password length error', [6]);
                return json($result);
            }

            // 确认密码
            if ($password != $newpassword) {
                $result['error'] = 1;
                $result['msg']   = lang('password disaccord');
                return json($result);
            }

            // 修改邮件验证为已经验证
            $where['id'] = $seller['id'];
            $data = [
                'password'        => md5($password),
                'update_time'     => time()
            ];
            if (\app\common\model\Seller::update($data, $where)) {
                // 清空session
                Session::delete('seller');
                $result['msg']   = '修改成功';
                return json($result);
            } else {
                $result['error'] = 1;
                $result['msg']   = '修改失败';
                return json($result);
            }
        }

        // 查找系统设置
        $system = \app\common\model\System::find(1);

        $view['mobile'] = Request::isMobile();
        $view['system'] = $system;
        $view['seller'] = $seller;


        // 商户分类
        $SellerClass = \app\common\model\SellerClass::field('id, class_name')
            ->order('sort asc')
            ->select()
            ->toArray();
        // 区域分类
        $areaClass = Config::get('lang.area');
        $view['class_list']  = $SellerClass;
        $view['areaClass']   = $areaClass;
        View::assign($view);
        return View::fetch();
    }

    // 第一步 校验注册
    public function check(){
        $email     = trim(Request::post("email", '', 'htmlspecialchars'));
        $username  = trim(Request::post("username", '', 'htmlspecialchars'));
        $mailcode  = trim(Request::post("mailcode", '', 'htmlspecialchars'));
        $code        = trim(Request::post("captcha", '', 'htmlspecialchars'));
        $result = [
            'error' => 0,
            'msg'  => '',
            'data' => '',
            'url'  => '',
            'wait' => 0,
        ];

        // 非空判断
        if (empty($email) || empty($username) || empty($mailcode) || empty($code)) {
            $result['error'] = 1;
            $result['msg']   = lang('info is not');
            return json($result);
        }

        // 邮箱合法性判断
        if (!is_email($email)) {
            $result['error'] = 1;
            $result['msg']   = lang('email format error');
            return json($result);
        }

        // 普通验证码
        $system = \app\common\model\System::find(1);
        if ($system['code']) {
            if (!captcha_check($code)) {
                $result['error'] = 1;
                $result['msg']   = '验证码错误';
                return json($result);
            }
        }

        // 邮箱验证码验证
        $mailVcode = Cache::get("forgetpwd_email_code_" . $email);
        if(!$mailVcode){
            $result['error'] = 1;
            $result['msg']   = '邮箱验证码已经过期';
            return json($result);
        }
        if($mailVcode!=$mailcode){
            $result['error'] = 1;
            $result['msg']   = '邮箱验证码错误';
            return json($result);
        }

        // 查账号与邮箱是否一致
        $sInfo = \app\common\model\Seller::where('username', '=', $username)->where('email','=',$email)->find();
        if (!$sInfo) {
            $result['error'] = 1;
            $result['msg']   = '未查到信息';
            return json($result);
        }
        Session::set('seller', [
            'id'         => $sInfo['id'],
        ]);
        $result['error'] = 0;
        $result['msg']   = 'success';
        $result['data']  = $sInfo;
        return json($result);
    }

    // 生成6位随机验证码
    public function codestr(){
        $arr=array_merge(range('0','9'));
        shuffle($arr);
        $arr=array_flip($arr);
        $arr=array_rand($arr,6);
        $res='';
        foreach ($arr as $v){
            $res.=$v;
        }
        return $res;
    }

    // 第二步 发送验证码到邮件
    public function sendEmail(){
        $sender = Request::param('email');
        // 检查是否邮箱格式
        if (!is_email($sender)) {
            return json(['error' => 1, 'msg' => '邮箱码格式有误']);
        }
        // 检查配置项信息
        $data = \app\common\model\Config::where('inc_type','smtp')
            ->select();
        $config = convert_arr_kv($data,'name','value');
        // 所有项目必须填写
        if (empty($config['smtp_server']) || empty($config['smtp_port']) || empty($config['smtp_user']) || empty($config['smtp_pwd'])) {
            return json(['error' => 1, 'msg' => '请完善邮件配置信息！']);
        }

        // 限制1分钟内不能重复发送
        $last_time = Cache("forgetpwd_email_time_" . $sender);
        $countdown = 60;
        $fds = time() - $last_time;
        if ($fds < $countdown) {
            return json(['error' => 1, 'msg' => '发送频繁,请 ' . ($countdown - $fds) . ' 秒稍后重试~']);
        }

        // 限制ip每天只能发送5次
        $send_times = Cache("forgetpwd_ip_" . request()->ip());
        if ($send_times > 5) {
            return json(['error' => 1, 'msg' => '请24小时后再次尝试-1']);
        }

        // 邮箱每天只能发送10次code
        $send_times = Cache("register_times_" . $sender);
        if ($send_times > 10) {
            return json(['error' => 1, 'msg' => '请24小时后再次尝试-2']);
        }

        $code = $this->codestr();
        // 验证码主题
        $body = '<span style="dispaly:block;color:#666;font-size:16px">您的验证码为：</span>
                <p style="color:#000;font-size:28px;font-weight: bold;margin:0;"> '.$code.'</p>
                <span style="dispaly:block;color:#000000">请在30分钟内使用该验证码，如果不是本人操作，请忽略此信息。【'.$config['email_id'].'】</span>
                <hr/>
                <span style="dispaly:block;color:#666">该邮件为系统发出，请勿回复</span>';

        $send = send_email($sender, '普通商户找回密码邮箱验证',$body);

        if ($send) {
            // 记录发送验证码的时间，用于验证1分钟内无法连续发送,这个有效期无所谓,比一分钟长就行
            Cache::set("forgetpwd_email_time_" . $sender, time(), 65);

            // 记录手机号发送验证码的次数，用于验证手机号/邮箱一天内发送次数
            Cache::remember('register_times_' . $sender, 0, 60 * 60 * 24);
            Cache::inc('register_times_' . $sender);

            // 记录IP的次数，用于IP一天内发送次数
            Cache::remember('forgetpwd_ip_' . request()->ip(), 0, 60 * 60 * 24);
            Cache::inc('forgetpwd_ip_' . request()->ip());

            // 把验证码存入缓存中去,注册的时候需要比对是否正确 有效期30分钟
            Cache::set("forgetpwd_email_code_" . $sender, $code, 60 * 30);

            return json(['error' => 0, 'msg' => '邮件发送成功！','countdown'=>$countdown]);
        } else {
            return json(['error' => 1, 'msg' => '邮件发送失败！','countdown'=>$countdown]);
        }
    }

    /**
     * 操作错误跳转
     * @param mixed   $msg    提示信息
     * @param string  $url    跳转的URL地址
     * @param mixed   $data   返回的数据
     * @param integer $wait   跳转等待时间
     * @param array   $header 发送的Header信息
     * @return void
     */
    protected function error($msg = '', string $url = null, $data = '', int $wait = 3, array $header = []): Response
    {
        if (is_null($url)) {
            $url = request()->isAjax() ? '' : 'javascript:history.back(-1);';
        } elseif ($url) {
            $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : app('route')->buildUrl($url)->__toString();
        }

        $result = [
            'code' => 0,
            'msg'  => $msg,
            'data' => $data,
            'url'  => $url,
            'wait' => $wait,
        ];

        $type = (request()->isJson() || request()->isAjax()) ? 'json' : 'html';
        if ($type == 'html') {
            $response = view(app('config')->get('app.dispatch_error_tmpl'), $result);
        } else if ($type == 'json') {
            $response = json($result);
        }
        throw new HttpResponseException($response);
    }

    /**
     * 操作成功跳转
     * @param mixed   $msg    提示信息
     * @param string  $url    跳转的URL地址
     * @param mixed   $data   返回的数据
     * @param integer $wait   跳转等待时间
     * @param array   $header 发送的Header信息
     * @return void
     */
    protected function success($msg = '', string $url = null, $data = '', int $wait = 3, array $header = []): Response
    {
        if (is_null($url) && isset($_SERVER["HTTP_REFERER"])) {
            $url = $_SERVER["HTTP_REFERER"];
        } elseif ($url) {
            $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : app('route')->buildUrl($url, get_back_url())->__toString();
        }

        $result = [
            'code' => 1,
            'msg'  => $msg,
            'data' => $data,
            'url'  => $url,
            'wait' => $wait,
        ];

        $type = (request()->isJson() || request()->isAjax()) ? 'json' : 'html';
        if ($type == 'html') {
            $response = view(app('config')->get('app.dispatch_success_tmpl'), $result);
        } else if ($type == 'json') {
            $response = json($result);
        }
        throw new HttpResponseException($response);
    }
}
