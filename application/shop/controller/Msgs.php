<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use think\Db;
use Msg\Msg;

/**
 * 汽修厂获取系统消息
 */
class Msgs extends Shop
{
	/**
	 * 进程初始化
	 */
	public function initialize()
	{
		parent::initialize();
		$this->coMsg = new Msg();
	}

	/**
	 * 消息列表
	 */
	public function getList()
	{
		$page = input('post.page') ? : 1;
		$list = $this->coMsg->msgList('cs_msg',$this->sid,$page);
		if($list){
			$this->result($list,1,'获取消息列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 获取消息详情
	 */
	public function detail()
	{
		$mid = input('post.mid');
		$datil = $this->coMsg->msgDetail('cs_msg',$mid,$this->sid,2);
		if($datil){
			$this->result($datil,1,'获取消息详情成功');
		}else{
			$this->result('',0,'获取消息详情失败');
		}
	}

}