<?php 
namespace app\agent\controller;
use think\Controller;
use app\base\controller\Base;
use think\Db;
/**
* 个人中心
*/
class Center extends Base{
	
	function initialize(){
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
		$token=input('post.token');
		// 根据token获取用户信息
		$aid=$this->postToken($token);
		// 页面数据
		$list=$this->alist($aid);
		if($list){
			if($area){
				$msg=['status'=>0,'msg'=>'获取页面数据成功','list'=>$list,'aid'=>$aid];
			}else{
				$msg=['status'=>0,'msg'=>'获取页面数据失败'];
			}
		}else{
			$msg=['status'=>0,'msg'=>'获取页面数据失败'];
		}
		return $msg;

	}

	/**
	 * 设置运营商供应地区
	 * @return [type]
	 */
	public function setArea(){
		$county=input('post.');
		// 获取上传凭证
		$voucher=upload('voucher');
		// 获取运营商代理级别
		if(!$data['level']){
			$msg=['status'=>0,'msg'=>'请选择代理级别'];
		}
		// 获取运营商所选择的区域
		foreach ($data['area'] as $k => $v) {
			$area=['area'=>$v,'aid'=>$aid];
		}

		$res=Db::table($this->area)->where('area','in',$data['area'])->select();

		if(!empty($res)){

			$msg=['status'=>0,'msg'=>'有重复的地区，请重新选择'];

		}else{

			Db::startTrans();
			Db::table($this->area)->where('aid',$aid)->delete();
			$res=Db::table($this->area)->insert($area);

			if($res){
				//填写总金额，上传支付凭证
				$ress=$this->upLicense($voucher,$data['deposit']);

				if($ress==true){
					Db::commit();
					$msg=['status'=>1,'msg'=>'设置成功'];
				}else{
					Db::rollback();
					$msg=['status'=>0,'msg'=>'设置失败'];
				}

			}else{
				Db::rollback();
				$msg=['status'=>0,'msg'=>'设置失败'];
			}
		}
		
		return $msg; 
	}

	/**
     * 获取县级城市名称
     * @return  未选中的城市名称及已选中的城市名称
     */
    public function selCounty(){
        $city=input('post.id');
        $county=$this->commonCounty($city); //未被选中城市
        $selCounty=Db::table('ca_area')->select();//已被选中的城市
        if($county && $selectCounty){
            $msg=['status'=>1,'msg'=>'获取列表成功','county'=>$county,'selCounty'=>$selCounty];
        }else{
            $msg=['status'=>0,'msg'=>'获取列表失败'];
        }
        
    }
	
	/**
	 * 上传营业执照,总金额
	 * @return 布尔
	 */
	public function upLicense($deposit){
		// 填写可开通运营商数量
		// 押金总额
		$data=[$account,$deposit];
		$data['shop_nums']=$deposit/7000;
		if($data){
			$res=Db::table($this->agent_set)->insert($data);
			if($res){
				return true;
			}
		}
		
	}


	/**
	 * 上传营业执照
	 * @return 成功或失败
	 */
	public function license(){
		return $lincese=input('post.lincese');
		$aid=input('post.aid');
		$license=upload('license','agent');
		$res=Db::table($this->agent)->where('aid',$aid)->setField('license',$license);
		if($res){
			$msg=['status'=>1,'msg'=>'上传营业执照成功'];
		}else{
			$msg=['status'=>0,'msg'=>'上传营业执照失败'];
		}
		return $msg;
	}

	/**
	 * 查看运营商所供应地区
	 * @return 返回地区名称
	 */
	public function area(){
		$aid=input('get.aid');
		$area_id=Db::table($this->area)->where('aid',$aid)->select();
		// 查询所供应地区所有id
		$area_id=arrayStr($area_id,'area');
		$area=Db::table($this->china)->whereIn('id',$area_id)->select();
		//给城市分级
		$area=getChild($area,$area[0]['pid']);
		return $area;
	}

	/**
	 * 修改账户信息
	 * @return 修改成功或失败
	 */
	public function editAccount(){
		$data=input('post.');
		$res=Db::table($this->agent_set)->where('aid',$data['aid'])->update($data);
		if($res){
			$msg=['status'=>1,'msg'=>'修改账户成功'];
		}else{
			$msg=['status'=>0,'msg'=>'修改账户失败'];
		}
		return $msg;
	}

	/**
	 * 查询运营商详细信息
	 * @param  运营商id
	 * @return 运营商详细信息
	 */
	private function alist($aid){
		$list=Db::table('ca_agent a')
			->leftjoin('ca_agent_set as','a.aid=as.aid')	
			->where('a.aid',$aid)
			->field('login,compay,account,leader,province,city,county,address,phone,open_shop,voucher,license')
			->find();
		return $list;
	}
	
	
	/**
	 * 获取token并验证
	 * @param  token
	 * @return 返回运营商id
	 */
	private function postToken($token){
		if(!$token){
			$msg=['status'=>0,'msg'=>'token不能为空'];
		}
		$arr=$this->checkToken($token);
		return $arr['id'];
	}
}











 ?>