<?php 
namespace app\agent\controller;
use app\base\controller\Agent;
use think\Db;
/**
* 注册登录
*/
class Reg extends Agent{
	function initialize(){
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
			if($data['code']==123456){
				// 密码加密
				$data['pass']=get_encrypt($data['pass']);
				$res=Db::name($this->agent)->strict(false)->insert($data);
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

	/**
	 * 注册银行列表
	 * @return [type] [description]
	 */
	public function regBank(){
		$bank=Db::table('co_bank_code')->select();
		if($bank){
			$data=['status'=>1,'msg'=>'获取银行列表成功','bank'=>$bank];
		}else{
			$data=['status'=>0,'msg'=>'获取银行列表失败'];
		}
		return $data;
	}
	
}











 ?>