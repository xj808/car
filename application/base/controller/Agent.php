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
        return upload('image','agent','http://192.168.1.110/car/public');
    }




	/**
	 * 上传支付凭证,总金额
	 * @return 布尔
	 */
	public function upLicense($area,$voucher,$deposit,$aid,$id = ''){
		// 填写可开通运营商数量
		// 押金总额
		$data=[
			'area'=>$area,
			'voucher'=>$voucher,
			'price'=>$deposit,
			'aid'=>$aid,
			'regions'=>$deposit/35000
		];
		if($data){
			if($id == ''){
				$res=Db::table('ca_increase')->insert($data);

				if($res){
					return true;
				}
			}else{
				$res=Db::table('ca_increase')->where(['id'=>$id,'aid'=>$aid])->update($data);

				if($res){
					return true;
				}
			}
				
			
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
			$res = Db::table('ca_ration')->where($where)->dec('open_stock',$v['def_num'])->update();
		}
		
		// 减少运营商可开通数量，增加已开通修车厂数量
		if($res !== false){
			$result = Db::table('ca_agent')
					->where('aid',$aid)
					->dec('shop_nums')->inc('open_shop')
					->update();
			if($result !== false){
				return true;
			}
		}

	}



	/**
     *@return  未被选中的城市 
     */
    public function commonCounty($city_id)
    {
        return Db::table('co_china_data')->where('pid',$city_id)->select();
    }


   

   



	
}