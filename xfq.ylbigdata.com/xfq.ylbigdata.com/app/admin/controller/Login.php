<?php
/**
 * 后台登录控制器
 * @author slomoo <slomoo@aliyun.com> 2022-02-03
 */
namespace app\admin\controller;

use think\captcha\facade\Captcha;
use think\facade\Request;
use think\facade\Session;
use think\facade\View;
use think\facade\Route;

class Login
{
    // 登录页面
    public function index()
    {
        // 运营端入口
        if (Session::has('admin')) {
            return redirect((string)url('Index/index'));
        }
        //print_r(Request::param());die;
        // 查找系统设置
        $system = \app\common\model\System::find(1);

        $view['mobile'] = Request::isMobile();
        $view['system'] = $system;
        View::assign($view);
        return View::fetch();
    }

    // 文旅端入口
    public function supervise()
    {
        // 已登录自动跳转
        if (Session::has('admin')) {
            return redirect((string)url('Index/index'));
        }
        //print_r(Request::param());die;
        // 查找系统设置
        $system = \app\common\model\System::find(1);

        $view['mobile'] = Request::isMobile();
        $view['system'] = $system;
        View::assign($view);
        return View::fetch();
    }

    // 校验登录
    public function checkLogin(){
        return \app\common\model\Admin::checkLogin();
    }

    // 验证码
    public function captcha(){
        return Captcha::create();
    }

    // 退出登录
    public function logout(){
        Session::delete('admin');
        return redirect('/admin/index/index');
    }
}
