<?php
/**
 * 商户管理控制器
 * @author slomoo <1103398780@qq.com> 2022/07/22
 */

namespace app\seller\controller\appt;

// 引入框架内置类
use app\seller\controller\Base;
use app\common\facade\MakeBuilder;
use think\facade\Request;
use think\facade\View;
use app\common\model\Seller as SellerModel;
use app\common\model\appt\Datetime as DatetimeModel;
use app\common\model\appt\Rule as RuleModel;


class Settings extends Base
{
    // 票种分类列表
    public function index()
    {

        $seller_id = session()['seller']['id'];

        $row = SellerModel::where("id",$seller_id)->field("appt_open,appt_limit")->find();

        if (Request::isGet()) {
            View::assign('row',$row);
            return View::fetch('appt/settings/index');
        } else {
            $post = Request::post("row/a");
            if($row->save($post)){
                $this->success('操作成功!');
            }
            $this->srror('操作失败!');
        }
    }
}
