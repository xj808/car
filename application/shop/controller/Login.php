<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use Firebase\JWT\JWT;
use Msg\Sms;
use think\Db;

/**
* 登录
*/
class Login extends Shop
{

	/**
	 * 进程初始化
	 */
	public function initialize()
	{
		$this->sms = new Sms();
		header("Access-Control-Allow-Origin: *");
	}

	/**
	 * 进行登录操作
	 */
	public function doin()
	{
		// 获取提交过来的数据
		$data=input('post.');
		// 实例化验证
		$validate = validate('Login');	
		// 如果验证通过则进行登录操作
		if($validate->check($data)){
			// 查找用户是否存在
			$us = Db::table('cs_shop')->field('id,passwd,audit_status')->where('usname',$data['usname'])->find();
			if($us){
				if(compare_password($data['passwd'],$us['passwd'])){
					// 生成token作为验证使用
					$token = $this->token($us['id'],$data['usname']);
					// 更新运营商
					$this->setAid($us['id']);
					// 登录成功返回数据
					$this->result(['token'=>$token,'audit_status'=>$us['audit_status']],1,'登录成功');
				}else{
					$this->result('',0,'用户名或密码错误');
				}
			}else{
				$this->result('',0,'用户不存在');
			}
		}else{
			$this->result('',0,$validate->getError());
		}
	}

	/**
	 * 绑定运营商
	 */
	public function setAid($sid)
	{
		// 获取维修厂所在区域
		$county_id = Db::table('cs_shop_set')->where('sid',$sid)->value('county_id');
		// 通过县区id获得运营商的id
		$aid = Db::table('ca_area')->where('area',$county_id)->value('aid');
		// 更新运营商id
		$aid =  $aid ? : 0;
		Db::table('cs_shop')->where('sid',$sid)->setField('aid',$aid);
	}



	/**
	 * 找回密码
	 */
	public function forget()
	{
		// 获取提交过来的数据
		$data=input('post.');
		// 实例化验证
		$validate = validate('Forget');
		// 如果验证通过则进行登录操作
		if($validate->check($data)){
			// 检测手机验证码是否正确
			$check = $this->sms->compare($data['mobile'],$data['code']);
			if($check !== false){
				// 进行修改密码的操作
				$res=Db::table('cs_shop')->where('phone',$data['mobile'])->setField('passwd',get_encrypt($data['passwd']));
				if($res !== false){
                    $this->result('',1,'修改成功');
                }else{
                    $this->result('',0,'修改失败');
                }
			}else{
				$this->result('',0,'手机验证码无效或已过期');
			}
		}else{
			$this->result('',0,$validate->getError());
		}
	}

	/**
	 * 生成图文验证码
	 */
	public function getCode()
	{
		$code = $this->apiVerify();
		$this->result(['imgCode'=>$code],1,'获取成功');
	}

	/**
	 * 发送短信验证码
	 */
	public function vcode()
	{
		$mobile = input('post.mobile');
		$code = $this->apiVerify();
		$content = "您的短信验证码是【{$code}】。您正在通过手机号重置登录密码，如非本人操作，请忽略该短信。";
		$res = $this->sms->send_code($mobile,$content,$code);
		$this->result('',1,$res);
	}


	/**
     * @param   用户id
     * @param  用户登录账户
     * @return JWT签名
     */
    private function token($sid,$usname){
        $key=create_key();   //
        $token=['id'=>$sid,'usname'=>$usname];
        $JWT=JWT::encode($token,$key);
        JWT::$leeway = 600;
        return $JWT;
    }

    public function test()
    {
    	return Db::table('cs_shop')->select();
    }

}