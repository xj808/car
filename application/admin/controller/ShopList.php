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
				->field('id,company,phone,leader,card_sale_num,balance,tech_num')
				->page($page,$pageSize)
				->order('id desc')
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'获取列表失败');
		}
	}


	/**
	 * 点击详情获取内容
	 * @return [type] [description]
	 */
	public function detail()
	{
		// 获取修车厂id
		$sid = input('post.id');
		$list = Db::table('cs_shop cs')
				->join('cs_shop_set css','cs.id = css.sid')
				->join('ca_agent ca','cs.aid = ca.aid')
				->where('cs.id',$sid)
				->field('cs.id,css.license,ca.company,cs.audit_time,not_task')
				->find();
		if($list){
			$this->result($list,1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}


	/**
	 * 点击库存显示内容
	 * @return [type] [description]
	 */
	public function ration()
	{
		$sid = input('post.id');
		$list = Db::table('cs_ration cr')
				->join('co_bang_cate bc','bc.id = cr.materiel')
				->where('sid',$sid)
				->field('name,stock')
				->select();
		if($list){
			$this->result($list,1,'获取列表成功');
		}else{
			$this->result('',0,'获取列表失败');
		}
	}

	/**
	 * 临时关闭修车厂操作
	 * @return [type] [description]
	 */
	public function shotDown()
	{
		// 获取修车厂id
		$sid = input('post.id');
		// 获取关停修车厂理由
		$reason = input('post.reason');
		$data = [
			'sid'=>$sid,
			'reason'=>$reason,
		];
		Db::startTrans();
		$res = Db::table('cs_shut_logs')->insert($data);
		if($res !== false){
			$result = Db::table('cs_shop')->where('id',$sid)->update(['down_time'=>time(),'audit_status'=>5]);
			if($result !== false){
				Db::commit();
				$this->result('',1,'关闭成功');
			}else{
				Db::rollback();
				$this->result('',0,'关闭失败');
			}
		}else{
			$this->result('',0,'修改状态失败');
		}
	}


}