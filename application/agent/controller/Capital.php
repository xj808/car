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
		parent::initialize();
		$this->income='ca_income';
	}

	/**
	 * 收入明细列表
	 * @return 列表
	 */
	public function index()
	{
		$page = input('post.page') ? : 1;
		$pageSize = 10;
		$count = Db::table('ca_income')->where('aid',$this->aid)->count();
		$rows = ceil($count / $pageSize);
		$list=Db::table('ca_income ci')
					->join('cs_shop cs','ci.sid=cs.id')
					->join('co_car_cate cc','cc.id=ci.car_cate_id')
					->join('u_user uu','ci.uid=uu.id')
					->where('ci.aid',$this->aid)
					->field('ci.id,odd_number,cs.company,cs.phone,amount,ci.create_time')
					->order('id desc')
					->page($page,$pageSize)->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 收入明细详情
	 * @return 列表
	 */
	public function detail()
	{	
		$id=input('post.id');
		return Db::table('ca_income ci')
					->join('cs_shop cs','ci.sid=cs.id')
					->join('co_car_cate cc','cc.id=ci.car_cate_id')
					->join('u_user uu','ci.uid=uu.id')
					->field('odd_number,cs.phone,amount,ci.create_time,company,name')
					->where('ci.id',$id)
				   ->select();
	}


	/**
	 * 提现申请页面
	 * @return [type] [description]
	 */
	public function forward()
	{	
		$arr = Db::table('ca_agent ca')
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
		Db::startTrans();
		if(!empty($data['money'])){
			// 判断运营商余额是否充足
			if($this->ifMoney($data['money'],$this->aid)){
				// 减少运营商总金额
				$result = Db::table('ca_agent')->where('aid',$this->aid)->dec('balance',$data['money'])->update();
				if($result !== false){

					$data['odd_number']=build_order_sn();
					$data['aid']=$this->aid;
					// 获取运营商现有余额
					$data['sur_amount'] = Db::table('ca_agent')->where('aid',$this->aid)->value('balance');
					// 提现申请插入数据库
					$res=Db::table('ca_apply_cash')->strict(false)->insert($data);
					if($res){
						Db::commit();
						$this->result('',1,'申请成功,请等待审核');
					}else{
						Db::rollback();
						$this->result('',0,'申请成功,请等待审核');
					}
				}else{
					$this->result('',0,'减少运营商余额失败');
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
		$page = input('post.page');
		$pageSize = 10;
		$count = Db::table('ca_apply_cash')->where('aid',$this->aid)->count();
		$rows = ceil($count / $pageSize);
		
		$list = Db::table('ca_apply_cash')
				->where('aid',$this->aid)
				->field('odd_number,account,money,create_time,audit_time,audit_status')
				->order('id desc')->page($page,$pageSize)->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
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
		$balance=Db::table('ca_agent')->where('aid',$aid)->value('balance');
		if($balance>=$money){
			return true;
		}
	}


}