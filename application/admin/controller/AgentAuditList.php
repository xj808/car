<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 运营商审核列表
*/
class AgentAuditList extends Admin
{

	/**
	 * 运营商未审核列表
	 * @return [type] [description]
	 */
	public function index()
	{
		$page = input('post.page')? : 1;
		$pageSize = 10;
		$count = Db::table('ca_agent')->where('status',0)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('ca_agent')
				->where('status',1)
				->field('aid,company,leader,phone,create_time')
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
		
	}

	/**
	 * 审核列表详情
	 * @return [type] [description]
	 */
	public function detail()
	{
		$aid =input('post.aid');
		if(!empty($aid)){

			$list = Db::table('ca_agent')->where(['aid'=>$aid,'status'=>1])->field('aid,license,usecost')->find();
			if($list){
				$this->result($list,1,'获取数据成功');
			}else{
				$this->result($list,0,'暂无数据');
			}

		}else{
			$this->result('',0,'没有运营商信息');
		}
		
	}



	/**
	 * 审核列表通过操作
	 * @return [type] [description]
	 */
	public function  adopt()
	{
		//获取售卡利润、送货延迟罚款、汽修厂工时费、汽修厂成长基金、汽修厂好评奖励
		$data = input('post.');
		$validate = validate('SetAgent');
		if($validate->check($data)){
			Db::startTrans();
			// 设置售卡利润、送货延迟罚款、汽修厂工时费、汽修厂成长基金、汽修厂好评奖励
			$res = Db::table('ca_agent_set')->strict(false)->insert($data);
			if($res){
				// 修改运营商的状态为已通过审核状态
				if($this->setStatus(2,$data['aid']) !== false){

					Db::commit();
					$this->result('',1,'操作成功');
				
				}else{
					Db::rollback();
					$this->result('',0,'操作失败');
				}
				
			}else{
				Db::rollback();
				$this->result('',0,'设置失败');
			}
		}else{
			$this->result('',0,$validate->getError());
		}

	}



	/**
	 * 运营商审核列表详情驳回操作
	 * @return [type] [description]
	 */
	public function reject()
	{
		$aid = input('post.aid');
		$reason = input('post.reason');
		Db::startTrans();
		if(!empty($reason)){
			// 删除运营商注册信息
			$res = Db::table('ca_agent')->where('aid',$aid)->delete();
			if($res){
				// 获取运营商电话
				$phone = Db::table('ca_agent')->where('aid',$aid)->value('phone');
				// 编辑短信发送内容
				$content = '您的申请,因【'.$reason.'】被驳回,请重新注册。';
				// 给运营商发送短信告诉他您的开通被驳回
				$sms = $this->smsVerify($phone,$content);
				if($sms == '请求成功'){
					Db::commit();
					$this->result('',1,'驳回成功');
				}else{
					Db::commit();
					$this->result('',0,$this->smsVerify($phone,$content));
				}

			}else{
				Db::rollback();
				$this->result('',0,'清除用户信息失败');
			}
		}else{
			Db::rollback();
			$this->result('',0,'驳回理由不能为空');
		}
		
		
		
	}


	/**
	 * 修改运营商状态
	 * @param [type] $status [description]
	 */
	public function setStatus($status,$aid)
	{
		$result = Db::table('ca_agent')->where('aid',$aid)->setField('status',$status);
		if($result !== false){
			return true;
		}
	}
	
}