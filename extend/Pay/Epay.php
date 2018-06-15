<?php
/**
 * 企业支付到银行卡
 * 企业支付到商户零钱
 */
namespace pay;
use pay\WxaData;
use pay\WxaConfig;

class Epay extends WxaData{

    /**
      * 付款银行
      */ 
    public function toBank($trade_no,$card_no,$true_name,$bank_code,$amount,$desc){
        $url = 'https://api.mch.weixin.qq.com/mmpaysptrans/pay_bank';
        $parameters = array(
            'mch_id' => WxaConfig::MCHID,
            'partner_trade_no' => $trade_no,
            'nonce_str' => $this->getNonceStr(), 
            'enc_bank_no' => $this->rsa($card_no),
            'enc_true_name' => $this->rsa($true_name),
            'bank_code' => $bank_code,
            'amount' => $amount,
            'desc' => $desc
        );
        return $this->postSsl($url,$parameters);
    }

    /**
     * 查询企业付款到银行卡
     */
    public function banklog($trade_no){
        $url = 'https://api.mch.weixin.qq.com/mmpaysptrans/query_bank';
        $parameters = array(
            'mch_id' => WxaConfig::MCHID,
            'partner_trade_no' => $trade_no,
            'nonce_str' => $this->getNonceStr()
        );
        return $this->postSsl($url,$parameters);
    }


    /**
     * 企业付款到零钱
     */
    public function dibs($trade_no,$openid,$amount,$desc){
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        $parameters = array(
            'mch_appid' => WxaConfig::APPID,
            'mchid' => WxaConfig::MCHID,
            'nonce_str' => $this->getNonceStr(),
            'partner_trade_no' => $trade_no,
            'openid' => $openid,//'o-Fr90NaOYWL98Ms2uJwjwGdyF24',
            'check_name' => 'NO_CHECK',
            'amount' => $amount,
            'desc' => $desc,
            'spbill_create_ip' => '127.0.0.1'
        );
        return $this->postSsl($url,$parameters);
    }

    /**
     * 企业付款零钱查询
     */
    public function dibslog($trade_no){
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gettransferinfo';
        $parameters = array(
            'appid' => WxaConfig::APPID,
            'mch_id' => WxaConfig::MCHID,
            'partner_trade_no' => $trade_no,
            'nonce_str' => $this->getNonceStr()
        );
        return $this->postSsl($url,$parameters);
    }


    /**
     * 提交方法
     */
    private function postSsl($url,$parameters){
        $parameters['sign'] = $this->getSign($parameters);
        $xmlData = $this->arrayToXml($parameters);
        return $this->xmlToArray($this->curl_post_ssl($url, $xmlData, 60)); 
    }

    /**
     * 进行RSA加密操作
     */
    private function rsa($content){
        $crypto = '';
        $key = file_get_contents(WxaConfig::RSA_PATH);
        $pu_key = openssl_pkey_get_public($key);
        foreach (str_split($content, 117) as $chunk) {
            openssl_public_encrypt($chunk, $encryptData, $pu_key,OPENSSL_PKCS1_OAEP_PADDING);   
            $crypto .= $encryptData;
        }
        return base64_encode($crypto);
    }

    
}