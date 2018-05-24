<?php 
namespace app\agent\controller;
use app\base\controller\Base;
use think\Db;
use Firebase\JWT\JWT;


/**
* 登录
*/
class Login extends Base
{   

	/**
     * 登录
     * @return json
     */
    public function login(){
        $data=input('post.');
        $validate=validate('Login');
        if($validate->check($data)){
            $arr=Db::table('ca_agent')->where('login',$data['login'])->find();
            if($arr){
                if(compare_password($data['pass'],$arr['pass'])){
                    $token=$this->token($arr['aid'],$data['login']);
                    $ar=['token'=>$token,'status'=>$arr['status']];
                    $this->result($ar,1,'登录成功');
                }else{
                    $this->result('',0,'登录失败');
                }
            }else{
                $this->result('',0,'用户不存在');
            }
        }else{
             $this->result('',0,$validate->getError());
        }
    }
    
    

    /**
     * 忘记密码
     * @return json 修改成功或失败
     */
    public function forget(){
        $data=input('post.');
        $validate=validate('Forget');
        if($validate->check($data)){
            if($this->sms->compare($data['phone'],$data['code'])){
                $res=Db::table('ca_agent')->where('phone',$data['phone'])->setField('pass',get_encrypt($data['pass']));
                if($res!==false){
                    $msg=['status'=>1,'msg'=>'修改成功'];
                }else{
                    $msg=['status'=>0,'msg'=>'修改失败'];
                }
            }else{
                $msg=['status'=>0,'msg'=>'验证码错误'];
            }
        }else{
            $msg=['status'=>0,'msg'=>$validate->getError()];
        }
        return $msg;
    }


    /**
     * @param   用户id
     * @param  用户登录账户
     * @return JWT签名
     */
    private function token($uid,$login){
        $key=create_key();   //
        $token=['id'=>$uid,'login'=>$login];
        $JWT=JWT::encode($token,$key);
        JWT::$leeway = 60;
        return $JWT;
    }


    

}