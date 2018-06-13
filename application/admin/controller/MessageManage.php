<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
 * 消息管理
 */
class MessageManage extends Admin
{
	/**
	 * 发布系统消息
	 */
	public function addMessage()
	{		
		$data = input('post.');
		$validate = validate('MessageManage');
		if($validate->check($data)){
			$data['create_time'] = date('Y-m-d H:i:s', time());
			$data['create_person'] = Db::table('am_auth_user')
			                         ->where('id',$this->admin_id)
			                         ->value('name');
			$res = Db::table('am_msg')
		       ->strict(false)
		       ->insert($data);
			if($res){
				  $this->result('',1,'发布系统消息成功');
			} else {
 				  $this->result('',0,'发布系统消息失败，请重试');
			}
		} else {
			$this->result('',0,$validate->getError());
		}
		
	}

	/**
	 * 系统消息列表
	 * @return [type] [description]
	 */
	public function messageList()
	{
		$page = input('post.page')? : 1;
		$pageSize = 10;
		$count = Db::table('am_msg')->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('am_msg')
		        ->order('create_time')
		        ->page($page,$pageSize)
		        ->field('id,title,create_time')
		        ->select();
		if($count > 0){
            $this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
        }else{
            $this->result('',0,'暂无数据');
        }       
	}

	/**
	 * 系统消息详情
	 * @return [type] [description]
	 */
	public function messageDetail()
	{
		$id = input('post.id');
		$message = Db::table('am_msg')
		           ->where('id',$id)
		           ->find();
		$message['sendto'] = explode(",", $message['sendto']);
		return json($message);
	}

	/**
	 * 删除系统消息
	 * @return [type] [description]
	 */
	public function delMessage()
	{
		$id = input('post.id');
		$res = Db::table('am_msg')
		       ->where('id',$id)
		       ->delete();
		if($res){
			$this->result('',1,'删除系统消息成功');
		} else {
 			$this->result('',0,'删除系统消息失败，请重试');
		}
	}
}