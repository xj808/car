<?php 
namespace app\base\controller;
use think\Controller;
use Firebase\JWT\JWT;
use think\Db;
use msg\Sms;
/**
* token登录  验证
*/
class Base extends Controller{
    
    function initialize()
    {
        $this->sms=new Sms();
    }


    /**
     * 验证token
     * @return [type] [description]
     */
    public function ifToken()
    {
        $token=input('post.token');
        if(!$token || empty($token)){
            $this->error('token失效或未登录');
        }else{
            // 进行分割验证格式
            if (count(explode('.', $token)) != 3) {
                $this->error('token无效');
            }
            $id = $this->checkToken($token);
            if(!$id){
                $this->error('token失效或未登录');
            }else{
                return $id;
            }
        }
    }




    /**
     * 前台返回token时验证token
     * @param  token
     * @return 包含用户名、id的数组
     */
    private function checkToken($token){
        $key=create_key();
        $sign=JWT::decode($token,$key,array('HS256'));
        $sign=json_encode($sign);
        $sign=json_decode($sign,true);
        return $sign['id'];
    }

    /**
     * 获取省级名称
     * @return 省级地区名称
     */
    public function province(){

        return Db::table('co_china_data')->where('pid',1)->select();
    }

    /**
     * 获取市级
     * @return 市级地区名称
     */
    public function city(){
        // 获取省级id
        $province_id=input('post.id');
        return Db::table('co_china_data')->where('pid',$province_id)->select();
    }

    /**
     * @return 获取城市名称
     */
    public function county(){
        $city=input('post.id');
        return Db::table('co_china_data')->where('pid',$city)->select();
    }


    /**
     * 生成验证码
     * @return json 验证码
     */
    public function verify(){
        $this->result($this->apiVerify(),1,'验证码获取成功');
    }

    /**
     * 生成API验证码
     */
    public function apiVerify()
    {
        return mt_rand(1000,9999);
    }


    /**
     * 注册银行列表
     * @return [type] [description]
     */
    public function bankCode(){
        return Db::table('co_bank_code')->select();
    }


    /**
     * 手机发送验证码
     * @param  [type] $phone   [手机号]
     * @param  [type] $content [短信发送内容]
     * @return [type]          [发送成功或失败]
     */
    public function smsVerify($phone,$content,$code)
    {
        return $this->sms->send_code($phone,$content,$code);
    }

    

}