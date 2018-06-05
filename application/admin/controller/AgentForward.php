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
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}

	}
}