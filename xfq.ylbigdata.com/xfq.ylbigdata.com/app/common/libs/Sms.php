<?php
namespace app\common\libs;
use think\Db;
use think\facade\Cache;
use bingher\sms\AliSms;
class Sms
{
	//加载配置
	public function sms_config(){
		$config = [
			'version' => '2017-05-25',
			'host' => 'dysmsapi.aliyuncs.com',
			'scheme' => 'http',
			'region_id' => 'cn-hangzhou',
			'access_key' => config('sms_apikey'),
			'access_secret' => config('sms_apisecret'),
			'product' => config('sms_product'),
			'actions' => [
				'login' => [
					'sign_name' => config('sms_sign'),
					'template_code' => config('sms_login'),
					'template_param' => [
						'code' => '',
						'product' => '',
					]
				],
				'register' => [
					'sign_name' => config('sms_sign'),
					'template_code' => config('sms_register'),
					'template_param' => [
						'code' => '',
						'product' => '',
					]
				],
				'forget_password' => [
					'sign_name' => config('sms_sign'),
					'template_code' => config('sms_forget_pwd'),
					'template_param' => [
						'code' => '',
						'product' => '',
					]
				],
				'binding' => [
					'sign_name' => config('sms_sign'),
					'template_code' => config('sms_binding'),
					'template_param' => [
						'code' => '',
						'product' => '',
					]
				],
				'currency' => [
					'sign_name' => config('sms_sign'),
					'template_code' => config('sms_currency'),
					'template_param' => [
						'code' => '',
						'product' => '',
					]
				],
			],
		];
		return $config;
	}
	
	
	public function send($mobile='',$code = '',$event = 'register')
    {
		$config = self::sms_config();
		$sms = new AliSms($config);
		
		switch ($event){
		case 'login'://登录验证
			return $sms->login($mobile,['code'=>$code]);
		break;
		case 'register'://注册验证
			return $sms->register($mobile,['code'=>$code]);
		break;  
		case 'forget_password'://密码找回模板
			return $sms->forget_password($mobile,['code'=>$code]);
		break; 
		case 'binding'://号码绑定模板
			return $sms->binding($mobile,['code'=>$code]);
		break;
		default://通用短信模板
			return $sms->currency($mobile,['code'=>$code]);
		}
		
	}
	
	
}
