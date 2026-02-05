<?php
/**
 * 商户-核验-点控制器
 * @author slomoo <1103398780@qq.com> 2023/07/10
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Db;
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\View;
class MerchantVerificationPoints extends Base
{
    // 验证器
    protected $validate = 'MerchantVerificationPoints';

    // 当前主表
    protected $tableName = 'merchant_verification_points';

    // 当前主模型
    protected $modelName = 'MerchantVerificationPoints';
    public function index()
    {
        if (Request::isGet()) {
            return View::fetch('merchant_verification_points/index');
        } else {
            $param = Request::param();
            $where = [];
            if (isset($param['title']) && $param['title'] != '') {
                $where[] = ['mvp.title', 'like', '%' . $param['title'] . '%'];
            }
            if (isset($param['name']) && $param['name'] != '') {
                $where[] = ['mvp.name', 'like', '%' . $param['name'] . '%'];
            }
            if (isset($param['mobile']) && $param['mobile'] != '') {
                $where[] = ['mvp.mobile', '=', $param['mobile']];
            }
            if (isset($param['status']) && $param['status'] != '') {
                $where[] = ['mvp.status', '=', $param['status']];
            }
            if (isset($param['seller_nickname']) && !empty($param['seller_nickname'])) {
                $where[] = ['s.nickname', 'like',"%".$param['seller_nickname']."%"];
            }
            $order = "mvp.id desc";
            $list = Db::name('merchant_verification_points')
                ->alias('mvp')
                ->join('seller s','mvp.mid = s.id')
                ->field("mvp.*,s.nickname as seller_nickname")
                ->where($where)
                ->order($order)
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $this->pageSize,
                ]);
            return ['total' => $list->total(), 'current_page' => $list->currentPage(), 'last_page' => $list->lastPage(), 'data' => $list->items()];
        }
    }
    /*
     * 查看核销点
     * */
    public function see()
    {
        $id = Request::get('id', '');
        if(empty($id)){
            $this->error('参数错误！');
        }
        $model  = '\app\common\model\\' . $this->modelName;
        $vo = $model::where('id',$id)->with('seller')->findOrEmpty()->toArray();
        View::assign("detail",$vo);
        return View::fetch('merchant_verification_points/see');
    }
}
