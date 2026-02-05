<?php
/**
 * 通知公告控制器
 * @author slomoo <1103398780@qq.com> 2022/07/21
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;

class Notice extends Base
{
    // 验证器
    protected $validate = 'Notice';

    // 当前主表
    protected $tableName = 'notice';

    // 当前主模型
    protected $modelName = 'Notice';

    // 添加保存
    public function addPost()
    {
        if (Request::isPost()) {
            $data = MakeBuilder::changeFormData(Request::except(['file'], 'post'), $this->tableName);

            // xss过滤
            $data['title']      = removeXSS(filterText($data['title']));
            $data['keywords']   = removeXSS(filterText($data['keywords']));
            $data['description']= removeXSS(filterText($data['description']));
            $data['template']   = removeXSS(filterText($data['template']));
            $data['url']        = removeXSS(filterText($data['url']));
            $data['template']   = removeXSS(filterText($data['template']));
            $data['content'] = SafeFilter($data['content']);

            $result = $this->validate($data, $this->modelName);
            if (true !== $result) {
                $this->error($result);
            }
            
            $model  = '\app\common\model\\' . $this->modelName;
            
            $result = $model::addPost($data);
            if ($result['error']) {
                $this->error($result['msg']);
            } else {
                $this->success($result['msg'], 'index');
            }
        }
    }

    // 编辑保存
    public function editPost()
    {
        if (Request::isPost()) {
            $data = MakeBuilder::changeFormData(Request::except(['file'], 'post'), $this->tableName);

            // xss过滤
            $data['title']      = removeXSS(filterText($data['title']));
            $data['keywords']   = removeXSS(filterText($data['keywords']));
            $data['description']= removeXSS(filterText($data['description']));
            $data['template']   = removeXSS(filterText($data['template']));
            $data['url']        = removeXSS(filterText($data['url']));
            $data['template']   = removeXSS(filterText($data['template']));
            //$data['content'] = SafeFilter($data['content']);
            $data['content'] = $data['content'];

            $result = $this->validate($data, $this->modelName);
            if (true !== $result) {
                $this->error($result);
            }
            
            $model  = '\app\common\model\\' . $this->modelName;
            
            $result = $model::editPost($data);
            if ($result['error']) {
                $this->error($result['msg']);
            } else {
                $this->success($result['msg'], 'index');
            }
        }
    }
}
