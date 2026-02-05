<?php
/**
 * 消费券核算表控制器
 * @author slomoo <1103398780@qq.com> 2022/08/03
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\View;
use think\facade\Session;
use think\facade\Db;

class Accounting extends Base
{
    // 验证器
    protected $validate = 'Accounting';

    // 当前主表
    protected $tableName = 'accounting';

    // 当前主模型
    protected $modelName = 'Accounting';

    // 列表
    public function index()
    {
        // 获取主键
        $pk = MakeBuilder::getPrimarykey($this->tableName);
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();
            $orderByColumn = $param['orderByColumn'] ?? $pk;
            $isAsc         = $param['isAsc'] ?? 'desc';
            $where = [];
            if (!empty($param['nickname'])) {
                $where[] = ['nickname','like','%'.$param['nickname'].'%'];
            }
            if (!empty($param['class_id'])) {
                $where[] = ['class_id','=',$param['class_id']];
            }
            if (!empty($param['tags'])) {
                if($param['tags']==2) $param['tags'] = 0;
                $where[] = ['tags','=',$param['tags']];
            }
            if (!empty($param['area'])) {
                $where[] = ['area','=',$param['area']];
            }
            //$where[] = ['status','in',[1,3]];
            $model  = '\app\common\model\\' . $this->modelName;
            $list = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
            for ($i=0; $i < count($list['data']); $i++) { 
                $list['data'][$i]['area'] = $this->app->config->get('lang.area')[$list['data'][$i]['area']];
            }
            return $list;
        }
        // 商户分类
        $SellerClass = \app\common\model\SellerClass::field('id, class_name')
            ->order('sort asc')
            ->select()
            ->toArray();
        $view   = [
            'class_list' => $SellerClass
        ];
        View::assign($view);
        return View::fetch('accounting/index');
    }

    public function write_off_ids(){
        $ids = Request::param('id');
        $orderByColumn = $param['orderByColumn'] ?? 'id';
        $isAsc         = $param['isAsc'] ?? 'desc';
        $where = [];

        //$where[] = ['id','in',explode(',',$ids)];
        
        $where[] = ['accounting_id','=',$ids];

        $write_off_ids = \app\common\model\Accounting::where('id',$ids)->column('write_off_ids');

        $where[] = ['id','in',$write_off_ids[0]];

        $modelTitle = Request::param('tags') == 1 ? 'TourWriteOff' : 'WriteOff';
        $model  = '\app\common\model\\' . $modelTitle;
        $list = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
        foreach ($list['data'] as $key => $value) {
            $uids = Request::param('tags') == 1 ? $value['tourIssueUser']['uid'] : $value['couponIssueUser']['uid'];
            $list['data'][$key]['uinfo'] = \app\common\model\Users::field('nickname,name')->find($uids);
        }
        return $list;
    }

    // 审核
    public function edit($id){
        // 查询详情
        $model        = '\app\common\model\\' . $this->modelName;
        $accounting  = $model::edit($id)->toArray();
        // 格式化附件
        $accounting['data_url'] = json_decode($accounting['data_url'],true);
        // 商户分类
        $SellerClass = \app\common\model\SellerClass::field('id, class_name')->select()->toArray();
        for ($i=0; $i < count($SellerClass); $i++) { 
            if($accounting['class_id'] == $SellerClass[$i]['id']){
                $accounting['class_id'] = $SellerClass[$i]['class_name'];
            }
        }
        $accounting['area'] = $this->app->config->get('lang.area')[$accounting['area']];
        View::assign(['accounting' => $accounting]);
        $AuditRecord = \app\common\model\AuditRecord::where('aid',$id)->with(['admin','authGroup'])->order('create_time desc')->select()->toArray();
        View::assign(['AuditRecord' => $AuditRecord]);

        // 当前管理员所属角色 须根据角色ID判断审核阶段
        View::assign(['AdminInfo' => session('admin')['group_id']]);
        return View::fetch('accounting/edit');
    }

    // 审核保存
    public function editPost()
    {
        if (Request::isPost()) {

            $data = Request::except(['file'], 'post');

            // 完成页展示
            if($data['step']==5){
                return View::fetch('Accounting/complete');
            }

            $where['id'] = $data['aid'] = $data['id'];

            $data['group_id'] = Session::get('admin.group_id');
            $data['admin_id'] = Session::get('admin.id');

            $result = $this->validate($data,'AuditRecord');
            if (true !== $result) {
                $this->error($result);
            }

            // 2022-08-25 每一级审核不通过时 需要回退前一级状态为待审核
            $upData = [];
            $upData['update_time'] = time();
            // 平台审核
            /*if($data['step']==1){
                if($data['status']==1){
                    $upData['sup_status'] = 0;
                    $upData['tour_status'] = 0;
                    $upData['back_status'] = 0;
                }
                $upData['status'] = $data['status'];
            }
            // 文旅审核
            if($data['step']==2){
                if($data['tour_status']==2){
                    $upData['status'] = 0;
                }
                if($data['tour_status']==1){
                    $upData['sup_status'] = 0;
                    $upData['back_status'] = 0;
                }
                $data['status'] = $upData['tour_status'] = $data['tour_status'];
            }*/
            // 财政审核
            /*if($data['step']==3){
                if($data['sup_status']==2){
                    $upData['tour_status'] = 0;
                }
                if($data['sup_status']==1){
                    $upData['back_status'] = 0;
                }
                $data['status'] = $upData['sup_status'] = $data['sup_status'];
            }
            // 银行打款
            if($data['step']==4){
                if($data['back_status']==2){
                    $upData['sup_status'] = 0;
                }
                $data['status'] = $upData['back_status'] = $data['back_status'];
            }*/

            // 2023-05-23 
            $write_off_ids = \app\common\model\Accounting::field('write_off_ids,tags')->where('id',$where['id'])->find();

            // 事务操作
            Db::startTrans();
            try {


                // 2023-05-23 运营、文旅不通过时，解除结算ID绑定，需重新选择
                // 平台审核
                if($data['step']==1){
                    if($data['status']==1){
                        $upData['sup_status'] = 0;
                        $upData['tour_status'] = 0;
                        $upData['back_status'] = 0;
                    }
                    $upData['status'] = $data['status'];

                    // 团体
                    if($data['status']!=1 && $write_off_ids['tags']==1){
                        // 2023-05-23 清除核销记录绑定得结算ID
                        \app\common\model\TourWriteOff::whereIn('id',$write_off_ids['write_off_ids'])
                            ->where('accounting_id',$where['id'])
                            ->where('type',2)
                            ->update(['accounting_id'=>0,'update_time'=>time()]);

                        // 清除绑定得id 否则 write_off_ids 验证器通不过、商户还可以继续提交，但是核销信息无法关联。
                        $upData['write_off_ids'] = '';
                    }
                    // 散客
                    if($data['status']!=1 && $write_off_ids['tags']==0){
                        // 2023-05-23 清除核销记录绑定得结算ID
                        \app\common\model\WriteOff::whereIn('id',$write_off_ids['write_off_ids'])
                            ->where('accounting_id',$where['id'])
                            ->update(['accounting_id'=>0,'update_time'=>time()]);

                        // 清除绑定得id 否则 write_off_ids 验证器通不过、商户还可以继续提交，但是核销信息无法关联。
                        $upData['write_off_ids'] = '';
                    }
                }
                // 文旅审核
                if($data['step']==2){
                    if($data['tour_status']==2){
                        $upData['status'] = 0;
                    }
                    if($data['tour_status']==1){
                        $upData['sup_status'] = 0;
                        $upData['back_status'] = 0;
                    }
                    $data['status'] = $upData['tour_status'] = $data['tour_status'];

                    if($data['tour_status']!=1 && $write_off_ids['tags']==1){
                        // 2023-05-23 清除核销记录绑定得结算ID
                        \app\common\model\TourWriteOff::whereIn('id',$write_off_ids['write_off_ids'])
                            ->where('accounting_id',$where['id'])
                            ->where('type',2)
                            ->update(['accounting_id'=>0,'update_time'=>time()]);

                        // 清除绑定得id 否则 write_off_ids 验证器通不过、商户还可以继续提交，但是核销信息无法关联。
                        $upData['write_off_ids'] = '';
                    }

                    if($data['tour_status']!=1 && $write_off_ids['tags']==0){
                        // 2023-05-23 清除核销记录绑定得结算ID
                        \app\common\model\WriteOff::whereIn('id',$write_off_ids['write_off_ids'])
                            ->where('accounting_id',$where['id'])
                            ->update(['accounting_id'=>0,'update_time'=>time()]);

                        // 清除绑定得id 否则 write_off_ids 验证器通不过、商户还可以继续提交，但是核销信息无法关联。
                        $upData['write_off_ids'] = '';
                    }
                }

                // 修改审核状态
                \app\common\model\Accounting::update($upData, $where);
                // 记录审核操作
                $data['create_time'] = time();
                unset($data['id']);
                \app\common\model\TourAuditRecord::strict(false)->insertGetId($data);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->error($e->getMessage());
            }

            /*// 修改审核状态
            \app\common\model\Accounting::update($upData, $where);
            // 记录审核操作
            $data['create_time'] = time();
            unset($data['id']);
            \app\common\model\AuditRecord::strict(false)->insertGetId($data);*/
            $this->success('审核成功!', 'index');
        }
    }
}
