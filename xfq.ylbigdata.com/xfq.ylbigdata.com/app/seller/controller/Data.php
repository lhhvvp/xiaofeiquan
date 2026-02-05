<?php
/**
 * 商户管理-数据核算控制器
 * @author slomoo <1103398780@qq.com> 2022/07/22
 */
namespace app\seller\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\Session;
use think\facade\View;
use think\facade\Config;
class Data extends Base
{
    // 验证器
    protected $validate = 'WriteOff';

    // 当前主表
    protected $tableName = 'write_off';

    // 当前主模型
    protected $modelName = 'WriteOff';


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
            if (!empty($param['coupon_title'])) {
                $where[] = ['coupon_title','=',$param['coupon_title']];
            }

            if (!empty($param['create_time'])) {
                $getDateran = get_dateran($param['create_time']);
                $where[] = ['create_time', 'between', $getDateran];
            }

            $where[] = ['mid','=',session()['seller']['id']];
            $where[] = ['accounting_id','=',0];

            $model  = '\app\common\model\\' . $this->modelName;
            return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
        }
       
        return View::fetch('data/index');
    }

    // 根据核算记录ID 修改附件信息
    public function accounting_data_url()
    {
        if(Request::isPost()){
            $data = Request::except(['file'], 'post');
            // 2022-08-28 待审核时 可撤销审核  从新提交
            if($data['status']==3){
                // 检查是否通过审核  通过审核则不允许撤销
                $accountingInfo = \app\common\model\Accounting::where('id',$data['tour_accounting_id'])->where('mid',session()['seller']['id'])->find();
                if(!$accountingInfo){
                    $this->error('当前记录不存在');
                }
                if($accountingInfo['status']==1){
                    $this->error('撤销失败,当前记录已经通过审核');
                }
                $res = \app\common\model\Accounting::where('id',$data['tour_accounting_id'])
                ->update(['update_time'=>time(),'status'=>0,'tour_status'=>0,'sup_status'=>0,'back_status'=>0]);
                if(!$res){
                    $this->error('撤销失败');
                }
                $this->success('撤销成功请重新提交资料', 'apply');
            }
            if(!$data['tour_accounting_id']){
                $this->error('参数错误请刷新页面重试');
            }
            if(!isset($data['images_'])){
                $this->error('请重新上传资料');
            }
            // 修改附件地址 修改状态未待审核
            // 2022-08-25 二次提交时将所有审核步骤变为待审核
            $res = \app\common\model\Accounting::where('id',$data['tour_accounting_id'])
                ->update(['data_url'=>json_encode($data['images_'],true),'update_time'=>time(),'status'=>3,'tour_status'=>0,'sup_status'=>0,'back_status'=>0]);
            if(!$res){
                $this->error('上传失败,请稍后重试');
            }
            $this->success('上传成功，请耐心等待审核结果', 'apply');
        }
        $this->error('非法请求禁止访问');
    }

    // 核算表单页
    public function accounting_form(){
        ini_set('memory_limit', '1280M');
        ini_set('max_execution_time', '300');
        ini_set("pcre.backtrack_limit", "10000000");
        // 申请操作
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            $data['mid'] = session()['seller']['id'];

            $sInfo = \app\common\model\Seller::find($data['mid']);
            $data['nickname'] = $sInfo['nickname'];
            $data['class_id'] = $sInfo['class_id'];
            $data['area']     = $sInfo['area'];
            $data['create_time'] = time();
            $data['no']       = 'PT'.date("His").strtoupper(set_salt(7));
            $result = $this->validate($data,  'Accounting');
            if (true !== $result) {
                // 验证失败 输出错误信息
                $this->error($result);
            } else {
                // 根据散客领取记录
                $tourInfo = \app\common\model\WriteOff::where('id','in',explode(',',$data['write_off_ids']))->where('mid',session('seller')['id'])->with(['couponIssueUser','couponIssue'])->select();

                // 结算金额
                $coupon_price_total  = 0;
                // 结算数量
                $listData       = $tourInfo->toArray();
                $coupon_number  = count($listData);
                foreach ($listData as $key => $value) {
                    $coupon_price_total += $value['coupon_price'];
                    // 查询散客
                    $listData[$key]['users'] = \app\common\model\Users::where('id',$value['couponIssueUser']['uid'])->find()->toArray();
                }
                // 生成PDF文件
                $pageData['title'] = '散客消费券业务';
                $pageData['addtitle'] = date('Y年m月d日 H:i:s',time());
                $pageData['no']       = $data['no'];
                $pageData['sum_coupon_price'] = $coupon_price_total;
                $pageData['writeoff_total']   = $coupon_number;
                $page_1 = '<style type="text/css">
                .jsxx {font-size: 18px;}
                .xxxx {font-size: 12px;}
                </style>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <table width="325" height="202" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr>
                    <td height="150" align="center" style="font-size: 30px; font-weight:bold;">
                    <p>商户结算申请对账单</p>
                    <p>&nbsp;</p></td>
                  </tr>
                </table>
                <table width="560" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxx">
                  <tr>
                    <td width="130" height="35" align="right">商户名称：</td>
                    <td>'.$data['nickname'].'</td>
                  </tr>
                  <tr>
                    <td height="35" align="right" >结算业务名称：</td>
                    <td>'.$pageData['title'].'</td>
                  </tr>
                  <tr>
                    <td height="35" align="right">申请结算时间：</td>
                    <td>'.$pageData['addtitle'].'</td>
                  </tr>
                  <tr>
                    <td height="35" align="right">结算单号：</td>
                    <td>'.$pageData['no'].'</td>
                  </tr>
                  <tr>
                    <td height="35" align="right">结算金额：</td>
                    <td>'.$pageData['sum_coupon_price'].'元</td>
                  </tr>
                  <tr>
                    <td height="35" align="right">结算核销数量：</td>
                    <td>'.$pageData['writeoff_total'].'</td>
                  </tr>
                </table>
                <table width="325" height="115" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr>
                    <td height="115" align="center" style="font-size: 18px; font-weight:bold;"><p>&nbsp;</p>
                    <p>收款账户</p></td>
                  </tr>
                </table>
                <table width="560" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxx">
                  <tr>
                    <td width="130" height="35" align="right">账户名称：</td>
                    <td>'.$data['card_name'].'</td>
                  </tr>
                  <tr>
                    <td height="35" align="right" >收款账户：</td>
                    <td>'.$data['cart_number'].'</td>
                  </tr>
                  <tr>
                    <td height="35" align="right">开 户 行：</td>
                    <td>'.$data['card_deposit'].'</td>
                  </tr>
                </table>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <table width="308" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxx">
                  <tr>
                    <td width="308" height="30" align="right">公司（盖章）：</td>
                  </tr>
                  <tr>
                    <td width="308" height="30" align="right">法人签字 ：</td>
                  </tr>
                  <tr>
                    <td height="30" align="right">日期 ：</td>
                  </tr>
                </table>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <p>附件</p>
                ';

                $page_2 = '';
                $page_2 .= '<p>结算单号: '.$pageData['no'].'</p>';
                $page_2 .= '
                <table width="800" border="1" align="center" cellpadding="0" cellspacing="0" bordercolor="#999999" class="xxxx">
                  <tr>
                    <td width="40" height="25" align="center">序号</td>
                    <td align="center">消费券名称</td>
                    <td width="80" align="center">领取人</td>
                    <td width="130" align="center">身份证号</td>
                    <td width="70" align="center">手机号</td>
                    <td width="70" align="center">领取时间</td>
                    <td width="70" align="center">消费券面额</td>
                    <td width="70" align="center">核销地点</td>
                    <td width="70" align="center">核销时间</td>
                  </tr>';
                  foreach ($listData as $k => $v) {
                      $page_2 .='<tr>
                        <td height="25" align="center">'.($k+1).'</td>
                        <td align="center">'.$v['couponIssue']['coupon_title'].'</td>
                        <td align="center">'.$v['users']['name'].'</td>
                        <td align="center">'.$v['users']['idcard'].'</td>
                        <td align="center">'.$v['users']['mobile'].'</td>
                        <td align="center">'.$v['couponIssueUser']['create_time'].'</td>
                        <td align="center">'.$v['coupon_price'].'</td>
                        <td align="center">已上报</td>
                        <td align="center">'.$v['create_time'].'</td>
                      </tr>';
                  }
                $page_2 .='</table>';

                $html = $page_1.$page_2;
                // 创建PDF文件
                $data_detail = createPdf($html,'普通商户散客结算申请对账单_'.$data['no'].'.pdf');
                if($data_detail['code']!=1){
                    $this->error($data_detail['msg']);
                }
                $data['data_detail'] = $data_detail['url'];

                // 检查当前所选ID中是否含有已经核算过的记录
                // ....
                $accId = \app\common\model\Accounting::insertGetId($data);
                if (!$accId) {
                    $this->error('添加失败');
                }
                // 触发事件
                \app\common\model\WriteOff::where('id','in',explode(',',$data['write_off_ids']))->update(['accounting_id'=>$accId]);
                $this->success('申请成功，请点击结算记录进行下一步操作~', 'index');
            }
        }

       /* if (Request::param('data')) {
            $data = json_decode(Request::param('data'),true);
            if(!$data['sum_coupon_price']){
                $this->error('待核算金额为空，无法核算');
            }
            $view['writeoff_total']   = count(explode(',',$data['ids']));
            $view['sum_coupon_price'] = $data['sum_coupon_price'];
            $view['write_off_ids'] = $data['ids'];
            
        }else{
            $this->error('待核算金额为空，无法核算');
        }*/
        // 银行卡信息
            $sInfo = \app\common\model\Seller::find(session()['seller']['id']);
            $view['sInfo'] = $sInfo;
            View::assign($view);
        return View::fetch('data/accounting_form');
    }

    public function apply(){
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();
            $orderByColumn = $param['orderByColumn'] ?? 'id';
            $isAsc         = $param['isAsc'] ?? 'desc';
            $where = [];

            if (!empty($param['create_time'])) {
                $getDateran = get_dateran($param['create_time']);
                $where[] = ['create_time', 'between', $getDateran];
            }

            $where[] = ['mid','=',session()['seller']['id']];

            $model  = '\app\common\model\\' . 'Accounting';
            $list = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
            // 2023-03-20 检查核算文件是否生成
            foreach ($list['data'] as $key => $value) {
                $value['data_detail'] = $_SERVER["DOCUMENT_ROOT"].$value['data_detail'];
                if(file_exists($value['data_detail'])) {
                    $list['data'][$key]['is_files_exits'] = 1;
                }else{
                    $list['data'][$key]['is_files_exits'] = 0;
                }
            }
            return $list;
        }
        return View::fetch('data/apply');
    }

    public function show()
    {
        $id = Request::param('id');
        $accInfo = \app\common\model\Accounting::where('id',$id)->where('mid',session()['seller']['id'])->with(['sellerClass','seller'])->find();
        if(!$accInfo){
            $this->error('记录不存在');
        }
        // 区域
        $areaClass = Config::get('lang.area');
        $accInfo['area'] = $areaClass[$accInfo['area']];
        // 格式化附件
        $accInfo['data_url'] = json_decode($accInfo['data_url'],true);
        $view['accInfo'] = $accInfo;
        View::assign($view);

        $AuditRecord = \app\common\model\AuditRecord::where('aid',$id)->with(['admin','authGroup'])->select()->toArray();
        View::assign(['AuditRecord' => $AuditRecord]);
        return View::fetch('data/show');
    }

    public function write_off_ids(){
        $ids = Request::param('id');
        $orderByColumn = $param['orderByColumn'] ?? 'id';
        $isAsc         = $param['isAsc'] ?? 'desc';
        $where = [];

        $where[] = ['id','in',explode(',',$ids)];
        $where[] = ['mid','=',session()['seller']['id']];
        $modelTitle = Request::param('tags') == 1 ? 'TourWriteOff' : 'WriteOff';
        $model  = '\app\common\model\\' . $modelTitle;
        return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
    }

    // 
    public function tour_write_off_ids(){
        $ids = Request::param('id');
        $orderByColumn = $param['orderByColumn'] ?? 'id';
        $isAsc         = $param['isAsc'] ?? 'desc';
        $where = [];

        $where[] = ['id','in',explode(',',$ids)];
        $where[] = ['mid','=',session()['seller']['id']];

        $modelTitle = Request::param('tags') == 1 ? 'TourWriteOff' : 'WriteOff';
        $model  = '\app\common\model\\' . $modelTitle;
        return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
    }

    // 普通商户结算旅行团打卡申请
    public function tour()
    {
        // 获取主键
        $pk = MakeBuilder::getPrimarykey($this->tableName);
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();
            $orderByColumn = $param['orderByColumn'] ?? $pk;
            $isAsc         = $param['isAsc'] ?? 'desc';
            $where = [];
            if (!empty($param['coupon_title'])) {
                $where[] = ['coupon_title','=',$param['coupon_title']];
            }

            if (!empty($param['create_time'])) {
                $getDateran = get_dateran($param['create_time']);
                $where[] = ['create_time', 'between', $getDateran];
            }

            $where[] = ['mid','=',session()['seller']['id']];
            $where[] = ['accounting_id','=',0];
            $where[] = ['type','=',2];

            $model  = '\app\common\model\\' . 'TourWriteOff';
            return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
        }
       
        return View::fetch('data/tour');
    }

    // 普通商户申请旅行团结算表单
    public function tour_accounting_form(){
        ini_set('memory_limit', '1280M');
        ini_set('max_execution_time', '300');
        ini_set("pcre.backtrack_limit", "10000000");
        // 申请操作
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            $data['mid'] = session()['seller']['id'];

            $sInfo = \app\common\model\Seller::find($data['mid']);
            $data['nickname'] = $sInfo['nickname'];
            $data['class_id'] = $sInfo['class_id'];
            $data['area']     = $sInfo['area'];
            $data['create_time'] = time();
            $data['no']       = 'PT'.date("His").strtoupper(set_salt(7));
            $data['tags']     = 1; // 团体结算申请
            $result = $this->validate($data,  'Accounting');
            if (true !== $result) {
                // 验证失败 输出错误信息
                $this->error($result);
            } else {
                // 查询团领取记录
                $tourInfo = \app\common\model\TourWriteOff::where('id','in',explode(',',$data['write_off_ids']))->with(['tourIssueUser'])->select();
                // 结算金额
                $coupon_price_total  = 0;
                // 结算数量
                $listData       = $tourInfo->toArray();
                $coupon_number  = count($listData);
                foreach ($listData as $key => $value) {
                    $coupon_price_total += $value['coupon_price'];
                    // 查询散客
                    $listData[$key]['users'] = \app\common\model\Users::where('id',$value['tourIssueUser']['uid'])->find()->toArray();
                }
                // 生成PDF文件
                $pageData['title'] = '团体消费券业务';
                $pageData['addtitle'] = date('Y年m月d日 H:i:s',time());
                $pageData['no']       = $data['no'];
                $pageData['sum_coupon_price'] = $coupon_price_total;
                $pageData['writeoff_total']   = $coupon_number;
                $page_1 = '<style type="text/css">
                .jsxx {font-size: 18px;}
                .xxxx {font-size: 12px;}
                </style>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <table width="325" height="202" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr>
                    <td height="150" align="center" style="font-size: 30px; font-weight:bold;">
                    <p>商户结算申请对账单</p>
                    <p>&nbsp;</p></td>
                  </tr>
                </table>
                <table width="560" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxx">
                  <tr>
                    <td width="130" height="35" align="right">商户名称：</td>
                    <td>'.$data['nickname'].'</td>
                  </tr>
                  <tr>
                    <td height="35" align="right" >结算业务名称：</td>
                    <td>'.$pageData['title'].'</td>
                  </tr>
                  <tr>
                    <td height="35" align="right">申请结算时间：</td>
                    <td>'.$pageData['addtitle'].'</td>
                  </tr>
                  <tr>
                    <td height="35" align="right">结算单号：</td>
                    <td>'.$pageData['no'].'</td>
                  </tr>
                  <tr>
                    <td height="35" align="right">结算金额：</td>
                    <td>'.$pageData['sum_coupon_price'].'元</td>
                  </tr>
                  <tr>
                    <td height="35" align="right">结算核销数量：</td>
                    <td>'.$pageData['writeoff_total'].'</td>
                  </tr>
                </table>
                <table width="325" height="115" border="0" align="center" cellpadding="0" cellspacing="0">
                  <tr>
                    <td height="115" align="center" style="font-size: 18px; font-weight:bold;"><p>&nbsp;</p>
                    <p>收款账户</p></td>
                  </tr>
                </table>
                <table width="560" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxx">
                  <tr>
                    <td width="130" height="35" align="right">账户名称：</td>
                    <td>'.$data['card_name'].'</td>
                  </tr>
                  <tr>
                    <td height="35" align="right" >收款账户：</td>
                    <td>'.$data['cart_number'].'</td>
                  </tr>
                  <tr>
                    <td height="35" align="right">开 户 行：</td>
                    <td>'.$data['card_deposit'].'</td>
                  </tr>
                </table>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <table width="308" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxx">
                  <tr>
                    <td width="308" height="30" align="right">公司（盖章）：</td>
                  </tr>
                  <tr>
                    <td width="308" height="30" align="right">法人签字 ：</td>
                  </tr>
                  <tr>
                    <td height="30" align="right">日期 ：</td>
                  </tr>
                </table>
                <p>&nbsp;</p>
                <p>&nbsp;</p>
                <p>附件</p>
                ';

                $page_2 = '';
                $page_2 .= '<p>结算单号: '.$pageData['no'].'</p>';
                $page_2 .= '
                <table width="800" border="1" align="center" cellpadding="0" cellspacing="0" bordercolor="#999999" class="xxxx">
                  <tr>
                    <td width="40" height="25" align="center">序号</td>
                    <td align="center">消费券名称</td>
                    <td width="80" align="center">领取人</td>
                    <td width="130" align="center">身份证号</td>
                    <td width="70" align="center">手机号</td>
                    <td width="70" align="center">领取时间</td>
                    <td width="70" align="center">消费券面额</td>
                    <td width="70" align="center">核销地点</td>
                    <td width="70" align="center">核销时间</td>
                  </tr>';
                  foreach ($listData as $k => $v) {
                      $page_2 .='<tr>
                        <td height="25" align="center">'.($k+1).'</td>
                        <td align="center">'.$v['coupon_title'].'</td>
                        <td align="center">'.$v['users']['name'].'</td>
                        <td align="center">'.$v['users']['idcard'].'</td>
                        <td align="center">'.$v['users']['mobile'].'</td>
                        <td align="center">'.$v['tourIssueUser']['create_time'].'</td>
                        <td align="center">'.$v['coupon_price'].'</td>
                        <td align="center">已上报</td>
                        <td align="center">'.$v['create_time'].'</td>
                      </tr>';
                  }
                $page_2 .='</table>';

                $html = $page_1.$page_2;
                // 创建PDF文件
                $data_detail = createPdf($html,'普通商户团体券结算申请对账单_'.$data['no'].'.pdf');
                if($data_detail['code']!=1){
                    $this->error($data_detail['msg']);
                }
                $data['data_detail'] = $data_detail['url'];

                // 检查当前所选ID中是否含有已经核算过的记录
                // ....
                $accId = \app\common\model\Accounting::insertGetId($data);
                if (!$accId) {
                    $this->error('添加失败');
                }
                // 触发事件
                \app\common\model\TourWriteOff::where('id','in',explode(',',$data['write_off_ids']))->data(['accounting_id'=>$accId])->update();
                $this->success('申请成功，请点击结算记录进行下一步操作~', 'index');
            }
        }

        /*if (Request::param('data')) {
            $data = json_decode(Request::param('data'),true);
            if(!$data['sum_coupon_price']){
                $this->error('待核算金额为空，无法核算');
            }
            $view['writeoff_total']   = count(explode(',',$data['ids']));
            $view['sum_coupon_price'] = $data['sum_coupon_price'];
            $view['write_off_ids'] = $data['ids'];
            
        }else{
            $this->error('待核算金额为空，无法核算');
        }*/
        // 银行卡信息
            $sInfo = \app\common\model\Seller::find(session()['seller']['id']);
            $view['sInfo'] = $sInfo;
            View::assign($view);
        return View::fetch('data/tour_accounting_form');
    }

    // 修改密码
    public function editpass(string $id)
    {
        $model = '\app\common\model\Seller';
        $info = $model::edit($id)->toArray();
        View::assign(['row' => $info]);
        return View::fetch();
    }

    // 修改保存
    public function editpassPost()
    {
        if (Request::isPost()) {
            $data = MakeBuilder::changeFormData(Request::except(['file'], 'post'), 'seller');

            $newpassword      = Request::param('newpassword');
            $pwd = checkPassword($newpassword);
            if($pwd['code']!=1){
                $res['error'] = 1;
                $res['msg']   = $pwd['msg'];
                return json($res);
            }
            
            // 校验原始密码是否正确
            $admininfo = \app\common\model\Seller::find($data['id']);

            if($data['password']!=$admininfo['password']){
                $res = ['error' => '1', 'msg' => '原始密码错误'];
                return json($res);
            }

            $where['id'] = $data['id'];
            // token 校验
            $check = Request::checkToken('__token__');
            if (false === $check) {
                $res = ['error' => '1', 'msg' => '请勿重复操作'];
                return json($res);
            }

            $data['password'] = md5($newpassword);
            \app\common\model\Seller::update($data, $where);
            Session::delete('seller');
            $res = ['error' => '0', 'msg' => '修改成功，请重新登录'];
            return json($res);
        }
    }
}
