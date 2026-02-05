<?php
/**
 * 商户管理控制器
 * @author slomoo <1103398780@qq.com> 2022/07/22
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
use Overtrue\Pinyin\Pinyin;
class Seller extends Base
{
    // 验证器
    protected $validate = 'Seller';

    // 当前主表
    protected $tableName = 'seller';

    // 当前主模型
    protected $modelName = 'Seller';

    // 添加消费券时调用该列表
    public function list()
    {
        // 获取主键
        $pk = MakeBuilder::getPrimarykey($this->tableName);

        $class_id = Request::param('class_id');
        View::assign(['class_id'=>$class_id]);
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();
            $orderByColumn = $param['orderByColumn'] ?? $pk;
            $isAsc         = $param['isAsc'] ?? 'desc';
            $where = [];
            if (!empty($param['nickname'])) {
                $where[] = ['nickname','like',"%".$param['nickname']."%"];
            }

            $where[] = ['class_id','=',$class_id];

            $where[] = ['status','=',1];
            $model  = '\app\common\model\\' . $this->modelName;
            return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
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
        return View::fetch('seller/list');
    }

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
            if (@$param['nickname']!='') {
                $where[] = ['nickname','like',"%".$param['nickname']."%"];
            }
            if (@$param['class_id']!='') {
                $where[] = ['class_id','=',$param['class_id']];
            }
            if (@$param['area']!='') {
                $where[] = ['area','=',$param['area']];
            }
            if (isset($param['searchKey']) && isset($param['searchValue'])) {
                $where[] = [$param['searchKey'],'=',$param['searchValue']];
            }
            if (@$param['status']!='') {
                $where[] = ['status','=',$param['status']];
            }else{
                $where[] = ['status','<>',4];
            }
            $model  = '\app\common\model\\' . $this->modelName;
            $list = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
            for ($i=0; $i < count($list['data']); $i++) { 
                @$list['data'][$i]['area'] = $this->app->config->get('lang.area')[$list['data'][$i]['area']];
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
        return View::fetch('seller/index');
    }

    // 添加商家
    public function addSeller()
    {
        // 商户分类
        $SellerClass = \app\common\model\SellerClass::field('id, class_name')
            ->order('sort asc')
            ->select()
            ->toArray();
        // 区域分类
        $areaClass = $this->app->config->get('lang.area');
        $view   = [
            'class_list' => $SellerClass,
            'areaClass'  => $areaClass
        ];
        View::assign($view);
        return View::fetch('seller/addSeller');
    }

    // 编辑商家
    public function editSeller()
    {
        $param = Request::param();
        // 查询商家详情
        $Seller = \app\common\model\Seller::where('id',$param['id'])
            ->order('create_time desc')
            ->find()
            ->toArray();
        // 营业资质
        $Seller['business_license_set'] = json_decode($Seller['business_license_set']);
        View::assign(['seller_list' => $Seller]);
        // 商户分类
        $SellerClass = \app\common\model\SellerClass::field('id, class_name')
            ->order('sort asc')
            ->select()
            ->toArray();
        // 区域分类
        $areaClass = $this->app->config->get('lang.area');
        View::assign(['class_list' => $SellerClass,'areaClass'  => $areaClass]);

        // 审核记录
        $ExamineRecord = \app\common\model\ExamineRecord::where('sid',$param['id'])->where('tags',1)
            ->order('create_time desc')
            ->with(['admin','authGroup'])
            ->select();
        View::assign(['ExamineRecord' => $ExamineRecord]);
        return View::fetch('seller/editSeller');
    }

    // 编辑保存
    public function addPost()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            //$data = MakeBuilder::changeFormData(Request::except(['file'], 'post'), $this->tableName);

            if(isset($data['business_license_set']) && $data['business_license_set']!='')
                $business_license_set = json_encode($data['business_license_set']);
            // xss过滤
            $data['name']      = removeXSS(filterText($data['name']));
            $data['email']      = removeXSS(filterText($data['email']));
            $data['nickname']   = removeXSS(filterText($data['nickname']));
            $data['image']      = removeXSS(filterText($data['image']));
            $data['mobile']      = removeXSS(filterText($data['mobile']));
            $data['do_business_time']      = removeXSS(filterText($data['do_business_time']));
            $data['address']      = removeXSS(filterText($data['address']));
            $data['credit_code']      = removeXSS(filterText($data['credit_code']));
            $data['cart_number']      = removeXSS(filterText($data['cart_number']));
            $data['card_name']      = removeXSS(filterText($data['card_name']));
            $data['card_deposit']      = removeXSS(filterText($data['card_deposit']));
            //$data['business_license']      = removeXSS(filterText($data['business_license']));
            $data['business_license_set']      = removeXSS(filterText($business_license_set));
            $data['permit_foroperation']      = removeXSS(filterText($data['permit_foroperation']));
            $data['content'] = SafeFilter($data['content']);
            // 2023-09-04 增加商户盐值
            $data['salt']            = set_salt(10);

            $result = $this->validate($data,'Seller');
            if (true !== $result) {
                $this->error($result);
            }
            $data['email_validated'] = 1;
            $model = '\app\common\model\\' . $this->modelName;

            // 密码校验
            $pwd = checkPassword($data['password']);
            if($pwd['code']!=1){
                $this->error($pwd['msg']);
            }

            // 检查数据是否存在
            $uname = $model::where(['username' => $data['username']])->find();
            if($uname){
                $this->error('账号已存在');
            }
            $email = $model::where(['email' => $data['email']])->find();
            if($email){
                $this->error('邮箱已存在');
            }

            if($data['class_id']==3){
                if($data['permit_foroperation']==''){
                    $this->error('请上传经营许可证');
                }
                if($data['social_liability_insurance']==''){
                    $this->error('请上传社会责任险');
                }
            }

            //判断最大人数是否必填
            if($data['class_id']==6 && $data['max_num']==''){
                $this->error('请填写最大人数');
            }
            
            // 2022-08-31 商家注册生成商家编码 规则：商家分类首字母_商家名称首字母4位_唯一编号
            $pinyin = new Pinyin();
            // 分类
            $class_id_name = [2=>'JQ',3=>'LXS',4=>'JY',5=>'YY', 6=>'JD', 7=>'GYS'];
            $strNickname = $pinyin->abbr($data['nickname']);
            $strNickname = strlen($strNickname) > 4 ? substr($strNickname,0,4) : $strNickname;
            $data['no']  = $class_id_name[$data['class_id']].strtoupper($strNickname.substr(md5(uniqid()),0,8));
            
            $data['password']        = md5($data['password']);
            $result = $model::addPost($data);
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
            $data = Request::except(['file'], 'post');

            $business_license_set = json_encode($data['business_license_set']);
            // xss过滤
            $data['name']      = removeXSS(filterText($data['name']));
            $data['email']      = removeXSS(filterText($data['email']));
            $data['nickname']   = removeXSS(filterText($data['nickname']));
            $data['image']      = removeXSS(filterText($data['image']));
            $data['mobile']      = removeXSS(filterText($data['mobile']));
            $data['do_business_time']      = removeXSS(filterText($data['do_business_time']));
            $data['address']      = removeXSS(filterText($data['address']));
            $data['credit_code']      = removeXSS(filterText($data['credit_code']));
            $data['cart_number']      = removeXSS(filterText($data['cart_number']));
            $data['card_name']      = removeXSS(filterText($data['card_name']));
            $data['card_deposit']      = removeXSS(filterText($data['card_deposit']));
            //$data['business_license']      = removeXSS(filterText($data['business_license']));
            $data['business_license_set']      = removeXSS(filterText($business_license_set));
            $data['permit_foroperation']      = removeXSS(filterText($data['permit_foroperation']));
            $data['content'] = SafeFilter($data['content']);
            
            $where['id'] = $data['id'];

            //判断最大人数是否必填
            if($data['class_id']==6 && $data['max_num']==''){
                $this->error('请填写最大人数');
            }
            
            $result = $this->validate($data,'Seller');
            if (true !== $result) {
                $this->error($result);
            }

            // 2022-11-10 审核页单独摘出去 check
            /*if(isset($data['status']) && in_array($data['status'],[1,2,3])){
                if($data['status']==3 && empty($data['remark'])){
                    $this->error('请填写审核备注');
                }
                $logData['tags']    = 1;
                $logData['sid']     = $data['id'];
                $logData['step']    = $data['status'];
                $logData['remarks']  = $data['remark'];
                $logData['group_id'] = Session::get('admin.group_id');
                $logData['admin_id'] = Session::get('admin.id');
                $logData['create_time']  = time();
                // 记录审核记录
                \app\common\model\ExamineRecord::strict(false)->insertGetId($logData);
            }*/
            \app\common\model\Seller::update($data, $where);
            $this->success('修改成功!', 'index');
        }
    }

    // 地图获取
    public function map()
    {
        return View::fetch('seller/map');
    }

    // 审核页面
    public function check()
    {
        $param = Request::param();
        // 查询商家详情
        $Seller = \app\common\model\Seller::where('id',$param['id'])
            ->order('create_time desc')
            ->find()
            ->toArray();
        // 营业资质
        $Seller['business_license_set'] = json_decode($Seller['business_license_set']);
        // 商户分类
        $SellerClass = \app\common\model\SellerClass::field('id, class_name')
            ->where('id',$Seller['class_id'])
            ->find()
            ->toArray();
        $Seller['class_id'] = $SellerClass['class_name'];
        // 区域分类
        $areaClass = $this->app->config->get('lang.area');
        $Seller['area'] = $areaClass[$Seller['area']];
        View::assign(['seller_list' => $Seller]);
        // 审核记录
        $ExamineRecord = \app\common\model\ExamineRecord::where('sid',$param['id'])->where('tags',1)
            ->order('create_time desc')
            ->with(['admin','authGroup'])
            ->select();
        View::assign(['ExamineRecord' => $ExamineRecord]);
        return View::fetch('seller/check');
    }

    // 审核保存
    public function checkPost()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');

            $where['id'] = $data['id'];

            if($data['status']==3 && empty($data['remark'])){
                $this->error('请填写审核备注');
            }
            $logData['tags']    = 1;
            $logData['sid']     = $data['id'];
            $logData['step']    = $data['status'];
            $logData['remarks']  = $data['remark'];
            $logData['group_id'] = Session::get('admin.group_id');
            $logData['admin_id'] = Session::get('admin.id');
            $logData['create_time']  = time();
            // 记录审核记录
            \app\common\model\ExamineRecord::strict(false)->insertGetId($logData);

            // 修改商户信息
            $data['update_time'] = time();
            \app\common\model\Seller::update($data, $where);
            $this->success('审核成功!', 'index');
        }
    }

    // 查看商户详情
    public function see()
    {
        $param = Request::param();
        // 查询商家详情
        $Seller = \app\common\model\Seller::where('id',$param['id'])
            ->order('create_time desc')
            ->find()
            ->toArray();
        // 营业资质
        $Seller['business_license_set'] = json_decode($Seller['business_license_set']);
        // 商户分类
        $SellerClass = \app\common\model\SellerClass::field('id, class_name')
            ->where('id',$Seller['class_id'])
            ->find()
            ->toArray();
        $Seller['class_id'] = $SellerClass['class_name'];
        // 区域分类
        $areaClass = $this->app->config->get('lang.area');
        $Seller['area'] = $areaClass[$Seller['area']];
        View::assign(['seller_list' => $Seller]);
        // 审核记录
        $ExamineRecord = \app\common\model\ExamineRecord::where('sid',$param['id'])->where('tags',1)
            ->order('create_time desc')
            ->with(['admin','authGroup'])
            ->select();
        View::assign(['ExamineRecord' => $ExamineRecord]);
        return View::fetch('seller/see');
    }
}
