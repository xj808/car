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
			$this->result('',0,'获取列表失败');
		}
	}


	/**
	 * 点击关停理由显示内容
	 * @return [type] [description]
	 */
	public function dowReason()
	{
		$sid = input('post.id');
		$reason = Db::table('cs_shop_set')->where('sid',$sid)->value('close_reason');
		if($reason){
			$this->result($reason,1,'获取理由成功');
		}else{
			$this->result('',0,'获取理由失败');
		}
	}
}