<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use think\Db;
/**
 * 汽修厂邦保养操作
 */
class Bang extends Shop
{
	/**
	 * 邦保养记录
	 */
	

	/**
	 * 获取邦保养信息
	 */
	public function getInfo()
	{
		// 获取车牌号
		$plate = input('post.plate','','strtoupper');
		// 检测该车辆是否在当前汽修厂
		$count  = 	Db::table('u_card')
					->where('sid',$sid)
					->where('plate',$plate)
					->where('remain_times','>',0)
					->count();
		// 如果该车存在
		if($count > 0){
			$info = $this->getCarInfo($plate);
			$check = $this->checkOil($sid,$info['oid'],$info['litre']);
			return ($check !== false) ? $info : '该油品库存不足';
		}else{
			return $plate.'not exsit';
		}

	}

	/**
	 * 进行邦保养操作
	 */
	public function handle()
	{
		# code...
	}

	/**
	 * 检测库存油品是否充足
	 */
	public function checkOil($sid,$oid,$litre)
	{
		// 获取该油品库
		$stock = Db::table('cs_ration')->where(['materiel' => $oid,'sid' => $sid])->value('stock');
		// 检测该油品库存是否充足
		return ($tock < $litre) ? false : true;
	}

	/**
	 * 获取车辆信息
	 */
	public function getCarInfo($plate)
	{
		return 	Db::table('u_card')
				->alias('c')
				->join(['u_user'=>'u'],'c.uid = u.id')
				->join(['co_bang_data'=>'d'],'c.car_cate_id = d.cid')
				->join(['co_car_cate'=>'car'],'c.car_cate_id = car.id')
				->join(['co_bang_cate'=>'ba'],'c.oil = ba.id')
				->where('plate',$plate)
				->field('u.name,u.phone,d.month,d.km,d.filter,d.litre,car.type,c.card_number,c.remain_times,ba.name as oil,c.oil as oid')
				->find();
	}
}