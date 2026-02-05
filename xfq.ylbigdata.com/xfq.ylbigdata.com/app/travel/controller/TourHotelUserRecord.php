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
// 引入导出的命名空间
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
            if (isset($param['spot_name'])  && $param['spot_name']!='') { 
                $map[] = ['spot_name','like','%'.$param['spot_name'].'%'];
            }

            // 旅行社商户ID
            $mid = session()['travel']['id'];
            // 查询所有团ID
            $ids = Db::name('tour')->where('mid',$mid)->column('id');
            if($ids){
                $map[] = ['tid','in',$ids];
            }
            $model  = '\app\common\model\\' . 'TourHotelUserRecord';
            $list = $model::getList($map, $this->pageSize, [$orderByColumn => $isAsc]);
            // 2023-07-20 获取团下的导游，讲列表计调人改为导游
            foreach ($list['data'] as $key => $value) {
                $list['data'][$key]['guide_info'] = \app\common\model\Guide::where('tid',$value['tour']['id'])->find();
            }
            return $list;
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

    // 导出
    public function export()
    {
        $tableNam = 'tour_hotel_user_record'; $moduleName = 'TourHotelUserRecord';
        ob_end_clean();
        // 获取主键
        $pk = \app\common\facade\MakeBuilder::getPrimarykey($tableNam);
        // 获取列表数据
        $columns = \app\common\facade\MakeBuilder::getListColumns($tableNam);
        // 搜索
        $where         = \app\common\facade\MakeBuilder::getListWhere($tableNam);
        // 2023-03-27 旅行社导出酒店打卡用户记录
        $tid = \app\common\model\Tour::where('mid',session('travel.id'))->column('id');
        $where[] = ['tid','in',$tid];
        $orderByColumn = \think\facade\Request::param('orderByColumn') ?? $pk;
        $isAsc         = \think\facade\Request::param('isAsc') ?? 'desc';
        $model         = '\app\common\model\\' . $moduleName;
        // 获取要导出的数据
        $list = $model::getList($where, 0, [$orderByColumn => $isAsc]);
        // 初始化表头数组
        $str         = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z','AA','AB','AC','AD'];
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        foreach ($columns as $k => $v) {
            $sheet->setCellValue($str[$k] . '1', $v['1']);
        }
        $list = isset($list['total']) && isset($list['per_page']) && isset($list['data']) ? $list['data'] : $list;
        foreach ($list as $key => $value) {
            foreach ($columns as $k => $v) {
                // 修正字典数据
                /*if (isset($v[4]) && is_array($v[4]) && !empty($v[4])) {
                    $value[$v['0']] = $v[4][$value[$v['0']]];
                }*/
                $sheet->setCellValue($str[$k] . ($key + 2), $value[$v['0']]);
            }
        }
        $moduleName = \app\common\model\Module::where('table_name', $tableNam)->value('module_name');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $moduleName . '导出' . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }
}
