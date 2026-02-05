<?php
/**
 * 导游生成酒店打卡记录控制器
 * @author slomoo <1103398780@qq.com> 2022/08/31
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;

class TourHotelSign extends Base
{
    // 验证器
    protected $validate = 'TourHotelSign';

    // 当前主表
    protected $tableName = 'tour_hotel_sign';

    // 当前主模型
    protected $modelName = 'TourHotelSign';
}
