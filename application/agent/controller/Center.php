<?php 
namespace app\agent\controller;
use app\base\controller\Agent;
use think\File;

/**
* 个人中心
*/
class Center extends Agent{
	
	function initialize(){
		parent::initialize();
		$this->agent='ca_agent';
		$this->area='ca_area';
		$this->agent_set='ca_agent_set';
		$this->china='co_china_data';
		
	}
	
	/**
	 * 个人中心首页
	 * @return 运营商信息，运营商id
	 */
	public function index(){
			// 页面数据
			$list = $this->alist($this->aid);

			if($list){
				$this->result($list,1,'获取页面数据成功');

			}else{
				$this->result('',0,'获取页面数据失败');
			}
		
	}

	
	/**
	 * 设置运营商供应地区及上传支付凭证
	 * @return [type]
	 */
	public function setArea(){
		$data = input('post.');
		foreach ($data['county'] as $k => $v) {
			$area[]=['area'=>$v,'aid'=>$this->aid];// 获取运营商所选择的区域
		}
		Db::startTrans();
		Db::table($this->area)->where('aid',$this->aid)->delete();
		$res=Db::table($this->area)->insertAll($area);
		if($res){
			
			//填写总金额，上传支付凭证
			$vo=$this->upLicense($data['voucher'],$data['deposit'],$this->aid);
			if($vo==true){
				Db::commit();
				$this->result('',1,'设置成功');
			}else{
				Db::rollback();
				$this->result('',0,'设置失败');
			}

		}else{
			Db::rollback();
			$this->result('',0,'设置失败');
		}
	}


	/**
     * 获取县级城市名称
     * @return  未选中的城市名称及已选中的城市名称
     */
    public function selCounty(){
        $city=input('post.id');

        $county=$this->commonCounty($city); //未被选中城市

        $selCounty=Db::table('ca_area')->select();//已被选中的城市
        if(!empty($county)){
        	$data = ['county'=>$county,'selCounty'=>$selCounty];
        	$this->result($data,1,'获取列表成功');
        }else{
        	$this->result($data,0,'获取列表失败');
        }

    }
	
	
	/**
	 * 上传营业执照
	 * @return 成功或失败
	 */
	public function license(){

		$license=input('post.license');

		$res=Db::table($this->agent)->where('aid',$this->aid)->setField('license',$license);
		if($res){
			$this->result('',1,'上传营业执照成功');
		}else{
			$this->result('',0,'上传营业执照失败');
		}
	}

	/**
	 * 查看运营商所供应地区
	 * @return 返回地区名称
	 */
	public function area(){

		// 通过区县id获得市级id
		$county=Db::table($this->area)->where('aid',$this->aid)->select();

		$county=array_str($county,'area');

		$city=$this->areaList($county);
		$province=$this->areaList($city);

		$list=Db::table($this->china)->whereIn('id',$province.','.$city.','.$county)->select();
		if($list){

			$list=get_child($list,$list[0]['pid']);
			$this->result($list,1,'获取列表成功');

		}else{	

			$this->result('',0,'暂未设置供应地区');
		}
	}


	/**
	 * 修改账户信息
	 * @return 修改成功或失败
	 */
	public function editAccount(){

		$data=input('post.');
		$validate=validate('Account');
		if($validate->check($data)){
			if($this->sms->compare($data['phone'],$data['code'])){

				$res=Db::table($this->agent)->where('aid',$this->aid)->update($data);

				if($res){
					
					$this->result('',1,'修改账户成功');
				}else{

					$this->result('',0,'修改账户失败');
				}
			}else{
				$this->result('',0,'手机验证码错误');

			}
		}else{
			$this->result('',0,$validate->getError());
		}
		

	}


	/**
	 * 发送修改账户信息的验证码
	 * @return [type] [description]
	 */
	public function accountCode()
	{
		$phone=input('post.phone');
		// 生成四位验证码
        $code=$this->apiVerify();

		$content="您的验证码是：【".$code."】。请不要把验证码泄露给其他人。";

		return $this->smsVerify($phone,$content,$code);
	}


	/**
	 * 修改密码
	 * @return [type] [description]
	 */
	public function modifyPass()
	{
		$data=input('post.');
		$validate=validate('ModifyPass');
		if($validate->check($data)){
			// 判断原密码是否输入正确
			if($this->pass($data['pass'],$this->aid) == false){

				$this->result('',0,'请输入正确的原密码');
			}
			// 判断手机验证码是否正确
			if($this->sms->compare($data['phone'],$data['code'])){

				if($this->xPass(get_encrypt($data['npass']),$this->aid)){

					$this->result('',1,'修改密码成功');

				}else{

					$this->result('',0,'修改密码失败');
				}

			}else{
				$this->result('',0,'手机验证码错误');
			}
			
		}else{
			$this->result('',0,$validate->getError());
		}
	}

	/**
	 * 判断原密码是否正确
	 * @param  [type] $npass [修改的原密码]
	 * @param  [type] $aid   [运营商id]
	 * @return [type]        [布尔值]
	 */
	private function pass($pass,$aid)
	{	
		$pwd=Db::table('ca_agent')->where('aid',$aid)->value('pass');
		if(get_encrypt($pass) !== $pwd){
			return false;
		}else{
			return true;
		}
	}

	/**
	 * 修改密码
	 * @param  [type] $npass [新密码]
	 * @param  [type] $aid   [运营商id]
	 * @return [type]        [布尔值]
	 */
	private function xPass($npass,$aid)
	{
		$res=Db::table('ca_agent')->where('aid',$aid)->setField('pass',$npass);
		if($res !== false){
			return true;
		}
	}

	/**
	 * 查询运营商详细信息
	 * @param  运营商id
	 * @return 运营商详细信息
	 */
	private function alist($aid){
		$list=Db::table('ca_agent a')
			->leftJoin('ca_increase ci','a.aid=ci.aid')	
			->where('a.aid',$aid)
			->field('a.aid,login,compay,account,leader,province,city,county,address,phone,open_shop,license,voucher')
			->find();
		return $list;
	}
	
	/**
	 * 运营商供应地区树形结构
	 * @return 城市地区id
	 */
	private function areaList($city)
	{
		$city=Db::table($this->china)->whereIn('id',$city)->select();
		return array_str($city,'pid');
	}

	/**
	 * 修改shop表状态为2
	 * @return  布尔值
	 */
	private function shopSta($aid){
		
		$res=Db::table($this->agent)->where('aid',$aid)->setField('status',2);
		if($res){
			return true;
		}
	}

	
	
}
