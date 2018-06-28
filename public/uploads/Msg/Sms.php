<?php 
namespace msg;
use think\Cache;

/**
 * 发送手机短信
 */
class Sms {
	/**
     * 调用端口
     */
    public function send_code($mobile,$content,$code=''){
    	// 检测手机号码
    	if(!preg_match('/^1[3|4|5|6|7|8|9][0-9]{9}/',$mobile)) return '手机号格式有误';
    	// 短信接口地址
    	$target = "http://106.ihuyi.com/webservice/sms.php?method=Submit";
    	// 发送数据
    	$post_data = "account=C01783704&password=31bd2fc060fc18d158416abf0eec31f1&mobile=".$mobile."&content=".rawurlencode($content);
    	$gets =  $this->xml_to_array($this->Post($post_data, $target));
    	// 如果发送成功，则保留验证码
    	if($gets['SubmitResult']['code'] == 2){
    		if($code !== '') \Cache::set('sms_'.$mobile,$code,600);
		}

		return $gets['SubmitResult']['msg'];
    }

    /**
     * 进行短信验证码核对
     * @return boolean
     */
    public function compare($mobile,$code){
    	$sms_code = \Cache::get('sms_'.$mobile);
    	return ($sms_code && $code == $sms_code) ? true : false;
    }


    /**
	 * 请求数据到短信接口，检查环境是否 开启 curl init
	 */
	public function Post($curlPost,$url){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
        $return_str = curl_exec($curl);
        curl_close($curl);
        return $return_str;
	}

	/**
     * xml转换成数组 
     * @return  array
     */
    public function xml_to_array($xml){
	    $reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
	    if(preg_match_all($reg, $xml, $matches)){
	        $count = count($matches[0]);
	        for($i = 0; $i < $count; $i++){
	        $subxml= $matches[2][$i];
	        $key = $matches[1][$i];
	            if(preg_match( $reg, $subxml )){
	                $arr[$key] = $this->xml_to_array( $subxml );
	            }else{
	                $arr[$key] = $subxml;
	            }
	        }
	    }
	    return $arr;
	}
    
}