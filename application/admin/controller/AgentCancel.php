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


	/**
	 * 取消合作详情
	 * @return [type] [description]
	 */
	public function detail()
	{
		$id = input('post.id');
		$list = Db::table('ca_apply_cancel ac')
				->join('ca_agent ca','ac.aid=ca.aid')
				->where('id',$id)
				->field('id,ac.company,phone,open_shop,balance,count_down,reason')
				->find();

		if($list){
			$this->result($list,1,'获取详情成功');
		}else{
			$this->result('',0,'获取详情失败');
		}
	}


	/**
	 * 点击驳回理由显示内容
	 * @return [type] [description]
	 */
	public function canReason()
	{
		$id = input('post.id');
		$reason = $this->reason($id,'ca_apply_cash','reason');
		if($reason){
			$this->result($reason,1,'获取理由成功');
		}else{
			$this->result('',0,'获取理由失败');
		}
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