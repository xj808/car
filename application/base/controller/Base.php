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
     * @param   用户id
     * @param  用户登录账户
     * @return JWT签名
     */
	public function token($uid,$login){
        $key=create_key();   //
        $token=['id'=>$uid,'login'=>$login];
        $JWT=JWT::encode($token,$key);
        JWT::$leeway = 60;
        return $JWT;
    }


    /**
     * 前台返回token时验证token
     * @param  token
     * @return 包含用户名、id的数组
     */
    public function checkToken($token){
        $key=create_key();
        if(empty($token)){
            return  false;
        }
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
        return $this->commonCounty($city);
    }

    /**
     *@return  未被选中的城市 
     */
    public function commonCounty($city_id)
    {
        return Db::table('co_china_data')->where('pid',$city_id)->select();
    }

    /**
     * json 返回数据时的消息方法
     * @param  [type] $status 状态
     * @param  [type] $msg    提示消息
     * @param  string $data   其他要传的值
     * @return [type]         [description]
     */
    public function jsonMsg($status,$msg,$data=''){
        return ['status'=>$status,'msg'=>$msg,'data'=>$data];
    }
}







 ?>