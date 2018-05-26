<?php
namespace app\agent\controller;
use app\base\controller\Agent;
/**
* 资金管理
*/
class Capital extends Agent
{
	function initialize()
	{
		parent::initialize();
		$this->income='ca_income';
	}

	/**
	 * 收入明细列表
	 * @return 列表
	 */
	public function index()
	{
		return Db::table('ca_income ci')
					->join('cs_shop cs','ci.sid=cs.id')
					->join('co_car_cate cc','cc.id=ci.car_cate_id')
					->join('u_user uu','ci.uid=uu.id')
					->field('order_num,cs.compay,cs.phone,amount,ci.create_time')
					->where('ci.aid',$this->aid)
				   ->paginate(10);
	}

	/**
	 * 收入明细详情
	 * @return 列表
	 */
	public function detail()
	{
		return Db::table('ca_income ci')
					->join('cs_shop cs','ci.sid=cs.id')
					->join('co_car_cate cc','cc.id=ci.car_cate_id')
					->join('u_user uu','ci.uid=uu.id')
					->field('order_num,cs.phone,amount,ci.create_time,compay,name')
					->where('ci.aid',$this->aid)
				   ->paginate(10);
	}


	/**
	 * 提现申请页面
	 * @return [type] [description]
	 */
	public function forward()
	{	
		$arr = Db::table('ca_agent ca')
				->join('ca_agent_set as','ca.aid=as.aid')
				->where('ca.aid',$this->aid)
				->field('bank,account,bank_name,balance')
				->find();
		if($arr){
			$this->result($arr,1,'获取提现信息成功');
		}else{
			$this->result($arr,0,'获取提现信息失败');
		}
	}


	/**
	 * 运营商提现申请操作
	 * @return [json] [成功或失败]
	 */
	public function cashApply()
	{
		
		$data=input('post.');
		if($data['money']){
			// 判断运营商余额是否充足
			if($this->ifMoney($data['money'],$this->aid)){
				$data['cash_apply_sn']=rand().time();
				$data['aid']=$this->aid;
				$res=Db::table('ca_cash_apply')->strict(false)->insert($data);
				if($res){
					$this->result('',1,'申请成功,请等待审核');
				}else{
					$this->result('',0,'申请成功,请等待审核');
				}
			}else{
				$this->result('',0,'您的余额不足');
			}
		}else{
			$this->result('',0,'提现金额不能为空');
		}
	}


	/**
	 * 提现明细列表
	 * @return [json]
	 */
	public function cash()
	{	
		return Db::table('ca_cash_apply')
					->where('aid',$this->aid)
					->field('cash_apply_sn,account,money,create_time,audit_time,audit_status')
					->paginate(10);
	}

	/**
	 * 判断运营商申请取现的金额是否大于现有金额
	 * @param  [type] $money [所取现金额]
	 * @param  [type] $aid   [运营商id]
	 * @return [type]        [布尔值]
	 */
	public function ifMoney($money,$aid)
	{
		// 获取运营商现有所有收入
		$balance=Db::table('ca_agent_set')->where('aid',$aid)->value('balance');
		if($balance>=$money){
			return true;
		}
	}


}