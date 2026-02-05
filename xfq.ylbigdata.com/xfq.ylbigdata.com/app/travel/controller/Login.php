<?php
/**
 * 后台登录控制器
 * @author slomoo <slomoo@aliyun.com> 2022-02-03
 */
namespace app\travel\controller;

use think\captcha\facade\Captcha;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;

class Login
{
    // 登录页面
    public function index()
    {
        // 已登录自动跳转
        if (Session::has('travel') && session('travel')['email_validated'] ==1 && session('travel')['status'] ==1 && session('travel')['class_id'] ==3) {
            return redirect((string)url('Index/index'));
        }
        // 查找系统设置
        $system = \app\common\model\System::find(1);

        $view['mobile'] = Request::isMobile();
        $view['system'] = $system;
        View::assign($view);
        return View::fetch();
    }

    // 校验登录
    public function checkLogin(){
        return \app\common\model\Travel::checkLogin();
    }

    // 验证码
    public function captcha(){
        return Captcha::create();
    }

    // 退出登录
    public function logout(){
        Session::delete('travel');
        return redirect('index');
    }
}
