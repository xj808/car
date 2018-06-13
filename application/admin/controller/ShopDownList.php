<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 关停汽修厂列表
*/
class ShopDownList extends Admin
{	
	
	/**
	 * 关停汽修厂列表
	 * @return [type] [description]
	 */
	public function index()
	{
		$page = input('post.page')? : 1;
		$pageSize = 10;
		$count = Db::table('cs_shop')->where('audit_status',5)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('cs_shop')
				->where('audit_status',5)
				->page($page,$pageSize)
				->field('id,company,phone,leader,down_time,not_task')
				->order('id desc')
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}


	/**
	 * 点击关停理由显示内容
	 * @return [type] [description]
	 */
	public function dowReason()
	{
		$sid = input('post.id');
		$reason = Db::table('cs_shut_logs')->where('sid',$sid)->order('create_time desc')->value('reason');
		if($reason){
			$this->result($reason,1,'获取理由成功');
		}else{
			$this->result('',0,'获取理由失败');
		}
	}



	/**
	 * 重新开通修车厂
	 * @return [type] [description]
	 */
	public function openAgain()
	{
		$sid = input('post.id');
		$reason = input('post.reason');
		$audit_person = Db::table('am_auth_user')->where('id',$this->admin_id)->value('name');
		$data = [
			'sid'=>$sid,
			'reason'=>$reason,
			'audit_person'=>$audit_person,
		];
		Db::startTrans();
		$res = Db::table('cs_reopen_logs')->insert($data);
		if($res){
			$open = Db::table('cs_shop')->where('id',$sid)->setField('audit_status',2);
			if($open !== false){
				Db::commit();
				$this->result('',0,'开通成功');
			}else{
				Db::rollback();
				$this->result('',0,'开通失败');
			}
		}else{
			Db::rollback();
			$this->result('',0,'添加数据失败');
		}
		
	}
}