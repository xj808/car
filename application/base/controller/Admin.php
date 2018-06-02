<?php
namespace app\base\controller;
use app\base\controller\Base;
use think\Db;
/**
* 
*/
class Admin extends Base
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
     * 运营商列表  点击区域个数显示内容
     * @return [type] [description]
     */
    public function region($aid)
    {
        // 通过区县id获得市级id
        $county=Db::table('ca_area')->where('aid',$aid)->select('area');
        // 把区县id转换成字符串
        $county = array_str($county,'area');
        // 市级id转换为字符串
        $city=$this->areaList($county);
        // 省级id转换为字符串
        $province=$this->areaList($city);
        // 查询所有省市县的数据
        $list=Db::table('co_china_data')->whereIn('id',$province.','.$city.','.$county)->select();
        if($list){
            // 把数据换成树状结构
            return get_child($list,$list[0]['pid']);
            

        }else{  
            return '暂未设置地区';
        }

    }


    /**
     * 把运营商的市县id转换为字符串
     * @return 城市地区id
     */
    private function areaList($city)
    {
        $a = Db::table('co_china_data')->whereIn('id',$city)->select();
        return $a = array_str($a,'pid');
    }
}