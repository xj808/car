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
	 * 运营商直接取消和修车厂的合作
	 * @return [type] [description]
	 */
	public function agentShop()
	{
		$data = input('post.');
		Db::startTrans();
		// 发送短信给被取消运营商下面的用户
		$res = $this->smsCancel($data['sid'],$data['content'],$data['company'],$data['reason']);
		if($res == ture){
			// 修改修车厂审核状态shop表
			if($this->setStatus($data['sid'])){
				// 增加运营商库存
				if($this->agentMateriel($data['sid'],$this->aid)){
					// 增加运营商可开店数量
					if($this->shopNum($data['sid'],$this->aid)){

						Db::commit();
						$this->result('',1,'操作成功');
					}else{
						Db::rollback();
						$this->result('',0,'操作失败');
					}

				}else{
					Db::rollback();
					$this->result('',0,'增加运营商库存失败');
				}

			}else{
				Db::rollback();
				$this->result('',0,'修改修车厂状态失败');
			}
			
		}else{
			Db::rollback();
			$this->result('',0,'发送信息失败');
		}
	}


	/**
	 * 已审核列表
	 * @return [type] [description]
	 */
	public function auditIndex()
	{
		$page = input('post.page');
		return $this->auditStatus(1,$page);
	}


	/**
	 * 修车厂取消合作未审核列表
	 * @return [type] [description]
	 */
	public function index()
	{
		$page = input('post.page');
		return $this->auditStatus(0,$page);
	}



	/**
	 * 修车厂取消合作申请列表
	 * @return [type] [description]
	 */
	public function auditStatus($status,$page)
	{
		$pageSize = 10;
		$count = Db::table('cs_apply_cancel')->where(['aid'=>$this->aid])->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('cs_apply_cancel cac')
			->join('cs_shop cs','cs.id=cac.sid')
			->where(['cac.aid'=>$this->aid,'cac.audit_status'=>$status])
			->field('cac.id,cac.sid,cac.company,cac.leader,phone,service_num,cac.reason,cac.create_time')
			->order('id desc')->page($page,$pageSize)->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}



	/**
	 * 取消形式修车厂自己把剩下的用户油发送
	 * @param  [type] $sid [description]
	 * @param  [type] $aid [description]
	 * @return [type]      [description]
	 */
	public function adopt()
	{	
		Db::startTrans();
		// 获取修车厂名称、取消合作原因、
		$data=input('post.');
		$validate = validate('shopCancel');
		if(!$validate->check($data)){
			$this->result('',0,$validate->getError());
		}else{
			// 修改修车厂审核状态shop表及取消合作表的状态为1
			if($this->setStatus($data['sid']) == true){
				// 增加运营商库存
				if($this->agentMateriel($data['sid'],$this->aid) == true){
					// 增加运营商可开店数量
					if($this->shopNum($data['sid'],$this->aid) == true){
							// 清空取消合作修车厂的库存
							if($this->clearRation($data['sid']) == true){
								// 判断运营商选择让用户领油还是换店   1领油  2换店
								if($data['num'] == 1){

                                    Db::table('u_card')->where('sid',$data['sid'])->setField('remain_times',0);
                                    $res = $this->oilSms($data['sid'],$data['company'],$data['reason'],$data['content']);
                                    if($res == '请求成功'){
                                    	Db::commit();
										$this->result('',0,'操作成功,请在通过列表查看需要油品详情');
                                    }else{
                                    	Db::rollback();
										$this->result('',0,$res);
                                    }

                                }else{
                                	$res = $this->oilSms($data['sid'],$data['company'],$data['reason']);
                                    if($res == '请求成功'){
                                    	Db::commit();
										$this->result('',0,'操作成功,请在通过列表查看需要油品详情');
                                    }else{
                                    	Db::rollback();
										$this->result('',0,$res);
                                    }
                                }	
							}else{
								Db::rollback();
								$this->result('',0,'操作失败');
							}
					}else{
						Db::rollback();
						$this->result('',0,'增加可开店数量失败');
					}
				}else{
					Db::rollback();
					$this->result('',0,'增加库存失败');
				}

			}else{
				Db::rollback();
				$this->result('',0,'修改状态失败');
			}
			
			
		}
		
		

	}

	/**
	 * 审核通过列表详情
	 * @return [type] [description] 交接油品。  用户信息
	 */
	public function detail()
	{	
		$sid = input('post.sid');
		$ration=Db::table('cs_apply_cancel')->where('sid',$sid)->json(['detail'])->find();
		$user_oil=$this->userCar($sid);
		return $data=['ration'=>$ration['detail'],'user_oil'=>$user_oil];
	}



	/**
	 * 修改修车厂审核状态为取消合作
	 * @param [type] $sid [修车厂id]
	 */
	private function setStatus($sid)
	{
		$res = Db::table('cs_shop')->where('id',$sid)->setField('audit_status',4);
		if($res !== false){
			// 修改取消合作表审核时间
			$re = Db::table('cs_apply_cancel')->where('id',$sid)->update(['audit_time'=>time(),'audit_status'=>1]);
			if($re !== false){
				return true;
			}
		}
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
			// 增加授信库
			Db::table('ca_ration')->where($where)->inc('open_stock',$v['stock']+$materiel_stock)->update();
			// 减少补给库数量补充给授信库
			Db::table('ca_ration')->where($where)->dec('materiel_stock',$materiel_stock)->update();
			
		}
		// 把运营商与修车厂需要交接的物料表入库到取消合作表
		if($this->shopOil($sid) == true){
			return true;
		}
		
		
	}


	/**
	 * 获取修车厂剩余库存量
	 * @param  [type] $sid [修车厂id]
	 * @return [type]      [description]
	 */
	private function shopRation($sid)
	{
		return Db::table('cs_ration cr')
				->join('co_bang_cate bc','cr.materiel = bc.id')
				->where('sid',$sid)->field('sid,materiel,ration,stock,name')->select();
	}


	/**
	 * 发送短信给用户
	 * @param  [type] $sid     [description]
	 * @param  [type] $content [description]
	 * @return [type]          [description]
	 */
	public function oilSms($sid,$company,$reason,$content='')
	{	
		// $con=$company.'因'.$reason.'停止邦保养服务,您可以'.$content;
		if($content == ''){
            $con = '【'.$company.'】'.'因,【'.$reason.'】停止邦保养服务,您可以更换到附近其他维修厂';
        }else{
            $con = '【'.$company.'】'.'因,【'.$reason.'】停止邦保养服务,您可以到【'.$content.'】领域剩余油品';
        }
		$user_data=$this->userCar($sid);
		if($user_data){
			foreach ($user_data as $k => $v) {
				$res = $this->smsVerify($v['phone'],$con);
			}
			// print_r($re);exit;
			if($res == "提交成功"){
				return  $res;
			}else{
				return $res;
			}	
		}else{
			return '该修车厂没有用户';
		}
		
		

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
				->where([['uc.sid','=',$sid],['uc.remain_times','>',0]])
				->field('phone,name,oil_name,litre,plate,uc.oil,remain_times')
				->select();
	
	}


	


	/**
	 * 清空修车厂的库存
	 * @return [type] [description]
	 */
	private function clearRation($sid)
	{
		$arr = Db::table('cs_ration')->where('sid',$sid)->field('ration,warning')->find();
		if(!empty($arr)){
			$res=Db::table('cs_ration')->where('sid',$sid)->delete();
			if($res){
				return true;
			}
		}else{
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
		if(empty($arr)){
			return true;
		};
		$num = $arr['ration']/$arr['warning'];
		$res = Db::table('ca_agent')
		->where('aid',$aid)
		->inc('shop_nums',$num)
		->dec('open_shop',$num)
		->update();
		if($res !== false){
			return true;
		}
		
	}

	

	/**
	 * 把运营商与修车厂需要交接的表入库到取消合作表
	 * @param  [type] $sid [description]
	 * @return [type]      [description]
	 */
	public function shopOil($sid)
	{
		$data=$this->shopRation($sid);

		foreach ($data as $key => $value) {
			$data[$key]=['name'=>$value['name'],'stock'=>$value['stock']];
		}

		$arr=['detail'=>$data];
		$res = Db::table('cs_apply_cancel')->json(['detail'])->where('sid',$sid)->update($arr);
		if($res !== false){
			return true;
		}
	}




}