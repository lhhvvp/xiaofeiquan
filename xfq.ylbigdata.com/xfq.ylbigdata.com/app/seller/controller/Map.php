<?php
/**
 * 商户管理控制器
 * @author slomoo <1103398780@qq.com> 2022/07/22
 */

namespace app\seller\controller;

// 引入框架内置类
use app\seller\controller\Base;

use think\facade\View;
class Map extends Base
{
    // 票种分类列表
    public function qqmap()
    {
        return View::fetch();
    }
    public function amap(){
        return View::fetch();
    }
}
