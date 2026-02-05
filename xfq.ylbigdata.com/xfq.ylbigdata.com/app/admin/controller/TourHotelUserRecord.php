<?php
/**
 * 游客酒店打卡记录控制器
 * @author slomoo <1103398780@qq.com> 2022/08/31
 */
namespace app\admin\controller;

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
            $where = [];
            if (isset($param['name']) && $param['name'] != '') {
                $where[] = ['t.name', 'like', '%' . $param['name'] . '%'];
            }
            if (isset($param['uname']) && $param['uname'] != '') {
                $where[] = ['u.name', 'like', '%' . $param['uname'] . '%'];
            }
            if (isset($param['address']) && $param['address'] != '') {
                $where[] = ['re.address', 'like', $param['address']];
            }
            if (isset($param['spot_name']) && $param['spot_name'] != '') {
                $where[] = ['re.spot_name', '=', $param['spot_name']];
            }
            $order = "re.id desc";
            $list = Db::name($this->tableName)
                ->alias('re')
                ->leftJoin('tour t','re.tid = t.id')
                ->leftJoin('users u','u.id = re.uid')
                ->field("re.*,t.name,t.term,t.numbers,t.guide_name,t.planner,t.mobile,u.name as uname")
                ->where($where)
                ->order($order)
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $this->pageSize,
                ]);
            return ['total' => $list->total(), 'current_page' => $list->currentPage(), 'last_page' => $list->lastPage(), 'data' => $list->items()];
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

    // 酒店打卡记录
    public function hotelLog()
    {
        $param = Request::param();
        if(isset($param['date']) && $param['date']){
            $date = explode(" - ", trim($param['date']));
        }else{
            $date = date("Y-m-d")." - ".date("Y-m-d");
            $date = explode(" - ", trim($date));
        }

        if (!is_array($date)) {
            $this->error('日期格式错误');
        }

        $start_time = strtotime($date[0]."00:00:00");
        $end_time   = strtotime($date[1]."23:59:59");

        $list = Db::name('tour_hotel_sign')
            ->alias('a')
            ->leftJoin('tour t','t.id = a.tid')
            ->field('a.id,t.name,t.numbers,t.term,a.hotel_name,a.latitude,a.longitude,a.create_time')
            ->where('a.create_time', 'between', [$start_time, $end_time])
            ->select()
            ->toArray();

        View::assign(['list' => $list]);
        return View::fetch();
    }
}
