<?php
/**
 * 管理员列表模型
 * @author slomoo <slomoo@aliyun.com> 2022-07-19
 */
namespace app\common\model;

// 引入框架内置类
use think\facade\Db;
use think\facade\Event;
use think\facade\Request;
use think\facade\Session;

use think\captcha\facade\Captcha;

// 引入构建器
use app\common\facade\MakeBuilder;

class Admin extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 获取列表
    public static function getList(array $where = [], int $pageSize = 0, array $order = ['sort', 'id' => 'desc'])
    {
        if ($pageSize) {
            $list = self::where($where)
                ->order($order)
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $pageSize,
                ]);
        } else {
            $list = self::where($where)
                ->order($order)
                ->select();
        }

        $auth = new \Auth();
        foreach ($list as $k => $v) {
            $title  = '';
            $groups = $auth->getGroups($v->id);
            foreach ($groups as $group) {
                $title .= $group['title'] . ',';
            }
            $title                  = rtrim($title, ',');
            $list[$k]['group_name'] = $title;
        }
        return MakeBuilder::changeTableData($list, 'Admin');
    }

    /**
     * 管理员登录校验
     * @return array|\think\response\Json
     * @throws \think\Exception
     */
    public static function checkLogin()
    {
        // 查找所有系统设置表数据
        $system = \app\common\model\System::find(1);

        $postData = Request::param();
        // 解密
        $a = sm2($postData['pubkey']);
        if(!is_array($a)){
            $data = ['error' => '1', 'msg' => '参数异常'];
            return json($data);
        }

        $username  = $a['username'];
        $password  = $a['password'];

        $open_code = $system['code'];
        if ($open_code) {
            $code = $a['vercode'];
            if (!Captcha::check($code)) {
                $data = ['error' => '1', 'msg' => '验证码错误'];
                return json($data);
            }
        }
        // 查询账户是否存在
        $result = self::where(['username' => $username])->find(); // , 'password' => md5($password)
        if (empty($result)) {
            $data = ['error' => '1', 'msg' => '帐号或密码错误'];
            return json($data);
        }
        // token 校验
        $check = Request::checkToken('__token__');
        if (false === $check) {
            $data = ['error' => '2', 'msg' => 'Token验证有误'];
            return json($data);
        }
        // 错误超过一定次数校验
        if($result['lock_time'] != '' && time() - strtotime($result['lock_time']) < 10*60 ){
            $data = ['error' => '1', 'msg' => '该账号已被锁定、请10分钟后重试'];
            return json($data);
        }
        // 密码校验
        $upd_where['id'] = $result['id'];
        if(md5($password) != $result['password']){
            // 超过3次锁定
            if( $result['err_num'] >= 2 ) {
                self::where('id', '=', $result['id'])
                ->data(['lock_time'=>date('Y-m-d H:i:s', time()),'err_num'=>0])
                ->update();
                $data = ['error' => '1', 'msg' => '账号密码错误次数超过3次、请10分钟后重试'];
                return json($data);
            } else {
                // 错误提示
                self::where('id', '=', $result['id'])->inc('err_num')->update();   // 错误次数+1
                $data = ['error' => '1', 'msg' => '账号密码错误、剩余'. (3 - ($result['err_num'] + 1) ) .'次、请稍后重试'];
                return json($data);
            }
        }
        self::where('id', '=', $result['id'])->update(['err_num'=>0]);

        // 校验状态
        if ($result['status'] == 1) {
            $uid = $result['id'];
            // 更新登录IP和登录时间
            self::where('id', '=', $result['id'])
                ->update(['loginnum'=>$result['loginnum'] + 1,'last_login_time'=>time(),'last_login_ip'=>Request::ip(),'login_time' => time(), 'login_ip' => Request::ip()]);

            // 查找规则
            $rules = Db::name('auth_group_access')
                ->alias('a')
                ->leftJoin('auth_group ag', 'a.group_id = ag.id')
                ->field('a.group_id,ag.rules,ag.title')
                ->where('uid', $uid)
                ->find();
            // 查询所有不验证的方法并放入规则中
            $authOpen  = AuthRule::where('auth_open', '=', '0')
                ->select();
            $authRole  = AuthRule::select();
            $authOpens = [];
            foreach ($authOpen as $k => $v) {
                $authOpens[] = $v['id'];
                // 查询所有下级权限
                $ids = getChildsRule($authRole, $v['id']);
                foreach ($ids as $kk => $vv) {
                    $authOpens[] = $vv['id'];
                }
            }

            $authOpensStr   = !empty($authOpens) ? implode(",", $authOpens) : '';
            $rules['rules'] = $rules['rules'] . $authOpensStr;

            // 重新查询要赋值的数据[原因是toArray必须保证find的数据不为空，为空就报错]
            $result = self::where(['username' => $username, 'password' => md5($password)])->find();
            Session::set('admin', [
                'id'         => $result['id'],
                'username'   => $result['username'],
                'login_time' => date('Y-m-d H:i:s', $result['login_time']),
                'login_ip'   => $result['login_ip'],
                'nickname'   => $result['nickname'],
                'loginnum'   => $result['loginnum'],
                'image'      => $result['image'],
            ]);
            Session::set('admin.group_id', $rules['group_id']);
            Session::set('admin.rules', explode(',', $rules['rules']));
            Session::set('admin.title', $rules['title']);

            // 触发登录成功事件
            Event::trigger('AdminLogin', $result);

            $data = ['error' => '0', 'href' => url('Index/index')->__toString(), 'msg' => '登录成功'];
            return json($data);
        } else {
            return json(['error' => 1, 'msg' => '用户已被禁用!']);
        }
    }

}