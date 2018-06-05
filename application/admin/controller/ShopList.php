<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 汽修厂列表
*/
class ShopList extends Admin
{
	/**
	 * 汽修厂列表
	 * @return [type] [description]
	 */
	public function index()
	{
		$page = input('post.page')? :1;
		$pageSize = 10;
		$count = Db::table('cs_shop')->where('audit_status',2)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('cs_shop')
				->where('audit_status',2)
				->field('company,phone,leader,card_sale_num,balance,tech_num')
				->order('id desc')
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'获取列表失败');
		}
	}



}