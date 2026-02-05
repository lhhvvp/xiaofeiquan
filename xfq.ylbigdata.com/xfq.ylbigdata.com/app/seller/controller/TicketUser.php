<?php
/**
 * 票务-商户-售票员控制器
 * @author slomoo <1103398780@qq.com> 2023/08/07
 */
namespace app\seller\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\Session;
use think\facade\View;
use think\facade\Db;

class TicketUser extends Base
{
    // 验证器
    protected $validate = 'TicketUser';

    // 当前主表
    protected $tableName = 'ticket_user';

    // 当前主模型
    protected $modelName = 'TicketUser';

    public function index()
    {
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();
            $where = [];
            if (isset($param['name']) && !empty($param['name'])) {
                $where[] = ['mv.name','like',"%".$param['name']."%"];
            }
            if (isset($param['status']) && $param['status'].'' !== '') {
                $where[] = ['mv.status','=',$param['status']];
            }
            $order = "mv.id desc";
            $where[] = ['mv.mid','=',session('seller.id')];
            $list = Db::name('ticket_user')
                ->alias('mv')
                ->join('seller s','mv.mid = s.id')
                ->field("mv.*,s.nickname as seller_nickname")
                ->where($where)
                ->whereNull('delete_time')
                ->order($order)
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $this->pageSize,
                ]);
            $data = $list->items();
            return ['total' => $list->total(), 'current_page' => $list->currentPage(), 'last_page' => $list->lastPage(), 'data' =>$data];
        }
        return View::fetch();
    }
    // 添加
    public function add()
    {
        return View::fetch();
    }
    // 添加保存
    public function addPost()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');

            // xss过滤
            $data['name']  = removeXSS(filterText($data['name']));
            $data['idcard_front_back']   = removeXSS(filterText($data['idcard_front_back']));
            $data['trust_agreement']     = removeXSS(filterText($data['trust_agreement']));
            $data['mobile']    = removeXSS(filterText($data['mobile']));
            $data['username']    = removeXSS(filterText($data['username']));
            $data['password']    = removeXSS(filterText($data['password']));


            $result = $this->validate($data, $this->modelName);
            if (true !== $result) {
                $this->error($result);
            }

            // 参数补全
            $data['uuid'] = gen_uuid();
            $data['salt'] = set_salt(6);
            // 密码加密
            $data['password'] = md5($data['password'].$data['salt']);
            $data['mid']  = session('seller.id');
            $data['loginnum'] = 0;
            $data['err_num']  = 0;
            $data['signpass'] = '';
            
            $model  = '\app\common\model\\' . $this->modelName;
            
            $result = $model::addPost($data);
            if ($result['error']) {
                $this->error($result['msg']);
            } else {
                $this->success($result['msg'], 'index');
            }
        }
    }

    public function edit(string $id)
    {
        // 查询详情
        $model = '\app\common\model\\' . $this->modelName;
        $info  = $model::where('uuid',$id)->find()->toArray();
        View::assign(['info' => $info]);
        return View::fetch();
    }

    // 编辑保存
    public function editPost()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            // xss过滤
            $data['name']  = removeXSS(filterText($data['name']));
            $data['idcard_front_back']   = removeXSS(filterText($data['idcard_front_back']));
            $data['trust_agreement']     = removeXSS(filterText($data['trust_agreement']));
            $data['mobile']    = removeXSS(filterText($data['mobile']));
            $data['username']    = removeXSS(filterText($data['username']));
            $data['password']    = removeXSS(filterText($data['password']));

            $result = $this->validate($data, $this->modelName);
            if (true !== $result) {
                $this->error($result);
            }
            
            $model  = '\app\common\model\\' . $this->modelName;

            $info  = $model::where('uuid',$data['uuid'])->find()->toArray();

            $data['password'] = md5($data['password'].$info['salt']);
            
            $result = $model::where('uuid',$data['uuid'])->update($data);
            if ($result) {
                $this->success('修改成功', 'index');
            }
            $this->error('修改失败');
        }
    }
}
