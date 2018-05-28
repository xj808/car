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
        $this->coMsg->getUrMsg(3,$this->msg,$this->aid);
	}

	/**
	 * 获取信息详情
	 * @return 信息详情
	 */
	public function msgDatil()
	{	$mid=input('post.mid');
		$datil=$this->coMsg->msgDetail($this->msg,$mid,$this->aid,3);
		if($datil){
			$this->result($datil,1,'获取消息详情成功');
		}else{
			$this->result($datil,0,'获取消息详情失败');
		}
	}

	/**
	 * 消息列表全部
	 * @return json  消息列表数据
	 */
	public function msgList(){
		$list=$this->coMsg->msgList($this->msg,$this->aid);
		if($list){
			$this->result($list,1,'获取消息列表成功');
		}else{
			$this->result($datil,0,'获取消息列表失败');
		}
	}


	/**
	 * 获取未读消息列表
	 * @return [type] [description]
	 */
	public function unread()
	{	
		$list=$this->coMsg->msgLists($this->msg,$this->aid,0);
		if(!$list){
			$this->result($list,1,'获取未读消息列表成功');
		}else{
			$this->result($list,1,'获取未读消息列表成功');
		}
	}


	/**
	 * 获取已读消息列表
	 * @return [type] [description]
	 */
	public function read()
	{
		$list=$this->coMsg->msgLists($this->msg,$this->aid,1);
		if($list){
			$this->result($list,1,'获取已读消息列表成功');
		}else{
			$this->result($list,1,'获取已读消息列表成功');
		}
	}

	/**
	 * 运营商当前辖区修理厂数量
	 * @return [type] [description]
	 */
	public function shopNum(){
		return Db::table('ca_agent')->where('aid',$this->aid)->value('open_shop');
	}
	
}