<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 运营商物料申请
*/
class AgentMateriel extends Admin
{



	/**
	 * 物料申请审核列表
	 * @return [type] [description]
	 */
	public function waitList()
	{
		$page = input('post.page')? : 1;
		$this->index($page,0);
	}


	/**
	 * 物料申请已通过列表
	 * @return [type] [description]
	 */
	public function adoptList()
	{
		$page = input('post.page')? : 1;
		$this->index($page,1);
	}


	/**
	 * 物料申请驳回列表
	 * @return [type] [description]
	 */
	public function rejectList()
	{
		$page = input('post.page')? : 1;
		$this->index($page,2);
	}



	/**
	 * 审核列表详情
	 * @return [type] [description]
	 */
	public function detail()
	{
		// 获取物料申请数据id
		$id = input('post.id');
		$list = Db::table('ca_apply_materiel am')
				->join('ca_agent ca','ca.aid = am.aid')
				->where('id',$id)
				->field('am.aid,company,phone,province,city,county,address')
				->find();
		if($list){
			$this->result($list,1,'获取详情成功');
		}else{
			$this->result('',0,'获取详情失败');
		}
	}


	/**
	 * 物料申请通过
	 * @return [type] [description]
	 */
	public function adopt()
	{
		$id = input('post.id');
		$aid = input('post.aid');
		Db::startTrans();
		$audit_person = Db::table('am_auth_user')->where('id',$this->admin_id)->value('name');
		$data=[
			'audit_status'=>1,
			'audit_person'=>$audit_person,
			'audit_time'=>time(),
		];
		// 修改物料订单状态为1
		$res = Db::table('ca_apply_materiel')->where('id',$id)->update($data);
		// 增加运营商库存
		if($res == true){
			// 获取运营商所申请的物料
			$list = $this->matNum($id);
			if(empty($list)){
				$this->result('',0,'您没有申请物料');
			}
			// 把物料循环加入到运营商库存里
			foreach($list as $k=>$v){
				$res = Db::table('ca_ration')
					->where(['aid'=>$aid,'materiel'=>$v['materiel_id']])
					->inc('materiel_stock',$v['num'])
					->update();
			}
			if($res !== false){
				// 获取运营商电话
				$mobile = Db::table('ca_agent')->where('aid',$aid)->value('phone');

				// 给运营商发送短信
				$content = "您申请的货物已发出，请注意查收。";
	        	$send = $this->sms->send_code($mobile,$content);
	        	Db::commit();
				$this->result('',1,'处理成功');
			}else{
				Db::rollback();
				$this->result('',0,'操作失败');
			}

		}else{
			Db::rollback();
			$this->result('',0,'修改状态失败');
		}

	}


	/**
	 * 物料申请驳回操作
	 * @return [type] [description]
	 */
	public function reject()
	{
		$id =input('post.id');
		$reason = input('post.reason');
		if(empty($reason)){
			$this->result('',0,'驳回理由不能为空');
		}
		$audit_person = Db::table('am_auth_user')->where('id',$this->admin_id)->value('name');
		$data=[
			'audit_status'=>2,
			'reason'=>$reason,
			'audit_person'=>$audit_person,
			'audit_time'=>time(),
		];
		$result = Db::table('ca_apply_materiel')->where('id',$id)->update($data);
		if($result !== false){
			// 获取运营商id
			$aid = Db::table('ca_apply_materiel')->where('id',$id)->value('aid');
			// 获取运营商电话
			$mobile = Db::table('ca_agent')->where('aid',$aid)->value('phone');
			// 获取物料申请时间
			$create_time = Db::table('ca_apply_materiel')->where('aid',$aid)->value('create_time');
			// 给运营商发送短信
			$content = "您于【".$create_time."】申请的物料,因【".$reason."】被驳回，请完成修订后重新提交。";
        	$send = $this->sms->send_code($mobile,$content);
        	Db::commit();
			$this->result('',1,'处理成功');
		}else{
			$this->result('',0,'驳回失败');
		}

	}




	/**
	 * 物料种类内容
	 * @return [type] [description]
	 */
	public function materiel()
	{
		$id = input('post.id');
		$list = $this->matNum($id);
		if($list){
			$this->result($list,1,'获取数据成功');
		}else{
			$this->result('',0,'获取数据失败');
		}
	}



	/**
	 * 点击驳回理由显示内容
	 * @return [type] [description]
	 */
	public function matReason()
	{
		$id = input('post.id');
		$reason = $this->reason($id,'ca_apply_materiel','reason');
		if($reason){
			$this->result($reason,1,'获取理由成功');
		}else{
			$this->result('',0,'获取理由失败');
		}
	}

	public function  downWord()
    {
    	$id = input('post.id');
        // New Word Document
        $PHPWord = new PhpWord();
        // New portrait section
        $section = $PHPWord->createSection();
         $Data  = Db::table('ca_apply_materiel am')
        		->join(['ca_agent'=>'ca'],'am.aid = ca.aid')
 				->where('id',$id)
 				->json(['detail'])
 				->field('id,odd_number,company,phone,province,city,county,address,am.create_time,detail')
 				->find();
 		$Data['caddress'] = $Data['province'].$Data['city'].$Data['county'].$Data['address'];
            $fontStyle = array('color'=>'000000', 'size'=>15,'align'=>'center');
            $PHPWord->addFontStyle('myOwnStyle', $fontStyle);
            $section->addText(iconv('utf-8', 'gb2312','编号：'.$Data['id']), 'myOwnStyle');
            $section->addTextBreak(1);
            $section->addText(iconv('utf-8', 'gb2312','订单号'.$Data['odd_number']), 'myOwnStyle');
            $section->addTextBreak(1);
            $section->addText(iconv('utf-8', 'gb2312','公司名称：'.$Data['company']), 'myOwnStyle');
            $section->addTextBreak(1);
            $section->addText(iconv('utf-8', 'gb2312','手机号：'.$Data['phone']), 'myOwnStyle');
            $section->addTextBreak(1);
            $section->addText(iconv('utf-8', 'gb2312','地址：'.$Data['caddress']), 'myOwnStyle');
            $section->addTextBreak(1);
            $section->addText(iconv('utf-8', 'gb2312','申请时间：'.$Data['create_time']), 'myOwnStyle');
            foreach ($Data['detail'] as $k => $v) {
            	$section->addText(iconv('utf-8', 'gb2312','物料名称：'.$v['materiel']), 'myOwnStyle');
            	$section->addText(iconv('utf-8', 'gb2312','物料数量：'.$v['num']), 'myOwnStyle');
            	$section->addText(iconv('utf-8', 'gb2312','备注：'.$v['remarks']), 'myOwnStyle');
            }
            // $section->addMemoryImage($img,$imageStyle);
            $section->addTextBreak(1);
            $section->addPageBreak();//分页
        $xlsTitle = iconv('utf-8', 'gb2312','运营商物料申请');//文件名称
        $fileName = '运营商物料申请'.date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
        header('pragma:public');
        header('Content-type:application/vnd.ms-word;charset=utf-8;name="'.$xlsTitle.'.doc"');
        header("Content-Disposition:inline;filename=$fileName.doc");//attachment新窗口打印inline本窗口打印
        ob_clean();//关键
         flush();//关键
        $objWrite = IOFactory::createWriter($PHPWord, 'Word2007');
        $objWrite->save('php://output');
        exit;
    }


	/**
	 * 物料申请待审核已审核列表
	 * @return [type] [description]
	 */
	private function index($page,$status)
	{
		$pageSize = 2;
		$count = Db::table('ca_apply_materiel')->where('audit_status',$status)->count();
		$rows = ceil($count / $pageSize);
		$list = Db::table('ca_apply_materiel am')
				->leftJoin('ca_agent ca','ca.aid = am.aid')
				->where('am.audit_status',$status)
				->page($page,$pageSize)
				->order('id desc')
				->field('am.id,company,leader,phone,am.create_time,am.audit_time')
				->select();
		if($count > 0){
			$this->result(['list'=>$list,'rows'=>$rows],1,'获取列表成功');
		}else{
			$this->result('',0,'暂无数据');
		}
	}



	/**
	 * 获取运营商所申请的物料
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	private function matNum($id)
	{
		$list =Db::table('ca_apply_materiel')->where('id',$id)->json(['detail'])->find();
		return $list['detail'];
	}






}