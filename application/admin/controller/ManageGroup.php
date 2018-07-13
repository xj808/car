<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 用户组
*/
class ManageGroup extends Admin
{
	/**
	 * 用户组列表
	 * @return [type] [description]
	 */
	public function groupList()
	{
		$page = input('post.page')? :1;
		$pageSize = 10;
		$count = Db::table('am_auth_role')->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('am_auth_role')
				->order('rid desc')
				->page($page,$pageSize)
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取用户组成功');
		}else{
			$this->result('',0,'暂无数据');
		}

	}

	/**
	 * 用户组添加
	 */
	public function addGroup()
	{
		$data = input('post.');
		$count = Db::table('am_auth_role')->where('rname',$data['rname'])->count();
		if(!empty($data['rname']) && $count < 0){
			$res = Db::table('am_auth_role')->strict(false)->insert($data);
			if($res){
				$this->result('',1,'添加用户组成功!');
			}else{
				$this->result('',0,'添加用户组失败!');
			}
		}else{
			$this->result('',0,'用户组名不能为空或用户组已存在!');
		}
	}

	/**
	 * 给用户组分配权限页面
	 * @return [type] [description]
	 */
	public function authGroup()
	{
		// 查询权限表
		$rid = input('post.rid');
		$data = Db::table('am_auth_auth')->select();
		if($data){
			$this->result(get_child($data),1,'获取权限信息成功');
		}else{
			$this->result('',0,'获取权限信息失败');
		}
		
	}

	/**
	 * 给用户组添加权限
	 */
	public function addAuth()
	{
		// 获取角色id  获取所选择的的权限id
		$data = input('post.');
		foreach ($data['rule_id'] as $k => $v) {
			$arr[] = ['rule_id'=>$v,'role_id'=>$data['role_id']];
		}
		Db::startTrans();
		$result = Db::table('am_rule_role')->where('role_id',$data['role_id'])->delete();
		$res = Db::table('am_rule_role')->insertAll($arr);
		if($result && $res){
			Db::commit();
			$this->result('',1,'用户组添加权限成功');
		}else{
			Db::rollback();
			$this->result('',0,'用户组添加权限失败');
		}
	}

	/**
	 * 用户组修改页面
	 * @return [type] [description]
	 */
	public function modifyIndex()
	{
		$rid = input('post.rid');
		$rname = Db::table('am_auth_role')->where('rid',$rid)->value('rname');
		if($rname){
			$this->result($rname,1,'获取信息成功');
		}else{
			$this->result('',0,'获取信息失败');
		}
	}

	/**
	 * 修改用户组操作
	 * @return [type] [description]
	 */
	public function modifyPost()
	{
		$data = input('post.');
		$res = Db::table('am_auth_role')->where('rid',$data['rid'])->setField('rname',$data['rname']);
		if($res !== false){
			$this->result('',1,'修改用户组成功');
		}else{
			$this->result('',0,'修改用户组失败');
		}
	}

	/**
	 * 用户组删除
	 * @return [type] [description]
	 */
	public function groupDel()
	{
		$rid = input('post.rid');
		Db::startTrans();
		$res = Db::table('am_auth_role')->where('rid',$rid)->delete();
		$result = Db::table('am_rule_role')->where('role_id',$rid)->delete();
		if($res && $result){
			Db::commit();
			$this->result('',1,'删除用户组成功');
		}else{
			Db::rollback();
			$this->result('',0,'删除用户组失败');
		}
	}
}