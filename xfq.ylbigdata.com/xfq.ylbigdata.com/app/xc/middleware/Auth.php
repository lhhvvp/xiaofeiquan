<?php
/**
 * 携程数据交互中间件
 * @author slomoo <slomoo@aliyun.com> 2023-08-22
 */

namespace app\xc\middleware;

use app\xc\service\XiechengService;
use think\facade\Request;
use think\Response;
use think\exception\HttpResponseException;
use think\facade\Db;
use think\facade\Config;

class Auth
{
    public function handle($request, \Closure $next)
    {
        $aes = new XiechengService;
        // 账号
        $accountId = Config::get('ota.xiecheng.accountId');
        // 接口密钥
        $signKey = Config::get('ota.xiecheng.signKey');
        // AES 加密密钥
        $aesKey = Config::get('ota.xiecheng.aesKey');
        // AES 加密初始向量
        $aesIv = Config::get('ota.xiecheng.aesIv');
        // 报文获取
        $request_body = file_get_contents('php://input');
        $request_data = json_decode($request_body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // 报文解析失败
            $this->result(sprintf('%04d', 1), '报文解析失败');
        }
        // 验证报文内容是否完整
        if (!isset($request_data['header']) || !isset($request_data['body'])) {
            $this->result(sprintf('%04d', 1), '报文解析失败，缺少header或body');
        }

        // 验证报文签名
        $header    = $request_data['header'];
        $bodyStr   = $request_data['body'];

        $signSource  = $header['sign'];
        $accountId_header   = $header['accountId'];
        $reqTimeStr  = $header['requestTime'];
        $version     = $header['version'];
        $serviceName = $header['serviceName'];
        // body加密串解密
        $bodyJsonStr = $aes->decrypt($bodyStr,$aesKey,$aesIv);
        if($bodyJsonStr === false){
            $this->result(sprintf('%04d', 1), '报文解析失败:body解密错误');
        }

        $signTarget  = strtolower(md5($accountId.$serviceName.$reqTimeStr.$bodyStr.$version.$signKey));
        // 验证商户
        if ($accountId !== $accountId_header) {
            $this->result(sprintf('%04d', 3), '供应商账户信息不正确');
        }
        // 验证签名
        if ($signSource !== $signTarget) {
            $this->result(sprintf('%04d', 2), '签名错误');
        }
        // 根据不同的操作，请求不同的接口地址
        switch ($serviceName) {
            // 订单验证
            case 'VerifyOrder':
                $response = $this->VerifyOrder($bodyJsonStr);
                break;
            // 预下单创建
            case 'CreatePreOrder':
                $response = $this->CreatePreOrder($bodyJsonStr);
                break;
            // 预下单支付
            case 'PayPreOrder':
                $response = $this->PayPreOrder($bodyJsonStr);
                break;
            // 预下单取消
            case 'CancelPreOrder':
                $response = $this->CancelPreOrder($bodyJsonStr);
                break;
            // 取消订单
            case 'CancelOrder':
                $response = $this->CancelOrder($bodyJsonStr);
                break;
            // 查询订单
            case 'QueryOrder':
                $response = $this->QueryOrder($bodyJsonStr);
                break;
            // 资源日期库存同步接口
            case 'DateInventoryModify':
                $response = $this->DateInventoryModify($bodyJsonStr);
                break;
            // 添加其他服务的调用逻辑
            default:
                $response = null;
                break;
        }
        // xc 的 controller action 普遍依赖“解密后的 bodyJsonStr”，
        // 因此这里直接返回 middleware 调用得到的响应，避免进入 controller 后再次执行导致 TypeError/500。
        if ($response !== null) {
            return $response;
        }
        return $next($request);
    }

    // 调用 订单验证
    private function VerifyOrder($data)
    {
        // 调用订单应用下的createOrder方法
        $app = app(); // 获取应用实例
        $response = $app->invoke(['app\xc\controller\Order','VerifyOrder'],[$data]);

        return $response;
    }

    // 调用 预下单创建
    private function CreatePreOrder($data)
    {
        // 调用订单应用下的createOrder方法
        $app = app(); // 获取应用实例
        $response = $app->invoke(['app\xc\controller\Order','CreatePreOrder'],[$data]);

        return $response;
    }


    // 调用 预下单支付
    private function PayPreOrder($data)
    {
        // 调用订单应用下的createOrder方法
        $app = app(); // 获取应用实例
        $response = $app->invoke(['app\xc\controller\Order','PayPreOrder'],[$data]);

        return $response;
    }


    // 调用 预下单取消
    private function CancelPreOrder($data)
    {
        // 调用订单应用下的createOrder方法
        $app = app(); // 获取应用实例
        $response = $app->invoke(['app\xc\controller\Order','CancelPreOrder'],[$data]);

        return $response;
    }


    // 调用 订单取消
    private function CancelOrder($data)
    {
        // 调用订单应用下的createOrder方法
        $app = app(); // 获取应用实例
        $response = $app->invoke(['app\xc\controller\Order','CancelOrder'],[$data]);

        return $response;
    }

    // 调用 订单取消
    private function QueryOrder($data)
    {
        // 调用订单应用下的QueryOrder方法
        $app = app(); // 获取应用实例
        $response = $app->invoke(['app\xc\controller\Order','QueryOrder'],[$data]);

        return $response;
    }
    // 调用 资源日期库存同步接口
    private function DateInventoryModify($data)
    {
        // 调用订单应用下的DateInventoryModify方法
        $app = app(); // 获取应用实例
        $response = $app->invoke(['app\xc\controller\Order','DateInventoryModify'],[$data]);

        return $response;
    }

    /**
     * 返回封装后的API数据到客户端
     * @param  integer $code 返回的code
     * @param  mixed   $msg 提示信息
     * @param  mixed   $data 要返回的数据
     * @param  array   $header 响应头
     * @return Response
     */
    protected function result(string $code = '', $msg = '',array $data = [], array $header = []): Response
    {
        $result = [
            'header' => [
                'resultCode' => $code,
                'resultMessage' => $msg
            ]
        ];
        $response = Response::create($result,'json')->header($header);

        throw new HttpResponseException($response);
    }

}
