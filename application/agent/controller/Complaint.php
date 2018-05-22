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
	 * 投诉管理首页和详情
	 * @return [type] [description]
	 */
	public function index()
	{
		$token = input('post.token');
		$aid = $this->checkToken($token);
		return Db::table('u_complain uc')
			->join('cs_shop ss','uc.sid = ss.id')
			->join('u_user uu','uc.uid = uu.id')
			->where('uc.aid',$aid)
			->field('compay,name,uu.phone,title,uc.create_time,content')
			->select();
	}


}