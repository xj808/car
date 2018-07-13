<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 权限管理
*/
class Auth extends Admin
{
	/**
	 * 权限列表
	 * @return [type] [description]
	 */
	public function authList()
	{
		$page = input('post.page')? :1;
		$pageSize = 10;
		// 获取权限列表总条数
		$count = Db::table('am_auth_auth')->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('am_auth_auth')
				->order('id desc')
				->page($page,$pageSize)
				->field('name,action,id,pid')->select();
		$list = get_level($list);
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
		
	}


	/**
	 * 权限添加下拉框
	 * @return [type] [description]
	 */
	public function authSel()
	{
		$list = Db::table('am_auth_auth')->select();
		if($list){
			$list = get_level($list);
			if($list){
				$this->result($list,1,'获取权限列表成功');
			}else{
				$this->result('',0,'暂无数据');
			}
		}else{
			$this->result('',0,'暂无数据');
		}
	}


	/**
	 * 添加权限
	 * @return [type] [description]
	 */
	public function authAdd()
	{
		// 权限名称，权限方法，权限id
		$data = input('post.');
		$validate = validate('Auth');
		if($validate->check($data)){	
			$res = Db::table('am_auth_auth')->strict(false)->insert($data);
			if($res){
				$this->result('',1,'添加权限成功');
			}else{
				$this->result('',0,'添加权限失败');
			}
		}else{
			$this->result('',0,$validate->getError());
		}

	}

	/**
	 * 权限修改页面
	 * @return [type] [description]
	 */
	public function saveIndex()
	{
		// 获取权限数据id
		$id = input('post.id');
		// 查询
		$data = Db::table('am_auth_auth')->where('id',$id)->find();
		if($data){
			$this->result($data,1,'获取权限数据成功');
		}else{
			$this->result('',1,'获取权限数据失败');
		}
	}


	/**
	 * 修改权限
	 * @return [type] [description]
	 */
	public function authModify()
	{
		// 权限名称，权限方法，权限父级id
		$data = input('post.');
		$validate = validate('Auth');
		if($validate->check($data)){	
			$res = Db::table('am_auth_auth')->where('id',$data['id'])->strict(false)->update($data);
			if($res !== false){
				$this->result('',1,'修改权限成功');
			}else{
				$this->result('',0,'修改权限失败');
			}
		}else{
			$this->result('',0,$validate->getError());
		}

	}

	/**
	 * 删除权限
	 * @return [type] [description]
	 */
	public function authDel()
	{
		// 获取数据id     
		$id = input('post.id');
		// 查询pid等于该id的数据  总条数大于0怎说明下面又子级权限
		$count = Db::table('am_auth_auth')->where('pid',$id)->count();
		Db::startTrans();
		if($count > 0 ){
			// 删除pid 等于该id的数据
			$res = Db::table('am_auth_auth')->where('id='.$id.' OR pid='.$id)->delete();
		}else{
			$res = Db::table('am_auth_auth')->where('id',$id)->delete();
		}
		if($res){
			Db::commit();
			$this->result('',1,'删除成功');
		}else{
			Db::rollback();
			$this->result('',1,'删除成功');
		}
		
	}
}