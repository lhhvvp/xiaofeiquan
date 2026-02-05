<?php
/**
 * 旅行团用户消费券领取记录模型
 * @author slomoo <1103398780@qq.com> 2022/08/21
 */
namespace app\common\model;
// 引入框架内置类
use think\facade\Db;
use think\Model;
use think\facade\Event;
use think\facade\Request;
use think\facade\Session;
// 引入构建器
use app\common\facade\MakeBuilder;
class TourIssueUser extends Base
{
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 获取列表
    public static function getRewriteList(array $where = [], int $pageSize = 0, array $order = ['sort', 'id' => 'desc'],$whereUid)
    {
        if ($pageSize) {
            $list = self::where($where)
                ->hasWhere('users',$whereUid)
                ->with(['users','tour','couponClass'])
                ->order($order)
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $pageSize,
                ]);
        } else {
            $list = self::where($where)->hasWhere('users',$whereUid)->with(['users','tour','couponClass'])
                ->order($order)
                ->select();
        }
        return MakeBuilder::changeTableData($list, 'TourIssueUser');
    }
    
    public function users()
    {
        return $this->belongsTo('Users', 'uid')->field('id,sex,email,last_login_time,last_login_ip,mobile,type_id,status,create_time,update_time,name,headimgurl,nickname,salt,uuid,openid');
    }
    public function tour()
    {
        return $this->belongsTo('Tour', 'tid');
    }
    public function couponClass()
    {
        return $this->belongsTo('CouponClass', 'issue_coupon_class_id');
    }
    
    // 获取列表
    public static function getList(array $where = [], int $pageSize = 0, array $order = ['sort', 'id' => 'desc'])
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
                $model = $model->where($where);
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
}