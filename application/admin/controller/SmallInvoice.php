<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 小程序发票管理
*/
class SmallInvoice extends Admin
{
	
	/**
	 * 申请列表
	 * @return [type] [description]
	 */
	public function applyList()
	{
		$page = input('post.page')? : 1;
		$this->index($page,0);
	}



	public function cardMoney($value='')
	{
		# code...
	}


	// 列表
	private function index($page,$status)
	{
		$pageSize = 10;
		$count = Db::table('u_tax')->where('status',$status)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('u_tax')
				->where('status',$status)
				->order('id desc')
				->page($page,$pageSize)
				->field('id,phone,contacter,total,fee,create_time,audit_time')
				->select();
		if($count > 0){
            $this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
        }else{
            $this->result('',0,'暂无数据');
        }  
	}

}