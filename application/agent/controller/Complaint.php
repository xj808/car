<?php
namespace app\agent\controller;
use app\base\controller\Agent;
use think\Db;
/**
* 投诉管理
*/
class Complaint extends Agent
{
	
	/**
	 * 投诉管理首页列表
	 * @return [type] [description]
	 */
	public function index()
	{
		$token = input('post.token');
		return Db::table('u_complain uc')
			->join('u_user uu','uc.uid = uu.id')
			->where('uc.aid',$this->aid)
			->field('uc.id,compay,name,uu.phone,title,uc.create_time')
			->paginate(10);
	}


	/**
	 * 投诉管理详情
	 * @return [type] [description]
	 */
	public function detail()
	{
		$id=input('post.id');
		return Db::table('u_complain uc')
			->join('u_user uu','uc.uid = uu.id')
			->where('uc.id',$id)
			->field('compay,name,uu.phone,uc.create_time,content')
			->select();
	}


}