<?php 
/**
* 汽修厂注册
*/
namespace app\shop\controller;
use app\base\controller\Shop;
use think\Db;
use Msg\Sms;

class Reg extends Shop
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
	 * 进行注册操作
	 */
	public function reg()
	{
		// 获取提交过来的数据
		$data=input('post.');
		// 实例化验证
		$validate = validate('Reg');
		// 如果验证通过则进行邦保养操作
		if($validate->check($data)){
			// 检测手机验证码是否正确
			$check = $this->sms->compare($data['phone'],$data['code']);
			if($check !== false){
				// 密码加密
				$data['passwd']=get_encrypt($data['passwd']);
				// 进行入库操作
				$res=Db::table('cs_shop')->strict(false)->insert($data);
				// 返回处理结果
				if($res){
					$this->result('',1,'注册成功');
				}else{
					$this->result('',0,'注册成功');
				}

			}else{
				$this->result('',0,'手机验证码无效或已过期');
			}
		}else{
			$this->result('',0,$validate->getError());
		}
	}

	/**
	 * 发送短信验证码
	 */
	public function vcode()
	{
		$mobile = input('post.mobile');
		$code = $this->apiVerify();
		$content="您的验证码是：【{$code}】。请不要把验证码泄露给其他人。";
		$res = $this->sms->send_code($mobile,$content,$code);
		$this->result('',1,$res);
	}
}