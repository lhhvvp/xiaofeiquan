<?php
/**
 * 旅行社消费券核算表控制器
 * @author slomoo <1103398780@qq.com> 2022/08/22
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
// 引入导出的命名空间
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use think\facade\Db;

class TourAccounting extends Base
{
    // 验证器
    protected $validate = 'TourAccounting';

    // 当前主表
    protected $tableName = 'tour_accounting';

    // 当前主模型
    protected $modelName = 'TourAccounting';

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
            if (!empty($param['area'])) {
                $where[] = ['area','=',$param['area']];
            }
            $where[] = ['status','in',[1,3]];
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
        return View::fetch('tour_accounting/index');
    }

    public function write_off_ids(){
        $ids = Request::param('id');
        $orderByColumn = $param['orderByColumn'] ?? 'id';
        $isAsc         = $param['isAsc'] ?? 'desc';
        $where = [];

        $where[] = ['id','in',explode(',',$ids)];

        $model  = '\app\common\model\\' . 'Tour'; 
        return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
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
        $status = [1=>'运营审核',2=>'文旅审核',3=>'财政审核',4=>'银行打款',5=>'完成'];
        $TourAuditRecord = \app\common\model\TourAuditRecord::where('aid',$id)->with(['admin','authGroup'])->select()->toArray();
        for ($i=0; $i < count($TourAuditRecord); $i++) { 
            $TourAuditRecord[$i]['step'] = $status[$TourAuditRecord[$i]['step']];
        }
        View::assign(['TourAuditRecord' => $TourAuditRecord]);

        // 当前管理员所属角色 须根据角色ID判断审核阶段
        View::assign(['AdminInfo' => session('admin')['group_id']]);
        return View::fetch('tour_accounting/edit');
    }

    // 审核保存
    public function editPost()
    {
        if (Request::isPost()) {

            $data = Request::except(['file'], 'post');

            // 完成页展示
            if($data['step']==5){
                return View::fetch('tour_accounting/complete');
            }

            $where['id'] = $data['aid'] = $data['id'];

            $data['group_id'] = Session::get('admin.group_id');
            $data['admin_id'] = Session::get('admin.id');

            $result = $this->validate($data,'TourAuditRecord');
            if (true !== $result) {
                $this->error($result);
            }

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
                    $upData['status'] = 3;
                }
                if($data['tour_status']==1){
                    $upData['sup_status'] = 0;
                    $upData['back_status'] = 0;
                }
                $data['status'] = $upData['tour_status'] = $data['tour_status'];
            }
            // 财政审核
            if($data['step']==3){
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
            $write_off_ids = \app\common\model\TourAccounting::field('write_off_ids')->where('id',$where['id'])->find();

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

                    if($data['status']!=1){
                        // 2023-05-23 清除核销记录绑定得结算ID
                        \app\common\model\TourWriteOff::whereIn('tid',$write_off_ids['write_off_ids'])
                            ->where('accounting_id',$where['id'])
                            ->where('type',1)
                            ->update(['accounting_id'=>0,'update_time'=>time()]);
                        \app\common\model\Tour::whereIn('id',$write_off_ids['write_off_ids'])
                            ->where('tour_accounting_id',$where['id'])
                            ->update(['tour_accounting_id'=>0,'update_time'=>time()]);

                        // 清除绑定得id 否则 write_off_ids 验证器通不过、商户还可以继续提交，但是核销信息无法关联。
                        $upData['write_off_ids'] = '';
                    }
                }
                // 文旅审核
                if($data['step']==2){
                    if($data['tour_status']==2){
                        $upData['status'] = 3;
                    }
                    if($data['tour_status']==1){
                        $upData['sup_status'] = 0;
                        $upData['back_status'] = 0;
                    }
                    $data['status'] = $upData['tour_status'] = $data['tour_status'];

                    if($data['tour_status']!=1){
                        // 2023-05-23 清除核销记录绑定得结算ID
                        \app\common\model\TourWriteOff::whereIn('tid',$write_off_ids['write_off_ids'])
                            ->where('accounting_id',$where['id'])
                            ->where('type',1)
                            ->update(['accounting_id'=>0,'update_time'=>time()]);
                        \app\common\model\Tour::whereIn('id',$write_off_ids['write_off_ids'])
                            ->where('tour_accounting_id',$where['id'])
                            ->update(['tour_accounting_id'=>0,'update_time'=>time()]);

                        // 清除绑定得id 否则 write_off_ids
                        $upData['write_off_ids'] = '';
                    }
                }

                // 修改审核状态
                \app\common\model\TourAccounting::update($upData, $where);
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
            $this->success('审核成功!', 'index');
        }
    }

    // 查看导游游客信息
    public function tourinfo($tid,$tags)
    {
        View::assign(['id' => $tid]);
        View::assign(['tags' => $tags]);
        $tags = Request::param('tags');
        // 导游管理
        if($tags==1){
            // 搜索
            if (Request::param('getList') == 1 && Request::param('tid')) {
                $model  = '\app\common\model\\' . 'Guide';
                $where  = [];
                $where[] = ['tid','=',Request::param('tid')];
                return $model::getList($where, $this->pageSize, ['id' => 'desc']);
            }
            return FormBuilder::getInstance()->fetch('tour_accounting/guide');
        }
        // 游客管理
        if($tags==2){
            // 搜索
            if (Request::param('getList') == 1 && Request::param('tid')) {
                $model  = '\app\common\model\\' . 'Tourist';
                $where = [];
                $where[] = ['tid','=',Request::param('tid')];
                return $model::getList($where, $this->pageSize, ['id' => 'desc']);
            }
           return View::fetch('tour_accounting/tourist'); 
        }
    }
    //查看游客保单信息
    public function detail_tourist(){
        $id = Request::param('id');
        $map = [];
        $map[] = ['id','=',$id];
        $detail  = \app\common\model\Tourist::where('id',$id)->find();
        View::assign(['detail' => $detail]);
        return View::fetch(); 
    }

    // 查看消费券
    public function couponinfo($id)
    {
        $id = Request::param('id');
        View::assign(['id' => $id]);
        if(Request::param('getList')==1 && $id){
            $model  = '\app\common\model\\' . 'TourCouponGroup';
            $where[] = ['tid','=',$id];
            //$where[] = ['cid','<>',3];
            return $model::getList($where, $this->pageSize, ['id' => 'desc']);
        }
        return FormBuilder::getInstance()->fetch('tour_accounting/receive');
    }

    // 景区打卡记录
    public function overlist($id)
    {
        $id = Request::param('id');
        View::assign(['id' => $id]);
        if(Request::param('getList')==1 && $id){
            $orderByColumn = $param['orderByColumn'] ?? 'id';
            $isAsc         = $param['isAsc'] ?? 'desc';
            $map = [];
            $map[] = ['tid','=',$id];
            $map[] = ['type','=',2];
            $model = '\app\common\model\\' . 'TourWriteOff';
            return $model::getList($map, $this->pageSize, ['id' => 'desc']);
        }
        return FormBuilder::getInstance()->fetch('tour_accounting/clock');
    }

    // 酒店打卡记录
    public function overlisthotel($id)
    {
        $id = Request::param('id');
        View::assign(['id' => $id]);
        if(Request::param('getList')==1 && $id){
            $orderByColumn = $param['orderByColumn'] ?? 'id';
            $isAsc         = $param['isAsc'] ?? 'desc';
            $map = [];
            $map[] = ['tid','=',$id];
            $model = '\app\common\model\\' . 'TourHotelUserRecord';
            return $model::getList($map, $this->pageSize, ['id' => 'desc']);
        }
        return View::fetch();
    }

    // 查看发票&合影
    public function photos($id)
    {
        $map = [];
        $map[] = ['id','=',$id];
        $model  = '\app\common\model\\' . 'Tour';
        $detail = $model::where($map)->find();
        
        $detail['invoice'] = $detail['invoice'] ? explode(',',$detail['invoice']) : [];
        $detail['dining'] = $detail['dining'] ? explode(',',$detail['dining']) : [];
        $detail['travelling_expenses'] = $detail['travelling_expenses'] ? explode(',',$detail['travelling_expenses']) : [];

        $detail['photos']  = $detail['photos'] ? explode(',',$detail['photos']) : [];
        View::assign(['detail' => $detail]);
        return FormBuilder::getInstance()->fetch('tour_accounting/photos');
    }

    // 查看详情
    // 查看导游游客信息
    public function detail($tid)
    {
        // 打卡记录
        $param = Request::param();
        if(@$param['overlist']){
            $type = $param['type']; // type=coupon | user
            $where = $type=='user' ? ['uid'=>$param['coupon_issue_id']] : ['coupon_issue_id'=>$param['coupon_issue_id']];
            $tourwriteoff  = \app\common\model\TourWriteOff::where('tid',$param['overlist'])
            ->where('type',2)
            ->where($where)
            ->with(['tourIssueUser','seller','tour','users','user'])
            ->select();
            View::assign(['tourwriteoff' => $tourwriteoff]);
            return FormBuilder::getInstance()->fetch('tour_accounting/detail_overlist');
        }
        $detail = [];
        // 查询团信息
        $detail['tour']  = \app\common\model\Tour::where('id',$tid)->with(['seller'])->find();
        $detail['tour']['invoice'] = $detail['tour']['invoice'] ? explode(',',$detail['tour']['invoice']) : [];
        $detail['tour']['photos']  = $detail['tour']['photos'] ? explode(',',$detail['tour']['photos']) : [];
        //print_r($detail['tour']->toArray());die;
        // 查询导游信息
        $detail['guide']  = \app\common\model\Guide::where('tid',$tid)->select();

        // 查询消费券信息
        $detail['coupon']  = \app\common\model\TourCouponGroup::where('tid',$tid)->with(['couponClass','couponIssue'])->select();

        // 查询游客
        $detail['tourist']  = \app\common\model\Tourist::where('tid',$tid)->with(['users'])->select();
        View::assign(['detail' => $detail]);
        return FormBuilder::getInstance()->fetch('tour_accounting/detail');
    }

    /**
     * [exportTid 根据旅行团ID导出酒店打卡记录]
     * @return   [type]            [根据旅行团ID导出酒店打卡记录]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-03-22
     * @LastTime 2023-03-22
     * @version  [1.0.0]
     */
    public function exportTid($tid)
    {
        $tableNam = 'tour_hotel_user_record'; $moduleName = 'TourHotelUserRecord';
        ob_end_clean();
        // 获取主键
        $pk = \app\common\facade\MakeBuilder::getPrimarykey($tableNam);
        // 获取列表数据
        $columns = \app\common\facade\MakeBuilder::getListColumns($tableNam);
        // 搜索
        $where         = \app\common\facade\MakeBuilder::getListWhere($tableNam);
        $where[] = ['tid','=',$tid];
        $orderByColumn = \think\facade\Request::param('orderByColumn') ?? $pk;
        $isAsc         = \think\facade\Request::param('isAsc') ?? 'desc';
        $model         = '\app\common\model\\' . $moduleName;
        // 获取要导出的数据
        $list = $model::getList($where, 0, [$orderByColumn => $isAsc]);
        // 初始化表头数组
        $str         = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        foreach ($columns as $k => $v) {
            $sheet->setCellValue($str[$k] . '1', $v['1']);
        }
        $list = isset($list['total']) && isset($list['per_page']) && isset($list['data']) ? $list['data'] : $list;
        foreach ($list as $key => $value) {
            foreach ($columns as $k => $v) {
                // 修正字典数据
                if (isset($v[4]) && is_array($v[4]) && !empty($v[4])) {
                    $value[$v['0']] = $v[4][$value[$v['0']]];
                }
                $sheet->setCellValue($str[$k] . ($key + 2), $value[$v['0']]);
            }
        }
        $moduleName = \app\common\model\Module::where('table_name', $tableNam)->value('module_name');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $moduleName . '导出' . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }
}
