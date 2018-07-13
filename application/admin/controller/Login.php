<?php
namespace app\admin\controller;
use app\base\controller\Base;
use think\Db;
use Firebase\JWT\JWT;
use think\Request;
/**
* 总后台登录
*/
class Login extends Base
{
	
	/**
	 * 登录
	 * @return [type] [description]
	 */
	public function index()
	{
		$data = input('post.');
		$validate = validate('Login');
		
		if($validate->check($data)){
			$arr = Db::table('am_auth_user')->where('name',$data['name'])->find();
			// 比较用户名是否存在
			if($arr['name'] == $data['name']){

				// 比较输入的密码是否和数据库存的密码一致
				if(compare_password($data['pwd'],$arr['pwd'])){
					$request = new Request();
					$a = [
						'last_login_ip'=>$request->ip(),
						'last_login_time'=>time(),
					];
					$res = Db::table('am_auth_user')->where('id',$arr['id'])->update($a);
					$token = $this->token($arr['id'],$data['name']);

					$this->result($token,1,'登录成功');
				}else{
					$this->result('',0,'密码错误');
				}

			}else{

				$this->result('',0,'用户名错误或用户不存在');
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
                $res=Db::table('am_auth_user')->where('phone',$data['phone'])->setField('pwd',get_encrypt($data['pass']));
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
	 * 忘记密码短信
	 * @return [type] [description]
	 */
	public function pwdApi()
	{	
		$phone = input('post.phone');
		$this->result('',1,$this->forCode($phone));
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
        JWT::$leeway =600;
        return $JWT;
    }


}