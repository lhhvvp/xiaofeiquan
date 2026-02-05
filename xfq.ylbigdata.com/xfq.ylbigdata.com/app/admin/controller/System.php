<?php
/**
 * 系统设置控制器
 * @author slomoo <slomoo@aliyun.com> 2022-03-05
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\View;

class System extends Base
{
    // 验证器
    protected $validate = 'System';

    // 当前主表
    protected $tableName = 'system';

    // 当前主模型
    protected $modelName = 'System';


    // 隐私服务  协议政策
    public function setPolicy()
    {
        $model = '\app\common\model\\' . $this->modelName;
        // 获取商品列表
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            $result = $model::editPost($data);
            if ($result['error']) {
                $this->error($result['msg']);
            } else {
                $this->success($result['msg'], 'setPolicy');
            }
        }
        $rs = $model::find(1);
        View::assign(['system' => $rs]);
        return View::fetch();
    }

    // 隐私服务  协议政策
    public function setTour()
    {
        $model = '\app\common\model\\' . $this->modelName;
        // 获取商品列表
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');
            $result = $model::where('id',1)->data(['tour_status'=>$data['tour_status'],'update_time'=>time()])->update();
            if ($result > 1) {
                $this->error('操作失败');
            } else {
                $this->success('操作成功', 'setTour');
            }
        }
        $rs = $model::find(1);
        View::assign(['system' => $rs]);
        return View::fetch();
    }
}
