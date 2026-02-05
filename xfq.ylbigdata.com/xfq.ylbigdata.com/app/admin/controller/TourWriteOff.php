<?php
/**
 * 团体券核销记录控制器
 * @author slomoo <1103398780@qq.com> 2022/08/22
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\View;

class TourWriteOff extends Base
{
    // 验证器
    protected $validate = 'TourWriteOff';

    // 当前主表
    protected $tableName = 'tour_write_off';

    // 当前主模型
    protected $modelName = 'TourWriteOff';

    // 列表
    public function index(){
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();

            $orderByColumn = $param['orderByColumn'] ?? 'id';
            $isAsc         = $param['isAsc'] ?? 'desc';
            $where = [];
            if (isset($param['coupon_title'])  && $param['coupon_title']!='') { 
                $where[] = ['coupon_title','like','%'.$param['coupon_title'].'%'];
            }
            if (isset($param['uuno']) && $param['uuno']!='') {
                $where[] = ['uuno','=',$param['uuno']];
            }
            if(@$param['mid']!='')
                $where[] = ['seller.nickname','like',"%".$param['mid']."%"];
            if (@$param['userid']!='') 
                $where[] = ['users.name','=',$param['userid']];

            if(@$param['create_time']!=''){
                $create_time = explode(' 至 ',$param['create_time']);
                $create_time_start = strtotime($create_time[0]);
                $create_time_end = strtotime($create_time[1]."23:59:59");
                $where[] = ['create_time','between',[$create_time_start,$create_time_end]];
            }

            // 读取当前商家的核验人员
            $model  = '\app\common\model\\' . 'TourWriteOff';

            $list = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
            foreach ($list['data'] as $key => $value) {
                $list['data'][$key]['userinfo'] = \app\common\model\Users::where('id',$value['tourIssueUser']['uid'])->find();
            }

            return $list;
        }

        $SellerClass = \app\common\model\Seller::field('id, nickname')
            ->where('status',1)
            ->select()
            ->toArray();
        View::assign(['seller_list' => $SellerClass]);
        return View::fetch();
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
}
