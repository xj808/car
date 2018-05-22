<?php
namespace app\agent\controller;
use app\base\controller\Agent;
use msg\Msg;
use think\Db;
/**
* 运营商首页，信息页面
*/
class Index extends Agent
{	
	function initialize(){
		parent::initialize();
		$this->msg='ca_msg';
		$this->coMsg=new Msg();

	}


	/**
	 * 运营商登录后向库里插入系统新发送的消息
	 * @return [type] [description]
	 */
	public function msg(){
		// $token=input('post.token');
		// $aid=$this->checkToken($token);
        $this->coMsg->getUrMsg(3,$this->msg,$aid);
	}

	/**
	 * 获取信息详情
	 * @return 信息详情
	 */
	public function msgDatil()
	{	$mid=input('post.mid');
		$token=input('post.token');
		$aid=$this->checkToken($token);
		$datil=$this->coMsg->msgDetail($this->msg,$mid,$aid,3);
		if($datil){
			$msg=$this->jsonMsg(1,'获取消息详情成功',$datil);
		}else{
			$msg=$this->jsonMsg(0,'获取消息详情失败');
		}
		return $msg;
	}

	/**
	 * 消息列表全部
	 * @return json  消息列表数据
	 */
	public function msgList(){
		$token=input('post.token');
		$aid=$this->checkToken($token);
		$list=$this->coMsg->msgList($this->msg,$aid);
		if($list){
			$msg=$this->jsonMsg(1,'获取消息列表成功',$list);
		}else{
			$msg=$this->jsonMsg(0,'获取消息列表失败');
		}
		return $msg;	
	}

	/**
	 * 获取未读消息列表
	 * @return [type] [description]
	 */
	public function unread()
	{	
		$token=input('post.token');
		$aid=$this->checkToken($token);
		$list=$this->coMsg->msgLists($this->msg,$aid,0);
		if($list){
			$msg=$this->jsonMsg(1,'获取未读列表成功',$list);
		}else{
			$msg=$this->jsonMsg(0,'获取未读列表失败');
		}
		return $msg;
	}


	/**
	 * 获取已读消息列表
	 * @return [type] [description]
	 */
	public function read()
	{
		$token=input('post.token');
		$aid=$this->checkToken($token);
		$list=$this->coMsg->msgLists($this->msg,$aid,1);
		if($list){
			$msg=$this->jsonMsg(1,'获取未读列表成功',$list);
		}else{
			$msg=$this->jsonMsg(0,'获取未读列表失败');
		}
		return $msg;
	}

	/**
	 * 运营商当前辖区修理厂数量
	 * @return [type] [description]
	 */
	public function shopNum(){
		$token=input('post.token');
		$aid=$this->checkToken($token);
		return Db::table('ca_agent_set')->where('aid',$aid)->value('open_shop');
	}
	
}