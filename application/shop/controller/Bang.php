<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use think\Db;
use Msg\Sms;

/**
 * 汽修厂邦保养操作
 */
class Bang extends Shop
{

	/**
	 * 进程初始化
	 */
	public function initialize()
	{
		// parent::initialize();
	}


	public function aa($value='')
	{
		print_r($_SERVER);
	}

	/**
	 * 邦保养记录
	 */
	public function log()
	{
		$page = input('post.page') ? : 1;
		// 获取每页条数
		$pageSize = 2;
		// 获取分页总条数
		$count = Db::table('cs_income')->where('sid',$this->sid)->count();
		print_r($count);exit;
		$rows = ceil($count / $pageSize);
		$list = Db::table('cs_income')
				->alias('i')
				->join(['u_card'=>'c'],'i.cid = c.id')
				->field('odd_number,cate_name,plate,i.create_time,i.id')
				->where('i.sid',$this->sid)
				->order('i.id desc')
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
	 * 获取邦保养详情
	 */
	public function detail()
	{
		$id = input('post.id');
		$info = Db::table('cs_income')
				->alias('i')
				->join(['u_card'=>'c'],'i.cid = c.id')
				->field('odd_number,i.create_time,litre,filter,grow_up,hour_charge,total,oil_name,cate_name')
				->where('i.id',$id)
				->find();
		// 返回给前端
		if($info){
			$this->result($info,1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 获取邦保养信息
	 */
	public function getInfo()
	{
		// 获取车牌号
		$plate = input('post.plate','','strtoupper');
		// 检测该车辆是否在当前汽修厂
		$count  = 	Db::table('u_card')
					->where('sid',$this->sid)
					->where('plate',$plate)
					->where('remain_times','>',0)
					->count();
		return Db::table('u_card')->select();exit;
		// 如果该车存在
		if($count > 0){
			$info = $this->getCarInfo($plate);
			$check = $this->checkOil($this->sid,$info['oid'],$info['litre']);
			if($check !== false){
				$this->result($info,1,'获取信息成功');
			}else{
				$this->result('',0,'该油品库存不足');
			}
		}else{
			$this->result('',0,$plate.'不属于该汽修厂');
		}

	}

	/**
	 * 进行邦保养操作
	 */
	public function handle()
	{
		// 获取提交过来的数据
		$data = input('post.');
		// 实例化验证
		$validate = validate('Bang');
		// 如果验证通过则进行邦保养操作
		if($validate->check($data)){
			// 检测手机验证码是否正确
			$check = $this->sms->compare($data['phone'],$data['code']);
			if($check !== false){
				// 检测库存是否充足
				$oilCheck = $this->checkOil($this->sid,$data['oid'],$data['litre']);
				// 如果库存充足，则进行邦保养操作
				if($oilCheck !== false){
					// 获取运营商处设定的金额
					$rd = 	Db::table('cs_shop')
							->alias('s')
							->join(['ca_agent'=>'a'],'s.aid = a.aid')
							->field('shop_fund,shop_hours')
							->where('s.id',$this->sid)
							->find();
					// 构建邦保养记录数据
					$arr = [
						'sid' => $this->sid,
						'order_number' => build_order_sn(),
						'cid' => $data['cid'],
						'oil' => $data['oil'],
						'uid' => $data['uid'],
						'litre' => $data['litre'],
						'filter' => $data['filter'],
						'grow_up' => $rd['shop_fund'],
						'hour_charge' => $rd['shop_hours'],
						'total' => $rd['shop_fund']+$rd['shop_hours']
					];
					// 开启事务
					Db::startTrans();
					// 减少用户卡的次数
					$card_dec = Db::table('u_card')->where('id',$data['cid'])->setDec('remain_times');
					// 汽修厂库存减少
					$ration_dec = Db::table('cs_ration')->where('sid',$this->sid)->where('materiel',$data['oid'])->setDec('stock',$data['litre']);
					// 汽修厂账户余额增加
					$shop_inc = Db::table('c_shop')->where('id',$this->sid)->setInc('balance',$arr['total']);
					// 生成邦保养记录
					$bang_log = Db::table('cs_income')->where('sid',$this->sid)->insert($arr);
					// 事务提交判断
					if($card_dec && $ration_dec  && $shop_inc && $bang_log){
						Db::commit();
						$this->result('',1,'提交成功');
					}else{
						Db::rollback();
						$this->result('',0,'提交失败');
					}
				}else{
					$this->result('',0,'该油品库存不足');
				}
			}else{
				$this->result('',0,'手机验证码无效或已过期');
			}
		}else{
			$this->result('',0,$validate->getError());
		}
	}


	/**
	 * 发送短信验证码
	 */
	public function vcode()
	{
		$mobile = input('post.mobile');
		$card_number = input('post.card_number');
		$code = $this->apiVerify();
		$content = "您邦保养卡号为【{$card_number}】参与本次保养的验证码为【{$code}】，请勿泄露给其他人。";
		$res = $this->sms->send_code($mobile,$content,$code);
		$this->result('',1,$res);
	}

	/**
	 * 检测库存油品是否充足
	 */
	public function checkOil($sid,$oid,$litre)
	{
		// 获取该油品库
		$stock = Db::table('cs_ration')->where(['materiel' => $oid,'sid' => $sid])->value('stock');
		// 检测该油品库存是否充足
		return ($stock < $litre) ? false : true;
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
				->field('u.name,u.phone,u.id as uid,d.month,d.km,d.filter,d.litre,car.type,c.card_number,c.remain_times,ba.name as oil,c.oil as oid,c.id as cid,c.plate')
				->find();
	}
}