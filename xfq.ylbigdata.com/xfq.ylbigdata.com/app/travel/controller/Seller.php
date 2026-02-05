<?php
/**
 * 商户分支机构管理控制器
 * @author slomoo <1103398780@qq.com> 2022/09/06
 */
namespace app\travel\controller;

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

    // 旅行社子商户列表
    public function child_node()
    {
        // 获取主键
        $pk = MakeBuilder::getPrimarykey('seller_child_node');
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();
            $orderByColumn = $param['orderByColumn'] ?? $pk;
            $isAsc         = $param['isAsc'] ?? 'desc';
            $where = [];
            if (@$param['nickname']!='') {
                $where[] = ['nickname','=',$param['nickname']];
            }
            if (@$param['class_id']!='') {
                $where[] = ['class_id','=',$param['class_id']];
            }
            if (@$param['area']!='') {
                $where[] = ['area','=',$param['area']];
            }
            if (@$param['status']!='') {
                $where[] = ['status','=',$param['status']];
            }
            $model  = '\app\common\model\\' . 'SellerChildNode';
            $list = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
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
        return View::fetch();
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
        $Seller = \app\common\model\SellerChildNode::where('id',$param['id'])
            ->order('create_time desc')
            ->find()
            ->toArray();
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
        /*$ExamineRecord = \app\common\model\ExamineRecord::where('sid',$param['id'])->where('tags',1)
            ->order('create_time desc')
            ->with(['admin','authGroup'])
            ->select();
        View::assign(['ExamineRecord' => $ExamineRecord]);*/
        return View::fetch('seller/editSeller');
    }

    // 编辑保存
    public function addPost()
    {
        if (Request::isPost()) {
            //$data = Request::except(['file'], 'post');
            $data = MakeBuilder::changeFormData(Request::except(['file'], 'post'), 'seller_child_node');
            $result = $this->validate($data,'SellerChildNode');
            if (true !== $result) {
                $this->error($result);
            }
            $model = '\app\common\model\\' . 'SellerChildNode';
            
            // 2022-08-31 商家注册生成商家编码 规则：商家分类首字母_商家名称首字母4位_唯一编号
            $pinyin = new Pinyin();
            // 分类
            $class_id_name = [2=>'JQ',3=>'LXS',4=>'JY',5=>'YY',6=>'JD',7=>'GYS'];
            $strNickname = $pinyin->abbr($data['nickname']);
            $strNickname = strlen($strNickname) > 4 ? substr($strNickname,0,4) : $strNickname;
            $data['no']  = $class_id_name[3].strtoupper($strNickname.substr(md5(uniqid()),0,8));
            
            // 父级商户ID
            $data['mid'] = Session::get('travel.id');
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
            $where['id'] = $data['id'];
            
            $result = $this->validate($data,'SellerChildNode');
            if (true !== $result) {
                $this->error($result);
            }

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
            \app\common\model\SellerChildNode::update($data, $where);
            $this->success('编辑成功', 'index');
        }
    }

    // 地图获取
    public function map()
    {
        return View::fetch('seller/map');
    }

    // 删除机构
    public function del($id)
    {
        if (Request::isPost()) {
            if (strpos($id, ',') !== false) {
                return $this->selectDel($id);
            }
            $model = '\app\common\model\\' . 'SellerChildNode';
            return $model::del($id);
        }
    }

    // 批量删除
    public function selectDel(string $id){
        if (Request::isPost()) {
            $model = '\app\common\model\\' . 'SellerChildNode';
            return $model::selectDel($id);
        }
    }
}
