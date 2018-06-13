<?php 
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
 * 小程序轮播图管理
 */
class SmallSlideshow extends Admin
{
	/**
	 * 轮播图上传
	 * @return 轮播图上传后的路径地址
	 */
	public function uploadPic()
	{
		return upload('image','slideshow','http://192.168.1.110/car/public');
	}

    /**
     * 添加轮播图信息
     */
    public function addPic()
    {
    	$data = input('post.');
    	$data['create_time'] = time();
    	$res = Db::table('am_banner')
    	       ->strict(false)
    	       ->insert($data);
    	if($res){
			$this->result('',1,'添加轮播图成功');
    	} else {
			$this->result('',0,'添加失败，请稍后重试');
    	}
    }

	/**
	 * 轮播图列表
	 * @return [type] [description]
	 */
	public function picList()
	{
		$page = input('post.page')? : 1;
		$pageSize = 10;
		$count = Db::table('am_banner')->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('am_banner')
				->order('create_time desc')
				->page($page,$pageSize)
				->field('id,title,pic,content,create_time')
				->select();
		if($count > 0){
            $this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
        }else{
            $this->result('',0,'暂无数据');
        }  
	}

	/**
	 * 轮播图修改
	 * @return [type] [description]
	 */
	public function picAlter()
	{
		$data = input('post.');
		$id = input('post.id');
		unset($data['token']);
		$res = Db::table('am_banner')
		       ->where('id',$id)
		       ->update($data);
		if($res){
			$this->result('',1,'修改信息成功');
		} else {
			$this->result('',0,'修改信息失败，请重试');
		}
	}

	/**
	 * 删除轮播图
	 * @return [type] [description]
	 */
	public function picDelete()
	{
		$id = input('post.id');
		$res = Db::table('am_banner')
		       ->where('id',$id)
		       ->delete();
		if($res){
			$this->result('',1,'删除轮播图成功');
		} else {
			$this->result('',0,'删除轮播图失败，请稍后重试');
		}
	}
}