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
		return $this->sList(2);
	}


	/**
	 * 等待审核列表
	 * @return [type] [description]
	 */
	public function index()
	{
		return $this->sList(1);
	}

	/**
	 * 列表
	 * @return [type] [description]
	 */
	public function sList($status)
	{
		$where=[['aid','=',$this->aid],['audit_status','=',$status]];
		return Db::table('cs_shop')
				->where($where)
				->field('id,company,leader,phone,create_time,service_num')
				->select();
	}



	/**
	 * 修车厂详情
	 * @return [type] [description]
	 */
	public function detail()
	{
		$sid=input('post.id');
		return Db::table('cs_shop s')
				->join('cs_shop_set ss','s.id=ss.sid')
				->field('usname,company,major,leader,province,city,county,address,phone,service_num,license,photo,aid,sid')
				->where('s.id',$sid)
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
		// 判断运营商的可开通数量
		if(Db::table('ca_agent')->where('aid',$this->aid)->value('shop_nums') == 0){
			Db::rollback();
			$this->result('',0,'您可开通修理厂数量为0,请提高您的可开通修理厂数量');
		}
		// 运营商授信库存减少一组
		$this->credit($this->aid);
		// 运营商可开通修车厂名额减少一个，已开通修车厂数量增加一个
		$this->open($this->aid);
		// 修车厂配给库存增加
		$this->shopRation($sid);
		// 更改修理厂审核状态
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
		$reason=input('post.reason');
		if($reason){
			$res=$this->status('cs_shop',$sid,3);
			if($res==true){
				$this->result('',1,'驳回成功');
			}else{
				$this->result('',0,'驳回失败');
			}
		}else{
			$this->result('',0,'驳回理由不能为空');
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