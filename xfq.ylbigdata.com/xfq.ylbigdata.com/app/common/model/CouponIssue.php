<?php
/**
 * 消费券表模型
 * @author slomoo <1103398780@qq.com> 2022/07/22
 */
namespace app\common\model;

// 引入框架内置类
use think\Model;
use think\facade\Request;

// 引入构建器
use app\common\facade\MakeBuilder;
use think\facade\Db;
class CouponIssue extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 在更新操作之前触发
    public  function beforeUpdate($data)
    {
        if ($data['provide_count'] > $data['total_count']) {
            // 如果field1和field2相等，抛出异常禁止更新操作
            throw new \Exception('领取失败');
        }
    }
    
    // 一对一获取所属模块
    public function couponClass()
    {
        return $this->belongsTo('CouponClass', 'cid');
    }

    /**
     * 用户是否拥有
     * @return \think\model\relation\HasOne
     */
    public function used()
    {
        return $this->hasOne('CouponIssueUser', 'issue_coupon_id','id')->field('issue_coupon_id');
    }

    /**
     * 获取消费券详情
     * @param int $id
     * @return array|\think\Model|null
     */
    public function getInfo(int $id)
    {
        /*return self::where('status', 1)
            ->where('id', $id)
            ->where('is_del', 0)
            ->where('remain_count > 0 OR limit_time = 1')
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('start_time', '<', time())->where('end_time', '>', time());
                })->whereOr(function ($query) {
                    $query->where('start_time', 0)->where('end_time', 0);
                });
            })->find();*/

        // 2023-03-02 根据iD查询消费券
        return self::find($id);
    }

    public function remainCount(int $id)
    {
        return self::where('id',$id)->column('remain_count,provide_count');
    }

    // 检查消费券是否过期
    public static function CouponIssueCheck($issue_coupon_ids)
    {
        // 非永久 限时 当前时间大于有效期结束时间 = 证明票券过期
        // 2023-03-10 根据时段获取需要修改过期的券
        /*$list = self::whereIn('id', $issue_coupon_ids)
            ->where('is_permanent',2)
            ->where('coupon_time_end', '<=', time())
            ->where('coupon_time_end', '<>', 0)
            ->column('id');
        // 修改领取记录表所有过期的票券的状态
        if($list){
            Db::name('coupon_issue_user')->whereIn('issue_coupon_id',$list)->update(['status'=>2]);
        }*/

        // 2023-03-10 根据券的到期时间 修改领取记录的状态
        Db::name('coupon_issue_user')
        ->whereIn('issue_coupon_id',$issue_coupon_ids)
        ->where('expire_time', '<=', time())
        ->where('status',0)
        ->update(['status'=>2]);
    }

    // 旅行团添加时选择旅行社消费券调用该列表
    public static function getListIssue(array $where = [], int $pageSize = 0, array $order = ['sort', 'id' => 'desc'])
    {
        $model = new static();
        $model = $model->alias($model->getName());

        // 获取with关联
        $moduleId  = \app\common\model\Module::where('model_name', $model->getName())->value('id');
        $fileds    = \app\common\model\Field::where('module_id', $moduleId)
            ->select()
            ->toArray();
        $listInfo  = [];  // 字段根据关联信息重新赋值
        $withInfo  = [];  // 模型关联信息(用于设置关联预载入)
        $fieldInfo = [];  // 字段包含.的时候从关联模型中获取数据
        foreach ($fileds as $filed) {
            // 数据源为模型数据时设置关联信息
            if ($filed['data_source'] == 2) {
                $listInfo[] = [
                    'field'          => $filed['field'],                   // 字段名称
                    'relation_model' => lcfirst($filed['relation_model']), // 关联模型
                    'relation_field' => $filed['relation_field'],          // 展示字段
                    'type'           => $filed['type'],                    // 字段类型
                    'setup'          => string2array($filed['setup']),     // 字段其他设置
                ];
                $withInfo[] = lcfirst($filed['relation_model']);
            }
            // 字段包含.的时候从关联模型中获取数据
            if (strpos($filed['field'], '.') !== false) {
                // 拆分字段名称为数组
                $filedArr    = explode('.', $filed['field']);
                $fieldInfo[] = [
                    'field'          => $filed['field'],       // 字段名称
                    'relation_model' => lcfirst($filedArr[0]), // 关联模型
                    'relation_field' => $filedArr[1],          // 展示字段
                    'type'           => $filed['type'],        // 字段类型
                ];
            }
        }

        // 关联预载入
        if ($withInfo) {
            $model = $model->with($withInfo);
        }

        // 筛选条件
        if ($where) {
            $whereNew = [];
            $whereHas = [];
            foreach ($where as $v) {
                if (strpos($v[0], '.') === false) {
                    $whereNew[] = $v;
                } else {
                    // 关联模型搜索
                    $filedArr = explode('.', $v[0]);

                    $whereHas[lcfirst($filedArr[0])][] = [
                        'field'        => $filedArr[1],
                        'field_option' => $v[1],
                        'field_value'  => $v[2],
                    ];
                }
            }
            // 关联模型搜索
            if ($whereHas) {
                foreach ($whereHas as $k => $v) {
                    $model = $model->hasWhere($k, function ($query) use ($v) {
                        foreach ($v as $vv) {
                            $query->where($vv['field'], $vv['field_option'], $vv['field_value']);
                        }
                    });
                }
            }
            // 当前模型搜索
            if ($whereNew) {
                $model = $model->where($where)->where(function ($query) {
                    $query->where(function ($query) {
                        $query->where('start_time', '<', time())->where('end_time', '>', time());
                    })->whereOr(function ($query) {
                        $query->where('start_time', 0)->where('end_time', 0);
                    });
                });
            }
        }

        // 查询/分页查询
        if ($pageSize) {
            $list = $model->order($order)
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $pageSize,
                ]);
        } else {
            $list = $model->order($order)
                ->select();
        }
        foreach ($list as $k => $v) {
            // 字段根据关联信息重新赋值(多级联动需另行处理)
            foreach ($listInfo as $vv) {
                if ($vv['type'] == 'linkage') {
                    // 拆分字段其他设置为数组
                    $setupFields = explode(',', $vv['setup']['fields']);
                    // 根据末级ID获取每级的联动数据
                    $levelData = getLinkageListData(ucfirst($vv['relation_model']), $v[$vv['field']], $setupFields[0], $setupFields[1], $setupFields[2]);
                    $levelData = array_reverse($levelData); // 以相反的元素顺序返回数组
                    $str       = '';                        // 要转换成的数据
                    foreach ($levelData as $level) {
                        $str .= $level[$setupFields[1]] . '-';
                    }
                    $list[$k][$vv['field']] = rtrim($str, '-');
                } else {
                    // 多选情况
                    if (strpos($v[$vv['field']], ',') !== false) {
                        $hasManyModel = '\app\common\model\\' . $vv['relation_model'];
                        $hasManyPk    = (new $hasManyModel())->getPk();
                        $hasManys     = $hasManyModel::where($hasManyPk, 'in', $v[$vv['field']])->column($vv['relation_field']);
                        if ($hasManys) {
                            $list[$k][$vv['field']] = implode(',', $hasManys);
                        }
                    } else {
                        $list[$k][$vv['field']] = !empty($v->{$vv['relation_model']}) ? $v->{$vv['relation_model']}->getData($vv['relation_field']) : '';
                    }
                }
            }
            // 字段包含.的时候从关联模型中获取数据
            foreach ($fieldInfo as $vv) {
                $list[$k][$vv['field']] = !empty($v->{$vv['relation_model']}) ? $v->{$vv['relation_model']}->getData($vv['relation_field']) : '';
            }
        }

        return MakeBuilder::changeTableData($list, $model->getName());
    }

    // 重写父类addPost方法，新增优惠卷操作
    public static function addPost($data)
    {
        try {
            if ($data) {
                foreach ($data as $k => $v) {
                    if (is_array($v)) {
                        $data[$k] = implode(',', $v);
                    }
                }
            }
            $result = self::create($data);
            if ($result) {
                return ['error' => 0, 'msg' => '添加成功', 'id' => $result->id];
            } else {
                return ['error' => 1, 'msg' => '添加失败'];
            }
        } catch (\Exception $e) {
            return ['error' => 1, 'msg' => $e->getMessage()];
        }
    }

    //根据用户id、卷id获得规则信息、打卡信息、是否可领卷
    public function getRecordConditionStatus($uid, $couponId)
    {
        //获得领取规则
        $couponReceiveCondition = \app\common\model\CouponReceiveCondition::where('coupon_id', $couponId)
            ->select()
            ->toArray();
        $conditionIdArr = array_column($couponReceiveCondition, 'condition_id');

        $condition = \app\common\model\CouponConditionDetails::where('id', 'in', $conditionIdArr)
            ->with(['SellerClass'])
            ->select()
            ->toArray();

        $conditionArr = array_column($condition, 'mark_num', 'class_id');

        //用户打卡商户类别数据
        $userRecordClassData = \app\common\model\SellerMarkQcUserRecord::field('count(seller_id) as num, class_id')
            ->where('uid', $uid)
            ->where('coupon_id', $couponId)
            ->group('class_id')
            ->select()
            ->toArray();
        $recordArr = array_column($userRecordClassData, 'num', 'class_id');

        //判断用户是否满足领取规则：循环规则，查看是否存在不满足规则的打卡
        $can_receive = true;
        foreach ($conditionArr as $class_id => $mark_num) {
            if (!isset($recordArr[$class_id]) || ($recordArr[$class_id] < $mark_num)) {
                $can_receive = false;
                break;
            }
        }

        //用户打卡数据
        $userRecordData = \app\common\model\SellerMarkQcUserRecord::field('id, seller_id, class_id, create_time')
            ->where('uid', $uid)
            ->with(['Seller'])
            ->select()
            ->toArray();

        return [
            'can_receive' => $can_receive,
            'condition' => $condition,
            'user_record_data' => $userRecordData,
        ];
    }
}