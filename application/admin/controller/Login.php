<?php
namespace app\admin\controller;
use app\base\controller\Base;
use think\Db;
use Firebase\JWT\JWT;
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
			$arr = Db::table('ca_auth_user')->where('name',$data['name'])->find();
			// 比较用户名是否存在
			if($arr['name'] == $data['name']){

				// 比较输入的密码是否和数据库存的密码一致
				if(compare_password($data['pwd'],$arr['pwd'])){

					$token = $this->token($data['name'],$arr['id']);

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
     * @param   用户id
     * @param  用户登录账户
     * @return JWT签名
     */
    private function token($uid,$login){

        $key=create_key();   //
        $token=['id'=>$uid,'login'=>$login];
        $JWT=JWT::encode($token,$key);
        JWT::$leeway =10;
        return $JWT;
    }


}