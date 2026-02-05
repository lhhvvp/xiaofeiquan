<?php
/**
 * 游客酒店打卡记录控制器
 * @author slomoo <1103398780@qq.com> 2022/08/31
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
use think\facade\Db;
class TourHotelUserRecord extends Base
{
    // 验证器
    protected $validate = 'TourHotelUserRecord';

    // 当前主表
    protected $tableName = 'tour_hotel_user_record';

    // 当前主模型
    protected $modelName = 'TourHotelUserRecord';

    // 所有打卡记录
    public function index()
    {
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();

            $orderByColumn = $param['orderByColumn'] ?? 'id';
            $isAsc         = $param['isAsc'] ?? 'desc';
            $map = [];
            if (isset($param['name'])  && $param['name']!='') { 
                $map[] = ['spot_name','like','%'.$param['name'].'%'];
            }

            // 旅行社商户ID
            $mid = session()['travel']['id'];
            // 查询所有团ID
            $ids = Db::name('tour')->where('mid',$mid)->column('id');
            if($ids){
                $map[] = ['tid','in',$ids];
            }
            $model  = '\app\common\model\\' . 'TourHotelUserRecord';
            return $model::getList($map, $this->pageSize, [$orderByColumn => $isAsc]);
        }
        return View::fetch();
    }

    // 查看打卡详情
    public function detail($id)
    {
        $param = Request::param();

        $orderByColumn = $param['orderByColumn'] ?? 'id';
        $isAsc         = $param['isAsc'] ?? 'desc';

        $map = [];
        $id   = $param['id'] ? $param['id'] : $id;
        $map[] = ['id','=',$id];
        $model  = '\app\common\model\\' . 'TourHotelUserRecord';
        $detail = $model::where($map)->with(['tourHotelSign','tour'])->find();
        $detail['images'] = $detail['images'] ? explode(',',$detail['images']) : [];
        View::assign(['detail' => $detail]);
        return View::fetch();
    }
}
