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
		return Db::table('ca_ration ar')
				->join('co_bang_cate bc','ar.materiel=bc.id')
				->field('name,materiel_stock')
				->where('aid',$this->aid)
				->select();
	}

	/**
	 * 运营商提高配给
	 * @return [json] 成功或失败 	  
	 */
	public function incRation()
	{
		$data=input('post.');

		$res=$this->upLicense($data['voucher'],$data['price'],$this->aid);
		
		if($res){
			$this->result('',1,'提高配给申请成功');
		}else{
			$this->result('',1,'提高配给申请失败');
		}
	}

	/**
	 * 运营商需要补货的物料列表
	 * @return [type] [description]
	 */
	public function applyIndex()
	{
		$data=$this->warning($this->aid);
		$ar=$this->ifApply($data);
		if(!empty($ar)){
			$this->result($ar,1,'获取列表成功');
		}else{
			$this->result('',0,'没有数据');
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
			$res=Db::table('ca_apply')->json(['detail'])->insert($ar);
			if($res){
				$this->success('',1,'申请成功');
			}else{
				$this->success('',1,'申请失败');
			}
	}




	/**
	 * 获得运营商本身库存
	 * @param  [type] $aid [运营商id]
	 * @return [type]      [description]
	 */
	private function warning($aid)
	{
		return Db::table('ca_ration cr')
				->join('co_bang_cate bc','cr.materiel=bc.id')
				->where('aid',$aid)->select();
	}

	/**
	 * 判断是否预警
	 * @param  [type] $data [运营商本身库存的数组列表]
	 * @return [type]       [description]
	 */
	private function ifApply($data)
	{
		foreach ($data as $k => $v) {
			if($v['materiel_stock']/$v['warning']<0.5){
				$arr=[
					'materiel'=>$v['name'],
					'materiel_stock'=>$v['materiel_stock'],
					'apply'=>$v['ration']-$v['materiel_stock'],
					'materiel_id'=>$v['id']
				];
				$ar[]=$arr;
			}
		}
		return $ar;
	}

	/**
	 * 获取申请物料的详情
	 * @param  [type] $data [物料id]
	 * @return [type]       [description]
	 */
	private function detail($data)
	{
		foreach ($data['materiel_id'] as $k => $v) {
			$arr[]=['materiel'=>$v,'num'=>$data['num'][$k],'remarks'=>$data['remarks'][$k]];
		};
		$ar=[
			'apply_sn'=>rand(1111,9999).'apply'.time(),
			'aid'=>$this->aid,
			'detail'=>$arr,
		];
		return $ar;
	}
}