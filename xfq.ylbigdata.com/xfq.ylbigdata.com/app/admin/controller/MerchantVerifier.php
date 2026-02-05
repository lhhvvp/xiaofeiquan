<?php
/**
 * 商户核验人管理控制器
 * @author slomoo <1103398780@qq.com> 2022/08/01
 */

namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\Session;
use think\facade\View;
use think\facade\Db;

class MerchantVerifier extends Base
{
    // 验证器
    protected $validate = 'MerchantVerifier';

    // 当前主表
    protected $tableName = 'merchant_verifier';

    // 当前主模型
    protected $modelName = 'MerchantVerifier';

    public function index()
    {
        // 搜索
        $model = '\app\common\model\\' . $this->modelName;
        if (Request::param('getList') == 1) {
            $param   = Request::param();
            $where   = [];
            $s_where = [];
            if (isset($param['name']) && !empty($param['name'])) {
                $where[] = ['MerchantVerifier.name', 'like', "%" . $param['name'] . "%"];
            }
            if (isset($param['mobile']) && !empty($param['mobile'])) {
                $where[] = ['MerchantVerifier.mobile', '=', $param['mobile']];
            }
            if (isset($param['status']) && $param['status'] . '' !== '') {
                $where[] = ['MerchantVerifier.status', '=', $param['status']];
            }
            if (isset($param['seller_nickname']) && !empty($param['seller_nickname'])) {
                $s_where[] = ['seller.nickname', 'like', "%" . $param['seller_nickname'] . "%"];
            }
            $order = ($param['orderByColumn'] ?? 'id') . ' ' . ($param['isAsc'] ?? 'desc');
            $list  = $model::with('seller')
                ->where($where)
                ->hasWhere("seller", $s_where)
                ->order($order)
                ->append(["status_text"])
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $this->pageSize,
                ]);

            return ['total' => $list->total(), 'current_page' => $list->currentPage(), 'last_page' => $list->lastPage(), 'data' => $list->items()];
        }
        $status_list = $model::getStatusList();
        View::assign("status_list", $status_list);
        return View::fetch();
    }

    // 添加保存
    public function addPost()
    {
        if (Request::isPost()) {
            $data = MakeBuilder::changeFormData(Request::except(['file'], 'post'), $this->tableName);

            // xss过滤
            $data['name']   = removeXSS(filterText($data['name']));
            $data['image']  = removeXSS(filterText($data['image']));
            $data['mobile'] = removeXSS(filterText($data['mobile']));
            $data['openid'] = isset($data['openid']) ? removeXSS(filterText($data['openid'])) : '';

            $result = $this->validate($data, $this->modelName);
            if (true !== $result) {
                $this->error($result);
            }
            $model           = '\app\common\model\\' . $this->modelName;
            $data["account"] = trim($data["account"]);
            if (empty($data["account"])) {
                $this->error('请输入登陆账户！');
            }
            if (!\app\handheld\library\Auth::instance()->preg_match_account($data["account"])) {
                $this->error('账号需大小写字母、数字、下划线组成，至少6位，最多32位！');
            }
            if (!\app\handheld\library\Auth::instance()->preg_match_password($data["password"])) {
                $this->error("密码需字母、数字、特殊字符任意2种组成,至少6位，最多32位！");
            }
            if ($model::where("account", $data["account"])->find()) {
                $this->error('账号重复，请更换后再试！');
            }
            //生成密码盐
            $data['salt'] = buildRandom(6, 3);
            //获取密码
            $data["password"] = \app\handheld\library\Auth::instance()->getEncryptPassword($data["password"], $data["salt"]);
            $result           = $model::addPost($data);
            if ($result['error']) {
                $this->error($result['msg']);
            } else {
                $this->success($result['msg'], 'index');
            }
        }
    }

    // 编辑保存
    public function editPost()
    {
        if (Request::isPost()) {
            $data = MakeBuilder::changeFormData(Request::except(['file'], 'post'), $this->tableName);

            // xss过滤
            $data['name']   = removeXSS(filterText($data['name']));
            $data['image']  = removeXSS(filterText($data['image']));
            $data['mobile'] = removeXSS(filterText($data['mobile']));
            $data['openid'] = isset($data['openid']) ? removeXSS(filterText($data['openid'])) : '';

            $result = $this->validate($data, $this->modelName);
            if (true !== $result) {
                $this->error($result);
            }
            $model = '\app\common\model\\' . $this->modelName;
            $data["account"] = trim($data["account"]);
            if (empty($data["account"])) {
                $this->error('请输入登陆账户！');
            }
            if (!\app\handheld\library\Auth::instance()->preg_match_account($data["account"])) {
                $this->error('账号需大小写字母、数字、下划线组成，至少6位，最多32位！');
            }
            if ($model::where([["account", "=", $data["account"]], ["id", "<>", $data["id"]]])->find()) {
                $this->error('账号重复，请更换后再试！');
            }
            $result = $model::editPost($data);
            if ($result['error']) {
                $this->error($result['msg']);
            } else {
                $this->success($result['msg'], 'index');
            }
        }
    }

    //2023-09-05 修改密码
    public function editPassword()
    {
        if(!Request::isPost()){
            $this->error("请求方式错误！");
        }
        $data = Request::post();
        if (empty($data["id"])) {
            $this->error("参数错误！");
        }
        if (empty($data["password"])) {
            $this->error("请输入密码！");
        }
        //修改密码
        if (!\app\handheld\library\Auth::instance()->preg_match_password($data["password"])) {
            $this->error("密码需字母、数字、特殊字符任意2种组成,至少6位，最多32位！");
        }
        //生成密码盐
        $salt = buildRandom(6, 3);
        //获取密码
        $password       = \app\handheld\library\Auth::instance()->getEncryptPassword($data["password"], $salt);
        $model          = '\app\common\model\\' . $this->modelName;
        $info           = $model::where("id", $data["id"])->find();
        $info->salt     = $salt;
        $info->password = $password;
        $info->save();
        $this->success("修改成功！");
    }

    //审核操作
    public function check()
    {
        if (Request::isGet()) {
            $param = Request::param();
            // 查询商家详情
            $row = \app\common\model\MerchantVerifier::where('id', $param['id'])
                ->with('seller')
                ->append(['status_text'])
                ->order('create_time desc')
                ->findOrEmpty()
                ->toArray();
            // 审核记录
            $ExamineRecord = \app\common\model\MerchantVerifierApprove::where('mv_id', $param['id'])
                ->order('create_time desc')
                ->with(['admin'])
                ->select();
            View::assign(['row' => $row, 'ExamineRecord' => $ExamineRecord]);
            return View::fetch('merchant_verifier/check');
        } else {
            $data = Request::except(['file'], 'post');
            if ($data['approve'] == 0 && empty($data['remark'])) {
                $this->error('请填写审核备注');
            }
            $logData['mv_id']       = $data['id'];
            $logData['approve']     = $data['approve'];
            $logData['remark']      = $data['remark'];
            $logData['admin_id']    = Session::get('admin.id');
            $logData['create_time'] = time();
            // 记录审核记录
            \app\common\model\MerchantVerifierApprove::strict(false)->insertGetId($logData);
            // 修改商户信息
            $data['update_time'] = time();
            $save                = \app\common\model\MerchantVerifier::where('id', $data['id'])->find();
            $save->status        = $data['approve'] ? 1 : 3;
            $save->save();
            $this->success('审核成功!', 'index');
        }
    }

    public function see()
    {
        $id = Request::get('id', '');
        if (empty($id)) {
            $this->error('参数错误！');
        }
        $vo = \app\common\model\MerchantVerifier::where('id', $id)->with('seller')->append(['status_text'])->findOrEmpty()->toArray();
        View::assign("detail", $vo);
        return View::fetch('merchant_verifier/see');

    }
}
