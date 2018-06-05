<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 运营商取消合作
*/
class AgentCancel extends Admin
{
	/**
	 * 取消合作申请列表
	 * @return [type] [description]
	 */
	public function waitList()
	{
		$page = input('post.page')? :1;
		$this->index($page,0);
	}



	/**
	 * 取消合作通过列表
	 * @return [type] [description]
	 */
	public function adoptList()
	{
		$page = input('post.page')? :1;
		$this->index($page,1);
	}


	public function detail()
	{
		$list = Db::table('ca_apply_cancel ac')
				->join('ca_agent ca','ac.aid=ca.aid')
				->where('ac.audit_status',$status)
				->select();
	}







	/**
	 * 获取运营商取消合作列表
	 * @param  [type] $page   [description]
	 * @param  [type] $status [description]
	 * @return [type]         [description]
	 */
	private function index($page,$status)
	{
		$pageSize = 10;
		$count = Db::table('ca_apply_cancel')->where('audit_status',$status)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('ca_apply_cancel ac')
				->join('ca_agent ca','ac.aid=ca.aid')
				->where('ac.audit_status',$status)
				->order('id desc')
				->field('id,ac.aid,ac.company,ac.leader,phone,ac.create_time,ac.audit_time')
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}
	
}