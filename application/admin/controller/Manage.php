<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 管理员
*/
class Manage extends Admin
{
	/**
	 * 管理员列表
	 * @return [type] [description]
	 */
	public function adminList()
	{
		$page = input('post.page');
		$pageSize = 10;
		$count = Db::table('am_auth_user')->count();
		$rows = ceil($count / $pageSize);
		// 查询用户表，查询角色表，查询角色与用户关联表
		$list = Db::table('am_auth_user au')
				->order('uid desc')
				->page($page,$pageSize)
				->field('uid,uname,role')
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取用户列表成功');
		}else{
			$this->result('',0,'获取用户列表失败');
		}
	}

	/**
	 * 添加页面及修改页面的用户组列表
	 */
	public function addIndex()
	{
		$list = Db::table('am_auth_role')->select();
		if($list){
			$this->result($list,1,'获取用户组成功');
		}else{
			$this->result('',0,'获取用户组失败');
		}
	}

	/**
	 * 添加用户
	 * @return [type] [description]
	 */
	public function adminAdd()
	{
		// 获取用户姓名，用户电话   角色id数组形式   角色名称字符串形式
		$data = input('post.');
		$validate = validate('Manage');
		if($validate->check($data)){
			Db::startTrans();
			$data['pwd']=get_encrypt('123456');
			$user_id = Db::table('am_auth_user')->strict(false)->insertGetId($data);
			foreach ($data['role_id'] as $k => $v) {
				$arr[]=['user_id'=>$user_id,'role_id'=>$v];
			};
			$result = Db::table('am_role_user')->insertAll($arr);
			if($user_id && $result){
				Db::commit();
				$this->result('',1,'用户添加成功,初始密码为123456');
			}else{
				Db::rollback();
				$this->result('',0,'用户添加失败,请重试');
			}
		}else{
			$this->result('',0,$validate->getError());
		}
	}


	/**
	 * 修改页面的默认数据
	 * @return [type] [description]
	 */
	public function modifyIndex()
	{
		// 获取用户id
		$uid = input('post.uid');
		$data = Db::table('am_auth_user')->where('uid',$uid)->field('uname,phone')->find();
		$role = Db::table('am_role_user')->where('user_id',$uid)->column('role_id');
		$data['role_id'] = $role;
		if($data && $role){
			$this->result($data,1,'获取要修改的数据成功');
		}else{
			$this->result('',0,$validate->getError());
		}
	}


	/**
	 * 修改用户信息
	 * @return [type] [description]
	 */
	public function adminModify()
	{
		// 获取用户id 用户姓名，用户电话   角色id数组形式   角色名称字符串形式
		$data = input('post.');
		$validate = validate('Manage');
		if($validate->check($data)){
			Db::startTrans();
			$where =['uid','=',$data['uid']];
			$user_id = Db::table('am_auth_user')->strict(false)->where('uid',$data['uid'])->update($data);
			// 删除该uid的角色表数据
			$res = Db::table('am_role_user')->strict(false)->where('user_id',$data['uid'])->delete();
			// 构造数据插入角色与用户关联表
			foreach ($data['role_id'] as $k => $v) {
				$arr[]=['user_id'=>$user_id,'role_id'=>$v];
			};
			$result = Db::table('am_role_user')->insertAll($arr);
			if($user_id !== false && $res && $result){
				Db::commit();
				$this->result('',1,'修改用户信息成功');
			}else{
				Db::rollback();
				$this->result('',0,'修改用户信息失败,请重试');
			}
		}else{
			$this->result('',0,$validate->getError());
		}


	}

	/**
	 * 删除用户
	 * @return [type] [description]
	 */
	public function delAdmin()
	{
		// 获取用户id
		$uid = input('post.uid');
		$res = Db::table('am_auth_user')->where('uid',$uid)->delete();
		$result = Db::table('am_role_user')->where('user_id',$uid)->delete();
		if($res && $result){
			$this->result('',1,'删除用户成功');
		}else{
			$this->result('',0,'删除用户失败');
		}
	}
}