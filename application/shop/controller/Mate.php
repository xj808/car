<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use think\Db;

/**
* 物料管理
*/
class Mate extends Shop
{
	
	/**
	 * 进程初始化
	 */
	public function initialize()
	{
		parent::initialize();
	}

	/**
	 * 获取物料库存
	 */
	public function remain()
	{
		$info = Db::table('cs_ration')
				->alias('r')
				->join(['co_bang_cate'=>'c'],'r.materiel = c.id')
				->field('r.ration,r.stock,c.name')
				->where('r.sid',$this->sid)
				->select();
		if($info){
			$this->result($info,1,'获取数据成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}


	/**
	 * 进行物料申请
	 */
	public function apply()
	{
		$count = $this->mateCount();
		if($count > 0){
			// 列出所有需要补充物料
			$info = Db::table('cs_ration')
					->alias('r')
					->join(['co_bang_cate'=>'c'],'r.materiel = c.id')
					->field('materiel,ration,stock,c.name')
					->where('sid',$this->sid)
					->where('stock < ration')
					->select();
			$this->result($info,1,'需要补充的物料');
		}else{
			$this->result('',0,'当前物料充足无需补充物料');
		}
	}
	

	/**
	 * 进行物料申请
	 */
	public function handle()
	{
		$data = input('post.');
		// 构建数组
		$aid = $this->getAgent();
		$arr = [
			'apply_sn' => build_only_sn(),
			'sid' => $this->sid,
			'aid' => $aid,
			'detail' => $data['detail']
		];
		// 检测是否有未处理的订单，防止重复提交
		$count = Db::table('cs_apply_materiel')->where('sid',$this->sid)->where('audit_status',0)->count();
		if($count > 0){
			$this->result('',0,'您有未处理的订单，请确认！');
		}else{
			// 进行数据插入
			if(Db::table('cs_apply_materiel')->strict(false)->insert($arr)){
				$this->result('',1,'提交成功');
			}else{
				$this->result('',0,'提交失败');
			}
		}
	}

	/**
	 * 取消物料申请订单
	 */
	public function cancel()
	{
		$id = input('post.id');
		$reason = input('post.reason');
		if(trim($reason) == ''){
			$this->result('',0,'取消理由不能为空');
		}
		// 检测订单处理状态
		$status = Db::table('cs_apply_materiel')->where('id',$id)->value('audit_status');
		// 如果订单未处理，则可以取消
		if($status == 0){
			$res = Db::table('cs_apply_materiel')->where('id',$id)->update(['audit_status'=>3,'reason'=>$reason]);
			if($res !== false){
				$this->result('',1,'提交成功');
			}else{
				$this->result('',0,'提交失败');
			}
		}else{
			// 如果订单已处理，则无法进行取消
			$this->result('',0,'订单已处理，无法取消！');
		}
	}

	/**
	 * 物料申请记录
	 */
	public function log()
	{
		$page = input('post.page') ? : 1;
		// 获取每页条数
		$pageSize = 2;
		// 获取分页总条数
		$count = Db::table('cs_apply_materiel')->where('sid',$this->sid)->count();
		$rows = ceil($count / $pageSize);
		// 查询数据内容
		$list = Db::table('cs_apply_materiel')
				->field('apply_sn,create_time,audit_status,audit_time,if_delay,reason,id')
				->where('sid',$this->sid)
				->order('id desc')
				->page($page, $pageSize)
				->select();
		foreach ($list as $k => $v) {
			if(!empty($list[$k]['audit_time'])){
				$list[$k]['audit_time']=date('Y-m-d H:i:s',$list[$k]['audit_time']);
			}
		}
		// 返回给前端
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 订单详情
	 */
	public function detail()
	{
		$id = input('post.id');
		$info = Db::table('cs_apply_materiel')->where('id',$id)->field('apply_sn,create_time,audit_time,detail')->find();
		$info['audit_time']=date();
		if($info){
			$this->result($info,0,'获取数据成功');
		}else{
			$this->result('',0,'数据异常');
		}
	}

	/**
	 * 确认收货
	 */
	public function confirm()
	{
		$id = input('post.id');
		// 检测订单处理状态
		$info = Db::table('cs_apply_materiel')->where('id',$id)->field('detail,audit_status')->find();
		// 如果订单未处理，则可以取消
		if($info['audit_status'] == 1){
			// 更改订单状态
			$change_status = Db::table('cs_apply_materiel')->where('id',$id)->setField('audit_status',2);
			// 维修厂库存增加
			// 
			if($res !== false){
				$this->result('',1,'确认收货成功');
			}else{
				$this->result('',0,'确认收货失败');
			}
		}else{
			// 如果订单已处理，则无法进行取消
			$this->result('',0,'订单状态无效，无法确认收货！');
		}
	}
	


}