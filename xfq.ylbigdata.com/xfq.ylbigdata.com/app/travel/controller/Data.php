<?php
/**
 * 商户管理-数据核算控制器
 * @author slomoo <1103398780@qq.com> 2022/07/22
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
use Mpdf\Mpdf;

class Data extends Base
{
    // 验证器
    protected $validate = 'TourWriteoff';

    // 当前主表
    protected $tableName = 'write_off';

    // 当前主模型
    protected $modelName = 'TourWriteoff';


    // 结算申请列表 - 以团为单位
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
            if (!empty($param['name'])) {
                $where[] = ['name','like','%'.$param['name'].'%'];
            }

            if (!empty($param['accounting_time'])) {
                $getDateran = get_dateran($param['accounting_time']);
                $where[] = ['accounting_time', 'between', $getDateran];
            }

            $where[] = ['mid','=',session()['travel']['id']];
            $where[] = ['tour_accounting_id','=',0];
            $where[] = ['status','=',5];
            $model  = '\app\common\model\\' . 'Tour';

            $list = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
            // 计算每个团下面的核销金额
            foreach ($list['data'] as $key => $value) {
                $list['data'][$key]['coupon_price'] = $this->coupon_price_total($value['id']);
            }
            return $list;
        }
       
        return View::fetch('data/index');
    }

    // 根据旅行团ID计算该团下面总共核销金额
    private function coupon_price_total($tid)
    {
        return \app\common\model\TourWriteOff::where('tid',$tid)->where('type',1)->sum('coupon_price');
    }

    // 根据核算记录ID 修改附件信息
    public function accounting_data_url()
    {
        if(Request::isPost()){
            $data = Request::except(['file'], 'post');
            if(!$data['tour_accounting_id'])
                $this->error('参数错误请刷新页面重试');
            if(!isset($data['images_']))
                $this->error('请上传附件信息');
            // 修改附件地址 修改状态未待审核
            $res = \app\common\model\TourAccounting::where('id',$data['tour_accounting_id'])
                ->where('mid',session()['travel']['id'])
                ->update(['data_url'=>json_encode($data['images_'],true),'update_time'=>time(),'status'=>3]);
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
            $data['mid'] = session()['travel']['id'];
            $sInfo = \app\common\model\Seller::find($data['mid']);
            $data['nickname'] = $sInfo['nickname'];
            $data['class_id'] = $sInfo['class_id'];
            $data['area']     = $sInfo['area'];
            $data['create_time'] = time();
            $data['no']       = 'LXS'.date("His").strtoupper(set_salt(7));

            $result = $this->validate($data,  'TourAccounting');
            if (true !== $result) {
                // 验证失败 输出错误信息
                $this->error($result);
            } else {
                // 根据团ID查询信息
                $tourInfo = \app\common\model\Tour::where('id','in',explode(',',$data['write_off_ids']))->with(['tourist','tour_hotel_user_record'])->select();
                // 统计游客数量
                $tourist_total  = 0;
                $listData       = $tourInfo->toArray();

                foreach ($listData as $key => $value) {
                    $tourist_total += count($value['tourist']);
                }

                // 2023-03-22 将酒店打卡次数更改到游客表上的numbers中
                foreach ($listData as &$order) {
                     // 获取tour_hotel_user_record数组中的所有uid
                    $vo_uids = array_column($order['tour_hotel_user_record'], 'uid');
                    foreach ($order['tourist'] as &$item) {
                        if (in_array($item['uid'], $vo_uids)) {
                            if (!isset($item['numbers'])) {
                                $item['numbers'] = 0;
                            }
                            $item['numbers'] += array_count_values($vo_uids)[$item['uid']];
                        } else {
                            $item['numbers'] = 0;
                        }
                    }
                    unset($order['tour_hotel_user_record']); // 释放除酒店打卡记录
                    unset($item); // 注意要释放$item的引用
                }
                unset($order); // 注意要释放$order的引用

                // 生成PDF文件
                $pageData['title'] = '旅游消费券';
                $pageData['addtitle'] = date('Y年m月d日 H:i:s',time());
                $pageData['no']       = $data['no'];
                $pageData['sum_coupon_price'] = $data['sum_coupon_price'];
                $pageData['writeoff_total']   = $data['writeoff_total'];
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
                    <p>旅行社结算申请对账单</p>
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
                    <td height="35" align="right">旅行团数量：</td>
                    <td>'.$pageData['writeoff_total'].'</td>
                  </tr>
                  <tr>
                    <td height="35" align="right">游客数量：</td>
                    <td>'.$tourist_total.'</td>
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
                    <td width="308" height="30" align="right">法人签字：</td>
                  </tr>
                  <tr>
                    <td height="30" align="right">日期：</td>
                  </tr>
                </table>
                <p>&nbsp;</p>
                <p>&nbsp;</p>';

                $page_2 = '';
                foreach ($listData as $key => $value) {
                    $page_2 .= '<p>团号: '.$value['no'].'</p>
                                <p>名称: '.$value['name'].'</p>
                    <table width="800" border="1" align="center" cellpadding="0" cellspacing="0" bordercolor="#999999" class="xxxx">
                      <tr>
                        <td width="40" height="25" align="center">序号</td>
                        <td align="center">游客姓名</td>
                        <td width="120" align="center">身份证号</td>
                        <td width="90" align="center">手机号</td>
                        <td width="80" align="center">领取时间</td>
                        <td width="70" align="center">消费券面额</td>
                        <td width="70" align="center">发票</td>
                        <td width="70" align="center">景区打卡照片</td>
                        <td width="70" align="center">酒店打卡次数</td>
                        <td width="80" align="center">核销时间</td>
                      </tr>';
                      foreach ($value['tourist'] as $k => $v) {
                          $page_2 .='<tr>
                            <td height="25" align="center">'.($k+1).'</td>
                            <td align="center">'.$v['name'].'</td>
                            <td align="center">'.$v['idcard'].'</td>
                            <td align="center">'.$v['mobile'].'</td>
                            <td align="center">'.date("Y-m-d H:i:s",$v['tour_receive_time']).'</td>
                            <td align="center">'.$v['tour_price'].'</td>
                            <td align="center">已上报</td>
                            <td align="center">已上报</td>
                            <td align="center">'.$v['numbers'].'</td>
                            <td align="center">'.date("Y-m-d H:i:s",$v['tour_writeoff_time']).'</td>
                          </tr>';
                      }
                    $page_2 .='</table>';
                }

                $html = $page_1.$page_2;
                // 创建PDF文件
                $data_detail = createPdf($html,'旅行社结算申请对账单_'.$data['no'].'.pdf');
                if($data_detail['code']!=1){
                    $this->error($data_detail['msg']);
                }
                $data['data_detail'] = $data_detail['url'];

                // 检查当前所选ID中是否含有已经核算过的记录
                // ....
                $accId = \app\common\model\TourAccounting::insertGetId($data);
                if (!$accId) {
                    $this->error('申请失败');
                }
                // 触发事件
                \app\common\model\Tour::where('id','in',explode(',',$data['write_off_ids']))
                ->where('tour_accounting_id',0)
                ->update(['tour_accounting_id'=>$accId,'accounting_time'=>time()]);
                \app\common\model\TourWriteOff::where('tid','in',explode(',',$data['write_off_ids']))
                ->where('accounting_id',0)
                ->where('type',1)->update(['accounting_id'=>$accId,'update_time'=>time()]);
                $this->success('申请成功，请点击结算记录进行下一步操作~', 'index');
            }
        }

        if (Request::param('data')) {
            $data = json_decode(Request::param('data'),true);
            if(!$data['sum_coupon_price']){
                $this->error('待核算金额为空，无法核算');
            }
            $view['writeoff_total']   = count(explode(',',$data['ids']));
            $view['sum_coupon_price'] = $data['sum_coupon_price'];
            $view['write_off_ids'] = $data['ids'];
            // 银行卡信息
            $sInfo = \app\common\model\Seller::find(session()['travel']['id']);
            $view['sInfo'] = $sInfo;
            View::assign($view);
        }else{
            $this->error('待核算金额为空，无法核算');
        }
        
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

            $where[] = ['mid','=',session()['travel']['id']];

            $model  = '\app\common\model\\' . 'TourAccounting';
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

        $accInfo = \app\common\model\TourAccounting::where('id',$id)->where('mid',session()['travel']['id'])->find();
        if(!$accInfo){
            return $this->error('未找到数据');
        }
        $accInfo['data_url'] = json_decode($accInfo['data_url'],true);
        $view['accInfo'] = $accInfo;
        View::assign($view);
        $AuditRecord = \app\common\model\TourAuditRecord::where('aid',$id)->with(['admin','authGroup'])->select()->toArray();
        View::assign(['AuditRecord' => $AuditRecord]);
        return View::fetch('data/show');
    }

    public function write_off_ids(){
        $ids = Request::param('id');
        $orderByColumn = $param['orderByColumn'] ?? 'id';
        $isAsc         = $param['isAsc'] ?? 'desc';
        $where = [];

        $where[] = ['id','in',explode(',',$ids)];
        $where[] = ['mid','=',session()['travel']['id']];

        $model  = '\app\common\model\\' . 'WriteOff';
        return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
    }

    // 列表
    public function tour()
    {
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();
            $orderByColumn = $param['orderByColumn'] ?? 'id';
            $isAsc         = $param['isAsc'] ?? 'desc';
            $where = [];
            if (!empty($param['coupon_title'])) {
                $where[] = ['coupon_title','=',$param['coupon_title']];
            }

            if (!empty($param['create_time'])) {
                $getDateran = get_dateran($param['create_time']);
                $where[] = ['create_time', 'between', $getDateran];
            }

            $where[] = ['mid','=',session()['travel']['id']];
            $where[] = ['accounting_id','=',0];

            $model  = '\app\common\model\\' . 'TourWriteOff';
            return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
        }
       
        return View::fetch('data/tour');
    }

    // 
    public function tour_write_off_ids(){ 
        $ids = Request::param('id');
        $orderByColumn = $param['orderByColumn'] ?? 'id';
        $isAsc         = $param['isAsc'] ?? 'desc';
        $where = [];

        $where[] = ['id','in',explode(',',$ids)];
        $where[] = ['mid','=',session()['travel']['id']];

        $model  = '\app\common\model\\' . 'Tour';
        return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
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
                $where[] = ['mid','=',session()['travel']['id']];
                return $model::getList($where, $this->pageSize, ['id' => 'desc']);
            }
            return FormBuilder::getInstance()->fetch('data/guide');
        }
        // 游客管理
        if($tags==2){
            // 搜索
            if (Request::param('getList') == 1 && Request::param('tid')) {
                $model  = '\app\common\model\\' . 'Tourist';
                $where = [];
                $where[] = ['tid','=',Request::param('tid')];
                $where[] = ['mid','=',session()['travel']['id']];
                return $model::getList($where, $this->pageSize, ['id' => 'desc']);
            }
           return View::fetch('data/tourist'); 
        }
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
        return FormBuilder::getInstance()->fetch('data/receive');
    }

    // 查看打卡记录
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
        return FormBuilder::getInstance()->fetch('data/clock');
    }

    // 散客结算记录
    public function guest()
    {
        // 获取主键
        $pk = MakeBuilder::getPrimarykey('WriteOff');
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

            $where[] = ['mid','=',session()['travel']['id']];
            $where[] = ['accounting_id','=',0];

            $model  = '\app\common\model\\' . 'WriteOff';
            return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
        }
       
        return View::fetch('data/guest');
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
            Session::delete('travel');
            $res = ['error' => '0', 'msg' => '修改成功，请重新登录'];
            return json($res);
        }
    }
}
