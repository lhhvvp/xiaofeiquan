<?php
namespace app\admin\route;//命名空间路径
use think\facade\Route;//引用门面路由类
 
// 运营端入口
Route::get('operate','login/index');          //内部路由定义路径时不需要加入应用路径
// 文旅端入口
Route::get('supervise','login/supervise');