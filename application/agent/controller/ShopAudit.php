<?php
namespace app\agent\controller;
use app\base\controller\Agent;
use think\Db;
/**
* 修车厂审核管理
*/
class ShopAudit extends Agent
{


	/**
	 * 已通过审核可正常运营的修车厂列表
	 * @return [type] [description]
	 */
	public function shopList()
	{
		return $this->alist(2);
	}


	/**
	 * 等待审核列表
	 * @return [type] [description]
	 */
	public function index()
	{
		return $this->alist(1);
	}


	/**
	 * 列表
	 * @return [type] [description]
	 */
	public function alist($status)
	{
		$where=[['aid','=',$this->aid],['audit_status','=',$status]];
		return Db::table('cs_shop s')
				->join('cs_shop_set ss','ss.sid=s.id')
				->field('compay,leader,phone,create_time,usname,major,province,city,county,service_num,license,photo,aid,sid')
				->where($where)
				->select();
	}


	/**
	 * 修车厂审核列表确认操作
	 * @return [type] [description]
	 */
	public function adopt()
	{
		$sid=input('post.sid');
		Db::startTrans();
		// 运营商授信库存减少一组
		$this->credit($this->aid);
		// 运营商可开通修车厂名额减少一个，已开通修车厂数量增加一个
		$this->open($this->aid);
		// 修车厂配给库存增加
		$this->shopRation($sid);
		if($this->status('cs_shop',$sid,2)==true){
			Db::commit();
			$this->result('',1,'操作成功');
		}else{
			Db::rollback();
			$this->result('',0,'操作失败');
		}
	}

	/**
	 * 修车厂审核驳回操作
	 * @return [type] [description]
	 */
	public function reject()
	{
		$sid=input('post.sid');
		if($sid){
			$res=$this->status('cs_shop',$sid,3);
			if($res==true){
				$this->result('',1,'驳回成功');
			}else{
				$this->result('',0,'驳回失败');
			}
		}else{
			$this->result('',0,'维修厂id不能为空');
		}
	}



	/**
	 * 给审核通过的修车厂增加配给库存
	 * @param [type] $sid 修车厂id
	 */
	private function shopRation($sid)
	{
		foreach ($this->bangCate() as $k => $v) {
			$arr[]=[
				'sid'=>$sid,
				'materiel'=>$v['id'],
				'ration'=>$v['def_num'],
				'warning'=>$v['def_num'],
				'stock'=>$v['def_num'],
			];
		}
		Db::table('cs_ration')->insertAll($arr);
	}

	



}