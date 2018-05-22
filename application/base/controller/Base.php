<?php 
namespace app\base\controller;
use think\Controller;
use Firebase\JWT\JWT;
use think\Db;
/**
* token登录  验证
*/
class Base extends Controller{
    
    

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
        return Db::table('co_china_data')->where('pid',$city_id)->select();
    }


    /**
     * 生成验证码
     * @return json 验证码
     */
    public function verify(){
        $this->result(rand(1111,9999),1,'验证码获取成功');
    }

    

}







 ?>