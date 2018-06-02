<?php 
namespace app\admin\validate;

use think\Validate;

class SetAgent extends Validate
{
    protected $rule = [
      'profit|售卡利润'	=>	'require',
      'delay_fine|送货延迟罚款' => 'require',
      'shop_hours|汽修厂工时费' => 'require',
      'shop_fund|汽修厂成长基金' => 'require',
      'shop_praise|汽修厂好评奖励' => 'require',
    ];
}