<?php
namespace app\travel\model;

use think\facade\Session;
use think\Model;

class Base extends Model
{
    // 获取左侧主菜单
    public static function getMenus()
    {
        return '[
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
                                "name": "travel/Data/index",
                                "title": "团体结算申请",
                                "icon": "fa fa-cog",
                                "href": "/travel/Data/index.html",
                                "children": []
                            },{
                                "id": 7,
                                "pid": 1,
                                "name": "Data/apply",
                                "title": "结算记录",
                                "icon": "fa fa-cog",
                                "href": "/travel/Data/apply.html",
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
                                "id": 6,
                                "pid": 2,
                                "name": "Writeoff/log",
                                "title": "团体核销记录",
                                "icon": "",
                                "href": "/travel/Writeoff/log.html",
                                "children": []
                            }
                        ]
                    },
                    {
                        "id": 3,
                        "pid": 0,
                        "name": "Travel",
                        "title": "旅行团管理",
                        "icon": "fa fa-user",
                        "children": [
                        {
                                "id": 8,
                                "pid": 3,
                                "name": "Travel/tour/index",
                                "title": "团队计划",
                                "icon": "fa fa-user",
                                "href": "/travel/tour/index.html",
                                "children": []
                            },
                                                        {
                                "id": 9,
                                "pid": 3,
                                "name": "Travel/tour/agg",
                                "title": "旅行社成团",
                                "icon": "fa fa-user",
                                "href": "/travel/tour/agg.html",
                                "children": []
                            },
                            {
                                "id": 12,
                                "pid": 3,
                                "name": "Travel/TourHotelUserRecord/index",
                                "title": "酒店打卡记录",
                                "icon": "",
                                "href": "/travel/TourHotelUserRecord/index.html",
                                "children": []
                            }
                        ]
                    },
                    {
                        "id": 4,
                        "pid": 0,
                        "name": "Travel",
                        "title": "分支机构管理",
                        "icon": "fa fa-user",
                        "children": [
                            {
                                "id": 8,
                                "pid": 14,
                                "name": "Travel/seller/child_node",
                                "title": "机构列表",
                                "icon": "fa fa-user",
                                "href": "/travel/seller/child_node.html",
                                "children": []
                            }
                        ]
                    },
                    {
                        "id": 19,
                        "pid": 0,
                        "name": "Travel",
                        "title": "线路管理",
                        "icon": "fa fa-user",
                        "children": [
                            {
                                "id": 20,
                                "pid": 19,
                                "name": "Travel/line/index",
                                "title": "线路列表",
                                "icon": "fa fa-user",
                                "href": "/travel/line/index.html",
                                "children": []
                            }
                        ]
                    },
                    {
                        "id": 21,
                        "pid": 0,
                        "name": "Travel",
                        "title": "游客管理",
                        "icon": "fa fa-user",
                        "children": [
                            {
                                "id": 22,
                                "pid": 21,
                                "name": "Travel/guest/index",
                                "title": "报名列表",
                                "icon": "fa fa-user",
                                "href": "/travel/guest/index.html",
                                "children": []
                            }
                        ]
                    }, {
                        "id": 3,
                        "pid": 0,
                        "name": "Ticket",
                        "title": "门票管理",
                        "icon": "fa fa-ticket-alt",
                        "children": [
                            {
                                "id": 23,
                                "pid": 3,
                                "name": "Ticket/buy",
                                "title": "购买门票",
                                "icon": "fa fa-ticket-alt",
                                "href": "/travel/ticket.BuyTicket/index.html",
                                "children": []
                            },
                            {
                                "id": 24,
                                "pid": 3,
                                "name": "Ticket/order",
                                "title": "门票订单",
                                "icon": "fa fa-file-alt",
                                "href": "/travel/ticket.Order/index.html",
                                "children": []
                            }
                        ]
                    }
                ]';

                /*
                {
                                "id": 8,
                                "pid": 3,
                                "name": "Travel/tour/index",
                                "title": "团队计划",
                                "icon": "fa fa-user",
                                "href": "/travel/tour/index.html",
                                "children": []
                            },
                ,
                            {
                                "id": 11,
                                "pid": 3,
                                "name": "Travel/tour/clock",
                                "title": "景区打卡记录",
                                "icon": "",
                                "href": "/travel/tour/clock.html",
                                "children": []
                            }
                ,{
                    "id": 13,
                    "pid": 1,
                    "name": "travel/Data/guest",
                    "title": "散客结算申请",
                    "icon": "fa fa-cog",
                    "href": "/travel/Data/guest.html",
                    "children": []
                }
                ,
                            {
                                "id": 12,
                                "pid": 2,
                                "name": "Writeoff/guest",
                                "title": "散客核销记录",
                                "icon": "",
                                "href": "/travel/Writeoff/guest.html",
                                "children": []
                            }
                ,
                {
                    "id": 15,
                    "pid": 3,
                    "name": "Travel/tour/tourguest",
                    "title": "游客管理",
                    "icon": "",
                    "href": "/travel/tour/tourguest.html",
                    "children": []
                }
                
                已开发  可用   暂时隐藏 根据pid放置
                {
                    "id": 5,
                    "pid": 2,
                    "name": "Writeoff/user",
                    "title": "人员管理",
                    "icon": "fa fa-user",
                    "href": "/travel/Writeoff/user.html",
                    "children": []
                },    */


    }
}