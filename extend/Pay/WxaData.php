<?php 
/**
 * 
 * 数据对象基础类
 *
 */
namespace pay;

class WxaData
{
	/**
     * 
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return 产生的随机字符串
     */
    public function getNonceStr($length = 32) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {  
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
        } 
        return $str;
    }

    /**
     * 生成签名 
     */ 
    public function getSign($Obj) {  
        foreach ($Obj as $k => $v) {  
            $Parameters[$k] = $v;  
        }  
        ksort($Parameters);  
        $String = $this->formatBizQueryParaMap($Parameters, false);  
        $String = $String . "&key=" . WxaConfig::KEY;  
        $String = md5($String);  
        $result_ = strtoupper($String);  
        return $result_;  
    }


    /**
     * SSL提交
     */
    public function curl_post_ssl($url, $vars, $second=30,$aHeader=array()){
        $ch = curl_init();
        //超时时间
        curl_setopt($ch,CURLOPT_TIMEOUT,$second);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);

        curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
        curl_setopt($ch,CURLOPT_SSLCERT,WxaConfig::SSLCERT_PATH);
        curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
        curl_setopt($ch,CURLOPT_SSLKEY,WxaConfig::SSLKEY_PATH);

        if( count($aHeader) >= 1 ){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        }
     
        curl_setopt($ch,CURLOPT_POST, 1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$vars);
        $data = curl_exec($ch);
        if($data){
            curl_close($ch);
            return $data;
        }
        else { 
            $error = curl_errno($ch);
            echo "call faild, errorCode:$error\n"; 
            curl_close($ch);
            return false;
        }
    }

    /**
     * CURL提交
     */
    public function postXmlCurl($xml, $url, $second = 30) {  
        $ch = curl_init();  
        //设置超时  
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);  
        curl_setopt($ch, CURLOPT_URL, $url);  
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);  
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); //严格校验  
        //设置header  
        curl_setopt($ch, CURLOPT_HEADER, FALSE);  
        //要求结果为字符串且输出到屏幕上  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);  
        //post提交方式  
        curl_setopt($ch, CURLOPT_POST, TRUE);  
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);  
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);  
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);  
        set_time_limit(0);  
        //运行curl  
        $data = curl_exec($ch);  
        //返回结果  
        if ($data) {  
            curl_close($ch);  
            return $data;  
        } else {  
            $error = curl_errno($ch);  
            curl_close($ch);  
            throw new WxPayException("curl出错，错误码:$error");  
        }  
    } 

    /**
     * 获取字符串值
     */
    public function getStrVal($str){
        $result=array();
        foreach (explode('&', $str) as $t){
            list($a,$b)=explode('=', $t);
            $result[$a]=$b;
        }
        return $result;
    }

    
    /**
     * 生成订单号
     */
    public function createOrder(){
        $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        return $yCode[intval(date('Y')) - 2017] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
    }

    /**
     * 生成唯一卡号
     */
    public function createCardNum(){
        static $i = -1;$i ++ ;
        $a = substr(date('YmdHis'), -12,12);
        $b = sprintf ("%02d", $i);
        if ($b >= 100){
            $a += $b;
            $b = substr($b, -2,2);
        }
        return $a.$b;
    }



    /**
     * xml转换成数组  
     */
    public function xmlToArray($xml) {  
        //禁止引用外部xml实体   
        libxml_disable_entity_loader(true);  
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);  
        $val = json_decode(json_encode($xmlstring), true);  
        return $val;  
    }

    /**
     * 数组转换成xml 
     */ 
    public function arrayToXml($arr) {  
        $xml = "<root>";  
        foreach ($arr as $key => $val) {  
            if (is_array($val)) {  
                $xml .= "<" . $key . ">" . $this->arrayToXml($val) . "</" . $key . ">";  
            } else {  
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";  
            }  
        }  
        $xml .= "</root>";  
        return $xml;  
    }

    /**
     * 格式化参数，签名过程需要使用  
     */
    private function formatBizQueryParaMap($paraMap, $urlencode) {  
        $buff = "";  
        ksort($paraMap);  
        foreach ($paraMap as $k => $v) {  
            if ($urlencode) {  
                $v = urlencode($v);  
            }  
            $buff .= $k . "=" . $v . "&";  
        }  
        $reqPar;  
        if (strlen($buff) > 0) {  
            $reqPar = substr($buff, 0, strlen($buff) - 1);  
        }  
        return $reqPar;  
    }

}