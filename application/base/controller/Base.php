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
        $this->sms = new Sms();
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
    public function city($pid){
        return Db::table('co_china_data')->where('pid',$pid)->select();
    }
    /**
     * @return 获取城市名称
     */
    public function county($cid){
        return Db::table('co_china_data')->where('pid',$cid)->select();
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
    public function smsVerify($phone,$content,$code = '')
    {
        return $this->sms->send_code($phone,$content,$code);
    }
<<<<<<< HEAD






=======
>>>>>>> origin/dev
     /**
     * 修改密码发送手机验证码
     * @return [type] [发送成功或失败]
     */
    public function forCode($phone)
    {
        // 生成四位验证码
        $code=$this->apiVerify();
        $content="您的短信验证码是：【".$code."】。您正在通过手机号重置登录密码，如非本人操作，请忽略该短信。";
       return  $this->smsVerify($phone,$content,$code);
    }
<<<<<<< HEAD
=======


>>>>>>> origin/dev
    /**
     * 获取每组油的升数
     * @return 数组
     */
    public function bangCate()
    {   $where=[['pid','>','0'],['def_num','>',0]];
        return Db::table('co_bang_cate')->where($where)->field('id,def_num')->select();
    }
<<<<<<< HEAD
=======


>>>>>>> origin/dev
    /**
     * 修改状态
     * @param  [type] $table   要修改的表
     * @param  [type] $sid     要修改的id
     * @param  [type] $status  要修改的状态值
     * @return [type]         [description]
     */
    public function status($table,$id,$status)
    {
        $res=Db::table($table)->where('id',$id)->setField('audit_status',$status);
        if($res!==false){
            return true;
        }
    }
<<<<<<< HEAD
=======

>>>>>>> origin/dev
    
}