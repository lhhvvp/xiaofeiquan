<?php
/**
 * @desc   分时预约API
 * @author slomoo
 * @email slomoo@aliyun.com
 */
declare (strict_types=1);

namespace app\api\controller;

use app\api\BaseController;
use app\api\middleware\Auth;
use app\api\service\JwtAuth;
use think\facade\Db;
use think\facade\Request;
use ip2region\XdbSearcher;
use app\common\model\appt\Datetime as DatetimeModel;
use app\common\model\appt\Log as LogModel;
use app\common\model\appt\LogTourist as LogTouristModel;
use app\common\model\Seller as SellerModel;
use app\common\model\MerchantVerifier as MerchantVerifierModel;
use think\facade\Validate;

class Appt extends BaseController
{

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except' => ['getDatetime']]
    ];

    //初始化
    protected function initialize()
    {
        parent::initialize();
    }

    /**
     * 获取指定商户分时预约时段列
     * @return array
     */
    public function getDatetime()
    {
        if (!Request::isGet()) {
            $this->apiError('请求方式错误！');
        }
        $seller_id  = Request::param('seller_id/d', 0);
        $date_start = Request::param('date_start/s', '');
        $date_end   = Request::param('date_end/s', '');
        if ($seller_id === 0) {
            $this->apiError('缺少商户参数');
        }
        $where = [
            ['seller_id', '=', $seller_id]
        ];
        if (!empty($date_start)) {
            $where[] = ['date', '>=', $date_start];
        }else{
            $where[] = ['date', '>=', date("Y-m-d")];
        }
        if (!empty($date_end)) {
            $where[] = ['date', '<=', $date_end];
        }
        $datetime_list = DatetimeModel::where($where)->append(['time_start_text', 'time_end_text', 'start', 'end'])->order('date asc')->select()->toArray();
        $groupedData   = [];
        if (!empty($datetime_list)) {
            $groupedData = array_reduce($datetime_list, function ($carry, $item) {
                $date = $item['date'];
                if (!isset($carry[$date])) {
                    $carry[$date] = [];
                }
                $carry[$date][] = $item;
                return $carry;
            }, []);
        }
        $number = SellerModel::where("id",$seller_id)->value("appt_limit");
        $this->apiSuccess('',['number'=>$number,'list'=>$groupedData]);
    }

    /**
     * 预约操作
     * @return array
     */
    public function createAppt()
    {
        if (!Request::isPost()) {
            $this->apiError('请求方式错误！');
        }
            $post     = Request::post();
            $validate = Validate::rule([
                'datetime_id' => 'require',
                'fullname'    => 'require|chs|max:10',
                'phone'       => 'require|mobile',
                'idcard'      => 'require|idCard',
                'lat'         => 'require',
                'lng'         => 'require',
                'number'      => 'require',
                'tourist'    =>'require'
            ]);
            $validate->message([
                'datetime_id.require' => '请选择时间段！',
                'fullname.require'    => '姓名不能为空！',
                'fullname.chs'        => '姓名只能是汉字！',
                'fullname.max'        => '姓名长度超出！',
                'phone.require'       => '手机号不能为空！',
                'phone.mobile'        => '手机号格式不符',
                'idcard.require'      => '身份证号不能为空！',
                'idcard.idCard'       => '身份证号格式不符！',
                'number.require'       => '预约人数必填！！',
                'lat.require'         => '当前位置纬度不能为空！',
                'lng.require'         => '当前位置经度不能为空！',
                'tourist.require'      =>'游客信息不能为空！'
            ]);
            if (!$validate->check($post)) {
                $this->apiError($validate->getError());
            }

            if(!isJson(htmlspecialchars_decode($post['tourist']))){
                $this->apiError("游客信息格式错误！");
            }
            $tourist_list = json_decode(htmlspecialchars_decode($post['tourist']), true);

            if(count($tourist_list) != $post['number']){
                $this->apiError("缺少游客信息！");
            }
            $user_id = Request::header('Userid');
            $datetime_info = DatetimeModel::where("id", $post['datetime_id'])->find();
            if ($datetime_info == null) {
                $this->apiError('该时段不存在！');
            }
            if (time() > (strtotime($datetime_info['date']) + $datetime_info['time_end'])) {
                $this->apiError('预约时间已过！');
            }
            if ($datetime_info['stock'] < $post['number']) {
                $this->apiError('该时段已约满！');
            }
            //判断预约数量
            $seller_info = \app\common\model\Seller::where("id",$datetime_info['seller_id'])->field("id,appt_open,appt_limit")->find();
            if(!$seller_info){
                $this->apiError('商户不存在！');
            }
            if($seller_info['appt_open'] != 1){
                $this->apiError('商户未开启预约！');
            }
            //判断当日是否预约
            $appt_number = LogTouristModel::where([['user_id','=',$user_id],['date','=',$datetime_info['date']],['seller_id','=',$datetime_info['seller_id']]])->count();
            if(($appt_number + $post['number']) > $seller_info['appt_limit']){
                $this->apiError('每日只允许预约'.$seller_info['appt_limit'].'人，您已预约'.$appt_number.'人！');
            }
            $data = [
                'code'        => uniqidDate(20),
                'seller_id'   => $datetime_info['seller_id'],
                'user_id'     => $user_id,
                'date'        => $datetime_info['date'],
                'time_start'  => $datetime_info['time_start'],
                'time_end'    => $datetime_info['time_end'],
                'fullname'    => $post['fullname'],
                'idcard'      => $post['idcard'],
                'phone'       => $post['phone'],
                'number'      => $post['number'],
                'status'      => 0,
                'lat'         => $post['lat'],
                'lng'         => $post['lng'],
                'address'     => '',
                'ip'          => Request::ip(),
                'create_time' => time()
            ];
            Db::startTrans();
            try {
                $logInfo = LogModel::create($data);
                //开始写入游客信息
                foreach($tourist_list as $item){
                    $checkToday = LogTouristModel::where(['seller_id'=>$datetime_info['seller_id'],'date'=>$datetime_info['date'],'tourist_fullname'=>$item['fullname'],'tourist_cert_id'=>$item['cert_id']])->find();
                    if($checkToday !== null){
                        throw new \think\Exception($item['fullname'].'在'.$datetime_info['date'].'已预约，请删除该游客后再试！');
                    }
                    $insertData = [
                        'code'=>uniqidDate(20),
                        'seller_id'   => $datetime_info['seller_id'],
                        'user_id'     => $user_id,
                        'date'        => $logInfo->date,
                        'time_start'  => $logInfo->time_start,
                        'time_end'    => $logInfo->time_end,
                        'log_id'=>$logInfo->id,
                        'tourist_fullname'=>$item['fullname'],
                        'tourist_cert_type'=>$item['cert_type'],
                        'tourist_cert_id'=>$item['cert_id'],
                        'tourist_mobile'=>$item['mobile'],
                        'create_time'=>time(),
                        'status'=>0
                    ];
                    LogTouristModel::create($insertData);
                }
                $datetime_info->stock = $datetime_info->stock - $post['number'];
                $datetime_info->save();
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->apiError('预约失败！'.$e->getMessage());
            }
            $this->apiSuccess('预约成功！');
    }

    /**
     * 分页获取预约列表
     * @return array
     */
    public function getList()
    {
        if (!Request::isGet()) {
            $this->apiError('请求方式错误！');
        }
            $param   = Request::get();
            $where   = [];
            $where[] = ["user_id", "=", Request::header("Userid")];
            if (isset($param['status']) && $param['status'] != '') {
                $where[] = ["status", "=", $param['status']];
            }
            $page      = max(1, (isset($param['page']) ? (int)$param['page'] : 1));
            $page_size = isset($param['page_size']) ? (int)$param['page_size'] : 10;
            $list      = LogModel::where($where)->with(['seller'])->append(['time_start_text', 'time_end_text', 'start', 'end', 'status_text'])->order("id DESC")->page($page, $page_size)->select();
            $list      = $list->visible(['seller' => ['nickname', 'image']])->hidden(['seller_id', 'user_id', 'writeoff_id', 'writeoff_name', 'lat', 'lng', 'address', 'ip'])->toArray();
            $this->apiSuccess('ok', $list);
    }

    /**
     * 获取预约记录详情
     * @return array
     */
    public function getDetail()
    {
        if (!Request::isGet()) {
            $this->apiError('请求方式错误！');
        }
        $id = Request::get('id', '');
        if ($id == '') {
            $this->apiError('缺少参数！');
        }
        $info = LogModel::where("id", $id)->with(['seller'])->append(['time_start_text', 'time_end_text', 'start', 'end', 'status_text', 'qrcode_str','tourist_list'])->find();
        $info = $info->visible(['seller' => ['nickname', 'image']])->hidden(['seller_id', 'user_id', 'writeoff_id', 'writeoff_name', 'lat', 'lng', 'address', 'ip'])->toArray();
        $this->apiSuccess('', $info);
    }

    public function writeOff()
    {
        if (!Request::isPost()) {
            $this->apiError('请求方式错误！');
        }
            $post     = Request::post();
            $validate = Validate::rule([
                'qrcode_str' => 'require',
                'be_id'      => 'require',
                'use_lat'    => 'require',
                'use_lng'    => 'require'
            ]);
            $validate->message([
                'qrcode_str.require' => '参数错误！',
                'be_id.require'      => '参数错误！',
                'use_lat.require'    => '核销纬度不能为空！',
                'use_lng.require'    => '核销经度不能为空'
            ]);
            if (!$validate->check($post)) {
                $this->apiError($validate->getError());
            }
            //先验证和核销人信息
            $user_id = Request::header("Userid");
            $hx_man  = MerchantVerifierModel::where([["uid","=", $user_id],["type","=","appt"]])->find();
            if (!$hx_man) {
                $this->apiError("核销人不存在！");
            }
            if ($hx_man['status'] != 1) {
                $this->apiError("核销人未通过审核！");
            }
            if ($hx_man['type'] != 'appt') {
                $this->apiError("核销人不允许核销预约！");
            }
            $qrcode_de_str = sys_decryption($post['qrcode_str'], $post['be_id']);
            if(!is_string($qrcode_de_str)){
                $this->apiError("核销码类型错误！");
            }
            $qrcode_de_arr = explode("&", $qrcode_de_str);
            if (count($qrcode_de_arr) != 3) {
                $this->apiError("核销码不正确！");
            }
            if(!in_array($qrcode_de_arr[0],['log','logtourist'])){
                $this->apiError("核销码不正确！");
            }
            if (time() > $qrcode_de_arr[2]) {
                $this->apiError("核销码过期，刷新后再试！");
            }
            if($qrcode_de_arr[0] == 'log'){
                //全部核销
                $log_info = LogModel::where("code", $qrcode_de_arr[1])->find();
                if (!$log_info) {
                    $this->apiError("核销记录不存在！");
                }
                if ($log_info['seller_id'] != $hx_man['mid']) {
                    $this->apiError("不允许核销其他商户的预约记录！");
                }
                if ($log_info['status'] != 0) {
                    $this->apiError("已被核销！");
                }
                if ($log_info['date'] != date("Y-m-d")) {
                    $this->apiError("预约日期不是今天，不允许核销！");
                }

                Db::startTrans();
                try {
                    $log_info->status     = 1;
                    $log_info->writeoff_time = time();
                    $log_info->writeoff_id   = $hx_man['id'];
                    $log_info->writeoff_name = $hx_man['name'];
                    $log_info->lat        = $post['use_lat'];
                    $log_info->lng        = $post['use_lng'];
                    $log_info->ip         = Request::ip();
                    $log_info->save();
                    //更新时间段核销数量
                    $datetime_info = DatetimeModel::whereDay('date', $log_info['date'])->where([['time_start', '<=', $log_info['time_end']]], ['time_end', '>', $log_info['time_start']])->find();
                    if ($datetime_info) {
                        $datetime_info->use_num = $datetime_info['use_num'] + $log_info['number'];
                        $datetime_info->save();
                    }
                    //更新游客信息
                    $updateData = [
                        'status'=>1,
                        'writeoff_time'=>$log_info->writeoff_time,
                        'writeoff_id'=>$log_info->writeoff_id,
                        'writeoff_name'=>$log_info->writeoff_name,
                        'writeoff_lat'=>$log_info->lat,
                        'writeoff_lng'=>$log_info->lng,
                        'writeoff_ip'=>$log_info->ip,
                    ];
                    LogTouristModel::where([["log_id","=",$log_info->id],["status","=",0]])->update($updateData);
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                    $this->apiError("核销失败！".$e->getMessage());
                }
                $this->apiSuccess("核销成功！");
            }
        $this->apiError("类型错误！");
    }

    public function cancelAppt()
    {
        if (Request::isPost()) {
            $post     = Request::post();
            $validate = Validate::rule([
                'log_id' => 'require'
            ]);
            $validate->message([
                'log_id.require' => '参数错误！'
            ]);
            if (!$validate->check($post)) {
                $this->apiError($validate->getError());
            }
            $user_id  = Request::header("Userid");
            $log_info = LogModel::where("id", $post['log_id'])->find();
            if ($log_info['user_id'] != $user_id) {
                $this->apiError('不存在！');
            }
            if ($log_info['status'] == 1) {
                $this->apiError('该预约已核销，不能取消！');
            }
            if ($log_info['status'] == 2) {
                $this->apiError('该预约已取消，无需重复操作！');
            }
            $log_info->status      = 2;
            $log_info->cancel_time = time();
            Db::startTrans();
            try {
                $log_info->save();
                //还原库存
                $datetime_info = DatetimeModel::whereDay('date', $log_info['date'])->where([['time_start', '<=', $log_info['time_end']]], ['time_end', '>', $log_info['time_start']])->find();
                if ($datetime_info) {
                    $datetime_info->stock = $datetime_info['stock'] + $log_info['number'];
                    $datetime_info->save();
                }
                //更新游客信息
                LogTouristModel::where([["log_id","=",$log_info->id],["status","=",0]])->update(['status'=>2]);
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->apiError("操作失败！");
            }
            $this->apiSuccess("操作成功！");
        }
        $this->apiError('请求方式错误！');
    }
}
