<?php

namespace app\handheld\controller;

use app\handheld\controller\Base;
use think\Exception;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Request;
use think\facade\Validate;
use app\common\model\MerchantVerifier as MerchantVerifierModel;

class Login extends Base
{

    protected $noNeedLogin = ['login'];

    public function login()
    {
        $post     = Request::post();
        $validate = Validate::rule([
            'username' => 'require',
            'password' => 'require'
        ]);
        $validate->message([
            'username.require' => '请输入用户名！',
            'password.require' => '请输入密码！'
        ]);

        if (!$validate->check($post)) {
            $this->apiError($validate->getError());
        }
        if (!$this->auth->preg_match_account($post["username"])) {
            $this->apiError("账号或密码错误！");
        }
        if (!$this->auth->preg_match_password($post["password"])) {
            $this->apiError("账号或密码错误！");
        }
        if (!$this->auth->login($post["username"], $post["password"])) {
            $this->apiError($this->auth->getError());
        }
        $info = $this->auth->getInfo();
        if($info['mobile']){
            $info['mobile'] = substr($info['mobile'], 0, 3) . "****" . substr($info['mobile'], 7);
        }
        $this->apiSuccess('登录成功！', ['info' =>$info, 'token' => $this->auth->getToken()]);
    }

}