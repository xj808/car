<?php
namespace  app\agent\controller;
use app\base\controller\Agent;
use think\Db;
/**
* 运营商的物料库存
*/
class MaterialAgent extends Agent
{

	/**
	 * 运营商总库存列表
	 * @return json  物料名称、物料库存剩余量
	 */
	public function index()
	{
		$page = input('post.page');
		$pageSize = 10;
		$count = Db::table('ca_ration')->where('aid',$this->aid)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('ca_ration ar')
				->join('co_bang_cate bc','ar.materiel=bc.id')
				->field('name,materiel_stock,open_stock')
				->where('aid',$this->aid)
				->order('id desc')->page($page,$pageSize)->select();

		if($count > 0){                   
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}



	/**
	 * 运营商申请物料操作
	 * @return [json] [成功或失败]
	 */
	public function applyAgent()
	{	
		// 获取物料种类id，获取要申请的总升数，和备注的30/40 油各多少升
		$data=input('post.');

		$ar=$this->detail($data);
			$res=Db::table('ca_apply_materiel')->json(['detail'])->insert($ar);
			if($res){
				$this->success('',1,'申请成功');
			}else{
				$this->success('',1,'申请失败');
			}
	}




	/**
	 * 获得运营商需要补货的列表
	 * @param  [type] $aid [运营商id]
	 * @return [type]      [description]
	 */
	public function applyIndex()
	{
		$list = Db::table('ca_ration cr')
				->join('co_bang_cate bc','cr.materiel=bc.id')
				->where('aid',$this->aid)
				->where('materiel_stock < ration')
				->select();
		// 判断是否有需要预警的物料
		if($this->ifWarning() == true){
			// 获取运营商需要补货的物料种类，id,物料库存和需要补货的数量
			foreach($list as $k=>$v){
				$arr=[
					'materiel'=>$v['name'],
					'materiel_stock'=>$v['materiel_stock'],
					'apply'=>$v['ration']-$v['materiel_stock'],
					'materiel_id'=>$v['id']
				];
				$data[]=$arr;
			}

			$this->result($data,1,'获取补货列表成功');

		}else{

			$this->result('',0,'暂无数据');
		}
	}


	/**
	 * 判断是否预警
	 * @param  [type] $data [运营商本身库存的数组列表]
	 * @return [type]       [description]
	 */
	public function ifWarning()
	{
		$count = Db::table('ca_ration')
				->where('aid',$this->aid)
				->where('warning > materiel_stock')
				->where('materiel_stock < ration')
				->count();

		if($count > 0 ){
			
			return true;
		}else{
			return false;

		}
	}

	/**
	 * 获取申请物料的详情
	 * @param  [type] $data [物料id]
	 * @return [type]       [description]
	 */
	private function detail($data)
	{
		foreach ($data['materiel_id'] as $k => $v) {
			$arr[]=['materiel_id'=>$v,'materiel'=>$data['materiel'][$k],'num'=>$data['num'][$k],'remarks'=>$data['remarks'][$k]];
		};
		$ar=[
			'odd_number'=>build_order_sn(),
			'aid'=>$this->aid,
			'detail'=>$arr,
		];
		return $ar;
	}
}