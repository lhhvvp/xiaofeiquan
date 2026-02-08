<?php
/**
 * 携程数据交互与安全
 * @author slomoo <slomoo@aliyun.com> 2023-08-22
 */
namespace app\xc\service;
use think\facade\Config;

class XiechengService {

    /**
     * 添加PKCS5填充
     * @param string $string
     * @param int $blocksize
     * @return string
     */
    private function addPkcs5Padding($string, $blocksize = 16) {
        $pad = $blocksize - (strlen($string) % $blocksize);
        $string .= str_repeat(chr($pad), $pad);
        return $string;
    }

    /**
     * 去除PKCS5填充
     * @param string $string
     * @return string|bool
     */
    private function stripPkcs5Padding($string, $blocksize = 16) {
        $pad = ord(substr($string, -1));
        if ($pad < 1 || $pad > $blocksize) {
            return false;
        }
        return substr($string, 0, strlen($string) - $pad);
    }

    /**
     * 解码字节
     * @param string $hex
     * @return string
     */
    private function decodeBytes($hex)
    {
        $str = '';
        for($i=0;$i<strlen($hex);$i+=2){
            $tmpValue = (((ord($hex[$i]) - ord('a')) & 0xf ) <<4) + ((ord($hex[$i+1])- ord('a')) & 0xf);
            $str .= chr($tmpValue);
        }
        return  $str;
    }

    /**
     * 编码字节
     * @param string $string
     * @return string
     */
    private function encodeBytes($string)
    {
        $str = '';
        for($i=0;$i<strlen($string);$i++)
        {
            $tmpValue = ord($string[$i]);
            $ch = ($tmpValue >> 4 & 0xf) + ord('a');
            $str .= chr($ch);
            $ch = ($tmpValue & 0xf) + ord('a');
            $str .= chr($ch);
        }
        return $str;
    }

    /**
     * 解密
     * @param string $encryptedText
     * @param string $secretKey
     * @param string $vector
     * @return string|bool
     */
    public function decrypt($encryptedText, $secretKey, $vector) {
        // The OTA "encodeBytes" output must have an even length (2 chars per byte).
        // Guard against odd/invalid input to avoid PHP warnings being promoted to exceptions.
        if (!is_string($encryptedText) || $encryptedText === '' || (strlen($encryptedText) % 2) !== 0) {
            return false;
        }
        $decrypted = openssl_decrypt($this->decodeBytes($encryptedText), 'AES-128-CBC', $secretKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $vector);
        if ($decrypted === false) {
            return false;
        }
        return $this->stripPkcs5Padding($decrypted);
    }

    /**
     * 加密
     * @param string $decData
     * @param string $secretKey
     * @param string $vector
     * @return string
     */
    public function encrypt(string $decData, string $secretKey, string $vector): string {
        $base = openssl_encrypt($this->addPkcs5Padding($decData, 16), 'AES-128-CBC', $secretKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $vector);
        return $this->encodeBytes($base);
    }

    /**
     * [request 供应商请求携程]
     * @param    [type]            $header[请求头]
     * @param    [type]            $body  [请求体]
     * @return   [type]            $url   [请求地址]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-08-31
     * @LastTime 2023-08-31
     * @version  [1.0.0]
     */
    public function request($header, $body)
    {
        $bodyStr = $this->encrypt(json_encode($body),Config::get('ota.xiecheng.aesKey'),Config::get('ota.xiecheng.aesIv'));
        // 签名
        $header['sign'] = $this->sign($header,$bodyStr);

        // 组装请求数据
        // body 需要aes加密
        $requestData = [
            "header" => $header,
            "body" => $bodyStr 
        ];

        $headerStr = [
            "Content-Type: application/json; charset=utf-8"
        ];
        $data = json_encode($requestData);
        $res_json = http_curl_post_header(Config::get('ota.xiecheng.url'), $data, $headerStr);
        return $res_json;
    }

    public function sign($header, $bodyStr)
    {
        return strtolower(md5($header['accountId'].$header['serviceName'].$header['requestTime'].$bodyStr.$header['version'].Config::get('ota.xiecheng.signKey')));
    }

    public function getCurrentDate()
    {
        return date('Y-m-d H:i:s', time());
    }
}
