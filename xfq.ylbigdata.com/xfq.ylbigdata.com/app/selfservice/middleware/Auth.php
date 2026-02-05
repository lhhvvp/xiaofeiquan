<?php
/**
 * API中间件
 * @author slomoo <slomoo@aliyun.com> 2022-07-19
 */

namespace app\selfservice\middleware;

use app\selfservice\service\JwtAuth;
use think\facade\Request;
use think\Response;
use think\exception\HttpResponseException;
use think\facade\Db;

class Auth
{
    public function handle($request, \Closure $next)
    {
        $token = JwtAuth::getRequestToken();
        //第一次登录判断
        if (!$token) {
            $this->result([], 112, 'token不能为空');
        }

        $explode = explode('.',$token);
        if(count($explode)!=3){
            $this->result([], 112, 'token格式错误');
        }
        
        // 用户id
        $no = Request::header('No');
        if (!$no) {
            $this->result([], 112, '用户编码不能为空');
        }

        // 检查token是否存在
        $signpass = Db::name('seller')->field('signpass,expiry_time')
            ->where('no',$no)
            ->where('signpass',md5($token.$no))
            ->find();
        if(!$signpass) {
            $this->result([], 113, 'token信息错误');
        }

        if (time() - $signpass['expiry_time'] > 0){
            $this->result([], 111, 'token已过期');
        }
        // $new_expiry_time = time() + 604800; // 在续7天。  
        // Db::where('id',$Userid)->update(['expiry_time' => $new_expiry_time]);
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
