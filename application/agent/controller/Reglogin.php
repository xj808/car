<?php 
namespace app\agent\controller;
use app\base\controller\Jwttoken;
use think\Db;
/**
* 注册登录
*/
class Reglogin extends Jwttoken{
	// 注册
	public function reg(){
		$data=input('post.');
		$validate=validate('Reg');
		if($validate->check($data)){
			if(compare_mobiel_code($data['phone'],$data['code'])){
				// 密码加密
				$data['pass']=getEncrypt($data['pass']);
				$res=Db::table('m_agent')->strict(false)->insert($data);
				if($res){
					$data=['status'=>1,'msg'=>'注册成功'];
				}else{
					$data=['status'=>0,'msg'=>'注册失败'];
				}
			}else{
				$data=['status'=>0,'msg'=>'验证码错误或失效'];
			}
		}else{
			$data=['status'=>0,'msg'=>$validate->getError()];
		}
		return $data;
	}
	// 登录
    public function login(){
        $data=input('post.');
        $validate=validate('Login');
        if($validate->check($data)){
            $arr=Db::table('m_agent')->where('login',$data['login'])->find();
            if($arr){
                if(comparePassword($data['pass'],$arr['pass'])){
                    $token=$this->token($arr['aid'],$data['login']);
                    $msg=['status'=>1,'msg'=>'登录成功','token'=>$token];
                }else{
                    $msg=['status'=>0,'msg'=>'登录失败'];
                }
            }else{
               $msg=['status'=>0,'msg'=>'用户不存在']; 
            }
        }else{
            $msg=['status'=>0,'msg'=>$validate->getError()];
        }
        return $msg;
    }
}











 ?>