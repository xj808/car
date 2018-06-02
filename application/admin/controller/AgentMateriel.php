<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 运营商物料申请
*/
class AgentMateriel extends Admin
{
	public function index()
	{
		$page = input('post.page')? : 1;
		$pageSize = 10;
		$count = Db::table('ca_apply_materiel')->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('ca_apply_materiel am')
				->join('ca_agent ca','ca.aid = am.aid')
				->order('id desc')->page($page,$pageSize)
				->field('apply_sn,company,leader,phone,am.create_time')
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}
}