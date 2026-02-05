<?php
/**
 * BA签名的实现
 * @Author slomoo <slomoo@aliyun.com> 2023-08-24
 */
namespace app\meituan\service;

use think\facade\Config;

class MeituanService
{
    private static $partnerId;
    private static $clientId;
    private static $clientSecret;
    private static $url;
    private static $instance = null;

    public function __construct()
    {
        self::$partnerId = Config::get('ota.meituan.partnerID');
        self::$clientId  = Config::get('ota.meituan.clientId');
        self::$clientSecret = Config::get('ota.meituan.clientSecret');
        self::$url       = Config::get('ota.meituan.url');
    }

    /**
     * @return MeituanService|null
     */
    public static function create(){
        if(!isset(self::$instance)){
            $service = new MeituanService();
            $service->validSign();
            self::$instance = $service;
        }
        return self::$instance;
    }

    public function request($data, $uri)
    {
        date_default_timezone_set('GMT');
        $date = date('D, d M Y H:i:s e', time());
        date_default_timezone_set('PRC');
        $authorization = $this->buildSign('POST', $uri, $date);

        $header = array(
            "Content-Type: application/json; charset=utf-8",
            "Date: " . $date,
            "PartnerId: " . self::$partnerId,
            "Authorization: " . $authorization,
        );
        $data = array_merge(array(
            'code' => 200,
            'describe' => 'success',
            'partnerId' => self::$partnerId,
        ), $data);

        $data = json_encode($data);
        //请使用自己的curl工具
        $res_json = http_curl_post_header(self::$url . $uri, $data, $header);
        $res = json_decode($res_json, true);

        return $res;
    }

    public function outputError($res = '')
    {
        if (is_array($res)) {
            $data = array_merge(array(
                'code' => 300,
                'partnerId' => self::$partnerId,
            ), $res);
        } else {
            $data = array(
                'code' => 300,
                'describe' => $res,
                'partnerId' => self::$partnerId,
            );
        }

        exit(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public function outputSucc($res = array(), $desc = 'success')
    {
        $data = array_merge(array(
            'code' => 200,
            'describe' => $desc,
            'partnerId' => self::$partnerId,
        ), $res);

        exit(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function validSign()
    {
        //todo sign 验证

        if ($_SERVER['HTTP_AUTHORIZATION'] != self::buildSign($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER['HTTP_DATE'])) {
            self::outputError('BA验证错误');
        }
        return true;
    }

    private function buildSign($method, $uri, $date)
    {

        $string_to_sign = $method . ' ' . $uri . "\n" . $date;

        $client_secret = self::$clientSecret;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $client_secret, true));
        $authorization = 'MWS ' . self::$clientId . ':' . $signature;

        return $authorization;
    }
}