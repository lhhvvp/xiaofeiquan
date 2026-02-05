<?php
namespace app\seller\model;

use think\facade\Session;
use think\Model;
use think\facade\Db;
class Base extends Model
{
    // 获取左侧主菜单
    public static function getMenus()
    {
        if (!Session::has('seller')) {
            return redirect((string)url('Login/index'));
        }
        // 2023-09-14 适配达梦数据库
        $condition = env('database.type') === 'dm' ? 'condition' : '`condition`';
        
        $authRule = \app\common\model\SellerAuthRule::where('status', 1)
            ->where('','exp', 'FIND_IN_SET('.Session::get('seller.class_id').', '.$condition.')')
            ->order('sort asc')
            ->select()
            ->toArray();
        $menus = [];
        // 查找一级
        foreach ($authRule as $key => $val) {
            $authRule[$key]['href'] = (string)url($val['name']);
            if ($val['pid'] == 0) {
                $menus[] = $val;
            }
        }
        // 查找二级
        foreach ($menus as $k => $v) {
            $menus[$k]['children'] = [];
            foreach ($authRule as $kk => $vv) {
                if ($v['id'] == $vv['pid']) {
                    $menus[$k]['children'][] = $vv;
                }
            }
        }
        // 查找三级
        foreach ($menus as $k => $v) {
            if ($v['children']) {
                // 循环二级
                foreach ($v['children'] as $kk => $vv) {
                    $menus[$k]['children'][$kk]['children'] = [];
                    foreach ($authRule as $kkk => $vvv) {
                        if ($vv['id'] == $vvv['pid']) {
                            $menus[$k]['children'][$kk]['children'][] = $vvv;
                        }
                    }
                }
            }
        }
        return $menus;
        /*return '[
                    {
                        "id": 1,
                        "pid": 0,
                        "name": "Data",
                        "title": "结算管理",
                        "icon": "fa fa-cogs",
                        "children": [
                            {
                                "id": 4,
                                "pid": 1,
                                "name": "Data/index",
                                "title": "散客结算申请",
                                "icon": "fa fa-cog",
                                "href": "/seller/Data/index.html",
                                "children": []
                            },{
                                "id": 7,
                                "pid": 1,
                                "name": "Data/tour",
                                "title": "团体结算申请",
                                "icon": "fa fa-cog",
                                "href": "/seller/Data/tour.html",
                                "children": []
                            },{
                                "id": 8,
                                "pid": 1,
                                "name": "Data/apply",
                                "title": "结算记录",
                                "icon": "fa fa-cog",
                                "href": "/seller/Data/apply.html",
                                "children": []
                            }
                        ]
                    },
                    {
                        "id": 2,
                        "pid": 0,
                        "name": "Writeoff",
                        "title": "核销管理",
                        "icon": "fa fa-user",
                        "children": [
                            {
                                "id": 5,
                                "pid": 2,
                                "name": "Writeoff/user",
                                "title": "人员管理",
                                "icon": "fa fa-user",
                                "href": "/seller/Writeoff/user.html",
                                "children": []
                            },
                            {
                                "id": 6,
                                "pid": 2,
                                "name": "Writeoff/log",
                                "title": "核销记录",
                                "icon": "",
                                "href": "/seller/Writeoff/log.html",
                                "children": []
                            },
                            {
                                "id": 9,
                                "pid": 2,
                                "name": "Writeoff/tourlog",
                                "title": "团队核销记录",
                                "icon": "",
                                "href": "/seller/Writeoff/tourlog.html",
                                "children": []
                            }
                        ]
                    }
                ]';
                
            
                
                    ,
                    {
                        "id": 3,
                        "pid": 0,
                        "name": "Ticket",
                        "title": "门票管理",
                        "icon": "fa fa-user",
                        "children": [
                            {
                                "id": 7,
                                "pid": 3,
                                "name": "Ticket/set",
                                "title": "门票设置",
                                "icon": "fa fa-user",
                                "href": "/seller/Ticket/set.html",
                                "children": []
                            },
                            {
                                "id": 8,
                                "pid": 3,
                                "name": "Ticket/order",
                                "title": "门票订单",
                                "icon": "fa fa-user",
                                "href": "/seller/Ticket/order.html",
                                "children": []
                            }
                        ]
                    }

                    */
    }
}