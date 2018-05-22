<?php 
namespace app\shop\controller;
use app\base\controller\Shop;

use use app\base\controller\Shop;
>>>>>>> origin/dev
use think\Db;
/**
 * 汽修厂获取系统消息
 */
class Msg extends Shop
{
	/**
	 * 获取系统发送的关于发送对象最后一条信息的id
	 */
	public function getLastMid($rid)
	{
		return Db::table('am_msg')->where('sendto','like','%'.$rid)->max('id');
	}

	/**
	 * 获取当前用户信息库的最后一条信息的id
	 */
	public function getMaxMid($table,$uid)
	{
		return Db::table($table)->where('uid',$uid)->max('mid');
	}

	/**
	 * 获取当前信息列表
	 */
	public function msgList($table,$uid)
	{
		return 	Db::table($table)
				->alias('um')
				->join(['am_msg'=>'am'],'um.mid = am.id')
				->field('mid,status,title,create_time')
				->where('uid',$uid)
				->order('um.mid desc')
				->paginate(10);
	}

	/**
	 * 获取未读取的信息数据
	 */
	public function getUrMsg($rid,$table,$uid)
	{
		// 获取系统消息最后一条
		$s_mid = $this->getLastMid($rid);
		// 获取消息库里最后一条
		$u_mid = $this->getMaxMid($table,$uid);
		// 如果消息库里的id大于等于系统消息的id
		if($u_mid >= $s_mid){
			// 不做操作
			return false;
		}else{
			// 获取所差的数据条数
			$mids = Db::table('am_msg')->field('id')->where('id','>',$u_mid)->select();
			// 将所差数据插入数据库
			foreach ($mids as $k => $v) {
				$data[$k] = ['uid'=>$uid,'mid'=>$v];
			}
			Db::name($table)->insertAll($data);
		}
	}

	/**
	 * 获取消息详情
	 */
	public function msgDetail($table,$mid,$uid)
	{
		// 更新消息状态
		Db::table($table)->where(['id'=>$mid,'uid'=>$uid])->setField('status',1);
		// 返回消息详情
		return Db::table('am_msg')->where(['id'=>$mid,'uid'=>$uid])->find();
	}
	
}