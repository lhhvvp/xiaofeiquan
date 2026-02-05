<?php
/**
 * 核销记录控制器
 * @author slomoo <1103398780@qq.com> 2022/07/29
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\View;

class WriteOff extends Base
{
    // 验证器
    protected $validate = 'WriteOff';

    // 当前主表
    protected $tableName = 'write_off';

    // 当前主模型
    protected $modelName = 'WriteOff';

    public function index()
    {
        // 获取主键
        $pk = MakeBuilder::getPrimarykey($this->tableName);
        // 搜索
        if (Request::param('getList') == 1) {
            /*$where         = MakeBuilder::getListWhere($this->tableName);
            $orderByColumn = Request::param('orderByColumn') ?? $pk;
            $isAsc         = Request::param('isAsc') ?? 'desc';
            $cid           = Request::param('cid') ?? '';

            if($cid!='')
                array_push($where,['couponIssue.cid','=',$cid]);

            $model  = '\app\common\model\\' . $this->modelName;
            $list = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc,'id'=>'desc']);

            foreach ($list['data'] as $key => $value) {
                $list['data'][$key]['user_name'] = uid_to_name($value['couponIssueUser']['uid']);
            }
            return $list;*/
            $param = Request::param();
            $where = [];
            $orderByColumn = Request::param('orderByColumn') ?? $pk;
            $isAsc         = Request::param('isAsc') ?? 'desc';
            $cid           = Request::param('cid') ?? '';
            if (@$param['cid']!='') {
                $where[] = ['couponIssue.cid','=',$param['cid']];
            }

            if (@$param['uuno']!='') {
                $where[] = ['uuno','=',$param['uuno']];
            }

            if (@$param['coupon_issue_user_id']!='') {
                $where[] = ['coupon_issue_user_id','=',$param['coupon_issue_user_id']];
            }

            if(@$param['mid']!='')
                $where[] = ['seller.nickname','like',"%".$param['mid']."%"];

            if (@$param['userid']!='') {
                $where[] = ['users.name','=',$param['userid']];
            }

            if(@$param['create_time']!=''){
                $create_time = explode(' 至 ',$param['create_time']);
                $create_time_start = strtotime($create_time[0]);
                $create_time_end = strtotime($create_time[1]."23:59:59");
                $where[] = ['create_time','between',[$create_time_start,$create_time_end]];
            }
            $model  = '\app\common\model\\' . $this->modelName;
            $list = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc,'id'=>'desc']);
            foreach ($list['data'] as $key => $value) {
                $list['data'][$key]['user_name'] = uid_to_name($value['couponIssueUser']['uid']);
            }
            return $list;
        }
        // 消费券分类
        $CouponClass = \app\common\model\CouponClass::field('id, title')
            ->order('sort asc')
            ->select()
            ->toArray();
        View::assign(['class_list' => $CouponClass]);

        $SellerClass = \app\common\model\Seller::field('id, nickname')
            ->where('status',1)
            ->whereIn('class_id',[2,4,5])
            ->select()
            ->toArray();
        View::assign(['seller_list' => $SellerClass]);
        
        return View::fetch();
    }

    // 列表
    public function index1(){
        // 获取主键
        $pk = MakeBuilder::getPrimarykey($this->tableName);
        // 获取列表数据
        $columns = MakeBuilder::getListColumns($this->tableName);
        // 获取搜索数据
        $search = MakeBuilder::getListSearch($this->tableName);
        // 获取当前模块信息
        $model = '\app\common\model\\' . $this->modelName;
        $module = \app\common\model\Module::where('table_name', $this->tableName)->find();
        // 搜索
        if (Request::param('getList') == 1) {
            $where = MakeBuilder::getListWhere($this->tableName);
            $orderByColumn = Request::param('orderByColumn') ?? $pk;
            $isAsc = Request::param('isAsc') ?? 'desc';
            return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
        }
        // 构建页面
        return TableBuilder::getInstance()
            ->setUniqueId($pk)                              // 设置主键
            ->addColumns($columns)                         // 添加列表字段数据
            ->setSearch($search)                            // 添加头部搜索
            ->addColumn('right_button', '操作', 'btn')      // 启用右侧操作列
            ->addRightButtons($module->right_button)        // 设置右侧操作列
            ->addTopButtons($module->top_button)            // 设置顶部按钮组
            ->addRightButton('info', [                      // 添加额外按钮
                'title' => '查看',
                'icon'  => 'fa fa-search',
                'class' => 'btn btn-primary btn-xs',
                'href'  => url('see', ['parentId' => '__id__'])
            ])
            ->fetch();
    }

    // 查看详情
    public function see($parentId)
    {
        $orderByColumn = $param['orderByColumn'] ?? 'id';
        $isAsc         = $param['isAsc'] ?? 'desc';

        $map = [];

        $map[] = ['id','=',$parentId];
        $model  = '\app\common\model\\' . $this->modelName;
        $detail = $model::where($map)->with(['users','seller','couponIssueUser','couponIssue'])->find();
        // 查询领取人信息
        $detail['userinfo'] = \app\common\model\Users::where('id',$detail['couponIssueUser']['uid'])->find();
        // 商户核销点
        $detail['points'] = \app\common\model\MerchantVerificationPoints::where('mid',$detail->mid)->where('status',1)->select();
        // print_r($detail->toArray());die;
        View::assign(['detail' => $detail]);
        return View::fetch();
    }
}
