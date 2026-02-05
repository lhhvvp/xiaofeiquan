<?php
namespace app\travel\facade;

use think\Facade;

class ThinkAddons extends Facade
{
    protected static function getFacadeClass()
    {
        return 'app\admin\service\ThinkAddons';
    }
}