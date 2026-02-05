<?php
/**
 * 消费券规则控制器
 * @author xuemm 2024/01/08
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\View;

class CouponConditionDetails extends Base
{
    // 验证器
    protected $validate = 'CouponConditionDetails';

    // 当前主表
    protected $tableName = 'coupon_condition_details';

    // 当前主模型
    protected $modelName = 'CouponConditionDetails';

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
            if (!empty($param['class_id'])) {
                $where[] = ['class_id','=',$param['class_id']];
            }
            $model  = '\app\common\model\\' . $this->modelName;
            return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
        }

        // 商户分类
        $SellerClass = \app\common\model\SellerClass::field('id, class_name')
            ->order('sort asc')
            ->select()
            ->toArray();
        View::assign(['class_list'=>$SellerClass]);

        return View::fetch('coupon/conditionList');
    }

    // 添加规则
    public function addCondition()
    {
        // 商户分类
        $SellerClass = \app\common\model\SellerClass::field('id, class_name')
            ->order('sort asc')
            ->select()
            ->toArray();
        View::assign(['class_list' => $SellerClass]);
        return View::fetch('coupon/addCondition');
    }

    // 新增保存
    public function addPost()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');

            // xss过滤
            $data['mark_num'] = removeXSS(filterText($data['mark_num']));

            $result = $this->validate($data,'CouponConditionDetails');
            if (true !== $result) {
                $this->error($result);
            }

            // 检查数据是否存在
            $model = '\app\common\model\\' . $this->modelName;
            $uname = $model::where(['class_id' => $data['class_id'], 'mark_num' => $data['mark_num']])->find();
            if($uname){
                $this->error('该规则已存在，请直接选择');
            }

            $result = $model::addPost($data);
            if ($result['error']) {
                $this->error($result['msg']);
            } else {
                $this->success($result['msg'], 'index');
            }
        }
    }
}
