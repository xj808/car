<?php
namespace app\agent\controller;
use app\base\controller\Agent;
use think\Db;
/**
* 修车厂取消合作
*/
class ShopCancel extends Agent
{
	/**
	 * 修车厂取消合作列表
	 * @return [type] [description]
	 */
	public function index()
	{
		
		return Db::table('cs_cancel')->where('aid',$this->aid)->select();
	}

	/**
	 * 修车厂取消合作审核通过
	 * @return [type] [description]
	 */
	public function adopt()
	{

		$sid=input('post.sid');
		$form=input('post.form');
		Db::startTrans();
		// 判断修车厂取消合作选择哪种形式分配油
		if($form == 1){
			$ration=$this->formA($sid,$this->aid);
			if($ration){
				Db::commit();
				$this->result($ration,1,'您已同意汽修厂的取消合作申请，请尽快和修车厂办理油品交接');
			}else{
				Db::rollback();
				$this->result('',0,'确认失败');
			}
		}elseif($form == 2){

			$this->formB($sid);



		}else{

		}
	}


	/**
	 * 取消合作形式一：修车厂取消合作继续做完邦保养
	 * @param  [type] $sid [description]
	 * @return [type]      [description]
	 */
	public function formA($sid,$aid)
	{
			if($reation = $this->commonF($sid,$aid)){
				return $reation;
			}

	}



	public function formB($sid,$aid)
	{
		// 获取修车厂名称
		$company = Db::table('cs_cancel')->where(['sid'=>$sid,'aid'=>$aid])->value('company');
		// 获取用户车辆信息
		$user=$this->userCar();
		
		if($this->commonF($sid,$aid)){
			$this->smsCancel($user)
		}

	}


	/**
	 * A、B、C形式都可用到
	 * @param  [type] $sid [description]
	 * @param  [type] $aid [description]
	 * @return [type]      [description]
	 */
	public function commonF($sid,$aid)
	{
		// 修改修车厂审核状态shop表
			$this->setStatus($sid);
			// 修改取消合作表审核时间
			$this->cancelTime($sid);
			// 增加运营商授信库存
			$ration=$this->agentMateriel($sid,$aid);
			$this->shopNum($sid,$aid);
			// 清空取消合作修车厂的库存
			if($this->clearRation($sid) == true){
				return $ration;
				
			}else{
				return false;
				
			}
	}


	/**
	 * 修改修车厂审核状态为取消合作
	 * @param [type] $sid [修车厂id]
	 */
	private function setStatus($sid)
	{
		Db::table('cs_shop')->where('id',$sid)->setField('audit_status',4);
	}


	/**
	 * 修改取消合作表审核时间
	 * @return [type] [description]
	 */
	private function cancelTime($sid)
	{
		Db::table('cs_cancel')->where('id',$sid)->setField('audit_time',time());
	}



	/**
	 * 减少运营商补货库存
	 * @param  [type] $aid [description]
	 * @return [type]      [description]
	 */
	private function agentMateriel($sid,$aid)
	{
		$data=$this->shopRation($sid);
		foreach ($data as $k => $v) {
			$where=['aid'=>$aid,'materiel'=>$v['materiel']];
			// 运营商减少补货库存（给授信库增加）
			$materiel_stock=$v['ration']-$v['stock'];
			Db::table('ca_ration')->where($where)->inc('open_stock',$v['stock']+$materiel_stock)->update();
			Db::table('ca_ration')->where($where)->dec('materiel_stock',$materiel_stock)->update();
			
		}
		return $data;
		
	}


	/**
	 * 获取修车厂剩余库存量
	 * @param  [type] $sid [修车厂id]
	 * @return [type]      [description]
	 */
	private function shopRation($sid)
	{
		$ration=Db::table('cs_ration cr')
				->join('co_bang_cate bc','cr.materiel = bc.id')
				->where('sid',$sid)->field('sid,materiel,ration,stock,name')->select();
		// 获取运营商补货库存需补给给开通库存的数量
		$userCar=$this->userCar($sid);

		foreach ($ration as $k => $v) {

			foreach ($userCar as $key => $value) {

				if($v['materiel'] == $value['oil']){
					// 运营商需要回收的物料和需要补给修理厂的物料
					$ration[$k]['stock']=$v['stock']-$value['litre'];
					

				}	
			}
		}
		return $ration;
	}


	/**
	 *获取修车厂用户电话、车辆用油升数、车牌号
	 * @param  [type] $sid [修车厂id]
	 * @return [type]      [description]
	 */
	private function userCar($sid)
	{
		return Db::table('u_card uc')
				->join('u_user uu','uc.uid = uu.id')
				->join('co_bang_data bd','uc.car_cate_id = bd.cid')
				->where('sid',1)
				->field('phone,name,oil_name,litre,plate,uc.oil')
				->select();
	}


	

	/**
	 * 清空修车厂的库存
	 * @return [type] [description]
	 */
	private function clearRation($sid)
	{
		$res=Db::table('cs_ration')->where('sid',$sid)->delete();
		if($res){
			return true;
		}
	}


	/**
	 * 给运营商增加开通修理厂数量
	 * @param  [type] $sid [description] 修理厂id
	 * @param  [type] $aid [description] 运营商id
	 * @return [type]      [description]
	 */
	private function shopNum($sid,$aid)
	{
		// 根据修车厂配给计算运营商增加的可开通修理厂数量
		// 获取修车厂的配给数量
		$arr = Db::table('cs_ration')->where('sid',$sid)->field('ration,warning')->find();
		$num=$arr['ration']/$arr['warning'];
		Db::table('ca_agent')
		->where('aid',$aid)
		->inc('shop_nums',$num)
		->dec('open_shop',$num)
		->update();
		
	}


	public function smsCancel($user)
	{	
		$this->smsVerify($user['phone'],$content);
	}


}