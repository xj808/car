<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 运营商物料申请
*/
class AgentMateriel extends Admin
{



	/**
	 * 物料申请审核列表
	 * @return [type] [description]
	 */
	public function waitList()
	{
		$page = input('post.page')? : 1;
		$this->index($page,0);
	}


	/**
	 * 物料申请已通过列表
	 * @return [type] [description]
	 */
	public function adoptList()
	{
		$page = input('post.page')? : 1;
		$this->index($page,1);
	}


	/**
	 * 物料申请驳回列表
	 * @return [type] [description]
	 */
	public function rejectList()
	{
		$page = input('post.page')? : 1;
		$this->index($page,2);
	}



	/**
	 * 审核列表详情
	 * @return [type] [description]
	 */
	public function detail()
	{
		$id = input('post.id');
		$list = Db::table('ca_apply_materiel am')
				->join('ca_agent ca','ca.aid = am.aid')
				->where('id',$id)
				->field('am.aid,company,phone,province,city,county')
				->find();
		if($list){
			$this->result($list,1,'获取详情成功');
		}else{
			$this->result('',0,'获取详情失败');
		}
	}


	/**
	 * 物料申请通过
	 * @return [type] [description]
	 */
	public function adopt()
	{
		$id = input('post.id');
		$aid = input('post.aid');
		Db::startTrans();
		$audit_person = Db::table('am_auth_user')->where('id',$this->admin_id)->value('name');
		$data=[
			'audit_status'=>1,
			'audit_person'=>$audit_person,
			'audit_time'=>time(),
		];
		// 修改物料订单状态为1
		$res = Db::table('ca_apply_materiel')->where('id',$id)->update($data);
		// 增加运营商库存
		if($res == true){
			// 获取运营商所申请的物料
			$list = $this->matNum($id);
			if(empty($list)){
				$this->result('',0,'您没有申请物料');
			}
			// 把物料循环加入到运营商库存里
			foreach($list as $k=>$v){
				$res = Db::table('ca_ration')
					->where(['aid'=>$aid,'materiel'=>$v['materiel_id']])
					->inc('materiel_stock',$v['num'])
					->update();
			}
			if($res !== false){
				Db::commit();
				$this->result('',1,'操作成功');
			}else{
				Db::rollback();
				$this->result('',0,'操作失败');
			}

		}else{
			Db::rollback();
			$this->result('',0,'修改状态失败');
		}

	}


	/**
	 * 物料申请驳回操作
	 * @return [type] [description]
	 */
	public function reject()
	{
		$id =input('post.id');
		$reason = input('post.reason');
		if(empty($reason)){
			$this->result('',0,'驳回理由不能为空');
		}
		$audit_person = Db::table('am_auth_user')->where('id',$this->admin_id)->value('name');
		$data=[
			'audit_status'=>2,
			'reason'=>$reason,
			'audit_person'=>$audit_person,
			'audit_time'=>time(),
		];
		$result = Db::table('ca_apply_materiel')->where('id',$id)->update($data);
		if($result !== false){
			$this->result('',1,'驳回成功');
		}else{
			$this->result('',0,'驳回失败');
		}

	}




	/**
	 * 物料种类内容
	 * @return [type] [description]
	 */
	public function materiel()
	{
		$id = input('post.id');
		$list = $this->matNum($id);
		if($list){
			$this->result($list,1,'获取数据成功');
		}else{
			$this->result('',0,'获取数据失败');
		}
	}



	/**
	 * 点击驳回理由显示内容
	 * @return [type] [description]
	 */
	public function matReason()
	{
		$id = input('post.id');
		$reason = $this->reason($id,'ca_apply_materiel','reason');
		if($reason){
			$this->result($reason,1,'获取理由成功');
		}else{
			$this->result('',0,'获取理由失败');
		}
	}








	/**
	 * 物料申请待审核已审核列表
	 * @return [type] [description]
	 */
	private function index($page,$status)
	{
		$pageSize = 10;
		$count = Db::table('ca_apply_materiel')->where('audit_status',$status)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('ca_apply_materiel am')
				->join('ca_agent ca','ca.aid = am.aid')
				->where('am.audit_status',$status)
				->order('id desc')->page($page,$pageSize)
				->field('am.id,company,leader,phone,am.create_time,am.audit_time')
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}



	/**
	 * 获取运营商所申请的物料
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	private function matNum($id)
	{
		$list =Db::table('ca_apply_materiel')->where('id',$id)->json(['detail'])->find();
		return $list['detail'];
	}






}