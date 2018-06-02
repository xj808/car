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
		$page = input('post.page') ? : 1;
		return $this->sList(2,$page);
	}


	/**
	 * 等待审核列表
	 * @return [type] [description]
	 */
	public function index()
	{
		$page = input('post.page') ? : 1;
		return $this->sList(1,$page);
	}

	/**
	 * 列表
	 * @return [type] [description]
	 */
	public function sList($status,$page)
	{
		$where=[['aid','=',$this->aid],['audit_status','=',$status]];
		$count = Db::table('cs_shop')->where($where)->count();
		$pageSize = 10;

		$rows = ceil($count / $pageSize);
		$list = Db::table('cs_shop')
				->where($where)
				->field('id,company,leader,phone,create_time,service_num')
				->order('id desc')->page($page,$pageSize)
				->select();

		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{

			$this->result('',0,'暂无数据');
		}
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
		if(Db::table('ca_agent')->where('aid',$this->aid)->value('shop_nums') <= 0){
			Db::rollback();
			$this->result('',0,'您可开通修理厂数量为0,请提高您的可开通修理厂数量');
		}

		// 运营商授信库存减少一组,可开通修车厂名额减少一个，已开通修车厂数量增加一个
		if($this->credit($this->aid)){
			// 修车厂配给库存增加
			if($this->shopRation($sid)){
				// 更改修理厂审核状态
				if($this->status('cs_shop',$sid,2)==true){
					Db::commit();
					$this->result('',1,'操作成功');
				}else{
					Db::rollback();
					$this->result('',0,'操作失败');
				}
			}else{
				Db::rollback();
				$this->result('',0,'修车厂库存修改失败');
			}

		}else{
			Db::rollback();
			$this->result('',0,'运营商开通库存减少失败');
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
				'warning'=>ceil($v['def_num']*20/100),
				'stock'=>$v['def_num'],
			];
		}
		$res = Db::table('cs_ration')->insertAll($arr);
		if($res){
			return true;
		}
	}


	//修车厂申请物料修改over时间
	// public function a()
	// {
	// 	$crea = Db::table('cs_apply_materiel')->where('id',5)->value('create_time');
	// 	$crea = strtotime($crea);
	// 	Db::table('cs_apply_materiel')->where('id',5)->setField('over_time',$crea+259200);

	// }

	



}