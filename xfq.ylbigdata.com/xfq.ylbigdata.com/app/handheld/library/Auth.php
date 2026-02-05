<?php

namespace app\handheld\library;

use think\facade\Db;
use \app\common\model\MerchantVerifier as MerchantVerifierModel;
use think\Exception;
class Auth
{
    protected static $instance    = null;
    protected        $_error      = '';
    protected        $_logined    = false;
    protected        $_info       = null;
    protected        $_token      = '';
    protected        $_expirytime = 0;       //过期时间
    protected        $keeptime    = 2592000; //登陆持续时间
    protected        $allowFields = ['id', 'type', 'name', 'image', 'mobile', 'token', 'logintime', 'loginerror','status','mid'];

    /**
     *
     * @return Auth
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * 获取Member模型
     * @return User
     */
    public function getMemberModel()
    {
        return $this->_info;
    }

    /**
     * 兼容调用member模型的属性
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->_info ? $this->_info->$name : null;
    }

    /**
     * 兼容调用member模型的属性
     */
    public function __isset($name)
    {
        return isset($this->_info) ? isset($this->_info->$name) : false;
    }

    /**
     * 根据Token初始化
     *
     * @param string $token Token
     * @return boolean
     */
    public function init($token)
    {
        if ($this->_logined) {
            return true;
        }
        if (!$token) {
            $this->setError('登录认证TOKEN不能为空！');
            return false;
        }
        $info = MerchantVerifierModel::where("token", $token)->find();
        if (!$info) {
            $this->setError('核销人不存在！');
            return false;
        }
        if ($info['status'] != 1) {
            $this->setError('核销人状态不允许登陆！');
            return false;
        }
        if ($info['token_time'] < time()) {
            $this->setError('token已失效！');
            return false;
        }
        $info->token      = $token;
        $info->logintime  = time();
        $info->token_time = time() + $this->keeptime;
        $info->save();
        $this->_info    = $info;
        $this->_logined = true;
        $this->_token   = $token;
        return true;

    }

    /**
     * 退出
     *
     * @return boolean
     */
    public function logout()
    {
        if (!$this->_logined) {
            $this->setError('您尚未登录');
            return false;
        }
        //设置登录标识
        $this->_logined = false;
        //删除Token
        $this->_info->token = "";
        $this->_info->save();
        return true;
    }



    /**
     * 用户登录
     *
     * @param string $account  账号,用户名、邮箱、手机号
     * @param string $password 密码
     * @return boolean
     */
    public function login($account, $password)
    {
        //$field = Validate::regex($account, '/^1\d{10}$/') ? 'mobile' : 'account';
        $info = null;
        try{
            $info = MerchantVerifierModel::where("account", '=', $account)->find();
            if (!$info) {
                throw new Exception("账号错误");
            }
            if (($info->loginlock_time + 300) > time()) {
                throw new Exception("登陆被锁定5分钟，请稍后再试");
            }
            if ($info->status !== 1) {
                throw new Exception("用户被拉黑");
            }
            if ($info->password != $this->getEncryptPassword($password, $info->salt)) {
                throw new Exception("密码错误");
            }
        }catch (\Exception $e){
            $this->setError($e->getMessage());
            if($info){
                $info->loginerror = $info->loginerror+1;
                if($info->loginerror >= 5){
                    $info->loginerror = 0;
                    $info->loginlock_time = time();
                }
                $info->save();
            }

            return false;
        }
        //登陆成功情况错误
        return $this->direct($info->id);
    }
    /**
     * 直接登录账号
     * @param int $id
     * @return boolean
     */
    public function direct($id)
    {
        $info = MerchantVerifierModel::where("id", $id)->find();
        if (empty($info)) {
            $this->setError('核销员不存在！');
            return false;
        }
        if ($info['status'] != 1) {
            $this->setError('核销员状态不符！');
            return false;
        }
        $info->token      = gen_uuid();
        $info->logintime  = time();
        $info->token_time = time() + $this->keeptime;
        //情况错误次数
        $info->loginerror = 0;
        //情况加锁时间
        $info->loginlock_time = 0;
        $info->save();
        $this->_info       = $info;
        $this->_logined    = true;
        $this->_token      = $info->token;
        $this->_expirytime = $info->token_time;
        return true;
    }
    /**
     * 判断是否登录
     * @return boolean
     */
    public function isLogin()
    {
        if ($this->_logined) {
            return true;
        }
        return false;
    }

    /**
     * 获取当前Token
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * 获取过期时间戳
     * @return string
     */
    public function getExpirytime()
    {
        return $this->_expirytime;
    }

    /**
     * 获取会员基本信息
     */
    public function getInfo()
    {
        $data        = $this->_info->toArray();
        $allowFields = $this->getAllowFields();
        $info        = array_intersect_key($data, array_flip($allowFields));
        return $info;
    }

    /**
     * 获取允许输出的字段
     * @return array
     */
    public function getAllowFields()
    {
        return $this->allowFields;
    }

    /**
     * 设置允许输出的字段
     * @param array $fields
     */
    public function setAllowFields($fields)
    {
        $this->allowFields = $fields;
    }


    /**
     * 设置会话有效时间
     * @param int $keeptime 默认为永久
     */
    public function setKeeptime($keeptime = 0)
    {
        $this->keeptime = $keeptime;
    }

    public function getKeeptime()
    {
        return $this->keeptime;
    }

    public function setError($error)
    {
        $this->_error = $error;
        return $this;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->_error ? $this->_error : '';
    }

    /**
     * 获取密码加密后的字符串
     * @param string $password 密码
     * @param string $salt     密码盐
     * @return string
     */
    public function getEncryptPassword($password, $salt = '')
    {
        return md5(md5($password) . $salt);
    }

    //账户正则验证  6~32位
    public function preg_match_account($account = "")
    {

        $pattern_ac = '/^[a-zA-Z0-9_]{6,32}$/';
        if (preg_match($pattern_ac, $account)) {
            return true;
        }
        return false;
    }

    //由字母、数字、特殊字符，任意2种组成，6~32位
    public function preg_match_password($password = "")
    {
        $pattern_psw = '/^(?![a-zA-Z]+$)(?!\d+$)(?![^\da-zA-Z\s]+$).{6,32}$/';
        if (preg_match($pattern_psw, $password)) {
            return true;
        }
        return false;
    }
}
