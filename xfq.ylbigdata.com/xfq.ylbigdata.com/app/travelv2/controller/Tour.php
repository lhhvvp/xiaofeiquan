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
                $where[] = ['cid','in',[1,2,4]]; // 畅游 剧院 清爽
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
            ->where('id','in',[1,2,4])
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
                $where[] = ['status','in',[1,2,3]];

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
        return View::fetch('tour/add');
    }

    // 添加保存
    public function addPost()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            $result = $this->validate($data, $this->validate);

            if($data['numbers'] < 10) 
                $this->error('10人起成团');

            if($data['numbers'] > 50) 
                $this->error('单团上限50人申请');

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
            $where[] = ['status','in',[4,5]];

            // 旅行社商户ID
            $mid = session()['travel']['id'];
            $where[] = ['mid','=',$mid];
            $model  = '\app\common\model\\' . $this->modelName;
            return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
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
            $tour = \app\common\model\Tour::find($id);
            View::assign(['tour' => $tour]);
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

            $where = MakeBuilder::getListWhere('Guide');
            $model  = '\app\common\model\\' . 'Guide';
            // 旅行社商户ID
            $mid = session()['travel']['id'];
            $where[] = ['mid','=',$mid];
            $where[] = ['tid','=',Request::param('tid')];
            return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
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
        // 检查当前手机号是否存在与用户表内  存在则将用户ID冗余过来 否则创建用户在将用户ID冗余过来
        $modelUsers = '\app\common\model\\' . 'Users';
        $info  = $modelUsers::where('mobile',$mobile)->find();
        if($info){
            $uid = $info->id;
        }else{
            $inData['salt']         = set_salt(6); // 永久加密盐  用于手机号  身份证号加密
            $inData['create_time']  = time();
            $inData['mobile']       = $mobile;
            $inData['name']         = $data['name'];
            $inData['idcard']       = $data['idcard']; 
            $inData['mobile_validated'] = 1;
            $inData['openid']       = '';
            $inData['nickname']     = '';
            $inData['headimgurl']   = '';
            $inData['sex']          = 0;
            $inData['create_ip']    = request()->ip();
            $inData['uuid']         = gen_uuid();
            $inData['last_login_ip']= request()->ip();
            $inData['last_login_time']= time();

            if(isset($inData['idcard'])){

                // 校验数据正确性
                if(!isCreditNo($inData['idcard'])){ 
                    $this->error('请输入正确的身份证号码');
                }

                # 获取周岁 | 
                $inData['age'] = IdentityCard::age($inData['idcard']);
                # 获取生日
                $inData['birthday'] = IdentityCard::birthday($inData['idcard']);
                # 获取性别 | {男为M | 女为F}
                $sex = IdentityCard::sex($inData['idcard']);
                $inData['sex'] = $sex=='M' ? 1 : 0;
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


                //身份证号获取地址暂时无法获取 用注册ip代替
                /*$res = get_ip_area(request()->ip());
                $inData['province'] = isset($res['province']) ? $res['province'] : '';
                $inData['city']     = isset($res['city']) ? $res['city'] : '';
                $inData['district'] = isset($res['district']) ? $res['district'] : '';*/

                $inData['email_validated'] = 1; // 输入了身份证号表明实名认证
            }


            $uid = $modelUsers::insertGetId($inData);
        }
        return $uid;
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

            $where = MakeBuilder::getListWhere('Tourist');
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
                $tempCard = substr($data['idcard'], 0,4);
                if(in_array($tempCard,['6127','6108']))
                    $this->error('仅限来榆游客');

                // 检查当前手机号是否存在
                $result_phone = \app\common\model\Tourist::field('mobile')
                ->where('mobile', '=', $data['mobile'])
                ->where('tid',$data['tid'])
                ->find();
                if($result_phone){
                    $this->error('手机号已经注册过了');
                }
                // 校验身份证号
                
                $model = '\app\common\model\\' . 'Tourist';
                $result = $model::addPost($data);
                if ($result['error']) {
                    $this->error($result['msg']);
                } else {
                    $this->success($result['msg'], 'index');
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
                $tempCard = substr($data['idcard'], 0,4);
                if(in_array($tempCard,['6127','6108']))
                    $this->error('仅限来榆游客');
                // 添加游客信息
                $data['uid'] = $this->getuid($data['mobile'],$data);
                $model = '\app\common\model\\' . 'Tourist';
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
            foreach ($excel_array as $key => $value) {
                // 正则去除多余空白字符
                $data[$key]['name']   = preg_replace('/\s+/', '', $value['0']);
                $data[$key]['mobile'] = preg_replace('/\s+/', '', $value['1']);
                if(!check_phone($data[$key]['mobile']))
                    $this->result('',1,'当前游客手机号码错误: '.$data[$key]['name']);
                $data[$key]['idcard'] = preg_replace('/\s+/', '', $value['2']);
                if(!isCreditNo($data[$key]['idcard']))
                    $this->result('',1,'当前游客身份证号码错误: '.$data[$key]['name']);
                // 校验身份证号前4位 禁止 6127 6108的游客加入
                $tempCard = substr($value['2'], 0,4);
                if(in_array($tempCard,['6127','6108']))
                    $this->result('',1,'仅限来榆游客: '.$data[$key]['name']);
                $data[$key]['tid']         = $tid;
                $data[$key]['sort']        = $key;
                $data[$key]['create_time'] = time();
                $data[$key]['update_time'] = time();
                $data[$key]['mid']    = session()['travel']['id'];
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
            $tour_info = \app\common\model\Tour::where('id',$tid)->find();
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
                $gData['update_time'] = time();
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

        $uid = $user['uid'];

        // 事务操作
        Db::startTrans();
        try {
            // 领取存储的数据
            $saveData = [
                'uid' => $uid, 
                'tid' => $tid, 
                'issue_coupon_id' => $id, 
                'issue_coupon_class_id' => $issueCouponInfo['cid'], 
                'create_time'   => time(),
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
                if ($issueCouponInfo['total_count'] > 0) {
                    $issueCouponInfo['remain_count'] -= 1;

                    $issueCouponData['remain_count'] = $issueCouponInfo['remain_count'];
                    $issueCouponData['update_time']  = time();
                    Db::name('CouponIssue')->where('id',$id)->update($issueCouponData);
                }
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

    // 结束旅行团
    public function over()
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
            $numbers = count(explode(',', $tour['spot_ids']));
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
            }

            Db::startTrans();
            if($tour['travel_id']){
                // 核销旅行券=》生成游客核销记录
                try {
                    Db::name('tour_write_off')->replace()->insertAll($writeoff_tour);
                    // 修改团状态
                    Db::name('tour')
                    ->where('id',$tid)
                    ->update(['status'=>5,'over_time'=>time(),'invoice'=>$invoice,'photos'=>$photos,'travelling_expenses'=>$travelling_expenses,'dining'=>$dining]);
                    // 2022-08-23 将核销时间 冗余到游客表 只冗余旅行券的
                    \app\common\model\Tourist::where('tid',$tid)->data(['tour_writeoff_time'=>time()])->update();
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
                if(!isCreditNo($value['idcard'])){ 
                    $this->error('请输入正确的身份证号码: '.$value['idcard']);
                }
            }
            Db::startTrans();
            try {
                $upDatas = [];
                foreach ($tourist as $key => $value) {
                    // 校验用户是否存在
                    $uid = $this->getuid($value['mobile'],$value);
                    $upDatas[$key]['id'] = $value['id'];
                    $upDatas[$key]['uid'] = $uid; 
                    $upDatas[$key]['contract'] = $param['contract']; 
                    $upDatas[$key]['insurance'] = $param['insurance']; 
                    $upDatas[$key]['update_time'] = time(); 
                }
                //\app\common\model\Tourist::where('id',$value['id'])->data(['uid'=>$uid,'contract'=>$param['contract'],'insurance'=>$param['insurance'],'update_time'=>time()])->update();
                // 更新uid 合同保单
                $touristModel = new \app\common\model\Tourist;
                $touristModel->saveAll($upDatas); // 必须包含主键

                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
            }
            $this->success('操作成功','travel/tour/index');
        }
        return View::fetch();
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
            $model = '\app\common\model\\' . 'Tour';
            $result = $model::where('id',$data['tid'])->update(['is_locking'=>1,'update_time'=>time()]);
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
        $model  = '\app\common\model\\' . 'Tour';
        $detail = $model::where($map)->find();
        
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
}
