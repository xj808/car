<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 运营商提高配给
*/
class AgentIncRation extends Admin
{
		/**
		 * 等待审核列表
		 * @return [type] [description]
		 */
		public function waitAudit()
		{
			$page = input('post.page')? :1;
			$this->index(0,$page);
		}

		/**
		 * 已审核列表
		 */
		public function Audit()
		{
			$page = input('post.page')? :1;
			$this->index(1,$page);
		}


		/**
		 * 驳回列表
		 * @return [type] [description]
		 */
		public function reject()
		{
			$page = input('post.page')? :1;
			$this->index(2,$page);
		}



		/**
		 * 提高配给详情
		 * @param  [type] $id  [description]提高配给的订单唯一id
		 * @param  [type] $aid [description]运营商id
		 * @return [type]      [description]
		 */
		public function detail()
		{
			$id = input('post.id');
			$aid = input('post.aid');
			$detail = Db::table('ca_increase ci')
					->join('ca_agent ca','ci.aid = ca.aid')
					->where(['ci.aid'=>$aid,'id'=>$id])
					->field('ci.id,ci.aid,voucher,company,phone,ci.regions,price')
					->find();
			if($detail){
				$this->result($detail,1,'获取数据成功');
			}else{
				$this->result($detail,0,'获取数据失败');
			}
		}



		/**
		 * 运营商列表  点击区域个数显示内容
		 * @return [type] [description]
		 */
		public function detailArea()
		{
			// 获取运营商id
			$aid = input('post.aid');
			$list =$this->region($aid);
			if($list){
				$this->result($list,1,'获取列表成功');

			}else{	

				$this->result('',0,'暂未设置供应地区');
			}

		}


		/**
		 * 配给通过操作
		 * @return [type] [description]
		 */
		public function adopt()
		{	
			// 获取该配给的订单id、运营商id、区域个数
			$data = input('post.');
			Db::startTrans();
			// 给运营商添加库存，配给
			if($this->agentRation($data['id'],$data['aid'],$data['regions'])){
				// 修改提高配给订单的审核为已审核 和审核时间
				$res = Db::table('ca_increase')->where('id',$data['id'])->setField(['audit_status'=>1,'audit_time'=>time()]);
				if($res !== false){
					// 修改运营商状态
					if($result !== false){

						Db::commit();
						$this->result('',1,'操作成功操作');
					}else{
						Db::rollback();
						$this->result('',0,'操作失败');
					}
					
				}else{
					Db::rollback();
					$this->result('',0,'操作失败');
				}
			}else{
				Db::rollback();
				$this->result('',0,'增加运营商库存成功');
			}

		}



		/**
		 * 配给驳回操作
		 * @return [type] [description]
		 */
		public function rejec()
		{	
			// 获取驳回理由、获取运营商id、获取订单id
			$data = input('post.');
			// 删除运营商所选地区
			$area = $this->delArea($data['id'],$data['aid']);
			$res = Db::table('ca_area')->whereIn('area',$area)->delete();
			if($res){

				$result = Db::table('ca_increase')->where('id',$data['id'])->setField('audit_status',2);

				if($result !== false){
					Db::commit();
					$this->result('',1,'操作成功');
				}else{
					Db::rollback();
					$this->result('',0,'操作失败');
				}
			}else{
				Db::rollback();
				$this->result('',0,'删除失败');
			}
		}



		/**
		 * 运营商提高配给列表
		 * @param  [type] $status [description]
		 * @return [type]         [description]
		 */
		private function index($status,$page)
		{

			$pageSize = 10;
			$count = Db::table('ca_increase')->where('audit_status',$status)->count();
			$rows = ceil($count / $pageSize);
			// 显示已上传营业执照、配给表里未审核的数据
			$list = Db::table('ca_increase ci')
					->join('ca_agent ca','ci.aid = ca.aid')
					->where(['audit_status'=>$status])
					->field('id,ci.aid,company,phone,price,regions')
					->order('id desc')
					->page($page,$pageSize)
					->select();
			if($count > 0){
				$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
			}else{
				$this->result('',0,'暂无数据');
			}
		}




		/**
		 * 增加运营商的库存
		 * @param  [type] $aid    [description]
		 * @param  [type] $region [description]
		 * @return [type]         [description]
		 */
		private function agentRation($id,$aid,$region)
		{
			$count = Db::table('ca_ration')->where('aid',$aid)->count();
			// 判断是第一次配给还是提高配给
			if($count > 0){
				// 大于0 为提高配给   直接修改运营商的库存
				foreach ($this->bangCate() as $k => $v) {
					$res = Db::table('ca_ration')
						->where(['aid'=>$aid,'materiel'=>$v['id']])
						->inc('ration',$v['def_num']*$region)
						->inc('materiel_stock',$v['def_num']*$region)
						->inc('open_stock',$v['def_num']*$region)
						->update();
				}

			}else{

				// 如果小于等于0则表示第一次配给运营商库存插入数据 
				$arr = $this->firstData($aid,$region);
				$res = Db::table('ca_ration')->insertAll($arr);
			}
			if($res !== false){
				return true;
			}
			

		}




		/**
		 * 第一次配给所用数组
		 * @return [type] [description]
		 */
		private function firstData($aid,$region)
		{
			foreach ($this->bangCate() as $k => $v) {
					$arr[]=[
						'aid'=>$aid,
						'materiel'=>$v['id'],
						'ration'=>$v['def_num']*$region,
						'warning'=>ceil($v['def_num']*$region*20/100),
						'materiel_stock'=>$v['def_num']*$region,
						'open_stock'=>$v['def_num']*$region,
					];
			}
			return $arr;

		}


		/**
		 * 提高配给所用数组
		 * @return [type] [description]
		 */
		private function IncData($region)
		{
			foreach ($this->bangCate() as $k => $v) {
					$arr[]=[
						
						'materiel'=>$v['id'],
						'ration'=>$v['def_num']*$region,
						'materiel_stock'=>$v['def_num']*$region,
						'open_stock'=>$v['def_num']*$region,
					];
			}
			return $arr;

		}


		/**
		 * 获取运营商所选择的区域
		 * @param  [type] $id  [description]
		 * @param  [type] $aid [description]
		 * @return [type]      [description]
		 */
		private function delArea($id,$aid)
		{	
			// 查询运营商选择配给时的地区
			$area = Db::table('ca_increase')->where('id',$id)->value('area');
			$area = explode(',',$area);
			// 查询该运营商地区表里有没有这次选择的地区，有删除，没有返回true;
			$area_id = Db::table('ca_area')->where('aid',$aid)->column('area');
			// 比较两个数组的值获取想的值
			$are = array_intersect($area,$area_id);
			return $are;

		}






		



}