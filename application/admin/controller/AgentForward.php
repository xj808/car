<?php
namespace app\admin\controller;
use think\Db;
use app\base\controller\Admin;
/**
* 运营商提现
*/
class AgentForward extends Admin
{
	/**
	 * 运营商提现等待审核列表
	 * @return [type] [description]
	 */
	public function waitList()
	{
		$page = input('post.page');
		$this->index($page,0);
	}



	/**
	 * 运营商提现已审核列表
	 * @return [type] [description]
	 */
	public function auditList()
	{
		$page = input('post.page');
		$this->index($page,1);
	}



	/**
	 * 运营商提现驳回列表
	 * @return [type] [description]
	 */
	public function rejList()
	{
		$page = input('post.page');
		$this->index($page,2);
	}


	/**
	 * 收入明细列表
	 * @return [type] [description]
	 */
	public function incomeList()
	{
		$aid  = input('post.aid');
		$page = input('post.page')? : 1;
		$pageSize = 10;
		$count = Db::table('ca_income')->where('aid',$aid)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('ca_income')
				->where('aid',$aid)
				->field('form,amount,create_time')
				->order('id desc')->page($page,$pageSize)
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}


	/**
	 * 提现明细
	 * @return [type] [description]
	 */
	public function forwardList()
	{
		$aid = input('post.aid');
		$page = input('post.page')? :1;
		$pageSize = 10;
		$count = Db::table('ca_apply_cash')->where(['aid'=>$aid,'audit_status'=>1])->count();

		$rows = ceil($count / $pageSize);

		$list = Db::table('ca_apply_cash')
				->where(['aid'=>$aid,'audit_status'=>1])
				->order('id desc')->page($page,$pageSize)
				->field('audit_time,money,sur_amount')
				->select();

		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}	
	}


	/**
	 * 提现审核列表
	 * @return [type] [description]
	 */
	public function audit()
	{
		// 获得运营商id
		$aid = input('post.aid');
		// 获取本次提现审核的时间和金额
		$now = Db::table('ca_apply_cash')->where(['aid'=>$aid,'audit_status'=>0])->field('id,aid,money,sur_amount')->find();
		$old = Db::table('ca_apply_cash')
				->where(['aid'=>$aid,'audit_status'=>1])
				->order('audit_time desc')->limit(1)
				->find();
				
		$list = [
			'id'=>$now['id'],
			'aid'=>$now['aid'],
			'old_money'=>$old['money'],
			'old_time'=>$old['create_time'],
			'now_money'=>$now['money'],
			'now_amount'=>$now['sur_amount']
		];

		if($list){
			$this->result($list,1,'获取列表成功');
		}else{
			$this->result('',0,'获取列表失败');
		}


	}


	/**
	 * 提现申请通过
	 * @param  string $value [description]
	 * @return [type]        [description]
	 */
	public function adopt($value='')
	{
		$id = input('post.id');
		$aid = input('post.aid');
		$admin_id = Db::table('ca_auth_user')->where('id',$this->admin_id)->value('name');
		// 修改提现表未已通过，修改通过时间，修改审核人
		$res = Db::table('ca_apply_cash')->where('id',$id)->setField('audit_status',1);
	}







	/**
	 * 获取提现列表
	 * @param  [type] $page   [description]
	 * @param  [type] $status [description]
	 * @return [type]         [description]
	 */
	private function index($page,$status)
	{
		$pageSize = 10;
		$count = Db::table('ca_apply_cash')->where('audit_status',$status)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('ca_apply_cash ac')
				->join('ca_agent ca','ac.aid = ca.aid')
				->where('ac.audit_status',$status)
				->field('ac.id,ac.aid,company,leader,money,ac.create_time,ac.audit_time')
				->order('id desc')
				->page($page,$pageSize)
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}

	}
}