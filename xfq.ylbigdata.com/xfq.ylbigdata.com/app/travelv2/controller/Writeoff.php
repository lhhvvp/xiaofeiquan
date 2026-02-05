<?php
/**
 * 商户管理控制器
 * @author slomoo <1103398780@qq.com> 2022/07/22
 */
namespace app\travel\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\Db;
use think\facade\View;

class Writeoff extends Base
{
    // 验证器
    protected $validate = 'MerchantVerifier';

    // 当前主表
    protected $tableName = 'merchant_verifier';

    // 当前主模型
    protected $modelName = 'MerchantVerifier';

    // 列表
    public function user()
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
            if (isset($param['status']) && $param['status']!='') {
                $where[] = ['status','=',$param['status']];
            }

            // 读取当前商家的核验人员
            $mid = session()['travel']['id'];
            $where[] = ['mid','=',$mid];
            $model  = '\app\common\model\\' . 'MerchantVerifier';
            return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
        }
        return View::fetch('writeoff/user');
    }

    // 添加
    public function add()
    {
        return View::fetch('writeoff/addUser');
    }

    // 添加保存
    public function addPost()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            $result = $this->validate($data, $this->validate);
            if (true !== $result) {
                // 验证失败 输出错误信息
                $this->error($result);
            } else {
                $data['mid'] = session('travel')['id'];
                $model = '\app\common\model\\' . $this->modelName;
                $result = $model::addPost($data);
                if ($result['error']) {
                    $this->error($result['msg']);
                } else {
                    $this->success($result['msg'], 'index');
                }
            }
        }
    }

    // 获取微信二维码
    public function bind(string $id){
        try {
            // 获取当前商户id
            $mid = session()['travel']['id'];
            $uid = $id;
            $wxInfo = accesstoken();
            if($wxInfo['code']==0  && $wxInfo['msg']=='ok'){
                $url  = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$wxInfo['data']['access_token'];
                //$data = json_encode(['scene' => "mid=".$mid."&uid=".$uid,'page'=>"subcontracting/getopenid/getopenid"]);
                $data = json_encode(['scene' => "mid/".$mid."*uid/".$uid,'page'=>"pages/getopenid/getopenid"]);
                $filename   = $this->httpRequestPost($url,$data,'MUT_'.$mid.'_'.$uid);
                if(is_array($filename)){
                    echo "获取二维码错误：".$filename['errcode']." errmsg：".$filename['errmsg'];die;
                }
                echo '<body style="display: flex;align-items: center;justify-content: center;"><img src="/'.$filename.'"/></body>';die;
                return View::fetch('writeoff/bind');
            }else{
                $this->error('token获取错误,请重试！');
            }
        } catch ( \Exception $e) {
            return $e->getMessage();
        }
    }
    public function httpRequestPost($url,$data,$filename){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 'image/gif');
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//跳过ssl检测
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); //如果需要将结果直接返回到变量里，那加上这句。
        $res = curl_exec($ch);
        $res = json_decode($res,true);

        $name = "static/wximg/".$filename."_".time().".png";
        curl_close($ch);
        if($res['errcode']==0){
            file_put_contents($name,$res);
            return $name;
        }else{
            return $res;
        }
    }

    // 修改
    public function edit(string $id)
    {
        // 查询详情
        $model        = '\app\common\model\\' . $this->modelName;
        $userinfo  = $model::edit($id)->toArray();
        View::assign(['userinfo' => $userinfo]);
        return View::fetch('writeoff/editUser');
    }
    // 修改保存
    public function editPost()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            $where['id'] = $data['id'];

            $result = $this->validate($data, $this->validate);
            if (true !== $result) {
                $this->error($result);
            }

            \app\common\model\MerchantVerifier::update($data, $where);
            $this->success('修改成功!', 'index');
        }
    }

    // 删除
    public function del(string $id)
    {
        if (Request::isPost()) {
            if (strpos($id, ',') !== false) {
                return $this->selectDel($id);
            }
            $model = '\app\common\model\\' . $this->modelName;
            return $model::del($id);
        }
    }

    // 核销记录
    public function log()
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
            $model  = '\app\common\model\\' . 'TourWriteOff';

            $list = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
            foreach ($list['data'] as $key => $value) {
                $list['data'][$key]['userinfo'] = \app\common\model\Users::where('id',$value['tourIssueUser']['uid'])->find();
            }

            return $list;
        }
        return View::fetch('writeoff/log');
    }

    // 查看详情
    public function see($id)
    {
        $orderByColumn = $param['orderByColumn'] ?? 'id';
        $isAsc         = $param['isAsc'] ?? 'desc';

        $map = [];

        $map[] = ['id','=',$id];
        $model  = '\app\common\model\\' . 'TourWriteOff';
        $detail = $model::where($map)->with(['seller','tour','tourIssueUser'])->find();
        // 获取用户信息
        $detail['user'] = \app\common\model\Users::where('id',$detail['tourIssueUser']['uid'])->find();
        View::assign(['detail' => $detail]);
        return View::fetch();
    }

    // 旅行团散客核销记录
    public function guest()
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
            $model  = '\app\common\model\\' . 'WriteOff';
            $list = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
            // 获取游客领取信息
            foreach ($list['data'] as $key => $value) {
                $list['data'][$key]['userinfo'] = \app\common\model\Users::where('id',$value['couponIssueUser']['uid'])->find();
            }
            return $list;
        }
        return View::fetch('writeoff/guest');
    }

    // 散客核销记录增加 上传合同保单操作按钮
    public function editGuest($id){
        View::assign(['tid' => Request::param('tid')]);
        // 查询详情
        $model        = '\app\common\model\\' . 'Writeoff';
        $writeoff  = $model::edit($id)->toArray();
        View::assign(['writeoff' => $writeoff]);
        return View::fetch();
    }

    // 散客上传合同保单-》生成新的游客表
    public function editguestPost()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            // 根据核销记录ID查询整行核销记录
            if(!$data['id']){
                $this->error('参数异常请重试');
            }
            $modelWriteoff        = '\app\common\model\\' . 'Writeoff';
            $writeoff  = $modelWriteoff::edit($data['id']);

            // 检查当前核销记录是否已经存在于游客表内
            $modelTourGuest        = \app\common\model\TourGuest::find($data['id']);
            if($modelTourGuest){
                $this->error('当前记录已经存在无需继续操作');
            }
            // 合并为新的游客数据
            $gData = $writeoff->toArray();

            // 根据领取ID查询用户uid 并冗余到新游客表
            $couponissueuser = \app\common\model\CouponIssueUser::find($gData['coupon_issue_user_id'])->toArray();
            $gData['uid']    = $couponissueuser['uid'];
            // 表单赋值
            $gData['contract']  = $data['contract'];
            $gData['insurance'] = $data['insurance'];
            $gData['create_time'] = time();
            $gData['is_uploads_cert'] = 1;

            $result = $this->validate($gData, 'TourGuest');
            if (true !== $result) {
                // 验证失败 输出错误信息
                $this->error($result);
            } else {
                // 事务操作
                Db::startTrans();
                // 保存游客记录
                $model = '\app\common\model\\' . 'TourGuest';
                $result = $model::addPost($gData);

                // 修改核销记录表状态 is_uploads_cert 是否上传合同保单 为已经上传
                
                $writeoff->is_uploads_cert = 1;
                $rs = $writeoff->save();
                if($rs && !$result['error']){
                    Db::commit();
                    $this->success('操作成功', 'index');
                }
                Db::rollback();
                $this->error('操作失败','index');
            }
        }
    }
}
