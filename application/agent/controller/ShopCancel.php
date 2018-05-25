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
			$this->formA($sid);
		}elseif($form == 2){

		}else{

		}
	}


	/**
	 * 取消合作形式一：修车厂取消合作继续做完邦保养
	 * @param  [type] $sid [description]
	 * @return [type]      [description]
	 */
	public function formA($sid)
	{
		Db::startTrans();
		// 修改修车厂审核状态shop表
		if($this->setStatus($sid) == true){
			// 修改取消合作表审核时间
			if($this->cancelTime($sid) == true){
				// 计算运营商需补充给修车厂的油品量和要回收的物料
				$ration=$this->shopRation($sid);
				// 增加运营商库存
				if($this->agentRation($ration,$this->aid) == true){

					// 清空取消合作修车厂的库存
					if($this->clearRation($sid) == true){
						echo 1;exit;
						Db::commit();
						$this->result($ration,1,'您已同意汽修厂的取消合作申请，请尽快和修车厂办理油品交接');
					}else{
						Db::rollback();
						$this->result('',0,'确认失败');
					}

				}else{
					Db::rollback();
					$this->result('',0,'增加运营商库存失败');
				}
			}else{
				Db::rollback();
				$this->result('',0,'修改审核时间失败');
			}
		}else{
			Db::rollback();
			$this->result('',0,'修改状态失败');
		}
		
		
	}



	/**
	 * 修改修车厂审核状态为取消合作
	 * @param [type] $sid [修车厂id]
	 */
	public function setStatus($sid)
	{
		$res=Db::table('cs_shop')->where('id',$sid)->setField('audit_status',4);
		if($res){
			return true;
		}
	}


	/**
	 * 修改取消合作表审核时间
	 * @return [type] [description]
	 */
	public function cancelTime($sid)
	{
		$res=Db::table('cs_cancel')->where('id',$sid)->setField('audit_time',time());
		if($res){
			return true;
		}
	}


	/**
	 * 获取修车厂剩余库存量
	 * @param  [type] $sid [修车厂id]
	 * @return [type]      [description]
	 */
	public function shopRation($sid)
	{
		$ration=Db::table('cs_ration')->where('sid',$sid)->field('sid,materiel,stock')->select();
		$userCar=$this->userCar(1);

		foreach ($ration as $k => $v) {

			foreach ($userCar as $key => $value) {

				if($v['materiel'] == $value['oil']){

					$ration[$k]['stock']=$v['stock']-$value['litre'];

				}	
			}
		}
		return $ration;
	}


	/**
	 * 获取修车厂做邦保养所需油数
	 * @param  [type] $sid [修车厂id]
	 * @return [type]      [description]
	 */
	public function userCar($sid)
	{
		return Db::table('u_card uc')
			->join('co_bang_data bd','uc.car_cate_id=bd.cid')
			->where('sid',$sid)
			->field('uc.oil,bd.litre')
			->select();
	}


	/**
	 * 增加运营商库存
	 * @param  [type] $aid [description]
	 * @return [type]      [description]
	 */
	public function agentRation($data,$aid)
	{
		foreach ($data as $k => $v) {

			$res=Db::table('ca_ration')->where(['aid'=>$aid,'materiel'=>$v['materiel']])->inc('materiel_stock',$v['stock'])->update();
		}
		if($res){
			return true;
		}
		
	}

	/**
	 * 清空修车厂的库存
	 * @return [type] [description]
	 */
	public function clearRation($sid)
	{
		$res=Db::table('cs_ration')->where('sid',$sid)->delete();
		if($res){
			return true;
		}
	}
}