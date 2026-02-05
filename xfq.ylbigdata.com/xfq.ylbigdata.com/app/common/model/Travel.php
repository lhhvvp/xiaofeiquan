<?php
/**
 * 商户管理-旅行社商户独立模型
 * @author slomoo <slomoo@aliyun.com> 2022-08-11
 */
namespace app\common\model;

// 引入框架内置类
use think\facade\Db;
use think\Model;
use think\facade\Event;
use think\facade\Request;
use think\facade\Session;

// 引入构建器
use app\common\facade\MakeBuilder;

class Travel extends Base
{

    // 设置当前模型对应的完整数据表名称
    protected $table = 'tp_seller';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 一对一获取所属模块
    public function sellerClass()
    {
        return $this->belongsTo('SellerClass', 'class_id');
    }

    /**
     * 用户是否收藏
     * @return \think\model\relation\HasOne
     */
    public function coll()
    {
        return $this->hasOne('Collection', 'mid','id')->field('mid');
    }

    // 获取列表
    public static function getList(array $where = [], int $pageSize = 0, array $order = ['sort', 'id' => 'desc'])
    {
        $model = new static();
        $model = $model->alias($model->getName());

        // 获取with关联
        $moduleId  = \app\common\model\Module::where('model_name', $model->getName())->value('id');
        $fileds    = \app\common\model\Field::where('module_id', $moduleId)
            ->select()
            ->toArray();
        $listInfo  = [];  // 字段根据关联信息重新赋值
        $withInfo  = [];  // 模型关联信息(用于设置关联预载入)
        $fieldInfo = [];  // 字段包含.的时候从关联模型中获取数据
        foreach ($fileds as $filed) {
            // 数据源为模型数据时设置关联信息
            if ($filed['data_source'] == 2) {
                $listInfo[] = [
                    'field'          => $filed['field'],                   // 字段名称
                    'relation_model' => lcfirst($filed['relation_model']), // 关联模型
                    'relation_field' => $filed['relation_field'],          // 展示字段
                    'type'           => $filed['type'],                    // 字段类型
                    'setup'          => string2array($filed['setup']),     // 字段其他设置
                ];
                $withInfo[] = lcfirst($filed['relation_model']);
            }
            // 字段包含.的时候从关联模型中获取数据
            if (strpos($filed['field'], '.') !== false) {
                // 拆分字段名称为数组
                $filedArr    = explode('.', $filed['field']);
                $fieldInfo[] = [
                    'field'          => $filed['field'],       // 字段名称
                    'relation_model' => lcfirst($filedArr[0]), // 关联模型
                    'relation_field' => $filedArr[1],          // 展示字段
                    'type'           => $filed['type'],        // 字段类型
                ];
            }
        }

        // 关联预载入
        if ($withInfo) {
            $model = $model->with($withInfo);
        }

        // 筛选条件
        if ($where) {
            $whereNew = [];
            $whereHas = [];
            foreach ($where as $v) {
                if (strpos($v[0], '.') === false) {
                    $whereNew[] = $v;
                } else {
                    // 关联模型搜索
                    $filedArr = explode('.', $v[0]);

                    $whereHas[lcfirst($filedArr[0])][] = [
                        'field'        => $filedArr[1],
                        'field_option' => $v[1],
                        'field_value'  => $v[2],
                    ];
                }
            }
            // 关联模型搜索
            if ($whereHas) {
                foreach ($whereHas as $k => $v) {
                    $model = $model->hasWhere($k, function ($query) use ($v) {
                        foreach ($v as $vv) {
                            $query->where($vv['field'], $vv['field_option'], $vv['field_value']);
                        }
                    });
                }
            }
            // 当前模型搜索
            if ($whereNew) {
                $model = $model->where($where);
            }
        }

        // 查询/分页查询
        if ($pageSize) {
            $list = $model->order($order)
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $pageSize,
                ]);
        } else {
            $list = $model->order($order)
                ->select();
        }
        foreach ($list as $k => $v) {
            // 字段根据关联信息重新赋值(多级联动需另行处理)
            foreach ($listInfo as $vv) {
                if ($vv['type'] == 'linkage') {
                    // 拆分字段其他设置为数组
                    $setupFields = explode(',', $vv['setup']['fields']);
                    // 根据末级ID获取每级的联动数据
                    $levelData = getLinkageListData(ucfirst($vv['relation_model']), $v[$vv['field']], $setupFields[0], $setupFields[1], $setupFields[2]);
                    $levelData = array_reverse($levelData); // 以相反的元素顺序返回数组
                    $str       = '';                        // 要转换成的数据
                    foreach ($levelData as $level) {
                        $str .= $level[$setupFields[1]] . '-';
                    }
                    $list[$k][$vv['field']] = rtrim($str, '-');
                } else {
                    // 多选情况
                    if (strpos($v[$vv['field']], ',') !== false) {
                        $hasManyModel = '\app\common\model\\' . $vv['relation_model'];
                        $hasManyPk    = (new $hasManyModel())->getPk();
                        $hasManys     = $hasManyModel::where($hasManyPk, 'in', $v[$vv['field']])->column($vv['relation_field']);
                        if ($hasManys) {
                            $list[$k][$vv['field']] = implode(',', $hasManys);
                        }
                    } else {
                        $list[$k][$vv['field']] = !empty($v->{$vv['relation_model']}) ? $v->{$vv['relation_model']}->getData($vv['relation_field']) : '';
                    }
                }
            }
            // 字段包含.的时候从关联模型中获取数据
            foreach ($fieldInfo as $vv) {
                $list[$k][$vv['field']] = !empty($v->{$vv['relation_model']}) ? $v->{$vv['relation_model']}->getData($vv['relation_field']) : '';
            }
        }

        return MakeBuilder::changeTableData($list, $model->getName());
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
            if (!captcha_check($code)) {
                $data = ['error' => '1', 'msg' => '验证码错误'];
                return json($data);
            }
        }
        $result = self::where(['username' => $username])->find();//, 'password' => md5($password)
        if (empty($result)) {
            $data = ['error' => '1', 'msg' => '账号不存在'];
            return json($data);
        }

        if ($result['class_id']!=3) {
            $data = ['error' => 3, 'msg' => '检测到您是非旅行社商户，请移步其他登录窗口进行操作', 'href' => url('seller/login/index')->__toString()];
            return json($data);
        }

        $check = Request::checkToken('__travel__token__');
        if (false === $check) {
            $data = ['error' => '2', 'msg' => '令牌有误，刷新后重试', 'href' => url('login/index')->__toString()];
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
                self::where('id', '=', $upd_where['id'])
                ->data(['lock_time'=>date('Y-m-d H:i:s', time()),'err_num'=>0])
                ->update();
                $data = ['error' => '1', 'msg' => '账号密码错误次数超过3次、请10分钟后重试'];
                return json($data);
            } else {
                // 错误提示
                self::where('id', '=', $upd_where['id'])->inc('err_num')->update();   // 错误次数+1
                $data = ['error' => '1', 'msg' => '账号密码错误、剩余'. (3 - ($result['err_num'] + 1) ) .'次、请稍后重试'];
                return json($data);
            }
        }

        self::where('id', '=', $result['id'])->update(['err_num'=>0]);

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
            // 2022-08-26 bug 修复  
            $rules['rules']     = !empty($rules['rules']) ? $rules['rules'] . $authOpensStr : $authOpensStr;
            $rules['group_id']  = @$rules['group_id'] ? $rules['group_id'] : 1;
            $rules['title']     = @$rules['title'] ? $rules['title'] : '超管';

            $rules['rules'] = $rules['rules'] . $authOpensStr;

            // 重新查询要赋值的数据[原因是toArray必须保证find的数据不为空，为空就报错]
            $result = self::where(['username' => $username, 'password' => md5($password)])->find();
            Session::set('travel', [
                'id'         => $result['id'],
                'username'   => $result['username'],
                'login_time' => date('Y-m-d H:i:s', $result['login_time']),
                'login_ip'   => $result['login_ip'],
                'nickname'   => $result['nickname'],
                'loginnum'   => $result['loginnum'],
                'image'      => $result['image'],
                'class_id'   => $result['class_id'],
                'email_validated' => $result['email_validated'],
                'status'     => $result['status'],
            ]);
            Session::set('travel.group_id', $rules['group_id']);
            Session::set('travel.rules', explode(',', $rules['rules']));
            Session::set('travel.title', $rules['title']);

            // 触发登录成功事件
            Event::trigger('AdminLogin', $result);

            $data = ['error' => '0', 'href' => url('Index/index')->__toString(), 'msg' => '登录成功'];
            return json($data);

        } else if($result['status'] == 2 || $result['status'] == 3 || $result['status'] == 4){
            Session::set('travel', [
                'id'         => $result['id'],
            ]);
            $data = ['error' => 2, 'href' => url('/seller/register/index')->__toString(), 'msg' => '审核中'];
            return json($data);
        } else {
            return json(['error' => 1, 'msg' => '用户已被禁用!']);
        }
    }

    // 获取列表: 优惠券页面调用
    public static function getSellerList(array $where = [], int $pageSize = 0, array $order = ['id' => 'desc'])
    {
        $model = new static();
        $model = $model->alias($model->getName());

        // 筛选条件
        if ($where) {
            // 当前模型搜索
            $model = $model->where($where);
        }

        $list = $model->order($order)
            ->select();

        return $list;
    }
}