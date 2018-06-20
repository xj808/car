<?php 
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
 * 小程序系统设置
 */
class SmallSystemSet extends Admin
{
	/**
	 * 设置车主分享获得的奖励金额
	 */
	public function setMoney()
	{
		$data = input('post.');
		$validate = validate('SystemSet');
		if($validate->check($data)){
			$data['create_person'] = Db::table('am_auth_user')
			                         ->where('id',$this->admin_id)
			                         ->value('name');
			$data['type'] = '车主分享奖励金额';
			$res = Db::table('am_system_setup')
			       ->strict(false)
			       ->insert($data);
			if($res){
				$this->result('',1,'设置车主分享获得的奖励金额成功');
			} else {
				$this->result('',0,'设置失败，请重试');
			}
		} else {
			$this->result('',0,$validate->getError());
		}
	}

	/**
	 * 查询已经设置的车主分享的奖励金额
	 */
	public function selectMoney()
	{
		$am = Db::table('am_system_setup')
		         ->where('type','车主分享奖励金额')
		         ->field('money,create_person')
		         ->find();
		if($am){
			$this->result(['am'=>$am],1,'获取信息成功');
		} else {
			$this->result('',0,'获取信息失败,请先设置');
		}
	}

	/**
	 * 修改车主分享获得的奖励金额
	 */
	public function alterMoney()
	{
		$data = input('post.');
		unset($data['token']);
		$validate = validate('SystemSet');
		if($validate->check($data)){
			$data['create_person'] = Db::table('am_auth_user')
			                         ->where('id',$this->admin_id)
			                         ->value('name');
			$res = Db::table('am_system_setup')
			       ->where('type','车主分享奖励金额')
			       ->update($data);
			if($res){
				$this->result('',1,'修改车主分享获得的奖励金额成功');
			} else {
				$this->result('',0,'修改失败，请重试');
			}        
		} else {
			$this->result('',0,$validate->getError());
		}
	}	
}