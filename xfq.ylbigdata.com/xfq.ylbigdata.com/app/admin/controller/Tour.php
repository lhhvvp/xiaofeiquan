<?php
/**
 * 旅行团管理控制器
 * @author slomoo <1103398780@qq.com> 2022/08/14
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
// 引入导出的命名空间
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
class Tour extends Base
{
    // 验证器
    protected $validate = 'Tour';

    // 当前主表
    protected $tableName = 'tour';

    // 当前主模型
    protected $modelName = 'Tour';

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

            $model  = '\app\common\model\\' . $this->modelName;
            
            if(@$param['term']!=''){
                $term = explode(' 至 ',$param['term']);
                $term_start = strtotime($term[0]);
                $term_end = strtotime($term[1]);
                $where[] = ['term_start','>=',$term_start];
                $where[] = ['term_end','<=',$term_end];
            }
            if($param['mid']){
                $where[] = ['mid','=',$param['mid']];
            }
            if($param['name']){
                $where[] = ['name','=',$param['name']];
            }
            if($param['status']){
                $where[] = ['status','=',$param['status']];
            }
            $list = $model::getRewriteList($where, $this->pageSize, [$orderByColumn => $isAsc]);
            
            return $list;
        }
        $SellerClass = \app\common\model\Seller::field('id, nickname')
            ->where('status',1)
            ->whereIn('class_id',3)
            ->select()
            ->toArray();
        View::assign(['seller_list' => $SellerClass]);
        return View::fetch('tour/index');
    }

    // 编辑
    public function edit($id)
    {
        // 2023-03-18 旅行团新增文旅审核
        View::assign(['AdminInfo' => session('admin')['group_id']]);
        // 查询详情
        $model        = '\app\common\model\\' . $this->modelName;
        $tour  = $model::edit($id)->toArray();
        // 商户
        $tour['mInfo'] = \app\common\model\Seller::field('id,nickname,area,class_id')
        ->with(['sellerClass'])
        ->where('id',$tour['mid'])
        ->find()
        ->toArray();
        $tour['area'] = $this->app->config->get('lang.area')[$tour['mInfo']['area']];
        View::assign(['tour' => $tour]);
        // 查询审核记录--按照时间倒序
        $status = [1=>'初步审核',2=>'不通过',3=>'通过 上传导游、游客信息',4=>'确定成团'];
        $ExamineRecord = \app\common\model\ExamineRecord::where('sid',$id)->where('tags',2)->with(['admin','authGroup'])->select()->toArray();
        View::assign(['ExamineRecord' => $ExamineRecord]);
        // 查询旅行团所选消费券信息
        /*$ids    = $tour['travel_id'].','.$tour['spot_ids'];
        $idsArr = explode(',',$ids);*/

        // 查询当前团的团体全信息
        $tour_coupon_group = \app\common\model\TourCouponGroup::where('tid',$tour['id'])->select();
        $idsArr = array_column($tour_coupon_group->toArray(),'coupon_issue_id');

        $last_coupon_group = \app\common\model\TourCouponGroup::field('a.id,a.tid,a.coupon_issue_id')
            ->alias('a')
            ->leftJoin('tour t','a.tid = t.id')
            ->where('a.coupon_issue_id','in',$idsArr)
            ->where('a.tid','<',$tour['id'])
            ->where('a.is_receive',0)
            ->where('t.status','<>',6)
            ->select();
        $last_coupon_group_sign = $last_coupon_group->toArray();


        $last_coupon_group_sign_data = [];
        if($last_coupon_group_sign){
            //$new_coupon_issue_id = array_unique(array_column($last_coupon_group_sign,'coupon_issue_id'));
            // 所有团
            $last_coupon_group_tids = array_column($last_coupon_group_sign,'tid');
            $tour_numbers = $model::field('id,numbers')
            ->where('id','in',$last_coupon_group_tids)
            ->where('status','<>',6) // 2023-08-02 释放无效团占用的消费券
            ->select();
            $tour_numbers_arr = $tour_numbers->toArray();

            //print_r($tour_numbers_arr);die;
            //print_r($last_coupon_group_sign);die;

            // 将旅行团下面的人数 附加给团体券下的每张券
            /*foreach($last_coupon_group_sign as $k1 => $v1) {//消费券
                 $xfqid=$v1['coupon_issue_id'];//消费券ID
                 if(!array_key_exists($xfqid, $last_coupon_group_sign_data)){
                  $last_coupon_group_sign_data[$xfqid]=0;
                 }
                 foreach($tour_numbers_arr as $k2 => $v2) {//商户
                  if($v2['id']==$v1['tid']){
                   $last_coupon_group_sign_data[$xfqid]+=$v2['numbers'];
                  }
                 }
            }*/
            foreach ($last_coupon_group_sign as $key => $value) {
                // 2023-08-03初始值赋值
                $last_coupon_group_sign[$key]['numbers'] = 0;
                foreach ($tour_numbers_arr as $k => $v) {
                    if($value['tid'] == $v['id']);
                        $last_coupon_group_sign[$key]['numbers'] = $v['numbers'];
                }
            }
            // 去重 重复元素相加
            foreach($last_coupon_group_sign as $row){
                if(isset($last_coupon_group_sign_data[$row['coupon_issue_id']])){
                    $last_coupon_group_sign_data[$row['coupon_issue_id']]['numbers'] += $row['numbers'];
                }else{
                    $last_coupon_group_sign_data[$row['coupon_issue_id']] = $row;
                }
            }
        }

        //print_r($last_coupon_group_sign_data);die;
        $couponissue = \app\common\model\CouponIssue::field('id,remain_count,cid,uuno,coupon_title,coupon_price')
            ->where('id','in',$idsArr)
            ->select()
            ->toArray();
        $tour_cid_3  = [];
        foreach($couponissue as $key=>$value){
            $value['aaanumbers'] = $value['remain_count'];
            $couponissue[$key]['aaanumbers'] = $value['remain_count'];
            if($last_coupon_group_sign_data){
                foreach ($last_coupon_group_sign_data as $kk => $vv) {
                    if($value['id'] == $vv['coupon_issue_id']){
                        $couponissue[$key]['aaanumbers'] = ($value['remain_count'] - $vv['numbers']);
                    }
                }
            }
            /*foreach ($last_coupon_group_sign_data as $kk => $vv) {
                if($value['id'] == $kk){
                    $couponissue[$key]['aaanumbers'] = ($value['remain_count'] - $vv);
                }
            }*/
            if($value['cid']==3){
                $tour_cid_3 = $value;
                $tour_cid_3['aaanumbers'] = $couponissue[$key]['aaanumbers'];
                unset($couponissue[$key]);
            }
        }
        // 2022-08-25 将旅行券放在第一位
        array_unshift($couponissue,$tour_cid_3);
        View::assign(['couponissue' => $couponissue]);
        // 返回酒店信息
        $hotel = \app\common\model\TourHotel::where('tid','=',$id)
            ->select()
            ->toArray();
        View::assign(['hotel' => $hotel]);
        return View::fetch('tour/edit');
    }

    // 编辑保存
    public function editPost()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            $where['id'] = $data['id'];

            if(!isset($data['status']) || !in_array($data['status'],[1,2,3,8,9])){
                $this->error('参数异常');
            }
            $logData['tags']    = 2;
            $logData['sid']     = $data['id'];
            $logData['step']    = $data['status'];
            $logData['step']    = $data['status'];
            $logData['remarks']  = $data['remarks'];
            $logData['group_id'] = Session::get('admin.group_id');
            $logData['admin_id'] = Session::get('admin.id');
            $logData['create_time']  = time();
            // 记录审核记录
            \app\common\model\ExamineRecord::strict(false)->insertGetId($logData);
            // 修改旅行团状态
            $upData['status']   = $data['status'];
            $upData['update_time'] = time();
            \app\common\model\Tour::update($upData, $where);
            $this->success('审核成功!', 'index');
        }
    }

    // 审核通过之后的团列表
    public function agg()
    {
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();

            $orderByColumn = $param['orderByColumn'] ?? 'id';
            $isAsc         = $param['isAsc'] ?? 'desc';
            $where = [];
            if (isset($param['name'])  && $param['name']!='') { 
                $where[] = ['name','like','%'.$param['name'].'%'];
            }

            if (isset($param['status'])  && $param['status']!='') { 
                $where[] = ['status','=',$param['status']];
            }else{
                $where[] = ['status','in',[4,5,6]];
            }

            if (isset($param['is_locking'])  && $param['is_locking']!='') { 
                $where[] = ['is_locking','=',$param['is_locking']];
            }

            if (isset($param['tid'])  && $param['tid']!='') { 
                $where[] = ['id','=',$param['tid']];
            }
            
            if(@$param['term']!=''){
                $term = explode(' 至 ',$param['term']);
                $term_start = strtotime($term[0]);
                $term_end = strtotime($term[1]);
                $where[] = ['term_start','>=',$term_start];
                $where[] = ['term_end','<=',$term_end];
            }

            if (isset($param['mid'])  && $param['mid']!='') { 
                $where[] = ['mid','=',$param['mid']];
            } 

            /*if (isset($param['area'])  && $param['area']!='') { 
                $where[] = ['seller.area','=',$param['area']];
            }*/

            // 旅行社商户ID
            /*$mid = session()['travel']['id'];
            $where[] = ['mid','=',$mid];*/
            $model  = '\app\common\model\\' . $this->modelName;
            $list = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
            foreach ($list['data'] as $key => $value) {
                // 每个团下面的导游人数
                $list['data'][$key]['guide_total'] = \app\common\model\Guide::where('tid',$value['id'])->count();
                // 每个团下面的消费券总数
                $coupon_group_total = \app\common\model\TourCouponGroup::where('tid',$value['id'])->count();
                // 每个团下面的核销情况
                $hexiao_coupon_group_total = \app\common\model\TourCouponGroup::where('tid',$value['id'])->where('status',1)->count();
                $list['data'][$key]['coupon_group_total'] = $hexiao_coupon_group_total.'/'.$coupon_group_total;

                // 获取每个团下游客的人数
                $count_tourist = \app\common\model\Tourist::where('tid',$value['id'])->group('uid')->select();
                $list['data'][$key]['numbers'] = $value['numbers'].'/'.count($count_tourist->toArray());
            }
            return $list;
        }
        $SellerClass = \app\common\model\Seller::field('id, nickname')
            ->where('status',1)
            ->whereIn('class_id',3)
            ->select()
            ->toArray();
        View::assign(['seller_list' => $SellerClass]);

        // 系统配置
        $system = \app\common\model\System::field('is_clock_switch')->find(1);
        View::assign(['system' => $system]);
        return View::fetch('tour/agg');
    }

    // 所有打卡记录
    public function clock()
    {
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();

            $orderByColumn = $param['orderByColumn'] ?? 'id';
            $isAsc         = $param['isAsc'] ?? 'desc';
            $where = [];
            // 查询当前商家所有的旅行团
            $model  = '\app\common\model\\' . $this->modelName;
            $tids   = $model::where($where)->column('id');
            $map = [];
            if (isset($param['name'])  && $param['name']!='') { 
                $map[] = ['tid','like','%'.$param['name'].'%'];
            }

            $map[] = $tids ? ['tid','in',$tids] : ['tid','=','-999'];

            $map[] = ['type','=',2];
            $model  = '\app\common\model\\' . 'TourWriteOff';

            return $model::getList($map, $this->pageSize, [$orderByColumn => $isAsc]);
        }
        return View::fetch('tour/clock');
    }

    // 查看打卡详情
    public function clock_detail($id)
    {
        $param = Request::param();

        $orderByColumn = $param['orderByColumn'] ?? 'id';
        $isAsc         = $param['isAsc'] ?? 'desc';

        $map = [];
        $id   = $param['id'] ? $param['id'] : $id;
        $map[] = ['id','=',$id];
        $model  = '\app\common\model\\' . 'TourWriteOff';
        $detail = $model::where($map)->with(['seller','tour'])->find();
        $detail['images'] = $detail['images'] ? explode(',',$detail['images']) : [];
        View::assign(['detail' => $detail]);
        return View::fetch('tour/clock_detail');
    }

    // 不审核
    public function is_to_ckeck($id)
    {
        if (Request::isPost()) {
            // 2022-08-29 不审核直接通过
            $upData['status']      = 3;
            $upData['update_time'] = time();
            $upData['is_to_ckeck'] = 0;
            $res = \app\common\model\Tour::update($upData, ['id'=>$id]);
            if(!$res){
                $this->error('操作失败');
            }
            $this->success('操作成功', 'index');
        }
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
        return View::fetch();
    }

    /**
     * [export 导出]
     * @return   [type]            [导出]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-03-22
     * @LastTime 2023-03-22
     * @version  [1.0.0]
     */
    public function export()
    {
        $tableNam = 'tour'; $moduleName = 'Tour';
        ob_end_clean();
        // 获取主键
        $pk = \app\common\facade\MakeBuilder::getPrimarykey($tableNam);
        // 获取列表数据
        $columns = \app\common\facade\MakeBuilder::getListColumns($tableNam);
        // 搜索
        $where         = \app\common\facade\MakeBuilder::getListWhere($tableNam);
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

    /**
     * [export 导出]
     * @return   [type]            [导出]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-03-22
     * @LastTime 2023-03-22
     * @version  [1.0.0]
     */
    public function export_agg()
    {
        $tableNam = 'tour'; $moduleName = 'Tour';
        ob_end_clean();
        // 获取主键
        $pk = \app\common\facade\MakeBuilder::getPrimarykey($tableNam);
        // 获取列表数据
        $columns = \app\common\facade\MakeBuilder::getListColumns($tableNam);
        // 搜索
        $where         = \app\common\facade\MakeBuilder::getListWhere($tableNam);
        $orderByColumn = \think\facade\Request::param('orderByColumn') ?? $pk;
        $isAsc         = \think\facade\Request::param('isAsc') ?? 'desc';
        $model         = '\app\common\model\\' . $moduleName;
        $where[] = ['status','in',[4,5,6]];
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

    // 编辑保存
    public function editStatus()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');

            if(!isset($data['status']) || !in_array($data['status'],['verify','confirm']) || !isset($data['id'])){
                $this->error('参数异常');
            }

            $tour = \app\common\model\Tour::find($data['id']);
            if($tour->status!=6){
                $this->error('数据异常');
            }
            // 更新
            $tour->status = $data['status'] == 'verify' ? 1 : 4;
            $tour->update_time = time();
            $tour->save();

            $this->success('操作成功!', 'agg');
        }
    }
}
