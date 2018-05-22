<?php 
namespace app\agent\validate;

use think\Validate;

class Account extends Validate
{
    protected $rule = [
      'account|银行卡号'  	=> 'require',
      'bank_name|开户名'	=>	'require',
      'branch|开户行' =>  'require',
      'phone|手机号'  =>'require', 
      'code|验证码'	=>	'require'
    ];

}
