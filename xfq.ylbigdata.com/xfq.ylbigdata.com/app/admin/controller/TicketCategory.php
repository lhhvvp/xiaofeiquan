<?php
/**
 * 门票分类控制器
 * @author slomoo <1103398780@qq.com> 2023/06/28
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\View;

class TicketCategory extends Base
{
    // 验证器
    protected $validate = 'TicketCategory';

    // 当前主表
    protected $tableName = 'ticket_category';

    // 当前主模型
    protected $modelName = 'TicketCategory';

    /**
     * [index 商户门票分类]
     * @return   [type]            [商户门票分类]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-06-28
     * @LastTime 2023-06-28
     * @version  [1.0.0]
     */
    public function index()
    {
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();
            $orderByColumn = $param['orderByColumn'] ?? 'id';
            $isAsc         = $param['isAsc'] ?? 'desc';
            $where = [];
            if (isset($param['seller_id']) && $param['seller_id']!='') {
                $where[] = ['seller_id','=',$param['seller_id']];
            }
            if (isset($param['title']) && $param['title']!='') {
                $where[] = ['title','like',"%".$param['title']."%"];
            }
            if (isset($param['status']) && $param['status']!='') {
                $where[] = ['status','=',$param['status']];
            }
            $model  = '\app\common\model\\' . $this->modelName;
            $list = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
            return $list;
        }
        return View::fetch('ticket_category/index');
    }

    // 添加
    public function add()
    {
        return View::fetch('ticket_category/add');
    }

    // 修改
    public function edit($id)
    {
        $model = '\app\common\model\\' . $this->modelName;
        $info  = $model::edit($id)->toArray();
        View::assign(['info' => $info]);
        return View::fetch('ticket_category/edit');
    }
}
