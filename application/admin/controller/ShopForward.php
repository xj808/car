<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 修车厂提现
*/
class ShopForward extends Admin
{
	
	/**
	 * 提现申请列表
	 * @return [type] [description]
	 */
	public function waitList()
	{
		$page = input('post.page')? : 1;
		$this->index($page,0);
	}



	/**
	 * 提现通过列表
	 * @return [type] [description]
	 */
	public function rejectList()
	{
		$page = input('post.page')? : 1;
		$this->index($page,1);
	}



	/**
	 * 提现驳回列表
	 * @return [type] [description]
	 */
	public function adoptList()
	{
		$page = input('post.page')? : 1;
		$this->index($page,1);
	}





	/**
	 * 邦保养收入明细
	 * @return [type] [description]
	 */
	public function bangList()
	{
		$sid  = input('post.sid');
		$page = input('post.page')? : 1;
		$this->incomeList($page,'cs_income',$sid,'total','create_time');
	}



	/**
	 * 兑换收入明细
	 * @return [type] [description]
	 */
	public function chargeList()
	{
		$sid  = input('post.sid');
		$page = input('post.page')? : 1;
		$this->incomeList($page,'cs_ex_income',$sid,'ex_charge','ex_time');
	}


	/**
	 * 提现明细
	 * @return [type] [description]
	 */
	public function forwardList()
	{
		$sid = input('post.sid');
		$page = input('post.page')? :1;
		$pageSize = 10;
        $count = Db::table('cs_apply_cash')->where(['sid'=>$sid,'audit_status'=>1])->count();

        $rows = ceil($count / $pageSize);

        $list = Db::table('cs_apply_cash')
                ->where(['sid'=>$sid,'audit_status'=>1])
                ->order('id desc')->page($page,$pageSize)
                ->field('audit_time,money,sur_amount')
                ->select();

        if($count > 0){
            $this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
        }else{
            $this->result('',0,'暂无数据');
        }   
	}



	/**
	 * 提现审核列表
	 * @return [type] [description]
	 */
	public function auditList()
	{
		// 获取修车厂电话
		$phone = input('post.phone');
		// 获得修车厂id
		$sid = input('post.sid');
		// 获取本次提现审核的时间和金额
        $now = Db::table('cs_apply_cash')->where(['sid'=>$sid,'audit_status'=>0])->field('id,sid,money,sur_amount,create_time')->find();
        // 获取上次提现的时间和金额
        $old = Db::table('cs_apply_cash')
                ->where(['sid'=>$sid,'audit_status'=>1])
                ->order('audit_time desc')->limit(1)
                ->find();

        $list = [
            'id'=>$now['id'],
            'sid'=>$now['sid'],
            'old_money' =>$old['money'],
            'old_time'  =>$old['create_time'],
            'now_money' =>$now['money'],
            'now_amount'=>$now['sur_amount'],
            'phone'     =>$phone,
            'create_time'=>$now['create_time']
        ];

        if($list){
            $this->result($list,1,'获取列表成功');
        }else{
            $this->result('',0,'获取列表失败');
        }
	}




	/**
	 * 提现申请驳回操作
	 * @return [type] [description]
	 */
	public function reject()
	{
		// 获取修理厂电话，驳回理由，提现申请时间，订单id，运营商本次提现金额,运营商id
		$data = input('post.');
		Db::startTrans();
		// 获取管理员名称
		$audit_person = Db::table('am_auth_user')->where('id',$this->admin_id)->value('name');
		$arr = [
			'audit_status'=>2,
			'reason'=>$data['reason'],
			'audit_person'=>$data['audit_person'],
		];
		// 修改数据库状态为已驳回，修改驳回理由，修改处理人
		$res = Db::table('cs_apply_cash')->where('id',$data['id'])->update($arr);
		if($res !== false){

			// 驳回成功把运营商的余额增加
			$result = Db::table('cs_shop')->where('sid',$data['sid'])->inc('balance',$data['money'])->update();

			if($result !== false){
				// 给运营商发送短信
				$tx = '提现';
				$content = "您于【".$data['create_time']."】的【".$tx."】申请，因【".$data['reason']."】被驳回，请完成修订后重新提交。";
				// print_r($content);exit;
				$sms = $this->smsVerify($data['phone'],$content);
				if($sms == '提交成功'){
					Db::commit();
					$this->result('',1,'驳回成功,已给运营商发送短信');
				}else{
					Db::rollback();
					$this->result('',0,$sms);
				}
			}else{
				Db::rollback();
				$this->result('',0,'修车厂余额增加失败');
			}
			
		}else{
			Db::rollback();
			$this->result('',0,'修改状态成功');
		}

	}



	/**
	 * 点击驳回理由显示内容
	 * @return [type] [description]
	 */
	public function shopReason()
	{
		$id = input('post.id');
		$reason = $this->reason($id,'cs_apply_cancel','reason');
		if($reason){
			$this->result($reason,1,'获取理由成功');
		}else{
			$this->result('',0,'获取理由失败');
		}
	}


	/**
	 * 收入明细
	 * @return [type] [description]
	 */
	private function incomeList($page,$table,$sid,$money,$time)
	{
		$pageSize = 10;
		$count = Db::table($table)->where('sid',$sid)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table($table)
				->where('sid',$sid)
				->field($money.','.$time)
				->order('id desc')->page($page,$pageSize)
				->select();

		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}




	/**
	 * 修车厂提现列表
	 * @param  [type] $page   [description]
	 * @param  [type] $status [description]
	 * @return [type]         [description]
	 */
	private function index($page,$status)
	{	
		$pageSize = 10;
		$count = Db::table('cs_apply_cash')->where('audit_status',$status)->count();
		$rows = ceil($page / $pageSize);
		$list = Db::table('cs_apply_cash ac')
				->join('cs_shop cs','ac.sid = cs.id')
				->where('ac.audit_status',$status)
				->order('ac.id desc')
				->field('ac.sid,ac.id,company,leader,phone,money,ac.create_time,ac.audit_time')
				->page($page,$pageSize)
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'获取列表失败');
		}

	}
}