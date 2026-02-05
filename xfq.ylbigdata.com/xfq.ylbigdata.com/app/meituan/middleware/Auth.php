<?php
/**
 * API中间件
 * @author slomoo <slomoo@aliyun.com> 2022-07-19
 */

namespace app\meituan\middleware;

use think\facade\Request;
use think\Response;
use think\exception\HttpResponseException;
use think\facade\Db;
use app\meituan\service\MeituanService;

class Auth
{

    public function handle($request, \Closure $next)
    {
        // ba认证
        MeituanService::create();

        // 身份认证通过，继续处理请求
        return $next($request);
    }

    /**
     * 返回封装后的API数据到客户端
     * @param  mixed   $data 要返回的数据
     * @param  integer $code 返回的code
     * @param  mixed   $msg 提示信息
     * @param  string  $type 返回数据格式
     * @param  array   $header 发送的Header信息
     * @return Response
     */
    protected function result($data, int $code = 0, $msg = '', string $type = '', array $header = []): Response
    {
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'time' => time(),
            'data' => $data,
        ];

        $type     = $type ?: 'json';
        $response = Response::create($result, $type)->header($header);

        throw new HttpResponseException($response);
    }

}
