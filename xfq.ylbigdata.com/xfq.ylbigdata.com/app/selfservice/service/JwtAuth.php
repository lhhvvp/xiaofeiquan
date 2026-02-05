<?php
/**
 * JwtAuth认证
 * @author slomoo <slomoo@aliyun.com> 2022-07-19
 */

namespace app\selfservice\service;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\ValidationData;
use think\facade\Request;

/**
 * 单例 一次请求中所有出现jwt的地方都是一个用户
 * Class JwtAuth
 * @package app\selfservice\service
 */
class JwtAuth
{
    private static $_config = [
        'audience' => 'https://v2.wlxfq.dianfengcms.com',//接收人
        'id'       => 'W8MaHAJRUpxk',//token的唯一标识，这里只是一个简单示例
        'sign'     => 'selfservice',//签名密钥
        'issuer'   => 'https://v2.wlxfq.dianfengcms.com',//签发人
        'expire'   => 3600*24*30 //有效期
    ];
 
    //生成token
    public static function getToken($user_id){
 
        //签名对象
        $signer = new Sha256();
        //获取当前时间戳
        $time = time();
        //设置签发人、接收人、唯一标识、签发时间、立即生效、过期时间、用户id、签名
        $token = (new Builder())->issuedBy(self::$_config['issuer'])
            ->canOnlyBeUsedBy(self::$_config['audience'])
            ->identifiedBy(self::$_config['id'], true)
            ->issuedAt($time)
            ->canOnlyBeUsedAfter($time-1)
            ->expiresAt($time + self::$_config['expire'])
            ->with('user_id', $user_id)
            ->sign($signer, self::$_config['sign'])
            ->getToken();
        return (string)$token;
    }
 
    //从请求信息中获取token令牌
    public static function getRequestToken()
    {
        $token = Request::header('Token');
        if (empty($token)) {
            return false;
        }
 
        $header = $token;
        $method = 'bearer';
        //去除token中可能存在的bearer标识
        return trim(str_ireplace($method, '', $header));
    }
 
    //从token中获取用户id （包含token的校验）
    public static function getUserId($token = null)
    {
        $user_id = null;
 
        $token = empty($token)?self::getRequestToken():$token;
 
        if (!empty($token)) {
            $token = (new Parser())->parse((string) $token);
            //验证token
            $data = new ValidationData();
            $data->setIssuer(self::$_config['issuer']);//验证的签发人
            $data->setAudience(self::$_config['audience']);//验证的接收人
            $data->setId(self::$_config['id']);//验证token标识
 
            if (!$token->validate($data)) {
                //token验证失败
                return $user_id;
            }
 
            //验证签名
            $signer = new Sha256();
            if (!$token->verify($signer, self::$_config['sign'])) {
                //签名验证失败
                return $user_id;
            }
            //从token中获取用户id
            $user_id = $token->getClaim('user_id');
        }
 
        return $user_id;
    }

}
