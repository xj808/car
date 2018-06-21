<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 运营商列表
*/
class AgentList extends Admin
{
	
	/**
	 * 运营商列表
	 * @return [type] [description]
	 */
	public function index()
	{
		$page = input('post.page')? :1;
		$pageSize = 10;
		$count = Db::table('ca_agent')->where('status',2)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('ca_agent')
				->where('status',2)
				->field('aid,company,leader,phone,open_shop,regions,balance,sale_card,service_time')
				->order('aid desc')->page($page,$pageSize)
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}


	/**
	 * 运营商列表  点击运营商名称显示内容
	 * @return [type] [description]
	 */
	public function company()
	{
		$aid = input('post.aid');
		// 获取运营商详情
		$detail = Db::table('ca_agent')->where('aid',$aid)->field('license,company,leader,phone,province,city,county,address,branch,account,bank_name')->find();
		if($detail){
			$this->result($detail,1,'获取数据成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}


	/**
	 * 运营商列表  点击修车厂数量显示内容
	 * @return [type] [description]
	 */
	public function shopNum()
	{
		$aid = input('post.aid');
		$page = input('post.page')? :1;
		$pageSize = 10;
		$count = Db::table('cs_shop')->where(['aid'=>$aid,'audit_status'=>2])->count();
		$rows = ceil($count / $pageSize);
		// 获取该运营商已通过审核正常运行的修车厂列表
		$shopList = Db::table('cs_shop')
					->where(['aid'=>$aid,'audit_status'=>2])
					->field('company,leader,phone,create_time,audit_time')
					->order('id desc')
					->page($page,$pageSize)
					->select();
		$list = $this->ifNew($shopList);

		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}

	}


	/**
	 * 运营商列表  点击区域个数显示内容
	 * @return [type] [description]
	 */
	public function regionList()
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
	 * 当月的第一天
	 * @return [type] [description]
	 */
	private function monthFirst()
	{
		return date('Y-m-01 00:00:00',strtotime(date("Y-m-d")));
		
	}

	/**
	 * 当月的最后一天
	 * @return [type] [description]
	 */
	private function monthLast()
	{
		$first = date('Y-m-01 00:00:00',strtotime(date("Y-m-d")));
		return date('Y-m-d 23:59:59', strtotime("$first +1 month -1 day"));
	}



	/**
	 * 判断此修车厂是否是新开
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	private function ifNew($data)
	{
		foreach ($data as $k => $v) {
				$audit_time = date('Y-m-d H:i:s',$v['audit_time']);
			if($audit_time >= $this->monthFirst() && $v['audit_time'] <= $this->monthLast()){
				$data[$k]['if_new'] = "是";
			}else{
				$data[$k]['if_new'] = "否";
			}
		};
		return $data;
	}







}