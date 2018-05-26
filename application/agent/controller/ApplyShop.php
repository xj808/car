<?php
namespace app\agent\controller;
use app\base\controller\Agent;
/**
* 修车厂物料申请
*/
class ApplyShop extends Agent
{	
	function initialize()
	{
		parent::initialize();
	}


	/**
	 * 修理厂申请物料未审核列表
	 * @return [type] [description] 
	 */
	public function index()
	{
		$data=$this->apply(0,$this->aid);
		if($data){
			$this->result($data,1,'获取未审核列表成功');
		}else{
			$this->result('',0,'没有数据');
		}
	}

	/**
	 * 修理厂申请物料已审核发货列表
	 * @return [type] [description]
	 */
	public function audit()
	{	
		$data=$this->apply(1,$this->aid);
		if($data){
			$this->result($data,1,'获取已发货列表成功');
		}else{
			$this->result('',0,'没有数据');
		}
	}

	/**
	 * 修理厂自己主动关闭物料申请
	 * @return [type] [description]
	 */
	public function close()
	{
		$data=$this->apply(2,$this->aid);
		if($data){
			$this->result($data,1,'获取关闭物料申请列表成功');
		}else{
			$this->result($data,0,'没有数据');
		}
	}


	/**
	 * 修车厂物料申请列表
	 * @return [json] 修车厂物料列表 
	 */
	public function apply($status,$aid)
	{
		return Db::table('cs_apply sa')
			->join('cs_shop ss','sa.sid=ss.id')
			->where(['sa.aid'=>$aid,'sa.audit_status'=>$status])
			->field('sa.sid,compay,leader,phone,sa.create_time')
			->paginate(10);
	}


	/**
	 * 修车厂物料申请详情
	 * @return [json] 修车厂物料列表 
	 */
	public function detail($status,$aid)
	{	
		$sid = input('post.sid');
		return Db::table('cs_apply sa')
			->join('cs_shop ss','sa.sid=ss.id')
			->where('ss.id',$sid)
			->field('sa.id,sa.sid,compay,leader,phone,sa.create_time,detail,audit_status')
			->select();
	}

	/**
	 * 物料申请确认操作
	 * @return [type] 操作失败或成功
	 */
	public function adopt()
	{	
		// 获取该订单id
		$id=input('post.id');
		$sid=input('post.sid');
		Db::startTrans();
		// 给修车厂发送短信
		$this->applyCode($sid);
		//修车厂申请 状态改为已审核
		$res = $this->status('cs_apply',$id,1);
		if($res == true){
			Db::commit();
			$this->result('',1,'您已确认,请尽快发货');
		}else{
			Db::rollback();
			$this->result('',0,'操作失败');
		}
	}

	/**
	 * 运营商申请延迟
	 * @return [type] 
	 */
	public function delayed()
	{
		$id=input('post.id');
		// 点击延时over_time延迟三天
		$res=Db::table('cs_apply')->where(['id'=>$id,'aid'=>$this->aid])->inc('over_time',259200);
		if($res){
			$this->result('',1,'延时成功');
		}else{
			$this->result('',0,'延时失败');
		}
	}


	
	// public function shijian()
	// {
	// 	$over_time=time()+259200;
	// 	// $over_time=date("Y-m-d h:i:s",$over_time); 
	// 	Db::table('cs_apply')->where('id',1)->setField('over_time',$over_time);
	// }
	

	private function applyCode($sid)
	{
		$phone=Db::table('cs_shop')->where('id',$sid)->value('phone');
		$content="您申请的货物已发出,请注意查收";
		return $this->smsVerify($phone,$content);
	}



}