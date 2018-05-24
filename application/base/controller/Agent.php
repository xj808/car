<?php
namespace app\base\controller;
use app\base\controller\Base;
use think\Db;
/**
* 运营商公共
*/
class Agent extends Base
{	

	/**
     * 初始化
     * @return [type] [description]
     */
    function initialize()
    {   
    	parent::initialize();
        $this->aid=$this->ifToken();
    }

    /**
     * 上传图片的方法
     * @return [type] [description]
     */
    public function images()
    {           
        return upload('image','agent');
    }

	/**
	 * 上传支付凭证,总金额
	 * @return 布尔
	 */
	public function upLicense($voucher,$deposit,$aid){
		// 填写可开通运营商数量
		// 押金总额
		$data=[
			'voucher'=>$voucher,
			'price'=>$deposit,
			'aid'=>$aid,
		];
		if($data){
			$res=Db::table('ca_increase')->insert($data);
			if($res){
				return true;
			}
		}
		
	}

	/**
	 * 修改状态
	 * @param  [type] $table   要修改的表
	 * @param  [type] $sid     要修改的id
	 * @param  [type] $status  要修改的状态值
	 * @return [type]         [description]
	 */
	public function status($table,$id,$status)
	{
		$res=Db::table($table)->where('id',$id)->setField('audit_status',$status);
		if($res!==false){
			return true;
		}
	}


	/**
	 * 运营商授信库减少
	 * @param  [type] $aid 运营商id
	 * @return 布尔值
	 */
	public function credit($aid)
	{
		// 一组油的数量（L）
		$arr=$this->bangCate();
		foreach ($arr as $k => $v) {
			$where=[['aid','=',$aid],['materiel','=',$v['id']]];
			Db::table('ca_ration')->where($where)->dec('open_stock',$v['def_num'])->update();
		}

	}

	/**
	 * 获取每组油的升数
	 * @return 数组
	 */
	public function bangCate()
	{	$where=[['pid','>','0'],['def_num','>',0]];
		return Db::table('co_bang_cate')->where($where)->field('id,def_num')->select();
	}


	/**
	 *减少运营商可开通数量，增加已开通修车厂数量
	 * @param  [type] $aid 运营商id
	 * @return [type]      [description]
	 */
	public function open($aid)
	{
		Db::table('ca_agent_set')
			->where('aid',$aid)
			->dec('shop_nums')
			->inc('open_shop')
			->update();
		
	}


	/**
     *@return  未被选中的城市 
     */
    public function commonCounty($city_id)
    {
        return Db::table('co_china_data')->where('pid',$city_id)->select();
    }


   

   



	
}