<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use think\Db;
/**
* 礼品兑换
*/
class Gift extends Shop
{
	/**
	 * 进程初始化
	 */
	public function initialize()
	{
		parent::initialize();
	}
	
	/**
	 * 兑换列表
	 */
	public function index()
	{
		$page = input('post.page') ? : 1;
		// 获取每页条数
		$pageSize = 2;
		// 获取分页总条数
		$count = Db::table('cs_gift')->where('sid',$this->sid)->count();
		$rows = ceil($count / $pageSize);
		// 获取数据
		$list = Db::table('cs_gift')
				->alias('g')
				->join(['u_card'=>'c'],'g.cid = c.id')
				->field('excode,card_number,gcate,gid,ex_time')
				->where('g.sid',$this->sid)
				->order('g.id desc')
				->page($page, $pageSize)
				->select();
		// 返回给前端
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 兑换信息
	 */
	public function getGift()
	{
		$excode = input('post.excode');
		// 检测兑换码是否有效
		$res = $this->checkExCode($this->sid,$excode);
		// 如果是店铺提供的服务
		$gift = Db::table('cs_gift')->where('excode',$excode)->value('gift_name');
		if($gift){
			$this->result($gift,1,'获取数据成功');
		}else{
			$this->result('',0,'获取赠品信息失败');
		}
		
		
	}

	/**
	 * 进行兑换
	 */
	public function handle()
	{
		$excode = input('post.excode');
		// 检测兑换码是否有效
		$res = $this->checkExCode($this->sid,$excode);
		if($res){
			// 开启事务
			Db::startTrans();
			// 如果类别小于3则兑换赠品
			if($res['gcate'] < 3){
				// 检测库存是否充足
				$this->checkRation($this->sid,$res['gid']);
				// 如果充足则减少库存
				$se = Db::table('cs_ration')->where('sid',$this->sid)->where('materiel',$res['gid'])->setDec('stock',1);
			}else{
				// 获取兑换服务名称
				$server_name = Db::table('cs_service')->where('sid',$this->sid)->value('service');
				// 获取邦保养卡号
				$card_number = Db::table('cs_gift')->alias('g')->join(['u_card'=>'c'],'g.cid=c.id')->where('excode',$excode)->value('card_number');
				// 如果等于3则兑换自己的服务,添加收入记录
				$si = Db::table('cs_ex_income')->add(['sid'=>$this->sid,'money'=>100,'excode'=>$excode,'card_number'=>$card_number]);
				// 系统补助100元
				$sa = Db::table('cs_shop_set')->where('id',$this->sid)->setInc('balance',100);
			}
			// 兑换码失效，兑换信息改变
			$ex = Db::table('cs_gift')->where('excode',$excode)->update(['ex_time'=>time(),'status'=>1]);
			// 进行事务处理
			if(($se && $ex) || ($si && $sa && $ex)){
				Db::commit();
				$this->result('',1,'兑换成功');
			}else{
				Db::rollback();
				$this->result('',0,'兑换失败');
			}
		}
	}


	/**
	 * 兑换收入
	 */
	public function income()
	{
		$page = input('post.page') ? : 1;
		// 获取每页条数
		$pageSize = 2;
		// 获取分页总条数
		$count = Db::table('cs_ex_income')->where('sid',$this->sid)->count();
		$rows = ceil($count / $pageSize);
		// 获取数据
		$list = Db::table('cs_ex_income')
				->where('sid',$this->sid)
				->order('id desc')
				->page($page, $pageSize)
				->select();
		// 返回给前端
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 检测赠品库存是否充足
	 */
	public function checkRation($sid,$gid)
	{
		// 赠品库存是否充足
		$gs = Db::table('cs_ration')->where('sid',$sid)->where('materiel',$gid)->value('stock');
		// 如果库存充足则进行兑换
		if($gs <= 0) $this->result('',0,'该赠品库存不足，请进行补货后再进行兑换');
	}

	/**
	 * 检测兑换码是否有效
	 */
	public function checkExCode($sid,$excode)
	{
		// 查看兑换物品码是否有效
		$res = 	Db::table('cs_gift')
				->field('cid,gcate,gid')
				->where('excode',$excode)
				->where('sid',$sid)
				->where('status',1)
				->find();
		if($res){
			return $res;
		}else{
			$this->result('',0,'兑换码已失效或不属于本店');
		}
	}



}