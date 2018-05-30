<?php 
namespace app\shop\controller;
use app\base\controller\Shop;
use think\Db;
use think\File;
use msg\Sms;
/**
* 个人中心
*/
class Main extends Shop
{
	/**
	 * 进程初始化
	 */
	public function initialize()
	{
		parent::initialize();
		$this->Sms = new Sms();
	}

	/**
	 * 获取用户
	 */
	public function getUsers()
	{
		$page = input('post.page') ? : 1;
		// 获取每页条数
		$pageSize = 2;
		// 获取分页总条数
		$count = Db::table('u_card')->where('sid',$this->sid)->count();
		$rows = ceil($count / $pageSize);
		// 获取数据
		$list = Db::table('u_card')
				->alias('c')
				->join(['u_user'=>'u'],'c.uid = u.id')
				->field('plate,card_number,remain_times,u.name,u.phone,sale_time')
				->where('sid',$this->sid)
				->order('u.id desc')
				->page($page, $pageSize)
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 获取技师列表
	 */
	public function getTns()
	{
		$page = input('post.page') ? : 1;
		// 获取每页条数
		$pageSize = 2;
		// 获取分页总条数
		$count = Db::table('tn_user')->where('sid',$this->sid)->where('repair',1)->count();
		$rows = ceil($count / $pageSize);
		// 获取数据
		$list = Db::table('tn_user')
				->field('name,phone,server,cert,id')
				->where('sid',$this->sid)
				->where('repair',1)
				->order('id desc')
				->page($page, $pageSize)
				->select();
		// 返回数据给前端
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 获取技师列表
	 */
	public function getTnsDetail()
	{
		$id = input('post.id');
		$info = Db::table('tn_user')->where('id',$id)->field('name,phone,server,skill,wx_head,head')->find();
		// 返回数据给前端
		if($info){
			$this->result($info,1,'获取成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}

	/**
	 * 认证技师
	 */
	public function auditTns()
	{
		$id = input('post.id');
		$cert = input('post.cert');
		$tag = ($cert == 0) ? 1 : 0;
		$res = Db::table('tn_user')->where('id',$id)->setField('cert',$tag);
		if($res !== false){
			$this->result('',1,'认证成功');
		}else{
			$this->result('',0,'认证成功');
		}
	}

	/**
	 * 获取账户信息
	 */
	public function getAccount()
	{
		// 检测用户是否存在
		$count = $this->isExist();
		// 如果存在则修改用户信息
		if($count > 0){
			return Db::table('cs_shop_set')->field('bank,account_name,branch,account')->where('sid',$this->sid)->find();
		}else{
			$this->result('',0,'该用户为新用户');
		}
	}

	/**
	 * 修改银行账户
	 */
	public function setAccount()
	{
		// 获取数据
		$data = input('post.');
		// 获取用户手机号码
		$mobile = $this->getMobile();
		// 验证码短信验证码
		$check = $this->Sms->compare($mobile,$data['code']);
		if($check !== false){
			$res = 	Db::table('cs_shop_set')
					->where('id',$this->sid)
					->update(['branch'=>$data['branch'],'bank'=>$data['bank'],'account_name'=>$data['account_name'],'account'=>$data['account']]);
			if($res !== false){
				$this->result('',1,'修改成功');
			}else{
				$this->result('',0,'修改失败');
			}
		}else{
			$this->result('',0,'手机验证码无效或已过期');
		}
	}


	/**
	 * 获取基本信息
	 */
	public function getInfo()
	{
		// 检测用户是否存在
		$count = $this->isExist();
		// 如果存在则返回该用户信息
		if($count > 0){
			return 	Db::table('cs_shop')
					->alias('c')
					->join(['cs_shop_set'=>'s'],'s.sid = c.id')
					->field('license,photo,major,company,about,leader,province,city,county,address,serphone')
					->where('c.id',$this->sid)
					->find();
		}else{
			// 否则返回空信息
			$this->result('',0,'该用户为新用户');
		}
	}

	/**
	 * 修改基本信息
	 */
	public function setInfo()
	{
		// 获取提交过来的信息
		$data = input('post.');
		// 检测用户是否存在
		$count = $this->isExist();
		// 如果存在则修改用户信息
		if($count > 0){
			$save = Db::table('cs_shop_set')->where('sid',$this->sid)->update($data);
		}else{
			// 添加新的信息
			$save = Db::table('cs_shop_set')->where('sid',$this->sid)->insert($data);
		}
		if($save !== false){
			$this->result('',1,'保存成功');
		}else{
			$this->result('',0,'保存失败');
		}
	}


	

	/**
	 * 上传图片
	 */
	public function uploadImg()
	{
		return upload('file','shop','http://192.168.1.120');
	}

	/**
	 * 修改密码
	 */
	public function setPasswd()
	{
		// 获取数据
		$data = input('post.');
		// 获取用户手机号码
		$mobile = $this->getMobile();
		// 验证码短信验证码
		$check = $this->Sms->compare($mobile,$data['code']);
		if($check !== false){
			// 获取原密码
			$op = DB::table('cs_shop')->where('id',$this->sid)->value('passwd');
			// 检测原密码
			if(get_encrypt($data['passwd']) == $op){
				// 检测数据提交
				if($data['npass'] == $data['spass']){
					$res = Bb::table('cs_shop')->where('id',$this->sid)->setField('passwd',get_encrypt($data['passwd']));
					if($res !== false){
						$this->result('',1,'修改成功');
					}else{
						$this->result('',0,'修改失败');
					}
				}else{
					$this->result('',0,'两次密码不一致');
				}
				// 进行密码修改
			}else{
				$this->result('',0,'原密码错误');
			}
		}else{
			$this->result('',0,'手机验证码无效或已过期');
		}
	}
	
	/**
	 * 取消合作
	 */
	public function abolish()
	{
		// 获取取消合作理由
		$reason = input('poist.reason')
		// 获取代理商
		$info = Db::table('cs_shop')->where('id',$this->sid)->field('aid,company,leader')->find();
		if(strlen(trim($reason)) < 2){
			// 构建数据
			$data = [
				'sid' => $this->sid,
				'aid' => $info['aid'],
				'company' => $info['company'],
				'leader' => $info['leader'],
				'reason' => $reason
			];
			// 检测是否提交过，防止重复提交
			$count = Db::table('cs_apply_cancel')->where('sid',$this->sid)->count();
			if($count > 0){
				$this->result('',0,'已提交过，请勿重复提交');
			}else{
				$res = Db::table('cs_apply_cancel')->insert($data);
				if($res){
					$this->result('',1,'提交成功');
				}else{
					$this->result('',0,'提交失败');
				}
			}
		}else{
			$this->result('',0,'取消理由最少为2个字');
		}
		
	}


	/**
	 * 获取省列表
	 */
	public function getProvince()
	{
		return $this->province();
	}

	/**
	 * 获取市列表
	 */
	public function getCity()
	{
		return $this->city(input('post.pid'));
	}
	
	/**
	 * 获取县列表
	 */
	public function getCounty()
	{
		return $this->county(input('post.cid'));
	}

	/**
	 * 获取银行账户
	 */
	public function getBankCode()
	{
		return $this->bankCode();
	}

	/**
	 * 发送短信验证码
	 */
	public function pcode()
	{
		$mobile = $this->getMobile();
		$code = $this->apiVerify();
		$content = "您的短信验证码是【{$code}】。您正在通过手机号重置登录密码，如非本人操作，请忽略该短信。";
		return $this->Sms->send_code($mobile,$content,$code);
	}

	/**
	 * 更改账号验证码
	 */
	public function ecode()
	{
		$mobile = $this->getMobile();
		$code = $this->apiVerify();
		$content = "您的短信验证码是【{$code}】。您正在通过手机号修改银行账户号，如非本人操作，请忽略该短信。";
		return $this->Sms->send_code($mobile,$content,$code);
	}
	
}