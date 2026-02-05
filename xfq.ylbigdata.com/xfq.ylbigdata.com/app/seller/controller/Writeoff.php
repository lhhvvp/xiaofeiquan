<?php
/**
 * 商户管理控制器
 * @author slomoo <1103398780@qq.com> 2022/07/22
 */
namespace app\seller\controller;

// 引入框架内置类
use app\common\model\Seller;
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
// 引入导出的命名空间
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use think\facade\Validate;
use think\facade\View;
use app\common\model\MerchantVerifier as MerchantVerifierModel;
use app\common\model\MerchantVerificationPoints as MerchantVerificationPointsModel;
use think\Session;

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
            if (isset($param['type'])  && $param['type']!='') {
                $where[] = ['type','=',$param['type']];
            }
            if (isset($param['status']) && $param['status']!='') {
                $where[] = ['status','=',$param['status']];
            }
            if(isset($param['searchKey']) && $param['searchKey']!='' && isset($param['searchValue']) && $param['searchValue']!=''){
                $where[] = [$param['searchKey'],'in',$param['searchValue']];
            }
            // 读取当前商家的核验人员
            $mid = session()['seller']['id'];
            $where[] = ['mid','=',$mid];
            $model  = '\app\common\model\\' . 'MerchantVerifier';

            $list = MerchantVerifierModel::where($where)
                ->append(['status_text','type_text'])
                ->order([$orderByColumn => $isAsc])
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $this->pageSize,
                ]);
            return ['total' => $list->total(), 'current_page' => $list->currentPage(), 'last_page' => $list->lastPage(), 'data' => $list->items()];

        }
        View::assign("type_list",MerchantVerifierModel::getTypeList());
        return View::fetch('writeoff/user');
    }

    // 添加
    public function add()
    {

        View::assign("type_list",MerchantVerifierModel::getTypeList());
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
                $model = '\app\common\model\\' . $this->modelName;
                $data['mid'] = session('seller')['id'];
                if($data['type'] == 'coupon'){
                    $count = $model::where('mid',$data['mid'])->count();
                    if($count >= 6){
                        $this->error('最多可添加6个核销员！');
                    }
                    $data['status'] = 2;
                }else{
                    $data['status'] = 1;
                }
                $data["account"] = trim($data["account"]);
                if(empty($data["account"])){
                    $this->error('请输入登陆账户！');
                }
                if(!\app\handheld\library\Auth::instance()->preg_match_account($data["account"])){
                    $this->error('账号需大小写字母、数字、下划线组成，至少6位，最多32位！');
                }
                if (!\app\handheld\library\Auth::instance()->preg_match_password($data["password"])) {
                    $this->error("密码需字母、数字、特殊字符任意2种组成,至少6位，最多32位！");
                }
                if($model::where("account",$data["account"])->find()){
                    $this->error('账号重复，请更换后再试！');
                }

                //生成密码盐
                $data['salt'] = buildRandom(6,3);
                //获取密码
                $data["password"] = \app\handheld\library\Auth::instance()->getEncryptPassword($data["password"],$data["salt"]);
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
        header('Content-Type: image/jpeg');
        try {
            // 获取当前商户id
            $mid = session()['seller']['id'];
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
                echo $filename;die;
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
        return $res;
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
            $data['status'] = 2;
            //删除账户，账户不允许修改
            unset($data["account"]);
            if(!empty($data["password"])){
                //修改密码
                if (!\app\handheld\library\Auth::instance()->preg_match_password($data["password"])) {
                    $this->error("密码需字母、数字、特殊字符任意2种组成,至少6位，最多32位！");
                }
                //生成密码盐
                $data['salt'] = buildRandom(6,3);
                //获取密码
                $data["password"] = \app\handheld\library\Auth::instance()->getEncryptPassword($data["password"],$data["salt"]);
            }
            \app\common\model\MerchantVerifier::update($data, $where);
            $this->success('修改成功!', 'index');
        }
    }
    //查看详情
    public function seeUser()
    {
        $id = Request::get('id', '');

        if(empty($id)){
            $this->error('参数错误！');
        }
        $vo = \app\common\model\MerchantVerifier::where('id',$id)->find();
        View::assign("detail",$vo);
        return View::fetch('writeoff/seeUser');

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
        $mid = session()['seller']['id'];
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
            $mid = session()['seller']['id'];
            $where[] = ['mid','=',$mid];
            $model  = '\app\common\model\\' . 'WriteOff';
            $list = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);

            // 获取游客领取信息
            foreach ($list['data'] as $key => $value) {
                $list['data'][$key]['userinfo'] = \app\common\model\Users::where('id',$value['couponIssueUser']['uid'])->find();
            }
            return $list;
        }
        return View::fetch('writeoff/log');
    }

    // 团体核销记录
    public function tourlog()
    {
        $mid = session()['seller']['id'];

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
            $mid = session()['seller']['id'];
            $where[] = ['mid','=',$mid];
            $model  = '\app\common\model\\' . 'TourWriteOff';
            $list = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);

            foreach ($list['data'] as $key => $value) {
                $list['data'][$key]['userinfo'] = \app\common\model\Users::where('id',$value['tourIssueUser']['uid'])->find();
            }
            return $list;
        }
        return View::fetch('writeoff/tourlog');
    }

    // 查看详情
    public function toursee($id)
    {
        $orderByColumn = $param['orderByColumn'] ?? 'id';
        $isAsc         = $param['isAsc'] ?? 'desc';

        $map = [];

        $map[] = ['id','=',$id];
        $mid = session()['seller']['id'];
        $map[] = ['mid','=',$mid];
        $model  = '\app\common\model\\' . 'TourWriteOff';
        $detail = $model::where($map)->with(['seller','tour','tourIssueUser'])->find();
        // 获取用户信息
        $detail['user'] = \app\common\model\Users::where('id',$detail['tourIssueUser']['uid'])->find();
        // 获取旅行社信息
        $detail['travel'] = \app\common\model\Seller::field('nickname,username')->where('id',$detail['tour']['mid'])->find();
        View::assign(['detail' => $detail]);
        return View::fetch();
    }

    // 查看详情
    public function see($id)
    {
        $orderByColumn = $param['orderByColumn'] ?? 'id';
        $isAsc         = $param['isAsc'] ?? 'desc';

        $map = [];

        $map[] = ['id','=',$id];
        $mid = session()['seller']['id'];
        $map[] = ['mid','=',$mid];
        $model  = '\app\common\model\\' . 'WriteOff';
        $detail = $model::where($map)->with(['users','seller','couponIssueUser','couponIssue'])->find();
        // 查询领取人信息
        $detail['userinfo'] = \app\common\model\Users::where('id',$detail['couponIssueUser']['uid'])->find();
        // print_r($detail->toArray());die;
        View::assign(['detail' => $detail]);
        return View::fetch();
    }

    // 导出
    public function export()
    {
        $tableNam = 'write_off'; $moduleName = 'WriteOff';
        ob_end_clean();
        // 获取主键
        $pk = \app\common\facade\MakeBuilder::getPrimarykey($tableNam);
        // 获取列表数据
        $columns = \app\common\facade\MakeBuilder::getListColumns($tableNam);
        // 搜索
        $where         = \app\common\facade\MakeBuilder::getListWhere($tableNam);
        $mid = session()['seller']['id'];
        $where[] = ['mid','=',$mid];
        $orderByColumn = \think\facade\Request::param('orderByColumn') ?? $pk;
        $isAsc         = \think\facade\Request::param('isAsc') ?? 'desc';
        $model         = '\app\common\model\\' . $moduleName;
        // 获取要导出的数据
        $list = $model::getList($where, 0, [$orderByColumn => $isAsc]);
        // 初始化表头数组
        $str         = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        // 2023-09-18 增加领取人信息
        $receive = array(
            array(
                'uu_name',
                '领取人',
                'text',
                0,
                array(),
                '',
                'false'
            ),
            array(
                'uu_mobile',
                '领取人手机号',
                'number',
                0,
                array(),
                '',
                'false'
            ),
            array(
                'uu_idcard',
                '领取人身份证号',
                'text',
                0,
                array(),
                '',
                'false'
            )
        );

        $columns = array_merge($columns,$receive);
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
                switch ($v['0']) {
                    case 'uu_name':
                        $sheet->setCellValue($str[$k] . ($key + 2), $value['usersModel']['name']);
                        break;
                    case 'uu_mobile':
                        $sheet->setCellValue($str[$k] . ($key + 2), $value['usersModel']['mobile']);
                        break;
                    case 'uu_idcard':
                        $sheet->setCellValue($str[$k] . ($key + 2), $value['usersModel']['idcard']. "\t");
                        break;
                    default:
                        $sheet->setCellValue($str[$k] . ($key + 2), $value[$v['0']]);
                        break;
                }
            }
        }
        $moduleName = \app\common\model\Module::where('table_name', $tableNam)->value('module_name');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $moduleName . '导出' . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    // 导出
    public function exporttour()
    {
        ini_set("memory_limit","-1");
        ini_set('max_execution_time','300');
        $tableNam = 'tour_write_off'; $moduleName = 'TourWriteOff';
        ob_end_clean();
        // 获取主键
        $pk = \app\common\facade\MakeBuilder::getPrimarykey($tableNam);
        // 获取列表数据
        $columns = \app\common\facade\MakeBuilder::getListColumns($tableNam);
        // 搜索
        //$where         = \app\common\facade\MakeBuilder::getListWhere($tableNam);
        $mid = session()['seller']['id'];
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

    // 查看详情
    public function pointIndex()
    {
        if (Request::isGet()) {
            return View::fetch('writeoff/pointIndex');
        } else {
            $param = Request::param();
            $where[] = ['mid','=',session()['seller']['id']];
            if (isset($param['title']) && $param['title'] != '') {
                $where[] = ['title', 'like', '%' . $param['title'] . '%'];
            }
            if (isset($param['name']) && $param['name'] != '') {
                $where[] = ['name', 'like', '%' . $param['name'] . '%'];
            }
            if (isset($param['mobile']) && $param['mobile'] != '') {
                $where[] = ['mobile', 'like', '%' . $param['mobile'] . '%'];
            }
            if (isset($param['status']) && $param['status'] != '') {
                $where[] = ['status', '=', $param['status']];
            }
            $order = ($param['orderByColumn'] ?? 'id') . ' ' . ($param['isAsc'] ?? 'desc');

            $list = MerchantVerificationPointsModel::where($where)
                ->order($order)
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $this->pageSize,
                ]);

            return ['total' => $list->total(), 'current_page' => $list->currentPage(), 'last_page' => $list->lastPage(), 'data' => $list->items()];
        }
    }

    // 查看详情
    public function pointPost()
    {
        if (Request::isGet()) {
            $id = Request::get('id', '');

            if(!empty($id)){
                $vo = MerchantVerificationPointsModel::where('id',$id)->find();
                View::assign('vo',$vo);
            }
            return View::fetch('writeoff/pointPost');
        } else {
            $data = Request::post();
            $validate = Validate::rule([
                'title'  => 'require',
                'name'  => 'require|chs',
                'mobile'     => ['require','regex'=>'/^1[3456789]\d{9}$/'],
                'latitude'  => 'require',
                'longitude'  => 'require',
                'address'  => 'require'
            ]);
            $validate->message([
                'title.require'  => '请输入核销点名称！',
                'name.require'  => '姓名不能为空！',
                'name.chs'  => '姓名只能是汉字！',
                'mobile.require'      => '手机号不能为空！',
                'mobile.regex'     => '手机号格式不符',
                'latitude.require'  => '当前位置纬度不能为空！',
                'longitude.require'  => '当前位置经度不能为空！',
                'address.require'  => '当前位置详细地址不能为空！'
            ]);
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $seller_info = Seller::where('id',$data['mid'])->find();
            $juli = calculateDistance($data['latitude'],$data['longitude'],$seller_info['latitude'],$seller_info['longitude']);
            if($juli > 2000){
                $this->error('核销点距离景点不能超过2km!');
            }
            if(empty($data['id'])){
                $count = MerchantVerificationPointsModel::where('mid',$data['mid'])->count();
                if($count >= 2){
                    $this->error('最多可添加两个核销点!');
                }
                MerchantVerificationPointsModel::create($data);
            }else{
                $this->error('不允许修改!');
                //MerchantVerificationPointsModel::where('id',$data['id'])->save($data);
            }
            $this->success('操作成功!');
        }
    }
    public function pointState()
    {
        if (Request::isPost()) {
            $id = Request::post('id', '');
            if(empty($id)){
               $this->error('参数错误！');
            }
            $vo = MerchantVerificationPointsModel::where('id',$id)->find();
            $vo->status = $vo->status ? 0 : 1;
            if($vo->save()){
                $this->success('操作成功!');
            }else{
                $this->error('操作失败！');
            }
        }
        $this->error('参数错误！');
    }
    public function pointDel()
    {
        if (Request::isPost()) {
            $id = Request::post('id', '');
            if(empty($id)){
                $this->error('参数错误！');
            }
            $vo = MerchantVerificationPointsModel::where('id',$id)->find();

            if($vo->delete()){
                $this->success('删除成功!');
            }else{
                $this->error('删除失败！');
            }
        }
        $this->error('参数错误！');
    }
    /*
     * 查看核销点
     * */
    public function pointSee()
    {

            $id = Request::get('id', '');

            if(empty($id)){
                $this->error('参数错误！');
            }
            $vo = MerchantVerificationPointsModel::where('id',$id)->find();
            View::assign("detail",$vo);
            return View::fetch('writeoff/pointSee');
    }
}
