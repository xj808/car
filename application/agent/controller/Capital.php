<?php
namespace app\agent\controller;
use app\base\controller\Agent;
use think\Db;
/**
* 资金管理
*/
class Capital extends Agent
{
	function initialize()
	{
		$this->income='ca_income';
	}

	/**
	 * 收入明细列表
	 * @return 列表
	 */
	public function index()
	{
		$token=input('post.token');
		$aid=$this->checkToken($token);
		return Db::table('ca_income ci')
					->join('cs_shop cs','ci.sid=cs.id')
					->join('co_car_cate cc','cc.id=ci.car_cate_id')
					->join('u_user uu','ci.uid=uu.id')
					->field('order_num,amount,ci.create_time,compay,name')
					->where('ci.aid',$aid)
				   ->select();
	}

	/**
	 * 提现申请页面
	 * @return [type] [description]
	 */
	public function forward()
	{	
		$token=input('post.token');
		$aid=$this->checkToken($token);
		$balance=Db::table('ca_agent_set')->where('aid',$aid)->field('balance')->find();
		$arr=Db::table('ca_agent')->where('aid',$aid)->field('bank,account,bank_name')->find();
		if($arr){
			$msg=['status'=>1,'msg'=>'获取提现信息成功','balance'=>$balance,'arr'=>$arr];
		}else{
			$msg=$this->jsonMsg(0,'获取提现信息失败');
		}
		return $msg;
	}


	/**
	 * 运营商提现申请操作
	 * @return [json] [成功或失败]
	 */
	public function cashApply()
	{
		
		$data=input('post.');
		$aid=$this->checkToken($data['token']);
		$data['cash_apply_sn']=rand().time();
		$data['aid']=$aid;
		$res=Db::table('ca_cash_apply')->strict(false)->insert($data);
		if($res){
			$msg=$this->jsonMsg(1,'申请成功,请等待审核');
		}else{
			$msg=$this->jsonMsg(0,'申请失败');
		}
		return $msg;
	}


	/**
	 * 提现明细列表
	 * @return [json]
	 */
	public function cash()
	{	
		$token=input('post.token');
		$aid=$this->checkToken($token);
		return Db::table('ca_cash_apply')
					->where('aid',$aid)
					->field('cash_apply_sn,account,money,create_time,audit_time,audit_status')
					->select();
	}


}