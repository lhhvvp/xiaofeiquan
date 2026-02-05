<?php
/**
 * 消费券表控制器
 * @author slomoo <1103398780@qq.com> 2022/07/22
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;
use think\facade\Db;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\View;

class CouponIssue extends Base
{
    // 验证器
    protected $validate = 'CouponIssue';

    // 当前主表
    protected $tableName = 'coupon_issue';

    // 当前主模型
    protected $modelName = 'CouponIssue';

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
            
            if (@$param['coupon_title']!='') {
                $where[] = ['coupon_title','like',"%".$param['coupon_title']."%"];
            }
            if (@$param['cid']!='') {
                $where[] = ['cid','=',$param['cid']];
            }
            if (@$param['status']!='') {
                $where[] = ['status','=',$param['status']];
            }
            $model  = '\app\common\model\\' . $this->modelName;
            return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc,'id'=>'desc']);
        }
        // 消费券分类
        $CouponClass = \app\common\model\CouponClass::field('id, title')
            ->order('sort asc')
            ->select()
            ->toArray();
        View::assign(['class_list' => $CouponClass]);
        
        return View::fetch('coupon/index');
    }

    // 添加消费券
    public function add(){
        // 获取商品列表
        if (Request::param('getList') != '' && Request::param('menuId') !='') {
            $where = [];
            $where[] = ['id','in',Request::param('getList')];
            switch (Request::param('menuId')) {
                case '2':// 门票
                    return \app\common\model\Ticket::getTicketList($where, $this->pageSize);
                    break;
                case '3':// 线路
                    return \app\common\model\Line::getLineList($where, $this->pageSize);
                    break;
                case '4':// 商品
                    // code...
                    break;
                default:
                    // code...
                    break;
            }
        }
        // 获取商户列表
        if(Request::param('class_id') !='' && Request::param('getList')){
            $map = [];
            $map[] = ['id','in',Request::param('getList')];
            return \app\common\model\Seller::getSellerList($map, $this->pageSize);
        }

        // 获取规则列表
        if(Request::param('addCondition') && Request::param('getList')){
            $map = [];
            $map[] = ['id','in',Request::param('getList')];
            return \app\common\model\CouponConditionDetails::getConditionList($map, $this->pageSize);
        }

        // 获得核销商户列表
        $WriteOffSellerList = \app\common\model\Seller::field('id, nickname')
            ->where('class_id',7)
            ->where('status',1)
            ->where('nickname','<>', '')
            ->order('create_time desc')
            ->select()
            ->toArray();
        View::assign(['write_off_seller_list' => $WriteOffSellerList]);

        // 查询消费券分组
        $CouponClass = \app\common\model\CouponClass::field('id, title')
            ->order('sort asc')
            ->select()
            ->toArray();
        $view   = [
            'list' => $CouponClass
        ];
        View::assign($view);
        $TicketCategory = \app\common\model\TicketCategory::field('id, status')
            ->order('sort asc')
            ->select()
            ->toArray();
        View::assign(['ticket_class' => $TicketCategory]);
        return View::fetch('coupon/addCoupon');
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
                // 是否限时
                if($data['limit_time'] == 1){
                    $coupontimelist = explode(' - ',$data['coupon_time_limit']);
                    unset($data['coupon_time_limit']);
                    $data['start_time'] = strtotime($coupontimelist[0]);
                    $data['end_time'] = strtotime($coupontimelist[1]);
                }else{
                    unset($data['coupon_time_limit']);
                    $data['start_time'] = 0;
                    $data['end_time'] = 0;
                }

                // 是否有门槛
                if($data['is_threshold'] == 1 && !$data['use_min_price']){
                    $this->error('请输入最低消费金额！');
                }

                // 是否限时领取数量
                if($data['is_limit_total'] == 1 && $data['limit_total'] <= 0){
                    $this->error('请输入正确的领取数量！');
                }

                // 是否永久有效
                if($data['is_permanent'] == 2){
                    $coupon_time = explode(' - ',$data['coupon_time']);
                    unset($data['coupon_time']);
                    $data['coupon_time_start'] = strtotime($coupon_time[0]);
                    $data['coupon_time_end'] = strtotime($coupon_time[1]);
                }else{
                    unset($data['coupon_time']);
                    $data['coupon_time_start'] = 0;
                    $data['coupon_time_end'] = 0;
                }

                //处理领取规则
                $use_condition_id_str = $data['use_condition_id'];
                unset($data['use_condition_id']);
                
                // 生成唯一编号
                $data['uuno'] = strtoupper(gen_uuid());
                $data['remain_count'] = $data['total_count'];

                $model = '\app\common\model\\' . $this->modelName;
                $result = $model::addPost($data);
                if ($result['error']) {
                    $this->error($result['msg']);
                } else {
                    //处理优惠卷、领取规则关系
                    if (!empty($use_condition_id_str)) {
                        $use_condition_id_arr = explode(",", $use_condition_id_str);
                        $conditionData = [];
                        foreach ($use_condition_id_arr as $use_condition_id) {
                            $conditionData[] = [
                                'coupon_id' => $result['id'],
                                'condition_id' => $use_condition_id,
                            ];
                        }
                        $couponReceiveCondition = new \app\common\model\CouponReceiveCondition();
                        $couponReceiveCondition->saveAll($conditionData);
                    }

                    $this->success($result['msg'], 'index');
                }
            }
        }
    }

    // 编辑消费券
    public function edit($id){
        // 查询消费券详情
        $model        = '\app\common\model\\' . $this->modelName;
        $couponissue  = $model::edit($id)->toArray();

        //获得核销规则
        $use_condition_id = '0';
        if ($couponissue['use_type'] == 1) {
            $CouponReceiveCondition = \app\common\model\CouponReceiveCondition::field('condition_id')
                ->where('coupon_id', $id)
                ->select()
                ->toArray();
            if (!empty($CouponReceiveCondition)) {
                $CouponReceiveConditionArr = array_column($CouponReceiveCondition, 'condition_id');
                $use_condition_id = implode(",", $CouponReceiveConditionArr);
            }
        }
        $couponissue['use_condition_id'] = $use_condition_id;

        // 获得核销商户列表
        $WriteOffSellerList = \app\common\model\Seller::field('id, nickname')
            ->where('class_id',7)
            ->where('nickname','<>', '')
            ->order('class_id asc,create_time desc')
            ->select()
            ->toArray();
        View::assign(['write_off_seller_list' => $WriteOffSellerList]);

        View::assign(['couponissue' => $couponissue]);
        // 消费券分类
        $CouponClass = \app\common\model\CouponClass::field('id, title')
            ->order('sort asc')
            ->select()
            ->toArray();
        View::assign(['list' => $CouponClass]);
        $TicketCategory = \app\common\model\TicketCategory::field('id, status')
            ->order('sort asc')
            ->select()
            ->toArray();
        View::assign(['ticket_class' => $TicketCategory]);
        return View::fetch('coupon/editCoupon');
    }

    // 编辑保存
    public function editPost()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            $where['id'] = $data['id'];

            $result = $this->validate($data,'CouponIssue');
            if (true !== $result) {
                $this->error($result);
            }
            // 是否限时
            if($data['limit_time'] == 1){
                $coupontimelist = explode(' - ',$data['coupon_time_limit']);
                unset($data['coupon_time_limit']);
                $data['start_time'] = strtotime($coupontimelist[0]);
                $data['end_time'] = strtotime($coupontimelist[1]);
            }else{
                unset($data['coupon_time_limit']);
                $data['start_time'] = 0;
                $data['end_time'] = 0;
            }

            // 是否有门槛
            if($data['is_threshold'] == 1 && !$data['use_min_price']){
                $this->error('请输入最低消费金额！');
            }

            // 是否限时领取数量
            if($data['is_limit_total'] == 1 && $data['limit_total'] <= 0){
                $this->error('请输入正确的领取数量！');
            }

            // 是否永久有效
            if($data['is_permanent'] == 2){
                $coupon_time = explode(' - ',$data['coupon_time']);
                unset($data['coupon_time']);
                $data['coupon_time_start'] = strtotime($coupon_time[0]);
                $data['coupon_time_end'] = strtotime($coupon_time[1]);
            }else{
                unset($data['coupon_time']);
                $data['coupon_time_start'] = 0;
                $data['coupon_time_end'] = 0;
            }
            $data['remain_count'] = $data['total_count'];

            // 2023-03-23 每次编辑消费券之后重新变成待审核状态
            $data['status'] = 0;

            //获得领取规则id
            $use_condition_id_str = $data['use_condition_id'];
            unset($data['use_condition_id']);

            \app\common\model\CouponIssue::update($data, $where);

            //处理优惠卷、领取规则关系
            //先删除该优惠卷规则
            Db::name('couponReceiveCondition')->where('coupon_id',$data['id'])->delete();

            //再新增规则
            if (!empty($use_condition_id_str)) {
                $use_condition_id_arr = explode(",", $use_condition_id_str);
                $conditionData = [];
                foreach ($use_condition_id_arr as $use_condition_id) {
                    $conditionData[] = [
                        'coupon_id' => $data['id'],
                        'condition_id' => $use_condition_id,
                    ];
                }
                $couponReceiveCondition = new \app\common\model\CouponReceiveCondition();
                $couponReceiveCondition->saveAll($conditionData);
            }

            $this->success('修改成功!', 'index');
        }
    }

    // 状态变更
    public function state(string $id)
    {
        if (Request::isPost()) {
            $info = \app\common\model\CouponIssue::find($id);
            //$data['status'] = $info['status'] == 1 ? -1 : 1;
            switch ($info->status) {
                // 未开启状态-》提审
                case 0:
                    $data['status'] = 3; // 未开启提交审核中
                    break;
                case 1:
                    $data['status'] = -1; // 发行中停止后无效
                    break;
                case 4:
                    $data['status'] = 1;  // 审核通过后确认发行
                    break;
                default:
                    // code...
                    break;
            }
            $res = \app\common\model\CouponIssue::update($data, ['id'=>$id]);
            if(!$res){
                $this->error('操作失败');
            }
            $this->success('操作成功', 'index');
        }
    }

    // 消费券创建下一批次  2022-08-28
    public function next($id){
        // 查询消费券当前父子级
        $model        = '\app\common\model\\' . $this->modelName;
        $couponissue  = $model::edit($id)->toArray();
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            $where['id'] = $data['id'];
            if($id!=$where['id']){
                $this->error('数据异常');
            }
            // 检查当前主键ID是否有子级
            $pidInfo  = $model::where('pid',$id)->find();
            if($pidInfo){
                $this->error('已经存在子级'.$pidInfo['id']);
            }
            // 剔除主键
            unset($couponissue['id']);
            // 复制数据
            $newData = $couponissue;
            // 生成唯一编号
            $newData['uuno'] = strtoupper(gen_uuid());
            // 将上一批次剩余数量添加到本次投放数量
            // 计划任务去执行
            $newData['total_count'] = $newData['remain_count'] = $data['total_count'];

            // 父级ID处理
            $newData['pid'] = $data['id'];
            $newData['create_time'] = time();
            $newData['update_time'] = time();
            // 表单提交数据处理
            $newData['is_rollback'] = $data['is_rollback'];
            $newData['coupon_title'] = $data['coupon_title'];
            $newData['coupon_price'] = $data['coupon_price'];
            $newData['status'] = 0; // 未发行状态
            // 是否限时
            if($data['limit_time'] == 1){
                $coupontimelist = explode(' - ',$data['coupon_time_limit']);
                unset($data['coupon_time_limit']);
                $newData['start_time'] = strtotime($coupontimelist[0]);
                $newData['end_time'] = strtotime($coupontimelist[1]);
            }else{
                unset($data['coupon_time_limit']);
                $newData['start_time'] = 0;
                $newData['end_time'] = 0;
            }
            $newData['limit_time'] = $data['limit_time'];

            // 是否永久有效
            if($data['is_permanent'] == 2){
                $coupon_time = explode(' - ',$data['coupon_time']);
                unset($data['coupon_time']);
                $newData['coupon_time_start'] = strtotime($coupon_time[0]);
                $newData['coupon_time_end'] = strtotime($coupon_time[1]);
            }else{
                unset($newData['coupon_time']);
                $newData['coupon_time_start'] = 0;
                $newData['coupon_time_end'] = 0;
            }

            if($data['is_permanent'] == 3 && $data['day']<=0){
                $this->error('有效期天数错误');
            }

            $newData['is_permanent'] = $data['is_permanent'];
            $newData['day'] = $data['day'];
            // 2023-03-17 下一批冗余数据归0
            $newData['rollback_num_extend'] = 0;
            $newData['rollback_num'] = 0;
            $newData['provide_count'] = 0;
            
            $result = $this->validate($newData,'CouponIssue');
            if (true !== $result) {
                $this->error($result);
            }
            $model = '\app\common\model\\' . $this->modelName;
            $result = $model::addPost($newData);
            if ($result['error']) {
                $this->error($result['msg']);
            } else {
                $this->success($result['msg'], 'index');
            }
        }
        
        View::assign(['couponissue' => $couponissue]);
        return View::fetch('coupon/next');
    }

    // 查看
    public function see($id){
        // 查询消费券详情
        $model        = '\app\common\model\\' . $this->modelName;
        $couponissue  = $model::edit($id)->toArray();

        //获得核销规则
        $use_condition_id = '0';
        if ($couponissue['use_type'] == 1) {
            $CouponReceiveCondition = \app\common\model\CouponReceiveCondition::field('condition_id')
                ->where('coupon_id', $id)
                ->select()
                ->toArray();
            if (!empty($CouponReceiveCondition)) {
                $CouponReceiveConditionArr = array_column($CouponReceiveCondition, 'condition_id');
                $use_condition_id = implode(",", $CouponReceiveConditionArr);
            }
        }
        $couponissue['use_condition_id'] = $use_condition_id;

        View::assign(['couponissue' => $couponissue]);

        // 获得核销商户列表
        $WriteOffSellerList = \app\common\model\Seller::field('id, nickname')
            ->where('class_id',7)
            ->where('nickname','<>', '')
            ->order('class_id asc,create_time desc')
            ->select()
            ->toArray();
        View::assign(['write_off_seller_list' => $WriteOffSellerList]);

        // 消费券分类
        $CouponClass = \app\common\model\CouponClass::field('id, title')
            ->order('sort asc')
            ->select()
            ->toArray();
        View::assign(['list' => $CouponClass]);
        $TicketCategory = \app\common\model\TicketCategory::field('id, status')
            ->order('sort asc')
            ->select()
            ->toArray();
        View::assign(['ticket_class' => $TicketCategory]);
        // 审核记录
        $ExamineRecord = \app\common\model\ExamineRecord::where('sid',$id)->where('tags',3)
            ->order('create_time desc')
            ->with(['admin','authGroup'])
            ->select();
        View::assign(['ExamineRecord' => $ExamineRecord]);
        // 构建页面
        return View::fetch('coupon/see');
    }

    /**
     * [check_coupon 消费券审核]
     * @return   [type]            [消费券审核]
     * @api      {get}
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-03-22
     * @LastTime 2023-03-22
     * @version  [1.0.0]
     */
    public function check_coupon(){
        $param = Request::param();
        // 查询消费券详情
        $model        = '\app\common\model\\' . $this->modelName;
        $couponissue  = $model::edit($param['id'])->toArray();

        //获得核销规则
        $use_condition_id = '0';
        if ($couponissue['use_type'] == 1) {
            $CouponReceiveCondition = \app\common\model\CouponReceiveCondition::field('condition_id')
                ->where('coupon_id', $param['id'])
                ->select()
                ->toArray();
            if (!empty($CouponReceiveCondition)) {
                $CouponReceiveConditionArr = array_column($CouponReceiveCondition, 'condition_id');
                $use_condition_id = implode(",", $CouponReceiveConditionArr);
            }
        }
        $couponissue['use_condition_id'] = $use_condition_id;

        View::assign(['couponissue' => $couponissue]);

        // 获得核销商户列表
        $WriteOffSellerList = \app\common\model\Seller::field('id, nickname')
            ->where('class_id',7)
            ->where('nickname','<>', '')
            ->order('class_id asc,create_time desc')
            ->select()
            ->toArray();
        View::assign(['write_off_seller_list' => $WriteOffSellerList]);

        // 消费券分类
        $CouponClass = \app\common\model\CouponClass::field('id, title')
            ->order('sort asc')
            ->select()
            ->toArray();
        View::assign(['list' => $CouponClass]);
        $TicketCategory = \app\common\model\TicketCategory::field('id, status')
            ->order('sort asc')
            ->select()
            ->toArray();
        View::assign(['ticket_class' => $TicketCategory]);
        // 审核记录
        $ExamineRecord = \app\common\model\ExamineRecord::where('sid',$param['id'])->where('tags',3)
            ->order('create_time desc')
            ->with(['admin','authGroup'])
            ->select();
        View::assign(['ExamineRecord' => $ExamineRecord]);
        return View::fetch('coupon/check_coupon');
    }

    /**
     * [check_coupon 消费券审核]
     * @return   [type]            [消费券审核]
     * @api      {get}
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-03-22
     * @LastTime 2023-03-22
     * @version  [1.0.0]
     */
    public function check_coupon_post(){
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');

            $where['id'] = $data['id'];

            /*if($data['status']==3 && empty($data['remark'])){
                $this->error('请填写审核备注');
            }*/

            $logData['tags']    = 3;
            $logData['sid']     = $data['id'];
            $logData['step']    = $data['status'];
            $logData['remarks']  = $data['remark'] ? $data['remark'] : '';
            $logData['group_id'] = session('admin.group_id');
            $logData['admin_id'] = session('admin.id');
            $logData['create_time']  = time();
            // 记录审核记录
            \app\common\model\ExamineRecord::strict(false)->insertGetId($logData);

            // 修改消费券信息
            $upData['update_time'] = time();
            $upData['status'] = $data['status'];
            \app\common\model\CouponIssue::update($upData, $where);
            $this->success('审核成功!', 'index');
        }
    }
}
