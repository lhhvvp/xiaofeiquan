<?php
/**
 * 旅行团管理控制器
 * @author slomoo <1103398780@qq.com> 2022/08/14
 */
namespace app\travel\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\View;
use PHPExcel_IOFactory;
use think\facade\Db;
use \AlicFeng\IdentityCard\IdentityCard;
use \AlicFeng\IdentityCard\Area;
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

    //初始化
    protected function initialize()
    {
        parent::initialize();
        $this->CouponIssue      = new \app\common\model\CouponIssue;
        $this->Users            = new \app\common\model\Users;
        $this->CouponClass      = new \app\common\model\CouponClass;
        $this->Seller           = new \app\common\model\Seller;
    }

    public function getajaxlist()
    {
        if(Request::isPost()){
            $param = Request::param();

            $where = [];
            if (@$param['ids']!='') {
                $idsArr  = explode(',',$param['ids']);
                $where[] = ['id','in',$idsArr];
            }

            $model  = '\app\common\model\\' . 'CouponIssue';
            $list   = $model::where($where)->with(['couponClass'])->select();
            $this->success('请求成功','index',$list);
        }
        $this->error('禁止请求');
    }

    // 旅行团添加时选择景区消费券调用该列表
    public function list()
    {
        // 获取主键
        $pk = MakeBuilder::getPrimarykey('CouponIssue');
        // 搜索
        if (Request::param('getList')) {
            $param = Request::param();
            $orderByColumn = $param['orderByColumn'] ?? $pk;
            $isAsc         = $param['isAsc'] ?? 'desc';
            $where = [];
            
            if (@$param['coupon_title']!='') {
                $where[] = ['coupon_title','like',"%".$param['coupon_title']."%"];
            }

            if (@$param['ids']!='') {
                $idsArr  = explode(',',$param['ids']);
                $where[] = ['id','in',$idsArr];
            }

            // 2022-08-26 增加消费券分类搜索
            if (@$param['cid']!='') {
                $where[] = ['cid','=',$param['cid']]; // 畅游 剧院 清爽
            }else{
                $where[] = ['cid','<>',3]; //[1,2,4,5] 畅游 剧院 清爽
            }
            
            $where[] = ['status','=',1];
            $where[] = ['is_del','=',0];
            $where[] = ['receive_type','=',1];
            $where[] = ['remain_count|is_permanent','>',0];
            $where[] = ['type','=',1];
            $where[] = ['use_store','in',[2,4,5]];
            $where[] = ['class_id','=',1];
            // 2022-08-31 过滤券类型
            $where[] = ['coupon_type','in',[1,3]];

            $model  = '\app\common\model\\' . 'CouponIssue';
            return $model::getListIssue($where, 300, [$orderByColumn => $isAsc]);
        }
        // 消费券分类
        $CouponClass = \app\common\model\CouponClass::field('id, title')
            ->order('sort asc')
            ->where('id','<>',3)// [1,2,4,5]
            ->select()
            ->toArray();
        View::assign(['class_list' => $CouponClass]);
        return View::fetch('tour/list');
    }

    // 旅行团添加时选择旅行社消费券调用该列表
    public function list_tour()
    {
        // 获取主键
        $pk = MakeBuilder::getPrimarykey('CouponIssue');
        // 搜索
        if (Request::param('getList')) {
            $param = Request::param();
            $orderByColumn = $param['orderByColumn'] ?? $pk;
            $isAsc         = $param['isAsc'] ?? 'desc';
            $where = [];
            
            if (@$param['coupon_title']!='') {
                $where[] = ['coupon_title','=',$param['coupon_title']];
            }
            $where[] = ['cid','=',3]; // 旅行社
            $where[] = ['status','=',1];
            $where[] = ['is_del','=',0];
            $where[] = ['receive_type','=',1];
            $where[] = ['remain_count|is_permanent','>',0];
            $where[] = ['type','=',1];
            $where[] = ['use_store','=',3];
            $where[] = ['class_id','=',1];

            $model  = '\app\common\model\\' . 'CouponIssue';
            return $model::getListIssue($where, $this->pageSize, [$orderByColumn => $isAsc]);
        }
        return View::fetch('tour/list_tour');
    }

    // 列表
    public function index()
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
                $where[] = ['status','in',[1,2,3,8,9]];

            // 旅行社商户ID
            $mid = session()['travel']['id'];
            $where[] = ['mid','=',$mid];
            $model  = '\app\common\model\\' . $this->modelName;
            return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
        }
        return View::fetch('tour/index');
    }

    // 添加
    public function add()
    {
        // 景区已经勾选的消费券
        if (Request::param('getList') != '') {
            $where = [];
            $where[] = ['id','in',Request::param('getList')];
            return \app\common\model\CouponIssue::where($where)->select();
        }
        // 获取省份区域
        $area = \app\common\model\Area::where('pid',0)
            ->select();
        View::assign(['area' => $area]);
        return View::fetch('tour/add');
    }

    // 添加保存
    public function addPost()
    {
        if (Request::isPost()) {
            /*$expiry_date  = 1690819199;// strtotime('2023-07-31 23:59:59');
            $current_date = time();

            if ($current_date > $expiry_date) {
                $this->error('暂停上报');
            }*/
            $system = \app\common\model\System::value('tour_status');
            if($system == 1) {
                $this->error('暂停上报');
            }
            // 2023-08-01 新增单商户每日限制330人上报团计划
            $data = Request::except(['file'], 'post');

            $starTimeTotady = strtotime(date("Y-m-d 00:00:00"));
            $map[] = ['create_time', '>', $starTimeTotady];
            $totalTour = Db::name('tour')
                ->where('mid', session('travel')['id'])
                ->where($map)
                ->sum('numbers');

            if(!is_numeric($totalTour) || !is_numeric($data['numbers'])){
                $this->error('请传入正确的团人数');
            }
            /*$maxAllowed = 390;
            if (($totalTour + $data['numbers']) > $maxAllowed) {
                $this->error('每天最多上报330人团计划');
            }*/
            
            $result = $this->validate($data, $this->validate);

            if($data['numbers'] < 10) 
                $this->error('10人起成团');

            if($data['numbers'] > 55) 
                $this->error('单团上限55人申请');

            // 2022-10-17 酒店校验: 如果团期为一天 则酒店信息非必填
            $hotelArr = $data['hotel_arr'];
            $lenHotel = count($hotelArr);
            if($data['term']){
                $termArr = explode(' - ',$data['term']);
                $data['term_start'] = strtotime($termArr[0]);
                $data['term_end']   = strtotime($termArr[1]);

                if(($data['term_end']-$data['term_start']) > 86400){
                    // 当前团期大于1天 所有酒店信息为必填
                    for ($i=0; $i < $lenHotel; $i++) { 
                        if($hotelArr[$i]==''){
                            $this->error('当前团期大于一天，第 '.($i+1).' 项酒店信息不能为空');
                        }
                    }
                }else{
                    $hotelArr = '';
                }
            }

            // 2023-04-17 旅行团增加城市选择
            if($data['city_id']=='')
                $this->error('请选择城市');
            // 拼接
            $data['area_id'] = $data['area_id'].'-'.$data['city_id'];
            unset($data['city_id']);

            if (true !== $result) {
                // 验证失败 输出错误信息
                $this->error($result);
            } else {
                // 事务操作
                Db::startTrans();
                $data['create_time'] = time();
                $data['mid'] = session('travel')['id'];
                $model = '\app\common\model\\' . $this->modelName;

                if($data['term']){
                    $termArr = explode(' - ',$data['term']);
                    $data['term_start'] = strtotime($termArr[0]);
                    $data['term_end']   = strtotime($termArr[1]);

                }
                unset($data['hotel_arr']);
                //$result = $model::addPost($data);
                $tid = $model::insertGetId($data);

                // 生成团体券
                $ids    = $data['travel_id'].','.$data['spot_ids'];
                $idsArr = explode(',',$ids);
                $couponissue = $this->CouponIssue::where('id','in',$idsArr)->field('id as coupon_issue_id,cid')->select()->toArray();

                // 加密数据=不允许修改的数据
                $enArr['id']     = $tid;
                $enArr['name']   = $data['name'];
                $enArr['no']     = $data['no'];
                $enArr['term_start']   = $data['term_start'];
                $enArr['term_end']   = $data['term_end'];
                $enArr['numbers'] = $data['numbers'];
                $enArr['planner'] = $data['planner'];
                $enArr['mobile']  = $data['mobile'];
                $enArr['mid']     = $data['mid'];
                foreach ($couponissue as $key => $value) {
                    $couponissue[$key]['tid']         = $tid;
                    $couponissue[$key]['create_time'] = time();

                    $enstr_salt = md5(json_encode($enArr,JSON_UNESCAPED_UNICODE).$value['coupon_issue_id']);
                    // 加密串=一条团信息
                    $couponissue[$key]['enstr_salt']  = $enstr_salt;
                }
                $res = Db::name('tour_coupon_group')->replace()->insertAll($couponissue);

                if($hotelArr!=''){
                    $add_hotel_array = [];
                    foreach ($hotelArr as $key => $value) {
                        $add_hotel_array[$key]['name'] = $value;
                        $add_hotel_array[$key]['tid']  = $tid;
                    }
                    // 2022-10-17 旅行团新增 添加酒店信息
                    $hotel = Db::name('tour_hotel')->replace()->insertAll($add_hotel_array);
                }

                if ($tid > 0 && $res > 0) {
                    Db::commit();
                    $this->success('添加成功','index');
                }
                Db::rollback();
                $this->error('添加失败','index');
            }
        }
    }

    // 修改
    public function edit(string $id)
    {
        // 查询详情
        $model        = '\app\common\model\\' . $this->modelName;
        $tour  = $model::edit($id)->toArray();
        View::assign(['tour' => $tour]);
        // 审核记录
        $ExamineRecord = \app\common\model\ExamineRecord::where('sid',$id)->where('tags',2)
            ->order('create_time desc')
            ->with(['admin','authGroup'])
            ->select();
        View::assign(['ExamineRecord' => $ExamineRecord]);
        // 酒店信息
        $TourHotel = \app\common\model\TourHotel::where('tid',$id)
            ->select();
        View::assign(['tourhotel' => $TourHotel]);
        // 获取省份区域
        $area = \app\common\model\Area::where('pid',0)
            ->select();
        View::assign(['area' => $area]);
        return View::fetch();
    }
    // 修改保存
    public function editPost()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            $where['id'] = $data['id'];
            
            //  || $data['spot_ids']==''
            if ($data['travel_id']=='' || $data['line_info']=='') {
                $this->error('必填项不能为空');
            }
            $model = '\app\common\model\\' . $this->modelName;
            $tour  = $model::edit($data['id'])->toArray();
            $idsStr = $tour['travel_id'].','.$tour['spot_ids'];
            $coupon_issue_id = explode(',',$idsStr);

            /*if($data['term']){
                $termArr = explode(' - ',$data['term']);
                $data['term_start'] = strtotime($termArr[0]);
                $data['term_end']   = strtotime($termArr[1]);
            }*/

            // 2022-10-17 酒店校验: 如果团期为一天 则酒店信息非必填
            $hotelArr = $data['hotel_arr'];
            $lenHotel = count($hotelArr);
            if($data['term']){
                $termArr = explode(' - ',$data['term']);
                $data['term_start'] = strtotime($termArr[0]);
                $data['term_end']   = strtotime($termArr[1]);

                if(($data['term_end']-$data['term_start']) > 86400){
                    // 当前团期大于1天 所有酒店信息为必填
                    for ($i=0; $i < $lenHotel; $i++) { 
                        if($hotelArr[$i]==''){
                            $this->error('当前团期大于一天，第 '.($i+1).' 项酒店信息不能为空');
                        }
                    }
                }else{
                    $hotelArr = '';
                }
            }
            // 2023-04-17 旅行团增加城市选择
            if($data['city_id']=='')
                $this->error('请选择城市');
            // 拼接
            $data['area_id'] = $data['area_id'].'-'.$data['city_id'];
            unset($data['city_id']);
            
            //$result = $model::editPost($data);
            // 事务操作
            Db::startTrans();
            $data['update_time'] = time();
            // 每次编辑都变为审核中
            $data['status'] = 1;
            unset($data['hotel_arr']);
            $tid = $model::where('id',$data['id'])->update($data);

            // 删除原来关联的团体信息 
            Db::name('tour_coupon_group')
            ->where('tid',$data['id'])
            ->where('coupon_issue_id','in',$coupon_issue_id)
            ->delete();

            // 生成团体券
            $ids    = $data['travel_id'].','.$data['spot_ids'];
            $idsArr = explode(',',$ids);
            $couponissue = $this->CouponIssue::where('id','in',$idsArr)->field('id as coupon_issue_id,cid')->select()->toArray();
            $tidInfo = $model::find($data['id'])->toArray();

            // 加密数据=不允许修改的数据
            $enArr['id']     = $tidInfo['id'];
            $enArr['name']   = $tidInfo['name'];
            $enArr['no']     = $tidInfo['no'];
            $enArr['term_start']   = $data['term_start'];
            $enArr['term_end']   = $data['term_end'];
            $enArr['numbers'] = $tidInfo['numbers'];
            $enArr['planner'] = $tidInfo['planner'];
            $enArr['mobile']  = $tidInfo['mobile'];
            $enArr['mid']     = $tidInfo['mid'];
            foreach ($couponissue as $key => $value) {
                $couponissue[$key]['tid']         = $tidInfo['id'];
                $couponissue[$key]['create_time'] = time();

                $enstr_salt = md5(json_encode($enArr,JSON_UNESCAPED_UNICODE).$value['coupon_issue_id']);
                // 加密串=一条团信息
                $couponissue[$key]['enstr_salt']  = $enstr_salt;
            }
            $res = Db::name('tour_coupon_group')->replace()->insertAll($couponissue);

            // 2022-10-17 旅行团新增 添加酒店信息
            if($hotelArr!=''){
                // 删除当前团之前关联的酒店信息
                Db::name('tour_hotel')
                ->where('tid',$data['id'])
                ->delete();
                $add_hotel_array = [];
                foreach ($hotelArr as $key => $value) {
                    $add_hotel_array[$key]['name'] = $value;
                    $add_hotel_array[$key]['tid']  = $data['id'];
                }
                $hotel = Db::name('tour_hotel')->replace()->insertAll($add_hotel_array);
            }

            if ($tid > 0 && $res > 0) {
                Db::commit();
                $this->success('修改成功','index');
            }
            Db::rollback();
            $this->error('添加失败','index');
        }
    }

    // 确认成团
    public function state($id)
    {
        try {
            $model = '\app\common\model\\' . $this->modelName;
            $info  = $model::find($id);
            $info->status = 4;
            $info->save();
            return json(['error' => 0, 'msg' => '修改成功!']);
        } catch (\Exception $e) {
            return json(['error' => 1, 'msg' => $e->getMessage()]);
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

            // 旅行社商户ID
            $mid = session()['travel']['id'];
            $where[] = ['mid','=',$mid];
            $where[] = ['status','in',[4,5,6]];
            $model  = '\app\common\model\\' . $this->modelName;
            $list = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
            return $list;
        }
        // 系统配置
        $system = \app\common\model\System::field('is_clock_switch')->find(1);
        View::assign(['system' => $system]);
        return View::fetch('tour/agg');
    }

    // 添加导游、游客信息
    public function addInfo($id,$tags)
    {   
        View::assign(['tid' => $id]);
        // 导游管理
        if($tags==1){
            return FormBuilder::getInstance()->fetch('tour/guide');
        }
        // 游客管理
        if($tags==2){
            $tour = \app\common\model\Tour::where('mid',session('travel')['id'])->find($id);
            $tourist_ids = Db::name('tourist')->where("tid",$tour['id'])->where(function($query){
                $query->where("is_authenticated",0)->whereOr("uid",0);
            })->column("id");
            $tourist_ids = empty($tourist_ids) ? '' : implode(",",$tourist_ids);
            View::assign(['tour' => $tour,'tourist_ids'=>$tourist_ids]);
            return View::fetch('tour/tourist'); 
        }
    }

    // 获取导游列表
    public function guide()
    {
        // 搜索
        if (Request::param('getList') == 1 && Request::param('tid') !='') {
            $param = Request::param();

            $orderByColumn = $param['orderByColumn'] ?? 'id';
            $isAsc         = $param['isAsc'] ?? 'desc';

            // 旅行社商户ID
            $mid = session()['travel']['id'];
            $where[] = ['mid','=',$mid];
            $where[] = ['tid','=',Request::param('tid')];

            $list = Db::name('guide')
                ->where($where)
                ->order([$orderByColumn => $isAsc])
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $this->pageSize,
                ]);
            return $list;
        }
    }

    // 添加导游人员页面
    public function addGuide(){
        View::assign(['tid' => Request::param('tid')]);
        return View::fetch();
    }
    // 添加保存
    public function addguidePost()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            $data['mid'] = session('travel')['id'];
            $result = $this->validate($data, 'Guide');
            if (true !== $result) {
                // 验证失败 输出错误信息
                $this->error($result);
            } else {
                $data['card_type'] = 1;
                $data['uid'] = $this->getuid($data['mobile'],$data);
                $model = '\app\common\model\\' . 'Guide';
                $result = $model::addPost($data);
                if ($result['error']) {
                    $this->error($result['msg']);
                } else {
                    $this->success($result['msg'], 'index');
                }
            }
        }
    }

    // 编辑导游人员页面
    public function editGuide($id){
        View::assign(['tid' => Request::param('tid')]);
        // 查询详情
        $model        = '\app\common\model\\' . 'Guide';
        $guide  = $model::edit($id)->toArray();
        View::assign(['guide' => $guide]);
        return View::fetch();
    }

    // 编辑保存
    public function editguidePost()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            $data['mid'] = session('travel')['id'];
            $result = $this->validate($data, 'Guide');
            if (true !== $result) {
                // 验证失败 输出错误信息
                $this->error($result);
            } else {
                $data['card_type'] = 1;
                $data['uid'] = $this->getuid($data['mobile'],$data);
                $model = '\app\common\model\\' . 'Guide';
                $result = $model::editPost($data);
                if ($result['error']) {
                    $this->error($result['msg']);
                } else {
                    $this->success($result['msg'], 'index');
                }
            }
        }
    }

    // 根据手机号获取uid
    private function getuid($mobile,$data)
    {
        if($data['card_type']==1){
            // 校验数据正确性
            if(!isCreditNo($data['idcard'])){ 
                $this->error('请输入正确的身份证号码');
            }
        }
        // 检查当前手机号是否存在与用户表内  存在则将用户ID冗余过来 否则创建用户在将用户ID冗余过来
        $modelUsers = '\app\common\model\\' . 'Users';
        $info  = $modelUsers::where('mobile',$mobile)->where('idcard',$data['idcard'])->find(); // 重新调整

        if($info){
            return $info->id;
        }else{
            // 校验手机号是否存在
            $mobileInfo = Db::name('users')->where('mobile', $mobile)->find();
            if ($mobileInfo) {
                $this->error('当前手机号已经绑定其他身份证号：'.$mobileInfo['idcard']);
            }
            // 校验身份证号是否存在
            $idcard = Db::name('users')
                ->where('idcard', $data['idcard'])
                //->where('idcard','<>','')
                ->whereNotNull('idcard')
                ->find();
            if ($idcard) {
                $this->error('当前身份证号已经绑定其他手机号：'.$idcard['mobile']);
            }
            // 2要素认证
            $system = \app\common\model\System::find(1);
            if($system['app_code']==''){
                $this->error('请配置认证代码');
            }

            $host = "https://dfidveri.market.alicloudapi.com";
            $path = "/verify_id_name";
            $method = "POST";
            $appcode = $system['app_code'];//"1fb45072d6ea46d4b6f1db63bdb6b78b";
            $headers = array();
            array_push($headers, "Authorization:APPCODE " . $appcode);
            //根据API的要求，定义相对应的Content-Type
            array_push($headers, "Content-Type".":"."application/x-www-form-urlencoded; charset=UTF-8");
            $bodys = "id_number=".$data['idcard']."&name=".$data['name'];
            $url = $host . $path;

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            if (1 == strpos("$".$host, "https://"))
            {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
            $res = curl_exec($curl);
            curl_close($curl);
            $jsonData = json_decode($res,true);

            if ($jsonData['status'] == 'OK' && $jsonData['state']==1) {
                // 处理 OK 状态的逻辑
                // $this->user_auth_log($jsonData,$param);
                $inData['salt']         = set_salt(6); // 永久加密盐  用于手机号  身份证号加密
                $inData['create_time']  = time();
                $inData['mobile']       = $mobile;
                $inData['name']         = $data['name'];
                $inData['idcard']       = $data['idcard']; 
                $inData['mobile_validated'] = 1;
                $inData['card_type']    = $data['card_type'];
                $inData['nickname']     = '';
                $inData['headimgurl']   = '';
                $inData['sex']          = 0;
                $inData['create_ip']    = request()->ip();
                $inData['uuid']         = gen_uuid();
                $inData['last_login_ip']= request()->ip();
                $inData['last_login_time']= time();

                if(isset($inData['idcard'])){
                    $inData['idcard'] = trim($inData['idcard']);
                    
                    if($inData['card_type']==1){
                      
                        # 获取周岁 | 
                        $inData['age'] = IdentityCard::age($inData['idcard']);
                        # 获取生日
                        $inData['birthday'] = IdentityCard::birthday($inData['idcard']);
                        # 获取性别 | {男为M | 女为F}
                        $sex = IdentityCard::sex($inData['idcard']);
                        $inData['sex'] = $sex=='M' ? 1 : 2;
                        # 获取生肖
                        $inData['zodiac'] = IdentityCard::constellation($inData['idcard']);
                        # 获取星座
                        $inData['starsign'] = IdentityCard::star($inData['idcard']);
                        /*# 获取省份
                        $province = Area::province($inData['idcard'], $default='');
                        # 获取省份
                        $city = Area::city($inData['idcard'], $default='');
                        # 获取省份
                        $area = Area::area($inData['idcard'], $default='');*/

                        $get_area_code_info = get_area_code_info($inData['idcard']);

                        $inData['province'] = isset($get_area_code_info['province']) ? $get_area_code_info['province'] : '';
                        $inData['city']     = isset($get_area_code_info['city']) ? $get_area_code_info['city'] : '';
                        $inData['district'] = isset($get_area_code_info['district']) ? $get_area_code_info['district'] : '';

                    }

                    //身份证号获取地址暂时无法获取 用注册ip代替
                    /*$res = get_ip_area(request()->ip());
                    $inData['province'] = isset($res['province']) ? $res['province'] : '';
                    $inData['city']     = isset($res['city']) ? $res['city'] : '';
                    $inData['district'] = isset($res['district']) ? $res['district'] : '';*/

                    $inData['email_validated'] = 1; // 输入了身份证号表明实名认证
                    $inData['auth_status'] = 1;
                }

                $uid = $modelUsers::insertGetId($inData);
                return $uid;
            } elseif ($jsonData['status'] == 'OK' && $jsonData['state']==2) {
                // 认证不通过
                $this->error('姓名和身份证号不匹配');
            } elseif ($jsonData['status'] == 'RATE_LIMIT') {
                // 处理 RATE_LIMIT 状态的逻辑
                $this->error('同一名字30分钟内只能认证10次');
            } elseif ($jsonData['status'] == 'INVALID_ARGUMENT') {
                // 处理 INVALID_ARGUMENT 状态的逻辑
                $this->error('认证失败');
            } else {
                // 处理其他状态的逻辑
                $this->error('身份认证无法通过');
            }
        }
    }

    // 状态
    public function stateGuide($id)
    {
        try {
            $model = '\app\common\model\\' . 'Guide';
            $info  = $model::find($id);
            $info->status = $info['status'] == 1 ? 0 : 1;
            $info->save();
            return json(['error' => 0, 'msg' => '修改成功!']);
        } catch (\Exception $e) {
            return json(['error' => 1, 'msg' => $e->getMessage()]);
        }
    }

    // 获取游客列表
    public function tourist()
    {
        // 搜索
        if (Request::param('getList') == 1 && Request::param('tid') !='') {
            $param = Request::param();

            $orderByColumn = $param['orderByColumn'] ?? 'id';
            $isAsc         = $param['isAsc'] ?? 'desc';

            $where = [];
            $model  = '\app\common\model\\' . 'Tourist';
            // 旅行社商户ID
            $mid = session()['travel']['id'];
            $where[] = ['mid','=',$mid];
            $where[] = ['tid','=',Request::param('tid')];
            return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
        }
    }
    //添加游客信息
    public function addTourist(){
        View::assign(['tid' => Request::param('tid')]);
        return View::fetch();
    }
    // 添加保存
    public function addtouristPost()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            $data['mid'] = session('travel')['id'];
            $result = $this->validate($data, 'Tourist');
            if (true !== $result) {
                // 验证失败 输出错误信息
                $this->error($result);
            } else {
                // 校验身份证号前4位 禁止 6127 6108的游客加入
                /*if($data['card_type']==1){
                    $tempCard = substr($data['idcard'], 0,4);
                    if(in_array($tempCard,['6127','6108']))
                        $this->error('仅限来榆游客');
                }*/
                $data['idcard'] = trim($data['idcard']);
                // 2023-03-09 游客信息添加证件类型
                switch ($data['card_type']) {
                    case 1:
                        if(!isCreditNo($data['idcard']))
                            $this->error('当前游客身份证号码错误');

                        $tempCard = substr($data['idcard'], 0,4);
                        if(in_array($tempCard,['6127','6108']))
                            $this->error('仅限来榆游客');
                        break;
                    case 2:
                        if(!passportVerify($data['idcard']))
                            $this->error('请输入正确的护照');
                        break;
                    case 3:
                        if(!taibaoVerify($data['idcard']))
                            $this->error('请输入正确的台湾通行证');
                        break;
                    case 4:
                        if(!gapassportVerify($data['idcard']))
                            $this->error('请输入正确的港澳通行证');
                        break;
                    case 5:
                        if(!_checkReturnHome($data['idcard']))
                            $this->error('请输入正确的回乡证');
                        break;
                    default:
                        // code...
                        break;
                }
                if($data['card_type'] != 1 && empty($data['card_file'])){
                    $this->error('请上传证件图片！');
                }
                // 检查当前手机号是否存在
                $result_phone = \app\common\model\Tourist::field('mobile')
                ->where('mobile', '=', $data['mobile'])
                ->where('tid',$data['tid'])
                ->find();
                if($result_phone){
                    $this->error('该手机号已在当前团下存在');
                }
                $model = '\app\common\model\\' . 'Tourist';
                $result = $model::addPost($data);
                if ($result['error']) {
                    $this->error($result['msg']);
                } else {
                    $this->success($result['msg'], 'javascript:history.back(-1)');
                }
            }
        }
    }

    // 状态
    public function stateTourist($id)
    {
        try {
            $model = '\app\common\model\\' . 'Tourist';
            $info  = $model::find($id);
            $info->status = $info['status'] == 1 ? 0 : 1;
            $info->save();
            return json(['error' => 0, 'msg' => '修改成功!']);
        } catch (\Exception $e) {
            return json(['error' => 1, 'msg' => $e->getMessage()]);
        }
    }

    //tourist_edit 修改游客信息
    public function editTourist($id){
        // 查询团信息
        $model        = '\app\common\model\\' . $this->modelName;
        $tour  = $model::edit(Request::param('tid'))->toArray();
        View::assign(['tour' => $tour]);
        // 查询详情
        $model        = '\app\common\model\\' . 'Tourist';
        $tourist  = $model::edit($id)->toArray();
        View::assign(['tourist' => $tourist]);
        return View::fetch();
    }

    // 编辑保存
    public function edittouristPost()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            $data['mid'] = session('travel')['id'];
            $result = $this->validate($data, 'Tourist');
            if (true !== $result) {
                // 验证失败 输出错误信息
                $this->error($result);
            } else {
                // 校验身份证号前4位 禁止 6127 6108的游客加入
                /*$tempCard = substr($data['idcard'], 0,4);
                if(in_array($tempCard,['6127','6108']))
                    $this->error('仅限来榆游客');*/
                $data['idcard'] = trim($data['idcard']);
                switch ($data['card_type']) {
                    case 1:
                        if(!isCreditNo($data['idcard']))
                            $this->error('当前游客身份证号码错误');
                        
                        $tempCard = substr($data['idcard'], 0,4);
                        if(in_array($tempCard,['6127','6108']))
                            $this->error('仅限来榆游客');
                        break;
                    case 2:
                        if(!passportVerify($data['idcard']))
                            $this->error('请输入正确的护照');
                        break;
                    case 3:
                        if(!taibaoVerify($data['idcard']))
                            $this->error('请输入正确的台湾通行证');
                        break;
                    case 4:
                        if(!gapassportVerify($data['idcard']))
                            $this->error('请输入正确的港澳通行证');
                        break;
                    case 5:
                        if(!_checkReturnHome($data['idcard']))
                            $this->error('请输入正确的回乡证');
                        break;
                    default:
                        // code...
                        break;
                }

                // 检查当前手机号是否存在
                $result_phone = \app\common\model\Tourist::field('mobile')
                ->where('mobile', '=', $data['mobile'])
                ->where('tid',$data['tid'])
                ->where('id','<>',$data['id'])
                ->find();
                if($result_phone){
                    $this->error('手机号已经注册过了');
                }

                // 添加游客信息
                $data['uid'] = $this->getuid($data['mobile'],$data);
                $model = '\app\common\model\\' . 'Tourist';
                $data['is_authenticated'] = 1; // 一键认证过
                $result = $model::editPost($data);
                if ($result['error']) {
                    $this->error($result['msg']);
                } else {
                    $this->success($result['msg'], 'index');
                }
            }
        }
    }

    // delTourist 删除游客信息
    public function delTourist($id){
        if ($id) {
            $ids = explode(',', $id);
            $rs  = \app\common\model\Tourist::destroy($ids);
            if($rs){
                $this->success('删除成功', 'index');
            }
        }
        $this->error('删除失败');
    }

    // 游客导入
    public function imports(){

        if(request()->isPost()){
            $tid = Request::param('tid');
            if(!$tid){
                $this->result('',1,'旅行团ID不存在,请刷新页面重试');
            }

            // 锁定之后无法导入
            $tour = \app\common\model\Tour::where(['id'=>$tid,'mid'=>session()['travel']['id']])->find();
            if(!$tour){
                $this->result('',1,'旅行团不存在,请刷新页面重试');
            }

            // 从2023-07-31 12点之后禁止锁客
            //$expiry_date  = 1690819199;// strtotime('2023-07-31 23:59:59');
            //$current_date = time();
            // $current_date > $expiry_date && 
            if ($tour->status==6) {
                $this->result('',1,'禁止导入');
            }

            if($tour['is_locking']){
                $this->result('',1,'团已锁定无法导入');
            }
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
            $file = public_path().'files/'.$info['1'];
            $file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));//获取文件扩展名
            //实例化PHPExcel类
            if ($file_extension == 'xlsx'){
                $objReader =\PHPExcel_IOFactory::createReader('Excel2007');
            } else if ($file_extension == 'xls') {
                $objReader =\PHPExcel_IOFactory::createReader('Excel5');
            }
            $obj_PHPExcel =$objReader->load($file, $encode = 'utf-8');  // 加载文件内容,编码utf-8
            $excel_array=$obj_PHPExcel->getsheet(0)->toArray();  // 转换为数组格式
 
            $highestRow = $obj_PHPExcel->getSheet(0)->getHighestRow() - 3; //取得总行数
            $highestRow = $highestRow - 3; //取得总行数
            array_shift($excel_array);  // 删除第一个数组(标题);
            array_shift($excel_array);  // 删除第二个数组(标题);

            $data = [];  // 数据库需要的二维数组
            // 2023-07-10 单团限制人数55人
            if(count($excel_array) > 56){
                $this->result('',1,'单团限制游客数量55人');
            }
            foreach ($excel_array as $key => $value) {
                // 正则去除多余空白字符
                $data[$key]['name']   = preg_replace('/\s+/', '', $value['0']);
                $data[$key]['mobile'] = preg_replace('/\s+/', '', $value['1']);
                if(!check_phone($data[$key]['mobile']))
                    $this->result('',1,'当前游客手机号码错误: '.$data[$key]['name']);
                $data[$key]['idcard'] = preg_replace('/\s+/', '', $value['2']);
                $data[$key]['idcard'] = strtoupper(trim($data[$key]['idcard']));
                //if(!isCreditNo($data[$key]['idcard']))
                    //$this->result('',1,'当前游客身份证号码错误: '.$data[$key]['name']);
                // 校验身份证号前4位 禁止 6127 6108的游客加入
                $tempCard = substr($value['2'], 0,4);
                if(in_array($tempCard,['6127','6108']))
                    $this->result('',1,'仅限来榆游客: '.$data[$key]['name']);
                $data[$key]['tid']         = $tid;
                $data[$key]['sort']        = $key;
                $data[$key]['card_type']   = 1;
                $data[$key]['create_time'] = time();
                $data[$key]['update_time'] = time();
                $data[$key]['mid']    = session()['travel']['id'];
            }

            // 过滤表格重复手机号
            $chongfu_mobile = array_column($data,'mobile');
            $origin_total = count($chongfu_mobile);
            $now_total = count(array_unique($chongfu_mobile));
            if($now_total!=$origin_total){
                $this->result('',1,'表格不能存在重复手机号,请刷新页面重试');
            }

            // 校验手机号是否重复
            $phone_array = array_column($data, 'mobile');
            $phone_ids   = implode(',', $phone_array);
            $result_phone = \app\common\model\Tourist::field('mobile')
                ->where('mobile', 'in', $phone_ids)
                ->where('tid',$tid)
                ->select();

            if ($result_phone->toArray()) {
                $result_phone_array = array_column($result_phone->toArray(), 'mobile');
                $result_phone_ids = implode(',', $result_phone_array);
                $this->result($result_phone_ids,1,'检测到以下手机号重复：');
            }

            unlink(__DOCUMENT_PATH__.'/public/'.$savename);
            $result = Db::name('tourist')->replace()->insertAll($data);
            if (!$result) {
                $this->result('',1,'插入数据失败');
            }
            $this->result('',0,'文件上传成功，已经导入'.$result.'条数据');
        }
    }

    // 旅行团领取消费券界面
    public function receive()
    {
        $id = Request::param('id');
        View::assign(['id' => $id]);

        if(Request::param('getList')==1){
            $model  = '\app\common\model\\' . 'TourCouponGroup';
            $where[] = ['tid','=',$id];
            return $model::getList($where, $this->pageSize, ['id' => 'desc']);
        }
        return FormBuilder::getInstance()->fetch('tour/receive');
    }

    // 一键领取消费券领取动作
    public function operate()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            if (!$data['tid'] || !is_numeric($data['tid']) || !$data['id']) $this->error('数据异常请刷新页面重试');

            $couponId = $data['id']; // 消费券ID
            $tid      = $data['tid'];// 团ID

            // 检查当前旅行团游客是否锁定
            $tour_info = \app\common\model\Tour::where('id',$tid)->where('mid',session('travel')['id'])->find();
            if(!$tour_info){
                $this->error('旅行团不存在');
            }
            if($tour_info['is_locking']==0){
                $this->error('旅行团游客未锁定，请前去游客信息管理锁定游客后在进行操作');
            }

            // 检查该团下所有游客是否均已上传合同、保单、关联到用户表
            $total = \app\common\model\Tourist::where('tid',$tid)->count();

            // 查询团下面所有游客信息
            $tourist = \app\common\model\Tourist::where('tid',$data['tid'])
            ->where('status',1)
            ->where('uid','<>',0)
            ->where('contract','<>','')
            ->where('insurance','<>','')
            ->with(['users'])
            ->select();

            $len = count($tourist);
            if($total != $len || $len <=0){
                $this->error('检测到还有游客信息不完整，暂时无法领取消费券');
            }

            // 校验领取数量
            $cInfo = \app\common\model\CouponIssue::where('id',$couponId)->find();
            if($len > $cInfo['remain_count']){
                $this->error('消费券库存不足，无法领取');
            }

            $res = [];
            for ($i=0; $i < $len; $i++) { 
                $res[] = $this->issueUserCoupon($couponId,$tourist[$i]->toArray(),$tid);
            }
            $rrsTotal = count($res);
            // 修改状态--已领取
            if($rrsTotal == $len){
                $gData['id']          = $data['gid'];
                $gData['is_receive']  = 1;
                $gData['update_time'] = $gData['receive_time'] = time();
                \app\common\model\TourCouponGroup::editPost($gData);
            }
            $this->success('领取成功','index');
        }

        $this->error('非法请求，禁止访问');
    }

    // 数据校验&&消费券领取动作
    private function issueUserCoupon($id, $user, $tid)
    {
        $issueCouponInfo = $this->CouponIssue->getInfo((int)$id);
        if (!$issueCouponInfo) $this->error("未找到对应的消费券信息");

        if($issueCouponInfo['status'] ==0) $this->error('未开启消费券');
        if($issueCouponInfo['status'] ==-1) $this->error('无效消费券');
        if($issueCouponInfo['is_del'] ==1) $this->error('当前消费券已被删除');
        if($issueCouponInfo['limit_time']==1){
            if($issueCouponInfo['start_time'] > time()) $this->error('活动未开启');
            if($issueCouponInfo['end_time'] < time()) $this->error('已过领取时间');
        }
        if($issueCouponInfo['status'] ==2) $this->error('已领完');
        if($issueCouponInfo['remain_count'] <=0 ) $this->error('库存不足');

        if ($issueCouponInfo->remain_count <= 0 && !$issueCouponInfo->is_permanent){
            // 修改消费券状态
            Db::name('CouponIssue')->where('id',$id)->update(['status'=>2,'update_time'=>time()]);
            $this->error('抱歉消费券已经领取完了！');
        }

        $uid = $user['uid'];

        // 事务操作
        Db::startTrans();
        // 库存校验
        $remain_count = $this->CouponIssue->remainCount((int)$id);
        if($remain_count[0]['remain_count'] <= 0 ) $this->error('已抢完');
        if($remain_count[0]['provide_count'] >= $issueCouponInfo['total_count']) $this->error('已领完');
        try {
            // 2023-03-10 根据不同类型计算券的到期时间
            $expire_time_count = 0;
            switch ($issueCouponInfo['is_permanent']) {
                case 1:
                    // 永久
                    $expire_time_count = 4070880000; // 2099-01-01
                    break;
                case 2:
                    // 期限
                    $expire_time_count = $issueCouponInfo['coupon_time_end'];
                    break;
                case 3:
                    // 按天$day = 7;
                    $day_time = $issueCouponInfo['day'];
                    $expire_time_count = strtotime('+'.$day_time.' day');
                    break;
                default:
                    // code...
                    break;
            }
            // 领取存储的数据
            $saveData = [
                'uid' => $uid, 
                'tid' => $tid, 
                'issue_coupon_id' => $id, 
                'issue_coupon_class_id' => $issueCouponInfo['cid'], 
                'create_time'   => time(),
                'expire_time'   => $expire_time_count,
                'coupon_title'  => $issueCouponInfo['coupon_title'],
                'coupon_price'  => $issueCouponInfo['coupon_price'],
                'use_min_price' => $issueCouponInfo['use_min_price'],
                'coupon_create_time'  => strtotime($issueCouponInfo['create_time']),
                'time_start'    => $issueCouponInfo['start_time'],
                'time_end'      => $issueCouponInfo['end_time'],
                'is_fail'       => 1,
                'type'          => $issueCouponInfo['cid'] == 3 ? 1 : 2,
                'is_limit_total' => $issueCouponInfo['is_limit_total'],
            ];
            // 查询是否领取过
            $tiu = \app\common\model\TourIssueUser::where('uid',$uid)
            ->where('tid',$tid)
            ->where('issue_coupon_id',$id)
            ->find();

            if(!$tiu){
                // 保存领取记录
                $issueId = Db::name('TourIssueUser')->strict(false)->insertGetId($saveData);

                // 生成数据加密串 2022-07-29 改为不可逆加密 md5
                $saltData  = array_merge(['id'=>$issueId],$saveData);

                // 领取记录加密串 = 领取部分数据记录 + 用户盐值
                $enstr_salt = md5(json_encode($saltData,JSON_UNESCAPED_UNICODE).$user['users']['salt']);
                Db::name('TourIssueUser')
                ->where('id',$issueId)
                ->update(['enstr_salt'=>$enstr_salt,'update_time'=>time()]);

                // 2022-08-23 将领取时间 消费券面额 冗余到游客表 只冗余旅行券的
                if($issueCouponInfo['cid'] == 3){
                    Db::name('Tourist')
                    ->where('tid',$tid)
                    ->where('uid',$uid)
                    ->data(['tour_receive_time'=>time(),'tour_price'=>$issueCouponInfo['coupon_price']])
                    ->update();
                }

                // 消费券剩余领取数量 - 1  total_count > 0证明限制总量
                Db::name('CouponIssue')
                ->where('id',$id)
                ->whereColumn('provide_count','<=','total_count')
                ->where('remain_count','>=',0)
                ->dec('remain_count')
                ->update();
            }else{
                // 已经领取过的直接修改更新时间即可。
                Db::name('TourIssueUser')
                ->where('id',$tiu['id'])
                ->update(['update_time'=>time()]);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
    }

    // 结束旅行团界面
    public function overpage($id)
    {
        if(!$id){
            $this->error('参数异常');
        }
        View::assign(['id' => $id]);
        return View::fetch('tour/overpage');
    }

    // 结束旅行团界面--修改发票信息
    public function overpage_modif($id)
    {
        if(!$id){
            $this->error('参数异常');
        }
        $tour = \app\common\model\Tour::where('mid',session('travel')['id'])->find($id);
        $tour->photos = $tour->photos ? explode(',',$tour->photos) : [];
        $tour->invoice = $tour->invoice ? explode(',',$tour->invoice) : [];
        $tour->dining = $tour->dining ? explode(',',$tour->dining) : [];
        $tour->travelling_expenses = $tour->travelling_expenses ? explode(',',$tour->travelling_expenses) : [];
        View::assign('row',$tour);
        View::assign(['id' => $id]);

        return View::fetch('tour/overpage_modif');
    }

    // 结束旅行团
    public function over_modif()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            if (!$data['id']) $this->error('数据异常请刷新页面重试');


            $tid = $data['id'];

            if(!isset($data['photos'])){
                $this->error('请上传旅行团合影');
            }

            if(!isset($data['invoice'])){
                $this->error('请上传住宿发票');
            }

            if(!isset($data['dining'])){
                $this->error('请上传就餐发票');
            }

            $tour = \app\common\model\Tour::where('mid',session('travel')['id'])->find($tid);
            if(!$tour){
                $this->error('旅行团不存在');
            }
            if( $tour['modif_numbers'] >= 3 ){
                $this->error('没有修改机会了');
            }

            $photos  = implode(',',$data['photos']);
            $invoice = implode(',',$data['invoice']);
            
            $travelling_expenses = '';

            if(isset($data['travelling_expenses']))
                $travelling_expenses = implode(',',$data['travelling_expenses']);
            
            $dining = implode(',',$data['dining']);


            Db::name('tour')
            ->where('id',$tid)
            ->inc('modif_numbers',1)
            ->update(['update_time'=>time(),'invoice'=>$invoice,'photos'=>$photos,'travelling_expenses'=>$travelling_expenses,'dining'=>$dining]);

            $this->success('操作成功');
        }

        $this->error('非法请求，禁止访问');
    }

    // 结束旅行团
    public function over()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            if (!$data['id']) $this->error('数据异常请刷新页面重试');

            $tid = $data['id'];

            // 2023-08-23 当当前时间大于2023-09-01 00:00:00时，禁止结束旅行团
            // 2023-11-08 放开
            /*if(time() >= 1693497600){
                $this->error('禁止结束旅行团');
            }*/

            if(!isset($data['photos'])){
                $this->error('请上传旅行团合影');
            }

            if(!isset($data['invoice'])){
                $this->error('请上传住宿发票');
            }

            if(!isset($data['dining'])){
                $this->error('请上传就餐发票');
            }

            /*if(!isset($data['travelling_expenses'])){
                $this->error('请上传交通发票');
            }*/

            $photos  = implode(',',$data['photos']);
            $invoice = implode(',',$data['invoice']);
            
            $travelling_expenses = '';

            if(isset($data['travelling_expenses']))
                $travelling_expenses = implode(',',$data['travelling_expenses']);
            
            $dining = implode(',',$data['dining']);

            // 查询团信息
            $tour = \app\common\model\Tour::find($tid);
            if($tour['tour']==5){
                $this->error('已经结束无需继续操作');
            }

            // 统计团下所有游客
            $tourist = \app\common\model\Tourist::where('tid',$tid)->select();
            $total   = count($tourist);
            if(!$tourist){
                $this->error('该旅行团没有游客无法结束'); // 领取记录不存在
            }
            
            // 检查团下所有游客是否已经完成打卡
            $tour_issue_user = \app\common\model\TourWriteOff::where('tid',$tid)
            ->where('is_clock',1)
            ->where('type',2)
            ->select();

            // 打卡次数 = 游客数量 * 景区券数量
            $numbers = $tour['spot_ids']!='' ? count(explode(',', $tour['spot_ids'])) : 0;
            $total   = $total * $numbers;

            // 2023-03-01 如果开启打卡 需要校验是否全部都打卡
            $system       = \app\common\model\System::find(1);
            if($system->is_clock_switch == 1){
                $len = count($tour_issue_user);
                if($total != $len || $len <=0)
                    $this->error('检测到还有游客未打卡，暂时无法结束该团');
            }

            // 查询是否有酒店打卡记录
            $tour_hotel_sign = \app\common\model\TourHotelSign::where('tid',$tid)
            ->select();
            if($tour_hotel_sign){
                $tour_hotel_sign = $tour_hotel_sign->toArray();
                foreach ($tour_hotel_sign as $key => $value) {
                    if($value['need_numbers'] != $value['tourist_numbers']){
                        $this->error('检测到'.$value['hotel_name'].'中还有游客暂未打卡，暂时无法结束该团');
                    }
                } 
            }
            
            // 检测是否已经全部核销
            $gInfo = \app\common\model\TourCouponGroup::where('tid',$tid)->where('status',1)->count();
            if($gInfo != $numbers){
                $this->error('检测到还有景区券未核销，暂时无法结束该团');
            }

            // 查询消费券
            $iInfo = \app\common\model\CouponIssue::find($tour['travel_id']);
            // 查询团体
            $gInfo = \app\common\model\TourCouponGroup::where('tid',$tid)->where('coupon_issue_id',$tour['travel_id'])->find();

            // 查询领取旅行消费券的游客
            $tour_issue_user_type_2 = \app\common\model\TourIssueUser::where('tid',$tid)
            ->where('type',1)
            ->select();
            if(count($tour_issue_user_type_2) == 0){
                $this->error('检测到游客未领取旅行券，无法结束该团');
            }

            $tour_issue_user_ids = [];
            // 批量格式化数据
            foreach ($tour_issue_user_type_2 as $key => $value) {
                // 记录核销操作
                $writeoff_tour[$key]['orderid']                = 0;
                $writeoff_tour[$key]['create_time']            = time();
                $writeoff_tour[$key]['tour_issue_user_id']     = $value['id'];  // 游客领取ID
                $writeoff_tour[$key]['tour_coupon_group_id']   = $gInfo['id'];  // 团体券ID
                $writeoff_tour[$key]['tid']                    = $value['tid'];
                $writeoff_tour[$key]['mid']                    = session()['travel']['id'];
                $writeoff_tour[$key]['type']                   = 1;
                $writeoff_tour[$key]['uuno']                   = $iInfo->uuno;
                $writeoff_tour[$key]['coupon_issue_id']        = $iInfo->id;
                $writeoff_tour[$key]['coupon_title']           = $iInfo->coupon_title;
                $writeoff_tour[$key]['coupon_price']           = $iInfo->coupon_price;
                $writeoff_tour[$key]['use_min_price']          = $iInfo->use_min_price;
                $writeoff_tour[$key]['time_start']             = $iInfo->time_start ? $iInfo->time_start : 0;
                $writeoff_tour[$key]['time_end']               = $iInfo->time_end ? $iInfo->time_end : 0;
                $writeoff_tour[$key]['userid']                 = session()['travel']['id'];
                $data = $writeoff_tour;
                // 核销加密串 = 领取记录加密串 + 核销记录md5串 + 核销用户盐值
                $writeoff_tour[$key]['enstr_salt']             = md5($value['enstr_salt'].json_encode($data,JSON_UNESCAPED_UNICODE).session()['travel']['id']);

                array_push($tour_issue_user_ids,$value['id']);
            }

            Db::startTrans();
            if($tour['travel_id']){
                // 核销旅行券=》生成游客核销记录
                try {
                    // 2023-05-22 团体券下得游客领取记录状态修改已使用
                    Db::name('tour_issue_user')
                    ->whereIn('id',$tour_issue_user_ids)
                    ->update(['status'=>1,'time_use'=>time(),'is_fail'=>0]);
                    // 
                    Db::name('tour_write_off')->replace()->insertAll($writeoff_tour);
                    // 修改团状态
                    Db::name('tour')
                    ->where('id',$tid)
                    ->update(['status'=>5,'over_time'=>time(),'invoice'=>$invoice,'photos'=>$photos,'travelling_expenses'=>$travelling_expenses,'dining'=>$dining]);
                    // 2022-08-23 将核销时间 冗余到游客表 只冗余旅行券的
                    \app\common\model\Tourist::where('tid',$tid)->data(['tour_writeoff_time'=>time()])->update();
                    // 2023-03-16 旅行团结束之后 将团体券状态也改成已核销
                    \app\common\model\TourCouponGroup::where('id',$gInfo['id'])
                    ->update(['status'=>1,'write_use'=>time(),'update_time'=>time()]);
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $this->error('核销失败',$e->getMessage());
                }
                $this->success('操作成功','data success');
            }
        }

        $this->error('非法请求，禁止访问');
    }

    // 打卡列表 = 核销列表
    public function clock()
    {
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();

            $orderByColumn = $param['orderByColumn'] ?? 'id';
            $isAsc         = $param['isAsc'] ?? 'desc';
            $where = [];
            // 旅行社商户ID
            $mid = session()['travel']['id'];
            $where[] = ['mid','=',$mid];
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

    // 查看详情
    public function see($id)
    {
        $orderByColumn = $param['orderByColumn'] ?? 'id';
        $isAsc         = $param['isAsc'] ?? 'desc';

        $map = [];

        $map[] = ['id','=',$id];
        $model  = '\app\common\model\\' . 'TourWriteOff';
        $detail = $model::where($map)->with(['users','seller','tour','user','tourIssueUser'])->find();

        //print_r($detail->toArray());die;
        View::assign(['detail' => $detail]);
        return View::fetch();
    }

    // 2023-07-14 一键认证
    public function authentication()
    {
        if(Request::isPost()){
            $param = Request::param();
            if(!$param['uid']) $this->error('游客ID不正确: UID '.$param['uid']);

            $tourist   = \app\common\model\Tourist::where('id',$param['uid'])->find();

            if(!$tourist){
                $this->error('当前信息获取异常:'.$param['uid']);
            }

            $tips = '游客：'.$tourist['name']. ' 手机号：'.$tourist['mobile'].'证件号：'.$tourist['idcard'].'Tips：';
            switch ($tourist['card_type']) {
                case 1:
                    if(!isCreditNo($tourist['idcard']))
                        $this->error($tips.'身份证号码错误');
                    break;
                case 2:
                    if(!passportVerify($tourist['idcard']))
                        $this->error($tips.'护照错误');
                    break;
                case 3:
                    if(!taibaoVerify($tourist['idcard']))
                        $this->error($tips.'台湾通行证错误');
                    break;
                case 4:
                    if(!gapassportVerify($tourist['idcard']))
                        $this->error($tips.'港澳通行证错误');
                    break;
                case 5:
                    if(!_checkReturnHome($tourist['idcard']))
                        $this->error($tips.'回乡证错误');
                    break;
                default:
                    // code...
                    break;
            }

            // 检查当前手机号是否存在与用户表内  存在则将用户ID冗余过来 否则创建用户在将用户ID冗余过来
            $modelUsers = '\app\common\model\\' . 'Users';
            $info  = $modelUsers::where('mobile',$tourist['mobile'])->where('idcard',$tourist['idcard'])->find();
            if($info){
                $upDatas = [];
                // 更新游客表
                $upDatas['uid'] = $info->id; 
                $upDatas['update_time'] = time(); 
                $upDatas['is_authenticated'] = 1; // 一键认证过
                Db::name('tourist')->where('id',$param['uid'])->data($upDatas)->update();
                $this->success($tips.'通过,数据匹配正确');
            }else{
                // 校验手机号是否存在
                $mobileInfo = Db::name('users')->where('mobile', $tourist['mobile'])->find();
                if ($mobileInfo) {
                    $this->error($tips.'当前手机号已经绑定其他身份证号'.$mobileInfo['idcard']);
                }
                // 校验身份证号是否存在
                $idcard = Db::name('users')
                    ->where('idcard', $tourist['idcard'])
                    //->where('idcard','<>','')
                    ->whereNotNull('idcard')
                    ->find();
                if ($idcard) {
                    $this->error($tips.'当前身份证号已经绑定其他手机号：'.$idcard['mobile']);
                }
                // 2要素认证
                $system = \app\common\model\System::find(1);
                if($system['app_code']=='') $this->error($tips.'请配置认证代码');
                
                $inData['salt']         = set_salt(6); // 永久加密盐  用于手机号  身份证号加密
                $inData['create_time']  = time();
                $inData['mobile']       = trim($tourist['mobile']);
                $inData['name']         = $tourist['name'];
                $inData['idcard']       = trim($tourist['idcard']);
                $inData['mobile_validated'] = 1;
                $inData['card_type']    = $tourist['card_type'];
                $inData['nickname']     = '';
                $inData['headimgurl']   = '';
                $inData['sex']          = 0;
                $inData['create_ip']    = request()->ip();
                $inData['uuid']         = gen_uuid();
                $inData['last_login_ip']= request()->ip();
                $inData['last_login_time']= time();
                $inData['email_validated'] = 0;
                $inData['auth_status'] = 0;
        
                // 2023-07-14 是身份证号，并且为未认证过，或者未绑定uid
                if($inData['card_type']==1) {
                    $host = "https://dfidveri.market.alicloudapi.com";
                    $path = "/verify_id_name";
                    $method = "POST";
                    $appcode = $system['app_code'];//"1fb45072d6ea46d4b6f1db63bdb6b78b";
                    $headers = array();
                    array_push($headers, "Authorization:APPCODE " . $appcode);
                    //根据API的要求，定义相对应的Content-Type
                    array_push($headers, "Content-Type".":"."application/x-www-form-urlencoded; charset=UTF-8");
                    $bodys = "id_number=".$tourist['idcard']."&name=".$tourist['name'];
                    $url = $host . $path;
        
                    $curl = curl_init();
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
                    curl_setopt($curl, CURLOPT_URL, $url);
                    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($curl, CURLOPT_FAILONERROR, false);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_HEADER, false);
                    if (1 == strpos("$".$host, "https://"))
                    {
                        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                    }
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
                    $res = curl_exec($curl);
                    curl_close($curl);
                    $jsonData = json_decode($res,true);
        
                    if ($jsonData['status'] == 'OK' && $jsonData['state']==1) {
                        // 处理 OK 状态的逻辑
        
                        # 获取周岁 | 
                        $inData['age'] = IdentityCard::age($inData['idcard']);
                        # 获取生日
                        $inData['birthday'] = IdentityCard::birthday($inData['idcard']);
                        # 获取性别 | {男为M | 女为F}
                        $sex = IdentityCard::sex($inData['idcard']);
                        $inData['sex'] = $sex=='M' ? 1 : 2;
                        # 获取生肖
                        $inData['zodiac'] = IdentityCard::constellation($inData['idcard']);
                        # 获取星座
                        $inData['starsign'] = IdentityCard::star($inData['idcard']);

                        $get_area_code_info = get_area_code_info($inData['idcard']);

                        $inData['province'] = isset($get_area_code_info['province']) ? $get_area_code_info['province'] : '';
                        $inData['city']     = isset($get_area_code_info['city']) ? $get_area_code_info['city'] : '';
                        $inData['district'] = isset($get_area_code_info['district']) ? $get_area_code_info['district'] : '';

                        // 重写认证状态
                        $inData['email_validated'] = 1;
                        $inData['auth_status'] = 1;

                        $uid = $modelUsers::insertGetId($inData);
                        $upDatas = [];
                        // 更新游客表
                        $upDatas['uid'] = $uid; 
                        $upDatas['update_time'] = time(); 
                        $upDatas['is_authenticated'] = 1; // 一键认证过
                        Db::name('tourist')->where('id',$param['uid'])->data($upDatas)->update();
                        $this->success($tips.'认证成功');
                    } elseif ($jsonData['status'] == 'OK' && $jsonData['state']==2) {
                        // 认证不通过
                        $this->error($tips.'姓名和身份证号不匹配');
                    } elseif ($jsonData['status'] == 'RATE_LIMIT') {
                        $this->error($tips.'同一名字30分钟内只能认证10次');
                    } elseif ($jsonData['status'] == 'INVALID_ARGUMENT') {
                        $this->error($tips.'认证失败,请求参数错误');
                    } else {
                        $this->error($tips.'秘钥信息错误/套餐余额用完/服务器内部错误');
                    }
                }
                $uid = $modelUsers::insertGetId($inData);
                $upDatas = [];
                // 更新游客表
                $upDatas['uid'] = $uid; 
                $upDatas['update_time'] = time(); 
                $upDatas['is_authenticated'] = 1; // 一键认证过
                Db::name('tourist')->where('id',$param['uid'])->data($upDatas)->update();
                $this->success($tips.'其他证件类型通过');
            }
        }
        $this->error('请求方式错误');
    }

    // 2022-08-31 建团时根据团类型选择上传对应的保单信息并生成用户信息
    public function ht_type($tid)
    {
        if(Request::isPost()){
            $param = Request::param();
            
            // 散拼
            if($param['ids']){
                // 获取游客表
                $idsArr    = explode(',',$param['ids']);
                $tourist   = \app\common\model\Tourist::where('id','in',$idsArr)->select();
            }else{
                // 整团
                $tourist   = \app\common\model\Tourist::where('tid','=',$param['tid'])->select();
            }
            foreach ($tourist as $key => $value) {
                $idcard_trim = trim($value['idcard']);
                switch ($value['card_type']) {
                    case 1:
                        if(!isCreditNo($idcard_trim))
                            $this->error('当前游客身份证号码错误：'.$value['idcard'].',手机号码：'.$value['mobile']);
                        /*$tempCard = substr($idcard_trim, 0,4);
                        if(in_array($tempCard,['6127','6108']))
                            $this->error('仅限来榆游客');*/
                        break;
                    case 2:
                        if(!passportVerify($idcard_trim))
                            $this->error('请输入正确的护照');
                        break;
                    case 3:
                        if(!taibaoVerify($idcard_trim))
                            $this->error('请输入正确的台湾通行证');
                        break;
                    case 4:
                        if(!gapassportVerify($idcard_trim))
                            $this->error('请输入正确的港澳通行证');
                        break;
                    case 5:
                        if(!_checkReturnHome($idcard_trim))
                            $this->error('请输入正确的回乡证');
                        break;
                    default:
                        // code...
                        break;
                }

                /*if(!isCreditNo($idcard_trim)){ 
                    $this->error('请输入正确的身份证号码: '.$value['idcard']);
                }*/
            }
            Db::startTrans();
            try {
                 $upDatas = [];
                foreach ($tourist as $key => $value) {
                    // 校验用户是否存在
                    $uid = $this->betch_getuid($value['mobile'],$value);
                    if (!filter_var($uid, FILTER_VALIDATE_INT) !== false) {
                        $this->error($uid);
                    }
                    $upDatas[$key]['id'] = $value['id'];
                    $upDatas[$key]['uid'] = $uid; 
                    $upDatas[$key]['contract'] = $param['contract']; 
                    $upDatas[$key]['insurance'] = $param['insurance']; 
                    $upDatas[$key]['update_time'] = time(); 
                    $upDatas[$key]['is_authenticated'] = 1; // 改为认证过
                }
                //\app\common\model\Tourist::where('id',$value['id'])->data(['uid'=>$uid,'contract'=>$param['contract'],'insurance'=>$param['insurance'],'update_time'=>time()])->update();
                // 更新uid 合同保单
                $touristModel = new \app\common\model\Tourist;
                $touristModel->saveAll($upDatas); // 必须包含主键

                // 提交事务
                Db::commit();
                $result = 1;
                
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $result = $uid;
            }
            
            if($result==1){
            //return json_encode($result);
                $this->success('操作成功','travel/tour/index');
            }
            $this->error($result);
        }
        return View::fetch();
    }
    
    // 根据手机号获取uid
    private function betch_getuid($mobile,$data)
    {
        if($data['card_type']==1){
            // 校验数据正确性
            if(!isCreditNo($data['idcard'])){ 
                return '请输入正确的身份证号码'.$data['idcard'];
            }
        }
        // 检查当前手机号是否存在与用户表内  存在则将用户ID冗余过来 否则创建用户在将用户ID冗余过来
        $modelUsers = '\app\common\model\\' . 'Users';
        $info  = $modelUsers::where('mobile',$mobile)->where('idcard',$data['idcard'])->find(); // 重新调整

        if($info){
            return $info->id;
        }else{
            // 校验手机号是否存在
            $mobileInfo = Db::name('users')->where('mobile', $mobile)->find();
            if ($mobileInfo) {
                return '当前手机号已经绑定其他身份证号：'.$mobileInfo['idcard'];
            }
            // 校验身份证号是否存在
            $idcard = Db::name('users')
                ->where('idcard', $data['idcard'])
                //->where('idcard','<>','')
                ->whereNotNull('idcard')
                ->find();
            if ($idcard) {
                return '当前身份证号已经绑定其他手机号：'.$idcard['mobile'];
            }
            // 2要素认证
            $system = \app\common\model\System::find(1);
            if($system['app_code']==''){
                return '请配置认证代码';
            }
            
            $inData['salt']         = set_salt(6); // 永久加密盐  用于手机号  身份证号加密
            $inData['create_time']  = time();
            $inData['mobile']       = $mobile;
            $inData['name']         = $data['name'];
            $inData['idcard']       = trim($data['idcard']);
            $inData['mobile_validated'] = 1;
            $inData['card_type']    = $data['card_type'];
            $inData['nickname']     = '';
            $inData['headimgurl']   = '';
            $inData['sex']          = 0;
            $inData['create_ip']    = request()->ip();
            $inData['uuid']         = gen_uuid();
            $inData['last_login_ip']= request()->ip();
            $inData['last_login_time']= time();
            $inData['email_validated'] = 0; // 输入了身份证号表明实名认证
            $inData['auth_status'] = 0;
    
            // 2023-07-14 是身份证号，并且为未认证过，或者未绑定uid
            if($inData['card_type']==1 && ($data['is_authenticated']==0 || $data['uid']==0)) {
                $host = "https://dfidveri.market.alicloudapi.com";
                $path = "/verify_id_name";
                $method = "POST";
                $appcode = $system['app_code'];//"1fb45072d6ea46d4b6f1db63bdb6b78b";
                $headers = array();
                array_push($headers, "Authorization:APPCODE " . $appcode);
                //根据API的要求，定义相对应的Content-Type
                array_push($headers, "Content-Type".":"."application/x-www-form-urlencoded; charset=UTF-8");
                $bodys = "id_number=".$data['idcard']."&name=".$data['name'];
                $url = $host . $path;
    
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_FAILONERROR, false);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HEADER, false);
                if (1 == strpos("$".$host, "https://"))
                {
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                }
                curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
                $res = curl_exec($curl);
                curl_close($curl);
                $jsonData = json_decode($res,true);
    
                if ($jsonData['status'] == 'OK' && $jsonData['state']==1) {
                    // 处理 OK 状态的逻辑
                    // $this->user_auth_log($jsonData,$param);
    
                    if(isset($inData['idcard'])){
                          
                        # 获取周岁 | 
                        $inData['age'] = IdentityCard::age($inData['idcard']);
                        # 获取生日
                        $inData['birthday'] = IdentityCard::birthday($inData['idcard']);
                        # 获取性别 | {男为M | 女为F}
                        $sex = IdentityCard::sex($inData['idcard']);
                        $inData['sex'] = $sex=='M' ? 1 : 2;
                        # 获取生肖
                        $inData['zodiac'] = IdentityCard::constellation($inData['idcard']);
                        # 获取星座
                        $inData['starsign'] = IdentityCard::star($inData['idcard']);

                        $get_area_code_info = get_area_code_info($inData['idcard']);

                        $inData['province'] = isset($get_area_code_info['province']) ? $get_area_code_info['province'] : '';
                        $inData['city']     = isset($get_area_code_info['city']) ? $get_area_code_info['city'] : '';
                        $inData['district'] = isset($get_area_code_info['district']) ? $get_area_code_info['district'] : '';
                    }
                    $inData['email_validated'] = 1; // 输入了身份证号表明实名认证
                    $inData['auth_status'] = 1;
                    $uid = $modelUsers::insertGetId($inData);
                    return $uid;
                } elseif ($jsonData['status'] == 'OK' && $jsonData['state']==2) {
                    // 认证不通过
                    return "姓名和身份证号不匹配(".$data['name']." ".$data['idcard'].")";
                } elseif ($jsonData['status'] == 'RATE_LIMIT') {
                    return "同一名字30分钟内只能认证10次 (".$data['name']." ".$data['idcard'].")";
                } elseif ($jsonData['status'] == 'INVALID_ARGUMENT') {
                     return "认证失败 (".$data['name']." ".$data['idcard'].")";
                } else {
                    return "身份认证无法通过 (".$data['name']." ".$data['idcard'].")";
                }
            }else{
                $uid = $modelUsers::insertGetId($inData);
                return $uid;
            }
        }
    }
    
    // 游客管理
    public function tourguest()
    {
        $mid = session()['travel']['id'];
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();

            $orderByColumn = $param['orderByColumn'] ?? 'id';
            $isAsc         = $param['isAsc'] ?? 'desc';
            $where = [];
            if (isset($param['coupon_title'])  && $param['coupon_title']!='') { 
                $where[] = ['coupon_title','like','%'.$param['coupon_title'].'%'];
            }
            if (isset($param['status']) && $param['status']!='') {
                $where[] = ['status','=',$param['status']];
            }

            // 读取当前商家的核验人员
            $mid = session()['travel']['id'];
            $where[] = ['mid','=',$mid];
            $model  = '\app\common\model\\' . 'TourGuest';
            $list = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
            // 获取游客领取信息
            foreach ($list['data'] as $key => $value) {
                //$list['data'][$key]['userinfo'] = \app\common\model\Users::where('id',$value['couponIssueUser']['uid'])->find();
            }
            return $list;
        }
        return View::fetch();
    }

    // 根据旅行团团号获取团信息
    public function tour_no_info()
    {
        if (Request::isPost()) {
            $param = Request::param();
            $info  = \app\common\model\Tour::where('no',$param['no'])->find();
            if($info)
                $this->success('请求成功','',$info);
            else
                $this->error('未查询到数据');
        }
        $this->error('禁止访问');
    }

    // 勾选游客-转移到旅行团游客表内  2022-09-06 取消该操作
    public function inTourist()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            $data['mid'] = session('travel')['id'];
            $result = $this->validate($data, 'Tourist');
            if (true !== $result) {
                // 验证失败 输出错误信息
                $this->error($result);
            } else {
                // 检查当前手机号是否存在
                $result_phone = \app\common\model\Tourist::field('mobile')
                ->where('mobile', '=', $data['mobile'])
                ->where('tid',$data['tid'])
                ->find();
                if($result_phone){
                    $this->error('手机号已经注册过了');
                }
                $model = '\app\common\model\\' . 'Tourist';
                $result = $model::addPost($data);
                if ($result['error']) {
                    $this->error($result['msg']);
                } else {
                    $this->success($result['msg'], 'index');
                }
            }
        }
        $this->error('禁止访问');
    }

    // 锁定团下游客添加
    public function locking()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            if(!$data['tid']){
                $this->error('参数错误');
            }

            // 从2023-07-31 12点之后禁止锁客
            //$expiry_date  = 1690819199;// strtotime('2023-07-31 23:59:59');
            //$current_date = time();

            $tourInfo = \app\common\model\Tour::where('id',$data['tid'])->find();
            // $current_date > $expiry_date && 
            if ($tourInfo->status==6) {
                $this->error('禁止锁客');
            }

            $model = '\app\common\model\\' . 'Tour';
            // 未上传保单 和 旅游合同 不允许锁定
            // 2023-07-11 未认证的禁止锁定游客
            $tourist = \app\common\model\Tourist::where('tid',$data['tid'])->select();
            if(!$tourist->toArray()) $this->error('请先上传游客信息');
            foreach ($tourist as $key => $value) {
                if($value['contract']=='') $this->error('请上该游客的合同 '.$value['name']);
                if($value['insurance']=='') $this->error('请上该游客的保单 '.$value['name']);

                // 查询认证状态
                $userInfo = \app\common\model\Users::find($value['uid']);
                if(!$userInfo){
                    $this->error('存在未绑定的用户'); 
                }

                if ($userInfo->auth_status !=1 && $userInfo->card_type==1) {
                    if($value['name']==$userInfo->name && $value['mobile']==$userInfo->mobile && $value['idcard']==$userInfo->idcard) {
                        Db::name('users')->where('id',$userInfo->id)->update(['auth_status'=>1,'update_time'=>time()]);
                    }else{
                        $this->error('当前用户未认证: '.$value['name']); 
                    } 
                }
            }
            $len = count($tourist->toArray());
            $grLen  = \app\common\model\Tourist::where('tid',$data['tid'])->group('uid')->select();

            $grLen  = count($grLen->toArray());
            if( $len != $grLen ) $this->error('用户ID重复'); 

            if($len < 10) 
                $this->error('10人起成团');

            if($len > 55) 
                $this->error('单团上限55人申请');
            
            $result = $model::where('id',$data['tid'])->update(['is_locking'=>1,'numbers'=>$len,'update_time'=>time()]);
            if (!$result) {
                $this->error('操作失败');
            } else {
                $this->success('操作成功', 'index');
            }
        }
        $this->error('禁止访问');
    }

    // 查看发票&合影
    public function photos($id)
    {
        $map = [];
        $map[] = ['id','=',$id];
        $map[] = ['mid','=',session('travel')['id']];
        $model  = '\app\common\model\\' . 'Tour';
        $detail = $model::where($map)->find();
        if(!$detail){
            $this->error('未找到数据');
        }
        
        $detail['invoice'] = $detail['invoice'] ? explode(',',$detail['invoice']) : [];
        $detail['dining'] = $detail['dining'] ? explode(',',$detail['dining']) : [];
        $detail['travelling_expenses'] = $detail['travelling_expenses'] ? explode(',',$detail['travelling_expenses']) : [];

        $detail['photos']  = $detail['photos'] ? explode(',',$detail['photos']) : [];
        View::assign(['detail' => $detail]);
        return View::fetch();
    }


    // 查看景区打卡记录
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
        return View::fetch();
    }

    // 查看酒店打卡记录
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

    // 酒店打卡记录详情
    public function overlisthoteldetail(){
        
    }

    // 导出
    public function export()
    {
        $tableNam = 'tour'; $moduleName = 'Tour';
        ob_end_clean();
        // 获取主键
        $pk = \app\common\facade\MakeBuilder::getPrimarykey($tableNam);
        // 获取列表数据
        $columns = \app\common\facade\MakeBuilder::getListColumns($tableNam);
        // 搜索
        //$where         = \app\common\facade\MakeBuilder::getListWhere($tableNam);
        $mid = session()['travel']['id'];
        $where[] = ['mid','=',$mid];
        $orderByColumn = \think\facade\Request::param('orderByColumn') ?? $pk;
        $isAsc         = \think\facade\Request::param('isAsc') ?? 'desc';
        $model         = '\app\common\model\\' . $moduleName;
        // 获取要导出的数据
        $list = $model::getList($where, 0, [$orderByColumn => $isAsc]);
        // 初始化表头数组
        $str         = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z','AA','AB','AC','AD'];
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        foreach ($columns as $k => $v) {
            $sheet->setCellValue($str[$k] . '1', $v['1']);
        }
        $list = isset($list['total']) && isset($list['per_page']) && isset($list['data']) ? $list['data'] : $list;
        foreach ($list as $key => $value) {
            foreach ($columns as $k => $v) {
                // 修正字典数据
                /*if (isset($v[4]) && is_array($v[4]) && !empty($v[4])) {
                    $value[$v['0']] = $v[4][$value[$v['0']]];
                }*/
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

    /************************ 2023-03-08 因旅行社出新需求 所以旅行团整体需逻辑重写 *******************/
    // 添加
    public function add_tour_v2()
    {
        // 景区已经勾选的消费券
        if (Request::param('getList') != '') {
            $where = [];
            $where[] = ['id','in',Request::param('getList')];
            return \app\common\model\CouponIssue::where($where)->select();
        }
        return View::fetch('tour/add_tour_v2');
    }

    // 添加保存
    public function addPostV2()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            $result = $this->validate($data, $this->validate);

            /*if($data['numbers'] < 10) 
                $this->error('10人起成团');

            if($data['numbers'] > 50) 
                $this->error('单团上限50人申请');*/

            // 2022-10-17 酒店校验: 如果团期为一天 则酒店信息非必填
            $hotelArr = $data['hotel_arr'];
            $lenHotel = count($hotelArr);
            if($data['term']){
                $termArr = explode(' - ',$data['term']);
                $data['term_start'] = strtotime($termArr[0]);
                $data['term_end']   = strtotime($termArr[1]);

                if(($data['term_end']-$data['term_start']) > 86400){
                    // 当前团期大于1天 所有酒店信息为必填
                    for ($i=0; $i < $lenHotel; $i++) { 
                        if($hotelArr[$i]==''){
                            $this->error('当前团期大于一天，第 '.($i+1).' 项酒店信息不能为空');
                        }
                    }
                }else{
                    $hotelArr = '';
                }
            }

            if (true !== $result) {
                // 验证失败 输出错误信息
                $this->error($result);
            } else {
                // 事务操作
                Db::startTrans();
                $data['create_time'] = time();
                $data['mid'] = session('travel')['id'];
                $model = '\app\common\model\\' . $this->modelName;

                if($data['term']){
                    $termArr = explode(' - ',$data['term']);
                    $data['term_start'] = strtotime($termArr[0]);
                    $data['term_end']   = strtotime($termArr[1]);

                }
                unset($data['hotel_arr']);
                $data['status'] = 4;
                //$result = $model::addPost($data);
                $tid = $model::insertGetId($data);

                // 生成团体券
                $ids    = $data['travel_id'].','.$data['spot_ids'];
                $idsArr = explode(',',$ids);
                $couponissue = $this->CouponIssue::where('id','in',$idsArr)->field('id as coupon_issue_id,cid')->select()->toArray();

                // 加密数据=不允许修改的数据
                $enArr['id']     = $tid;
                $enArr['name']   = $data['name'];
                $enArr['no']     = $data['no'];
                $enArr['term_start']   = $data['term_start'];
                $enArr['term_end']   = $data['term_end'];
                $enArr['numbers'] = $data['numbers'];
                $enArr['planner'] = $data['planner'];
                $enArr['mobile']  = $data['mobile'];
                $enArr['mid']     = $data['mid'];
                foreach ($couponissue as $key => $value) {
                    $couponissue[$key]['tid']         = $tid;
                    $couponissue[$key]['create_time'] = time();

                    $enstr_salt = md5(json_encode($enArr,JSON_UNESCAPED_UNICODE).$value['coupon_issue_id']);
                    // 加密串=一条团信息
                    $couponissue[$key]['enstr_salt']  = $enstr_salt;
                }
                $res = Db::name('tour_coupon_group')->replace()->insertAll($couponissue);

                if($hotelArr!=''){
                    $add_hotel_array = [];
                    foreach ($hotelArr as $key => $value) {
                        $add_hotel_array[$key]['name'] = $value;
                        $add_hotel_array[$key]['tid']  = $tid;
                    }
                    // 2022-10-17 旅行团新增 添加酒店信息
                    $hotel = Db::name('tour_hotel')->replace()->insertAll($add_hotel_array);
                }

                if ($tid > 0 && $res > 0) {
                    Db::commit();
                    $this->success('添加成功','index');
                }
                Db::rollback();
                $this->error('添加失败','index');
            }
        }
    }

    // 修改
    public function edit_tour_v2(string $id)
    {
        // 查询详情
        $model        = '\app\common\model\\' . $this->modelName;
        $tour  = $model::edit($id)->toArray();
        View::assign(['tour' => $tour]);
        // 审核记录
        $ExamineRecord = \app\common\model\ExamineRecord::where('sid',$id)->where('tags',2)
            ->order('create_time desc')
            ->with(['admin','authGroup'])
            ->select();
        View::assign(['ExamineRecord' => $ExamineRecord]);
        // 酒店信息
        $TourHotel = \app\common\model\TourHotel::where('tid',$id)
            ->select();
        View::assign(['tourhotel' => $TourHotel]);
        return View::fetch();
    }
    // 修改保存
    public function editPostV2()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            $where['id'] = $data['id'];
            
            //  || $data['spot_ids']==''
            if ($data['travel_id']=='' || $data['line_info']=='') {
                $this->error('必填项不能为空');
            }
            $model = '\app\common\model\\' . $this->modelName;
            $tour  = $model::edit($data['id'])->toArray();
            $idsStr = $tour['travel_id'].','.$tour['spot_ids'];
            $coupon_issue_id = explode(',',$idsStr);

            /*if($data['term']){
                $termArr = explode(' - ',$data['term']);
                $data['term_start'] = strtotime($termArr[0]);
                $data['term_end']   = strtotime($termArr[1]);
            }*/

            // 2022-10-17 酒店校验: 如果团期为一天 则酒店信息非必填
            $hotelArr = $data['hotel_arr'];
            $lenHotel = count($hotelArr);
            if($data['term']){
                $termArr = explode(' - ',$data['term']);
                $data['term_start'] = strtotime($termArr[0]);
                $data['term_end']   = strtotime($termArr[1]);

                if(($data['term_end']-$data['term_start']) > 86400){
                    // 当前团期大于1天 所有酒店信息为必填
                    for ($i=0; $i < $lenHotel; $i++) { 
                        if($hotelArr[$i]==''){
                            $this->error('当前团期大于一天，第 '.($i+1).' 项酒店信息不能为空');
                        }
                    }
                }else{
                    $hotelArr = '';
                }
            }

            //$result = $model::editPost($data);
            // 事务操作
            Db::startTrans();
            $data['update_time'] = time();
            // 每次编辑都变为审核中
            $data['status'] = 1;
            unset($data['hotel_arr']);
            $tid = $model::where('id',$data['id'])->update($data);

            // 删除原来关联的团体信息 
            Db::name('tour_coupon_group')
            ->where('tid',$data['id'])
            ->where('coupon_issue_id','in',$coupon_issue_id)
            ->delete();

            // 生成团体券
            $ids    = $data['travel_id'].','.$data['spot_ids'];
            $idsArr = explode(',',$ids);
            $couponissue = $this->CouponIssue::where('id','in',$idsArr)->field('id as coupon_issue_id,cid')->select()->toArray();
            $tidInfo = $model::find($data['id'])->toArray();

            // 加密数据=不允许修改的数据
            $enArr['id']     = $tidInfo['id'];
            $enArr['name']   = $tidInfo['name'];
            $enArr['no']     = $tidInfo['no'];
            $enArr['term_start']   = $data['term_start'];
            $enArr['term_end']   = $data['term_end'];
            $enArr['numbers'] = $tidInfo['numbers'];
            $enArr['planner'] = $tidInfo['planner'];
            $enArr['mobile']  = $tidInfo['mobile'];
            $enArr['mid']     = $tidInfo['mid'];
            foreach ($couponissue as $key => $value) {
                $couponissue[$key]['tid']         = $tidInfo['id'];
                $couponissue[$key]['create_time'] = time();

                $enstr_salt = md5(json_encode($enArr,JSON_UNESCAPED_UNICODE).$value['coupon_issue_id']);
                // 加密串=一条团信息
                $couponissue[$key]['enstr_salt']  = $enstr_salt;
            }
            $res = Db::name('tour_coupon_group')->replace()->insertAll($couponissue);

            // 2022-10-17 旅行团新增 添加酒店信息
            if($hotelArr!=''){
                // 删除当前团之前关联的酒店信息
                Db::name('tour_hotel')
                ->where('tid',$data['id'])
                ->delete();
                $add_hotel_array = [];
                foreach ($hotelArr as $key => $value) {
                    $add_hotel_array[$key]['name'] = $value;
                    $add_hotel_array[$key]['tid']  = $data['id'];
                }
                $hotel = Db::name('tour_hotel')->replace()->insertAll($add_hotel_array);
            }

            if ($tid > 0 && $res > 0) {
                Db::commit();
                $this->success('修改成功','index');
            }
            Db::rollback();
            $this->error('添加失败','index');
        }
    }

    // 编辑
    public function tour_detail_v2($id)
    {
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

        $last_coupon_group = \app\common\model\TourCouponGroup::field('id,tid,coupon_issue_id')->where('coupon_issue_id','in',$idsArr)->where('tid','<',$tour['id'])
            ->where('is_receive',0)->select();//
        $last_coupon_group_sign = $last_coupon_group->toArray();


        $last_coupon_group_sign_data = [];
        if($last_coupon_group_sign){
            // 所有团
            $last_coupon_group_tids = array_column($last_coupon_group_sign,'tid');
            $tour_numbers = $model::field('id,numbers')->where('id','in',$last_coupon_group_tids)->select();
            $tour_numbers_arr = $tour_numbers->toArray();
            foreach ($last_coupon_group_sign as $key => $value) {
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
            if($value['cid']==3){
                $tour_cid_3 = $value;
                $tour_cid_3['aaanumbers'] = $couponissue[$key]['aaanumbers'];
                unset($couponissue[$key]);
            }
        }
        // 2022-08-25 将旅行券放在第一位
        array_unshift($couponissue,$tour_cid_3);
        View::assign(['couponissue' => $couponissue]);

        // 2023-03-08 旅行社详情 查看导游
        $detail_guide_v2 = \app\common\model\Guide::field('')
            ->where('tid',$tour['id'])
            ->where('mid',session()['travel']['id'])
            ->select()
            ->toArray();
        View::assign(['detail_guide_v2' => $detail_guide_v2]);
        // 2023-03-08 旅行社详情 查看游客
        $detail_tourist_v2 = \app\common\model\Tourist::field('')
            ->where('tid',$tour['id'])
            ->where('mid',session()['travel']['id'])
            ->select()
            ->toArray();
        View::assign(['detail_tourist_v2' => $detail_tourist_v2]);
        return View::fetch();
    }

    // 导出酒店打卡记录
    public function export_hotel()
    {
        $dataParam = Request::except(['file'], 'get');
        $tableNam = 'tour_hotel_user_record'; $moduleName = 'TourHotelUserRecord';
        ob_end_clean();
        // 获取主键
        $pk = \app\common\facade\MakeBuilder::getPrimarykey($tableNam);
        // 获取列表数据
        $columns = \app\common\facade\MakeBuilder::getListColumns($tableNam);
        // 搜索
        //$where         = \app\common\facade\MakeBuilder::getListWhere($tableNam);
        $where[] = ['tid','=',$dataParam['tid']];
        $orderByColumn = \think\facade\Request::param('orderByColumn') ?? $pk;
        $isAsc         = \think\facade\Request::param('isAsc') ?? 'desc';
        $model         = '\app\common\model\\' . $moduleName;
        // 获取要导出的数据
        $list = $model::getList($where, 0, [$orderByColumn => $isAsc]);
        // 初始化表头数组
        $str         = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z','AA','AB','AC','AD'];
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        foreach ($columns as $k => $v) {
            $sheet->setCellValue($str[$k] . '1', $v['1']);
        }
        $list = isset($list['total']) && isset($list['per_page']) && isset($list['data']) ? $list['data'] : $list;
        foreach ($list as $key => $value) {
            foreach ($columns as $k => $v) {
                // 修正字典数据
                /*if (isset($v[4]) && is_array($v[4]) && !empty($v[4])) {
                    $value[$v['0']] = $v[4][$value[$v['0']]];
                }*/
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
