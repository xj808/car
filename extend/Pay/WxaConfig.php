<?php 
/**
* 	配置账号信息
*/
namespace pay;

class WxaConfig {
	// APPID：绑定支付的APPID
	const APPID = 'wx72a221c8fca19bee';

	// MCHID：商户号
	const MCHID = '1480664422';

	// KEY：商户支付密钥
	// 设置地址：https://pay.weixin.qq.com/index.php/account/api_cert
	const KEY = 'c12d686754427de9eba2e2d272a46638';

	// NOTIFY_URL: 异步通知地址
	const NOTIFY_URL = '';

	// 证书地址
	const SSLCERT_PATH = __DIR__.'/cert/apiclient_cert.pem';
	const SSLKEY_PATH = __DIR__.'/cert/apiclient_key.pem';

	// RSA证书地址
	const RSA_PATH = __DIR__.'/cert/apiclient_rsa.pem'; 

	// 调用IP地址
	const SPBILL_IP = '139.199.119.208';

}