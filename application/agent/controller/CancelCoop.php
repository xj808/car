<?php
namespace  app\agent\controller;
use app\base\controller\Agent;
use think\Db;
/**
* 取消合作模块
*/
class CancelCoop extends Agent
{
	
	function initialize()
	{
		parent::initialize();
	}


	public function index()
	{	
		// 获取运营商名称、负责人
		$agent=Db::table('ca_agent')->where('aid',$this->aid)->find();
		$this->cityList($this->aid);
		
	}



	public function cityList($aid)
	{
		// 获取运营商所管辖区域
		$county=Db::table('ca_area')->where('aid',$aid)->column('area');

		$data=get_child($data,1);
		
		print_r($data);exit;
		// // 获取省级id
		// $province=Db::table('co_china_data')->whereIn('id',$city_id)->select();
		// print_r($province);exit;
		
	}

	
}