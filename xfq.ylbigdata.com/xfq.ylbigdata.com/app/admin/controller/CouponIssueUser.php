<?php
/**
 * 消费券领取记录控制器
 * @author slomoo <1103398780@qq.com> 2022/07/28
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\View;
use ip2region\XdbSearcher;
use think\facade\Db;
use app\common\libs\MultiFloorXlsWriterService;

// 引入导出的命名空间
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CouponIssueUser extends Base
{
    // 验证器
    protected $validate = 'CouponIssueUser';

    // 当前主表
    protected $tableName = 'coupon_issue_user';

    // 当前主模型
    protected $modelName = 'CouponIssueUser';

    // 列表
    public function index(){
        $add_columns = [
            ['users.mobile','手机号','text','',[],'','false'],
        ];
        // 获取当前模块信息
        $model = '\app\common\model\\' . $this->modelName;
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();
            $where = [];
            $orderByColumn = Request::param('orderByColumn') ?? $pk;
            $isAsc = Request::param('isAsc') ?? 'desc';
            
            // 模型条件查询
            $uidwhere = [];
            if($param['uid']){
                $uidwhere[] = ['name','=',$param['uid']];
            }
            if($param['receive_id']){
                $where[] = ['CouponIssueUser.id','=',$param['receive_id']];
            }
            if($param['coupon_title']){
                $where[] = ['coupon_title','=',$param['coupon_title']];
            }
            if($param['issue_coupon_class_id']){
                $where[] = ['issue_coupon_class_id','=',$param['issue_coupon_class_id']];
            }
            if(isset($param['status']) && $param['status'] !='')
                $where[] = ['CouponIssueUser.status','=',$param['status']];

            //$list = $model::getRewriteList($where, $this->pageSize, [$orderByColumn => $isAsc]);
            $list = $model::getRewriteList($where, $this->pageSize, [$orderByColumn => $isAsc],$uidwhere);

            // 2023-03-10 需要在列表根据IP展示领取位置
            $dbPath = './ip2region/ip2region.xdb';

            /*$searcher = XdbSearcher::newWithFileOnly($dbPath);
            $sTime = XdbSearcher::now();
            foreach ($list['data'] as $key => $value) {
                if($value['ips']){
                    $aa = $searcher->search($value['ips']);
                    $list['data'][$key]['ips'] = str_replace('|0', '', $aa);
                }
            }*/
            /*$vIndex = XdbSearcher::loadVectorIndexFromFile($dbPath);

            if ($vIndex === null) {
                printf("failed to load vector index from '%s'\n", $dbPath);
                return;
            }

            // 2、使用全局的 vIndex 创建带 VectorIndex 缓存的查询对象。
            try {
                $searcher = XdbSearcher::newWithVectorIndex($dbPath, $vIndex);
            } catch (Exception $e) {
                printf("failed to create vectorIndex cached searcher with '%s': %s\n", $dbPath, $e);
                return;
            }

            // 3、查询
            $sTime = XdbSearcher::now();
            foreach ($list['data'] as $key => $value) {
                if($value['ips']){
                    $aa = $searcher->search($value['ips']);
                    $list['data'][$key]['ips'] = str_replace('|0', '', $aa);
                }
            }*/

            //try {
                // 加载整个 xdb 到内存。
                $cBuff = XdbSearcher::loadContentFromFile($dbPath);
                if (null === $cBuff) {
                    throw new \RuntimeException("failed to load content buffer from '$dbPath'");
                }
                // 使用全局的 cBuff 创建带完全基于内存的查询对象。
                $searcher = XdbSearcher::newWithBuffer($cBuff);
                foreach ($list['data'] as $key => $value) {
                    if($value['ips']){
                        $aa = $searcher->search($value['ips']);
                        $list['data'][$key]['ips'] = str_replace('|0', '', $aa);
                    }
                }
            //} catch (\Exception $e) {
            //    $this->apiError("区域获取错误".$e->getMessage());
            //}
            return $list;
        }
        // 消费券分类查询
        $CouponClass = \app\common\model\CouponClass::field('id, title')
            ->order('sort asc')
            ->select()
            ->toArray();
        View::assign(['class_list' => $CouponClass]);

        // 构建页面
        return TableBuilder::getInstance()
            ->addRightButton('info', [                      // 添加额外按钮
                'title' => '查看',
                'icon'  => 'fa fa-search',
                'class' => 'btn btn-primary btn-xs',
                'href'  => url('see', ['parentId' => '__id__'])
            ])
            ->fetch('coupon_issue_user/index');
    }

    // 查看详情
    public function see($parentId)
    {
        $orderByColumn = $param['orderByColumn'] ?? 'id';
        $isAsc         = $param['isAsc'] ?? 'desc';

        $map = [];

        $map[] = ['id','=',$parentId];
        $model  = '\app\common\model\\' . $this->modelName;
        $detail = $model::where($map)->with(['users','couponClass','couponIssue'])->find();
        View::assign(['detail' => $detail]);
        return View::fetch();
    }

    // 用户领取记录
    public function receiveList(){
        // 获取当前模块信息
        $model = '\app\common\model\\' . $this->modelName;
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();
            $where = $uidWhere = [];
            $orderByColumn = Request::param('orderByColumn') ?? 'id';
            $isAsc = Request::param('isAsc') ?? 'desc';

            if (@$param['sign']) {
                //进行自动签收动作后查询未签收快递

                //已签收物流，则券自动核销处理
                (new $model)->autoWriteOff();

                // 查询已签收的领券id
                $signList = $model::field('w.id')
                    ->alias('w')
                    ->leftJoin('LogisticsInformation l', 'w.id=l.coupon_issue_user_id')
                    ->where('w.delivery_address',  '<>', '')
                    ->where('l.delivery_status',  '=', 3)
                    ->select()
                    ->toArray();
                $signIdArr = array_column($signList, 'id');
                if (!empty($signIdArr)) {
                    $where[] = ['CouponIssueUser.id','not in', $signIdArr];
                }
            } else {
                //正常搜索操作
                if(@$param['issue_coupon_class_id']){//消费券分类
                    $where[] = ['issue_coupon_class_id','=',$param['issue_coupon_class_id']];
                }
                if(@$param['coupon_title']){//消费券名称
                    $where[] = ['coupon_title','=',$param['coupon_title']];
                }
                if(@$param['receive_id']){//领取编号
                    $where[] = ['CouponIssueUser.id','=',$param['receive_id']];
                }
                if(@$param['uid']){//领取人
                    $uidWhere[] = ['name','=',$param['uid']];
                }
                if(@$param['start_time']!=''){//领取时间
                    $start_time = explode(' 至 ',$param['start_time']);
                    $start_time_start = strtotime($start_time[0]);
                    $start_time_end = strtotime($start_time[1]."23:59:59");
                    $where[] = ['delivery_input_time', 'between', [$start_time_start, $start_time_end]];
                }
            }

            $where[] = ['delivery_address','<>',''];
            return $model::getRewriteList($where, $this->pageSize, [$orderByColumn => $isAsc], $uidWhere);
        }

        // 消费券分类查询
        $CouponClass = \app\common\model\CouponClass::field('id, title')
            ->order('sort asc')
            ->select()
            ->toArray();
        View::assign(['class_list' => $CouponClass]);

        return View::fetch('coupon_issue_user/receiveList');
    }

    // 查看物流信息
    public function seeTracking($id)
    {
        $map = [];
        $map[] = ['id','=',$id];
        $model  = '\app\common\model\\' . $this->modelName;
        $detail = $model::where($map)->with(['users','couponClass','couponIssue'])->find();
        View::assign(['detail' => $detail]);

        // 消费券分类查询
        $couponClass = \app\common\model\CouponClass::field('id, title')
            ->order('sort asc')
            ->select()
            ->toArray();
        $couponClassArr = array_column($couponClass, 'title', 'id');
        View::assign(['couponClassArr' => $couponClassArr]);

        //对接快递接口
        $trackingResult = [];
        if (!empty($detail['tracking_number'])) {
            $model = '\app\common\model\CouponIssueUser';
            $trackingResult = $model::syncTrackingResult($detail['tracking_number'], $detail['id']);

            //返回200,success,已签收,则可以进行核销操作
            (new $model)->systemWriteOff($detail['uid'], $id, $trackingResult);
        }
        View::assign(['trackingResult' => $trackingResult]);

        return View::fetch('coupon_issue_user/seeTracking');
    }

    // 编辑用户领取记录
    public function editTracking($id)
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            $where['id'] = $id;

            // 是否填写收货人
            if(empty($data['delivery_user'])){
                $this->error('请输入收货人！');
            }

            // 是否填写收货手机号
            if(empty($data['delivery_phone'])){
                $this->error('请输入收货手机号！');
            }

            // 是否填写收货地址
            if(empty($data['delivery_address'])){
                $this->error('请输入收货地址！');
            }

            $result = $this->validate($data,'CouponIssueUser');
            if (true !== $result) {
                $this->error($result);
            }

            \app\common\model\CouponIssueUser::update($data, $where);

            $this->success('填写成功!', 'index');
        }

        $map = [];
        $map[] = ['id','=',$id];
        $model = '\app\common\model\\' . $this->modelName;
        $detail = $model::where($map)->find();
        View::assign(['detail' => $detail]);

        // 消费券分类查询
        $couponClass = \app\common\model\CouponClass::field('id, title')
            ->order('sort asc')
            ->select()
            ->toArray();
        $couponClassArr = array_column($couponClass, 'title', 'id');
        View::assign(['couponClassArr' => $couponClassArr]);

        return View::fetch('coupon_issue_user/editTracking');
    }

    // 补充物流单号
    public function addTrackingNumber($id)
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            $where['id'] = $id;

            // 是否填写快递单号
            if(empty($data['tracking_number'])){
                $this->error('请输入快递单号！');
            }

            $result = $this->validate($data,'CouponIssueUser');
            if (true !== $result) {
                $this->error($result);
            }

            \app\common\model\CouponIssueUser::update($data, $where);

            $this->success('填写成功!', 'index');
        }

        $map = [];
        $map[] = ['id','=',$id];
        $model = '\app\common\model\\' . $this->modelName;
        $detail = $model::where($map)->find();
        View::assign(['detail' => $detail]);

        // 消费券分类查询
        $couponClass = \app\common\model\CouponClass::field('id, title')
            ->order('sort asc')
            ->select()
            ->toArray();
        $couponClassArr = array_column($couponClass, 'title', 'id');
        View::assign(['couponClassArr' => $couponClassArr]);

        return View::fetch('coupon_issue_user/addTrackingNumber');
    }

    /**
     * [export 导出用户领取记录]
     * @return   [type]            [导出]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-03-22
     * @LastTime 2024-01-27
     * @version  [1.0.0]
     */
    public function export_receive()
    {
        ob_end_clean();

        // 消费券分类查询
        $CouponClass = \app\common\model\CouponClass::field('id, title')
            ->order('sort asc')
            ->select()
            ->toArray();
        $CouponClassArr = array_column($CouponClass, 'title', 'id');

        // 导出字段
        $columns = [
            ['id', '编号'],
            ['issue_coupon_class_id', '消费券分类'],//1
            ['coupon_title', '消费券名称'],
            ['uid', '用户id'],
            ['users_name', '领取人'],//2
            ['delivery_user', '收货人'],
            ['delivery_phone', '收货手机号'],
            ['delivery_address', '收货地址'],
            ['tracking_number', '快递单号'],
            ['delivery_input_time', '快递填写时间'],
        ];

        $param = Request::param();
        $where = [];
        $orderByColumn  = Request::param('orderByColumn') ?? 'id';
        $isAsc          = Request::param('isAsc') ?? 'desc';

        // 模型条件查询
        $uidwhere = [];
        if(@$param['issue_coupon_class_id']){//消费券分类
            $where[] = ['issue_coupon_class_id','=',$param['issue_coupon_class_id']];
        }
        if(@$param['coupon_title']){//消费券名称
            $where[] = ['coupon_title','=',$param['coupon_title']];
        }
        if(@$param['receive_id']){//领取编号
            $where[] = ['CouponIssueUser.id','=',$param['receive_id']];
        }
        if(@$param['uid']){//领取人
            $uidwhere[] = ['name','=',$param['uid']];
        }
        if(@$param['start_time']!=''){//领取时间
            $start_time = explode(' 至 ',$param['start_time']);
            $start_time_start = strtotime($start_time[0]);
            $start_time_end = strtotime($start_time[1]."23:59:59");
            $where[] = ['delivery_input_time', 'between', [$start_time_start, $start_time_end]];
        }
        if(@$param['id']){//选择id导出
            $where[] = ['CouponIssueUser.id','in',explode(",", $param['id'])];
        }
        $where[] = ['delivery_address','<>',''];

        // 获取要导出的数据
        $model = '\app\common\model\\' . $this->modelName;
        $list = $model::getRewriteList($where, 0, [$orderByColumn => $isAsc], $uidwhere);

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
                switch ($v['0']) {
                    case 'issue_coupon_class_id':
                        $val = $CouponClassArr[$value[$v['0']]];
                        break;
                    case 'users_name':
                        $val = $value['users']['name'];
                        break;
                    case 'tracking_number':
                        $val = empty($value[$v['0']]) ? '' : " " . $value[$v['0']];
                        break;
                    default:
                        $val = $value[$v['0']];
                        break;
                }

                $sheet->setCellValue($str[$k] . ($key + 2), $val);

            }
        }

        //写备注信息
        $sheet->setCellValue([15, 1], '注：只允许填写未录入的快递信息，其余信息请勿修改！');
        $sheet->setCellValue([15, 3], '注：顺丰请输入运单号: 收货手机号后四位。例如：123456789:1234');

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="用户领取记录导出' . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    // 导入物流单号
    public function import_Tracking()
    {
        if(request()->isPost()){
            define('__DOCUMENT_PATH__',substr(__FILE__ ,0,-31) );

            // 获取表单上传文件
            $file = request()->file('file');
            if(empty($file)){
                $this->result('',1,'请选择上传文件！');
            }

            // 移动到框架应用根目录/public/upload/ 目录下，并修改文件名为时间戳
            $savename = \think\facade\Filesystem::disk('public')->putFile('files', $file, 'time');

            // 文件名称
            $info = explode('/', $savename);
            $file = public_path() . 'files/'.$info['1'];
            $file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));//获取文件扩展名

            //实例化PHPExcel类
            if ($file_extension == 'xlsx'){
                $objReader =\PHPExcel_IOFactory::createReader('Excel2007');
            } else if ($file_extension == 'xls') {
                $objReader =\PHPExcel_IOFactory::createReader('Excel5');
            }
            $obj_PHPExcel = $objReader->load($file, $encode = 'utf-8');  // 加载文件内容,编码utf-8
            $excel_array = $obj_PHPExcel->getsheet(0)->toArray();  // 转换为数组格式

            $highestRow = $obj_PHPExcel->getSheet(0)->getHighestRow() - 1; //取得总行数:总行-标题行
            array_shift($excel_array);  // 删除标题行

            //处理数据
            $i = 0;
            foreach ($excel_array as $key => $value) {
                // 正则去除多余空白字符
                $issue_coupon_user_id = trim($value['0']);
                $tracking_number      = trim($value['8']);
                if (empty($issue_coupon_user_id)) {
                    continue;
                }
                if (empty($tracking_number)) {
                    continue;
                }

                $model  = '\app\common\model\\' . $this->modelName;
                $detail = $model::find($issue_coupon_user_id);
                if (!$detail) {
                    continue;
                }
                //如果已经存在物流编号
                if (!empty(trim($detail['tracking_number']))) {
                    continue;
                }

                $model::where([["id", "=", $issue_coupon_user_id]])->update(['tracking_number'=>$tracking_number]);

                $i++;
            }

            unlink(public_path() . $savename);
            $this->result('',0,'文件上传成功，更新【'.$i.'】条数据');
        }
    }

    // 团体领取记录
    public function tour(){
        // 获取主键
        //$pk = MakeBuilder::getPrimarykey('tour_issue_user');
        // 获取列表数据
        //$columns = MakeBuilder::getListColumns('tour_issue_user');
        // 插入用户信息字段到第1个元素
        // array_splice($columns, 1, 0, [['users.city','城市','text','',[],'','false']]);
        $add_columns = [
            ['users.mobile','手机号','text','',[],'','false'],
            //['users.idcard','身份证号','text','',[],'','false'],
        ];
        //$columns = array_merge($columns,$add_columns);
        // 获取搜索数据
        //$search = MakeBuilder::getListSearch('tour_issue_user');
        // 获取当前模块信息
        $model = '\app\common\model\\' . 'TourIssueUser';
        $module = \app\common\model\Module::where('table_name', 'tour_issue_user')->find();
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();
            //$where = MakeBuilder::getListWhere('tour_issue_user');
            $where = [];
            if(Request::param('issue_coupon_class_id')){
                $where[] = ['issue_coupon_class_id','=',Request::param('issue_coupon_class_id')];
            }
            if(Request::param('coupon_title')){
                $where[] = ['coupon_title','=',Request::param('coupon_title')];
            }
            if(isset($param['status']) && $param['status'] !='')
                $where[] = ['TourIssueUser.status','=',$param['status']];

            // 模型条件查询
            $uidwhere = [];
            if($param['uid']){
                $uidwhere[] = ['name','=',$param['uid']];
            }

            $orderByColumn = Request::param('orderByColumn') ?? 'id';
            $isAsc = Request::param('isAsc') ?? 'desc';
            //$list  = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
            $list = $model::getRewriteList($where, $this->pageSize, [$orderByColumn => $isAsc],$uidwhere);
            foreach ($list['data'] as $key => $value) {
                $list['data'][$key]['time_use'] = $value['time_use'] > 0 ? date("Y-m-d H:i:s",$value['time_use']) : 0;
            }
            return $list;
        }
        // 消费券分类查询
        $CouponClass = \app\common\model\CouponClass::field('id, title')
            ->order('sort asc')
            ->select()
            ->toArray();
        View::assign(['class_list' => $CouponClass]);
        // 构建页面
        return TableBuilder::getInstance()
            ->addRightButton('info', [                      // 添加额外按钮
                'title' => '查看',
                'icon'  => 'fa fa-search',
                'class' => 'btn btn-primary btn-xs',
                'href'  => url('seee', ['parentId' => '__id__'])
            ])
            ->fetch('coupon_issue_user/tour');//
    }

    // 查看详情
    public function seee($parentId)
    {
        $orderByColumn = $param['orderByColumn'] ?? 'id';
        $isAsc         = $param['isAsc'] ?? 'desc';

        $map = [];

        $map[] = ['id','=',$parentId];
        $model  = '\app\common\model\\' . 'TourIssueUser';
        $detail = $model::where($map)->with(['users','tour'])->find();
        View::assign(['detail' => $detail]);
        return View::fetch();
    }

    // 导出
    public function exportCvs()
    {

        $tableNam = 'coupon_issue_user'; $moduleName = 'CouponIssueUser';
        ob_end_clean();
        // 获取主键
        $pk = \app\common\facade\MakeBuilder::getPrimarykey($tableNam);
        // 搜索
        $where         = \app\common\facade\MakeBuilder::getListWhere($tableNam);
        $orderByColumn = \think\facade\Request::param('orderByColumn') ?? $pk;
        $isAsc         = \think\facade\Request::param('isAsc') ?? 'desc';
        $model         = '\app\common\model\\' . $moduleName;

        $limit = 5000;//每次只从数据库取5000条以防变量缓存太大
        // buffer计数器
        $cnt = 0;
        $xlsTitle = ['编号','分类','名称','面额','领取人','手机号','领取时间'];

        /******************** 调整位置开始 ***************************/
        // 计算总数
        $ids = Request::param('id');
        if(isset($ids)){
            $idsArr = explode(',',$ids);
            $sqlCount = count($idsArr);
            array_push($where,['id','in',$idsArr]);
        }else{
            $sqlCount = $model::count();
        }
        /******************** 调整位置结束 ***************************/
        //$fileName = iconv('utf-8', 'gb2312', 'students');//文件名称
        $moduleName = \app\common\model\Module::where('table_name', $tableNam)->value('module_name');
        $moduleName = $moduleName . date('_YmdHis');// 文件名称可根据自己情况设定
        $zipname = 'zip-' . $moduleName . ".zip";
        // 输出Excel文件头，可把user.csv换成你要的文件名
        header('Content-Type: application/vnd.ms-excel;charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $zipname . '"');
        header('Cache-Control: max-age=0');
        $fileNameArr = array();
        // 逐行取出数据，不浪费内存
        for ($i = 0; $i < ceil($sqlCount / $limit); $i++) {
            $fp = fopen($moduleName . '_' . ($i+1) . '.csv', 'w'); //生成临时文件 
            // chmod('attack_ip_info_' . $i . '.csv',777);//修改可执行权限 
            $fileNameArr[] = $moduleName . '_' . ($i+1) . '.csv'; // 将数据通过fputcsv写到文件句柄 
            fputcsv($fp, $xlsTitle);
            
            $start = $i * $limit;
            /******************** 调整位置开始 ***************************/
            // 获取要导出的数据
            $dataArr = $model::getListExport($where, $limit, [$orderByColumn => $isAsc],$start); // 每次查询limit条数据
            /******************** 调整位置结束 ***************************/
            foreach ($dataArr as $key => $val) {
                $tempVal['uuno'] = $val['couponIssue']['uuno'];
                $tempVal['title'] = $val['couponClass']['title'];
                $tempVal['coupon_title'] = $val['coupon_title'];
                $tempVal['coupon_price'] = $val['coupon_price'];
                $tempVal['username'] = $val['users']['name'];
                $tempVal['mobile'] = $val['users']['mobile'];
                $tempVal['create_time'] = $val['create_time'];
                $cnt++;
                if ($limit == $cnt) {
                    // 刷新一下输出buffer，防止由于数据过多造成问题
                    ob_flush();
                    flush();
                    $cnt = 0;
                }
                fputcsv($fp, $tempVal);
            }
            fclose($fp); // 每生成一个文件关闭
        }
        // 进行多个文件压缩
        $zip = new \ZipArchive();
        $zip->open($zipname, $zip::CREATE); // 打开压缩包
        foreach ($fileNameArr as $file) {
            $zip->addFile($file, basename($file)); // 向压缩包中添加文件
        }
        $zip->close();  // 关闭压缩包
        foreach ($fileNameArr as $file) {
            unlink($file); // 删除csv临时文件
        }
        
        // 输出压缩文件提供下载
        header("Cache-Control: max-age=0");
        header("Content-Description: File Transfer");
        header("Content-Type: application/zip"); // zip格式
        header("Content-Transfer-Encoding: binary");
        header('Content-Length: ' . filesize($zipname));
        @readfile($zipname); // 输出文件
        unlink($zipname); // 删除压缩包临时文件
    }

    // 大文件导出
    public function exportLargeFile()
    {
        ini_set("memory_limit","-1");
        ini_set('max_execution_time','300');
        ob_end_clean();
        $tableNam = 'coupon_issue_user'; $moduleName = 'CouponIssueUser';
        $model         = '\app\common\model\\' . $moduleName;
        // 设置导出文件名
        $filename = 'maxfile.csv';
        // 设置每次读取的字节数
        $chunkSize = 1024 * 1024; // 每次读取1MB
        // 打开文件
        $handle = fopen($filename, 'w');
        // 写入表头
        fputcsv($handle, ['编号','分类','名称','面额','领取人','手机号','领取时间']);

        // 查询数据总数
        $total = $model::count();
        // 每页数据量
        $pageSize = 10000; // 每页10000条数据
        // 总页数
        $pageCount = ceil($total / $pageSize);
        // 分页查询数据
        for ($page = 1; $page <= $pageCount; $page++) {
            $data = $model::with(['couponIssue','couponClass','users'])->limit(($page - 1) * $pageSize, $pageSize)->select()->toArray();
            // 分块读取数据
            foreach ($data as $item) {
                // 处理数据
                $rowData = [
                    $item['couponIssue']['uuno'],
                    $item['couponClass']['title'],
                    $item['coupon_title'],
                    $item['coupon_price'],
                    $item['users']['name'],
                    $item['users']['mobile'],
                    $item['create_time'],
                ];
                // 将数据逐块写入文件
                fputcsv($handle, $rowData);
                // 如果数据量过大，可以在每次输出后清空缓冲区，释放内存
                if (ftell($handle) >= $chunkSize) {
                    fflush($handle);
                }
            }
        }
        // 关闭文件
        fclose($handle);

        // 压缩文件
        $zip = new \ZipArchive();
        $zip->open($filename . '.zip', \ZipArchive::CREATE);
        $zip->addFile($filename);
        $zip->close();
        // 删除原始文件
        unlink($filename);
        // 下载压缩后的文件
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '.zip"');
        header('Content-Length: ' . filesize($filename . '.zip'));
        readfile($filename . '.zip');
        // 删除压缩后的文件
        unlink($filename . '.zip');
    }

    public function xlsData()
    {
        $header = [
            [
                'title' => '一级表头1',
                'children' => [
                    [
                        'title' => '二级表头1',
                    ],
                    [
                        'title' => '二级表头2',
                    ],
                    [
                        'title' => '二级表头3',
                    ],
                ]
            ],
            [
                'title' => '一级表头2'
            ],
            [
                'title' => '一级表头3',
                'children' => [
                    [
                        'title' => '二级表头1',
                        'children' => [
                            [
                                'title' => '三级表头1',
                            ],
                            [
                                'title' => '三级表头2',
                            ],
                        ]
                    ],
                    [
                        'title' => '二级表头2',
                    ],
                    [
                        'title' => '二级表头3',
                        'children' => [
                            [
                                'title' => '三级表头1',
                                'children' => [
                                    [
                                        'title' => '四级表头1',
                                        'children' => [
                                            [
                                                'title' => '五级表头1'
                                            ],
                                            [
                                                'title' => '五级表头2'
                                            ]
                                        ]
                                    ],
                                    [
                                        'title' => '四级表头2'
                                    ]
                                ]
                            ],
                            [
                                'title' => '三级表头2',
                            ],
                        ]
                    ]
                ]
            ],
            [
                'title' => '一级表头4',
            ],
            [
                'title' => '一级表头5',
            ],
        ];
        $data= [];
        // header头规则 title表示列标题，children表示子列，没有子列children可不写或为空
        for ($i = 0; $i < 100; $i++) {
            $data[] = [
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
            ];
        }
        $fileName = '很厉害的文件导出类';
        $xlsWriterServer = new MultiFloorXlsWriterService();
        $xlsWriterServer->setFileName($fileName, '这是Sheet1别名');
        $xlsWriterServer->setHeader($header, true);
        $xlsWriterServer->setData($data);
     
        $xlsWriterServer->addSheet('这是Sheet2别名');
        $xlsWriterServer->setHeader($header);   //这里可以使用新的header
        $xlsWriterServer->setData($data);       // 这里也可以根据新的header定义数据格式
     
        $filePath = $xlsWriterServer->output();     // 保存到服务器
        $xlsWriterServer->excelDownload($filePath); // 输出到浏览器
    }
}
