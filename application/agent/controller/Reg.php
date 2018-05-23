<?php 
namespace app\agent\controller;
use app\base\controller\Base;
use think\Db;
/**
* 注册登录
*/
class Reg extends Base{
	function initialize(){
		parent::initialize();
		$this->agent="ca_agent";
		$this->agent_set="ca_agent_set";
	}
	 /**
	  * 注册操作
	  * @return json  注册成功或失败
	  */
	public function reg(){
		$data=input('post.');
		$validate=validate('Reg');
		if($validate->check($data)){
			if($this->sms->compare($data['phone'],$data['code'])){
				// 密码加密
				$data['pass']=get_encrypt($data['pass']);
				$res=Db::name($this->agent)->strict(false)->insert($data);
				if($res){
					$this->result('',1,'注册成功');
				}else{
					$this->result('',0,'注册成功');
				}
			}else{
				$this->result('',0,'验证码错误或失效');
			}
		}else{
			$this->result('',0,$validate->getError());
		}
	}

	/**
	 * 注册发送手机验证码
	 * @return [type] [发送成功或失败]
	 */
	public function regCode()
	{
		$phone=input('post.phone');
		// 生成四位验证码
        $code=$this->apiVerify();
		$content="您的验证码是：【".$code."】。请不要把验证码泄露给其他人。";
		return $this->smsVerify($phone,$content,$code);
	}
	
	
}
