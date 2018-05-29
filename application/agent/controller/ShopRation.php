<?php
namespace app\agent\controller;
use app\base\controller\Agent;
use think\Db;
/**
* 修理厂配给
*/
class ShopRation extends Agent
{
	/**
	 * 修车厂配给列表
	 * @return [type] [description]
	 */
	public function index()
	{	
		$page = input('post.page');
		$pageSize = 10;
		$where=[['aid','=',$this->aid],['audit_status','=',2]];
		$count = Db::table('cs_shop')->where($where)->count();
		$rows = ceil($count / $pageSize);

		$list=Db::table('cs_shop')->where($where)->select();
		if($count > 0){                   
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 提高修车厂配给
	 * @return [type] 
	 */
	public function incRation()
	{	
		$sid=input('post.sid');
		Db::startTrans();
		// 运营商可开通修车厂名额减少一个，已开通修车厂名额增加一个
		$this->open($this->aid);
		// 运营商开通修车厂库存减少一组
		$this->credit($this->aid);
		// 记录修理厂提高配给
		$this->recordRation($sid,$this->aid);
		// 增加修车厂配给和库存量
		$res=$this->addRation($sid);
		if($res==true){
			Db::commit();
			$this->result('',1,'操作成功');
		}else{
			Db::rollback();
			$this->result('',0,'操作失败');
		}

	}



	/**
	 * 记录修车厂提高配给
	 * @param [type] $sid 修车厂id
	 */
	public function recordRation($sid,$aid)
	{
		foreach ($this->bangCate() as $k => $v) {
			$arr['sid']=$sid;
			$arr['aid']=$aid;
			$arr['record']=[
				'materiel'=>$v['id'],
				'ration_num'=>$v['def_num'],
			];
			$data[]=$arr;
		}
		$res=Db::table('cs_increase')->json(['record'])->insertAll($data);
	}



	/**
	 * 增加修车厂配给和库存
	 * @param [type] $sid [description]
	 */
	private function addRation($sid)
	{
		$arr=$this->bangCate();
		foreach ($arr as $k => $v) {
			$where=[['sid','=',$sid],['materiel','=',$v['id']]];
			$res=Db::table('cs_ration')->where($where)->inc(['stock'=>$v['def_num'],'ration'=>$v['def_num']])->update();
		}
		if($res !== false){
			return true;
		}
	}




}