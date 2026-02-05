<?php
/**
 * @desc   测试模块API
 * @author slomoo
 * @email slomoo@aliyun.com
 */
declare (strict_types = 1);

// 生产者逻辑测试

namespace app\api\controller;
use think\Exception;
use think\facade\Cache;
use think\facade\Db;
class Test{
    public function index(){
        for ($i=0; $i < 1000; $i++) {
            $before = $i.'&TC' . date('YmdHis') . GetNumberCode(6).'&'.gen_uuid();
            $after = symencryption($before,$i);
            $result = symdecrypt($after,$i);
            $msg = '不匹配';
            if($before === $result) {
                $msg = '匹配';
            }
            echo $before.PHP_EOL.$after.PHP_EOL.$result.PHP_EOL.$msg.PHP_EOL;
        }
        exit;
        //$str = '1&TC20230724180122835656&d9540923-fd9e-4fee-9924-4af43589486a';
        $str = 'TkxUWVdBaEl2TmlPZzd6SDFMVlFlTVNKeFF6NjFNa1Q0NVVRNE9TU2c4VEgwS1lKU09WVnNQMkhsWVR6Ny1CSXpNU1RSSGpQME1WUW5OQ2gxYUc9bGFYNzA4Tz0t';

        $end = symdecrypt($str,$key);

        //$end = symencryption($str,$key);

        echo $end;die;

        $data = [
            "orderId" => uniqid()
        ];

        $aaa = set_salt(8);
        echo $aaa;

        echo "<pre>";

        $bbb = uniqid();
        echo $bbb;
        echo "<pre>";
        $data = 'SN_'.strtoupper($aaa.$bbb);


        print_r($data);die;
    }

    /*public function syncdb2(){
        $arrs = [
            116,
            117,
            118,
            119,
            121,
            126,
            128,
            133];
        $ids = Db::name('coupon_issue_user')::where('status',2)->where('is_rollback',2)->whereIn('issue_coupon_id',$arrs)->select();
    }*/

    // 负载均衡健康检查接口
    public function clb()
    {
        echo 'CLB check!!! ';exit;
    }

    public function tokenTaohua()
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
          CURLOPT_PORT => "8282",
          CURLOPT_URL => "http://111.20.184.10:8282/ws/service/",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:ser=\"http://service.webservice.center.udatech.com/\">\r\n   <soapenv:Header/>\r\n   <soapenv:Body>\r\n      <ser:buildResult>\r\n         <!--Optional:-->\r\n         <arg0>fb5ab2a6d29241e8a7c857371298c2a0</arg0>\r\n         <!--Optional:-->\r\n         <arg1>count</arg1>\r\n         <!--Optional:-->\r\n         <arg2>null</arg2>\r\n         <!--Optional:-->\r\n         <arg3>null</arg3>\r\n         <!--Optional:-->\r\n         <arg4>null</arg4>\r\n      </ser:buildResult>\r\n   </soapenv:Body>\r\n</soapenv:Envelope>",
          CURLOPT_HTTPHEADER => [
            "content-type: application/xml"
          ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return ['code'=>0,'msg'=>"cURL Error #:" . $err,'data'=>''];
        } else {
            return ['code'=>1,'msg'=>"success",'data'=>$response];
        }
    }

    // 定时同步桃花分
    public function rsyncTaohua(){

        $uuid = 'fb5ab2a6d29241e8a7c857371298c2a0';
        $cache_token_taohua = Cache::get('cache_token_taohua');
        if($cache_token_taohua) {
            $token = $cache_token_taohua;
        }else{
            $return = $this->tokenTaohua();
            if($return['code']!=1){
                $result = [
                    'code' => 1,
                    'msg' => $return['msg'],
                    'time' => time(),
                    'data' => $return,
                ];
                return json($result);
            }
            // 创建DOMDocument对象并加载XML数据
            $xmlElement = new \SimpleXMLElement($return['data']);
            $xmlElement->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xmlElement->registerXPathNamespace('ns2', 'http://service.webservice.center.udatech.com/');
            $json = (string) $xmlElement->xpath('//return')[0];
            // 获取JSON字符串并解码为PHP数组
            $data = json_decode($json, true);
            // 打印数组
            $token = $data['token'];

            Cache::set('cache_token_taohua',$token,60);
        }

        set_time_limit(0); //不超时
        $param = get_params();
        $page = isset($param['page']) ? $param['page'] : 0;
        if($page == 0) $page = 1;

        $where = array();
        $where[] = ['credit_score', '=', 0];
        $where[] = ['idcard','<>',''];
        $where[] = ['card_type','=',1];

        $limit = 100;

        $userList = \app\common\model\Users::field('id,idcard')->where($where)->limit($limit)->select()->toArray();
        foreach ($userList as $key => $value) {
            $curl = curl_init();
            curl_setopt_array($curl, [
              CURLOPT_PORT => "8282",
              CURLOPT_URL => "http://111.20.184.10:8282/ws/service/",
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => "",
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 30,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => "POST",
              CURLOPT_POSTFIELDS => "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:ser=\"http://service.webservice.center.udatech.com/\">\r\n   <soapenv:Header/>\r\n   <soapenv:Body>\r\n      <ser:buildResult>\r\n         <!--Optional:-->\r\n         <arg0>".$uuid."</arg0>\r\n         <!--Optional:-->\r\n         <arg1>get</arg1>\r\n         <!--Optional:-->\r\n         <arg2>".$token."</arg2>\r\n         <!--Optional:-->\r\n         <arg3>1</arg3>\r\n         <!--Optional:-->\r\n         <arg4>".$value['idcard']."</arg4>\r\n      </ser:buildResult>\r\n   </soapenv:Body>\r\n</soapenv:Envelope>",
              CURLOPT_HTTPHEADER => [
                "content-type: application/xml"
              ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo "cURL Error #:" . $err;
            } else {
                // 创建DOMDocument对象并加载XML数据
                $xmlElement = new \SimpleXMLElement($response);
                $xmlElement->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
                $xmlElement->registerXPathNamespace('ns2', 'http://service.webservice.center.udatech.com/');
                $json = (string) $xmlElement->xpath('//return')[0];
                // 获取JSON字符串并解码为PHP数组
                $data = json_decode($json, true);
                if(!empty($data)){
                    if(isset($data[0]['XM']) && $data[0]['XM']!=''){
                        $upData = [
                            'name' => $data[0]['XM'],
                            'credit_score' => $data[0]['SCORE'],
                            'credit_rating'=> @$data[0]['LEVEL_NAME'],
                            'update_credit'=>time()
                        ];
                    }else{
                        $upData = [
                            'credit_score' => $data[0]['SCORE'],
                            'credit_rating'=> @$data[0]['LEVEL_NAME'],
                            'update_credit'=>time()
                        ];
                    }
                }else{
                    $upData = [
                        'credit_score' => '-1',
                        'credit_rating'=> '',
                        'update_credit'=>time()
                    ];
                }
                // 更新桃花分
                \app\common\model\Users::update($upData, ['id' => $value['id']]);

                //echo "<br><script>parent.n = 0;</script><b>采集正在进行中，当前采集批次".$page."!!!</b>";
            }
        }
        // 自动跳转页面
       /* echo "<script>setTimeout(function() {
            window.location.href = '?page=".($page+1)."';
        }, 3000);</script>";exit;*/
    }

    // 跟新个人桃花分
    public function rsyncTaohuaSign(){

        set_time_limit(0); //不超时
        $uuid = 'fb5ab2a6d29241e8a7c857371298c2a0';
        $cache_token_taohua = Cache::get('cache_token_taohua');
        if($cache_token_taohua) {
            $token = $cache_token_taohua;
        }else{
            $return = $this->tokenTaohua();
            if($return['code']!=1){
                $result = [
                    'code' => 1,
                    'msg' => $return['msg'],
                    'time' => time(),
                    'data' => $return,
                ];
                return json($result);
            }
            // 创建DOMDocument对象并加载XML数据
            $xmlElement = new \SimpleXMLElement($return['data']);
            $xmlElement->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xmlElement->registerXPathNamespace('ns2', 'http://service.webservice.center.udatech.com/');
            $json = (string) $xmlElement->xpath('//return')[0];
            // 获取JSON字符串并解码为PHP数组
            $data = json_decode($json, true);
            // 打印数组
            $token = $data['token'];

            Cache::set('cache_token_taohua',$token,60);
        }
        $param = get_params();
        if(isset($param['uid']) && $param['uid']!=''){
            // 根据类型获取
            $userInfo = \app\common\model\Users::field('id,idcard,card_type')->where('id',$param['uid'])->find();
            if(!$userInfo){
                $result = [
                    'code' => 1,
                    'msg' => '未检查到用户信息',
                    'time' => time(),
                    'data' => $userInfo,
                ];
                return json($result);
            }
            if($userInfo->card_type==1){
                $curl = curl_init();
                curl_setopt_array($curl, [
                  CURLOPT_PORT => "8282",
                  CURLOPT_URL => "http://111.20.184.10:8282/ws/service/",
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => "",
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 30,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => "POST",
                  CURLOPT_POSTFIELDS => "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:ser=\"http://service.webservice.center.udatech.com/\">\r\n   <soapenv:Header/>\r\n   <soapenv:Body>\r\n      <ser:buildResult>\r\n         <!--Optional:-->\r\n         <arg0>".$uuid."</arg0>\r\n         <!--Optional:-->\r\n         <arg1>get</arg1>\r\n         <!--Optional:-->\r\n         <arg2>".$token."</arg2>\r\n         <!--Optional:-->\r\n         <arg3>1</arg3>\r\n         <!--Optional:-->\r\n         <arg4>".$userInfo->idcard."</arg4>\r\n      </ser:buildResult>\r\n   </soapenv:Body>\r\n</soapenv:Envelope>",
                  CURLOPT_HTTPHEADER => [
                    "content-type: application/xml"
                  ],
                ]);

                $response = curl_exec($curl);
                $err = curl_error($curl);

                curl_close($curl);

                if ($err) {
                    $result = [
                        'code' => 1,
                        'msg' => 'cURL Error #:更新失败'. $err,
                        'time' => time(),
                        'data' => $err,
                    ];
                    return json($result);
                } else {
                    // 创建DOMDocument对象并加载XML数据
                    $xmlElement = new \SimpleXMLElement($response);
                    $xmlElement->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
                    $xmlElement->registerXPathNamespace('ns2', 'http://service.webservice.center.udatech.com/');
                    $json = (string) $xmlElement->xpath('//return')[0];
                    // 获取JSON字符串并解码为PHP数组
                    $data = json_decode($json, true);
                    $upData = [
                        'update_credit'=>time()
                    ];
                    if(!empty($data)){
                        if(isset($data[0]['XM']) && $data[0]['XM']!=''){
                            $upData = [
                                'name' => $data[0]['XM'],
                                'credit_score' => $data[0]['SCORE'],
                                'credit_rating'=> @$data[0]['LEVEL_NAME'],
                                'update_credit'=>time()
                            ];
                        }else{
                            $upData = [
                                'credit_score' => $data[0]['SCORE'],
                                'credit_rating'=> @$data[0]['LEVEL_NAME'],
                                'update_credit'=>time()
                            ];
                        }
                    }
                    // 更新桃花分
                    $rs = \app\common\model\Users::update($upData, ['id' => $userInfo->id]);
                    $result = [
                        'code' => 0,
                        'msg' => '更新成功',
                        'time' => time(),
                        'data' => $rs,
                    ];
                    return json($result);
                }
            }
            $result = [
                'code' => 1,
                'msg' => '暂不支持其他证件获取信用分',
                'time' => time(),
                'data' => '',
            ];
            return json($result);
        }
        $result = [
            'code' => 1,
            'msg' => '请传入有效证件',
            'time' => time(),
            'data' => '',
        ];
        return json($result);
    }

    // 查看个人桃花分
    public function getUserTaohua(){

        set_time_limit(0); //不超时
        $uuid = 'fb5ab2a6d29241e8a7c857371298c2a0';
        $cache_token_taohua = Cache::get('cache_token_taohua');
        if($cache_token_taohua) {
            $token = $cache_token_taohua;
        }else{
            $return = $this->tokenTaohua();
            if($return['code']!=1){
                $result = [
                    'code' => 1,
                    'msg' => $return['msg'],
                    'time' => time(),
                    'data' => $return,
                ];
                return json($result);
            }
            // 创建DOMDocument对象并加载XML数据
            $xmlElement = new \SimpleXMLElement($return['data']);
            $xmlElement->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xmlElement->registerXPathNamespace('ns2', 'http://service.webservice.center.udatech.com/');
            $json = (string) $xmlElement->xpath('//return')[0];
            // 获取JSON字符串并解码为PHP数组
            $data = json_decode($json, true);
            // 打印数组
            $token = $data['token'];

            Cache::set('cache_token_taohua',$token,60);
        }
        $param = get_params();
        if(isset($param['idcard']) && $param['idcard']!=''){
            $curl = curl_init();
            curl_setopt_array($curl, [
              CURLOPT_PORT => "8282",
              CURLOPT_URL => "http://111.20.184.10:8282/ws/service/",
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => "",
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 30,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => "POST",
              CURLOPT_POSTFIELDS => "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:ser=\"http://service.webservice.center.udatech.com/\">\r\n   <soapenv:Header/>\r\n   <soapenv:Body>\r\n      <ser:buildResult>\r\n         <!--Optional:-->\r\n         <arg0>".$uuid."</arg0>\r\n         <!--Optional:-->\r\n         <arg1>get</arg1>\r\n         <!--Optional:-->\r\n         <arg2>".$token."</arg2>\r\n         <!--Optional:-->\r\n         <arg3>1</arg3>\r\n         <!--Optional:-->\r\n         <arg4>".$param['idcard']."</arg4>\r\n      </ser:buildResult>\r\n   </soapenv:Body>\r\n</soapenv:Envelope>",
              CURLOPT_HTTPHEADER => [
                "content-type: application/xml"
              ],
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                $result = [
                    'code' => 1,
                    'msg' => 'cURL Error #:获取失败'. $err,
                    'time' => time(),
                    'data' => $err,
                ];
                return json($result);
            } else {
                // 创建DOMDocument对象并加载XML数据
                $xmlElement = new \SimpleXMLElement($response);
                $xmlElement->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
                $xmlElement->registerXPathNamespace('ns2', 'http://service.webservice.center.udatech.com/');
                $json = (string) $xmlElement->xpath('//return')[0];
                // 获取JSON字符串并解码为PHP数组
                $data = json_decode($json, true);
                $upData = [
                    'update_credit'=>time()
                ];
                if(!empty($data)){
                    if(isset($data[0]['XM']) && $data[0]['XM']!=''){
                        $upData = [
                            'name' => $data[0]['XM'],
                            'credit_score' => $data[0]['SCORE'],
                            'credit_rating'=> @$data[0]['LEVEL_NAME'],
                            'update_credit'=>time()
                        ];
                    }else{
                        $upData = [
                            'credit_score' => $data[0]['SCORE'],
                            'credit_rating'=> @$data[0]['LEVEL_NAME'],
                            'update_credit'=>time()
                        ];
                    }
                }
                // 获取成功
                $result = [
                    'code' => 0,
                    'msg' => '查询成功',
                    'time' => time(),
                    'data' => $upData,
                ];
                return json($result);
            }
        }
        $result = [
            'code' => 1,
            'msg' => '请传入有效证件',
            'time' => time(),
            'data' => '',
        ];
        return json($result);
    }
}