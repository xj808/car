<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use think\Db;
use Msg\Sms;
/**
* 资金管理
*/
class Money extends Shop
{
	
	/**
	 * 进程初始化
	 */
	public function initialize()
	{
		parent::initialize();
	}

	/**
	 * 收入列表
	 */
	public function incomeList()
	{
		$page = input('post.page') ? : 1;
		// 获取每页条数
		$pageSize = 2;
		// 获取分页总条数
		$count = Db::table('cs_income')->where('sid',$this->sid)->count();
		$rows = ceil($count / $pageSize);
		// 获取数据
		$list = Db::table('cs_income')
				->alias('i')
				->join(['u_card' => 'c'],'i.cid = c.id')
				->field('i.odd_number,i.total,i.grow_up,i.create_time,c.plate,c.cate_name')
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
	 * 提现申请信息
	 */
	public function draw()
	{
		$info = Db::table('cs_shop')
				->alias('sp')
				->join(['cs_shop_set'=>'st'],'st.sid = sp.id')
				->join(['co_bank_code'=>'b'],'st.bank = b.code')
				->field('phone,balance,b.name as bank,account,account_name')
				->where('sp.id',$this->sid)
				->find();
		if($info){
			$this->result($info,1,'获取数据成功');
		}else{
			$this->result('',0,'获取数据失败');
		}
	}

	/**
	 * 提现申请操作
	 */
	public function handle()
	{
		$data = input('post.');
		// 进行短信验证码验证
		$check = $this->sms->compare($data['mobile'],$data['code']);
		// 获取账户信息
		$info = Db::table('cs_shop_set')->field('bank,account,account_name')->where('sid',$this->sid)->find();
		if($check !== false){
			// 开启事务
			Db::startTrans();
			// 构建提现数据
			$arr = [
				'odd_number' => build_order_sn(),
				'sid'		=> $this->sid,
				'bank'	=>	$info['bank'],
				'account_name'	=>	$info['account_name'],
				'account'	=>	$info['account'],
				'money'	=>	$data['money']
			];
			// 检测提现金额是否超限
			$max = Db::table('cs_shop')->where('id',$this->sid)->getField('balance');
			if($max < $data['money']){
				$this->result('',0,'超过最大提现额度');
			}
			// 实例化验证
			$validate = validate('Draw');
			// 进行数据验证
			if($validate->check($arr)){
				// 写入提现记录
				$draw_log = Db::table('cs_apply_cash')->insert($arr);
				// 账户余额减少
				$account_dec = Db::table('cs_shop')->where('id',$this->sid)->setDec('balance',$data['money']);
				// 进行事务提交
				if($draw_log && $account_dec) {
					Db::commit();
					$this->result('',1,'提交成功，请等待审核');
				}else{
					Db::rollback();
					$this->result('',0,'提交失败，请重新提交');
				}
			}else{
				$this->result('',0,$validate->getError());
			}
		}else{
			$this->result('',0,'手机验证码无效或已过期');
		}
		
	}

	/**
	 * 提现明细
	 */
	public function drawList()
	{
		$page = input('post.page') ? : 1;
		// 获取每页条数
		$pageSize = 2;
		// 获取分页总条数
		$count = Db::table('cs_apply_cash')->where('sid',$this->sid)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('cs_apply_cash')
				->field('account,money,create_time,audit_status,audit_time,reason')
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
	 * 发送短信验证码
	 */
	public function vcode()
	{
		$mobile = $this->getMobile();
		$code = $this->apiVerify();
		$content = "您本次提现的验证码是【{$code}】，请不要泄露个任何人。";
		$res = $this->sms->send_code($mobile,$content,$code);
		$this->result('',1,$res);
	}
}