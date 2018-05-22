<?php 
namespace app\agent\validate;

use think\Validate;

class Account extends Validate
{
    protected $rule = [
      'account|'  	=> 'require',
      'bank_name'	=>	'require',
      'branch' =>  'require',
      'phone'  =>'require', 
      'code'	=>	'require'
    ];
    protected $message=[
      'account.require'=>'银行卡号不能为空',
      'bank_name.require'=>'开户名不能为空',
      'branch.require'=>'开户行不能为空',
      'phone.require'=>'手机号不能为空',
    	'code.require'=>'验证码不能为空',
    	
    ];

}
