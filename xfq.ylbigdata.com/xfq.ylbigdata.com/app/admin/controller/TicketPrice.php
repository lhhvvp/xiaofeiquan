<?php
/**
 * 控制器
 * @author slomoo <1103398780@qq.com> 2023/07/04
 */
namespace app\admin\controller;

// 引入框架内置类
use app\common\model\ticket\Price as PriceModel;
use app\common\model\ticket\Ticket as TicketModel;
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\View;

class TicketPrice extends Base
{
    // 验证器
    protected $validate = 'TicketPrice';

    // 当前主表
    protected $tableName = 'ticket_price';

    // 当前主模型
    protected $modelName = 'TicketPrice';

    public function index()
    {
        if (Request::isGet()) {

            if(empty($ticket_id = Request::get('ticket_id',''))){
                $this->error('缺少参数！');
            }
            $data['ticket'] = TicketModel::where('id',$ticket_id)->find()->toArray();
            $data['list'] = PriceModel::where('ticket_id',$data['ticket']['id'])->select()->toArray();

            View::assign($data);
            return View::fetch('ticket_price/index');
        } else {
            $param = Request::param();
            $where = [];
            if (isset($param['ticket_id']) && $param['ticket_id'] != '') {
                $where[] = ['ticket_id', '=', $param['ticket_id']];
            }
            if (isset($param['start']) && isset($param['end'])) {
                $where[] = ['date', 'between time',[$param['start'],$param['end']]];
            }
            $list = PriceModel::where($where)
                ->order("date asc")
                ->select()->toArray();
            return $list;
        }
    }
}
