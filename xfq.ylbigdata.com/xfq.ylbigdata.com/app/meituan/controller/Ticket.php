<?php
/**
 * @desc   小程序支付API
 * @author slomoo
 * @email slomoo@aliyun.com
 * 2023-06-30 门票支付
 */
declare (strict_types=1);

namespace app\meituan\controller;

use app\meituan\BaseController;
use app\meituan\middleware\Auth;
use app\meituan\service\JwtAuth;
use app\common\model\TicketPrice;
use think\Exception;
use think\facade\Db;
use think\facade\Request;
use think\facade\Validate;
use app\meituan\service\MeituanService;

class Ticket extends BaseController
{
    /**
     * 控制器中间件 [不需要鉴权set]
     * @var array
     */

    protected $middleware = [
        Auth::class => ['except' => ['getMt']]
    ];

    /**
     * @api             {post} /ticket/pay 创建订单
     * @apiDescription  提交订单
     */
    public function pay()
    {
        echo 222;die;
    }

    // 测试请求美团接口
    public function getMt()
    {
        // 请求某个接口
        $uri = '/test/push/rhone/mtp/api/deal/change/notice';

        $data = [];
        $service = new MeituanService();

        $res = $service->request($data,$uri);
        if($res===NULL){
            return $service->outputError($res);
        }
        return $service->outputSucc($res);
    }
}