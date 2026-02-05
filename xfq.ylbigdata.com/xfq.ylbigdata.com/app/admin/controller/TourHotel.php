<?php
/**
 * 旅行团酒店关联表控制器
 * @author slomoo <1103398780@qq.com> 2022/10/17
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;

class TourHotel extends Base
{
    // 验证器
    protected $validate = 'TourHotel';

    // 当前主表
    protected $tableName = 'tour_hotel';

    // 当前主模型
    protected $modelName = 'TourHotel';
}
