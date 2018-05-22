<?php 
namespace app\agent\validate;

use think\Validate;

class Reg extends Validate
{
    protected $rule = [
      'login'  	=> 'require|unique:ca_agent',
      'pass'   	=> 'require',
      'qpass'  	=> 'require|confirm:pass',
      'compay' 	=> 'require',
      'province'=>'require',
      'city'	  =>'require',
      'county'	=>'require',
      'address'	=>'require',
      'leader'	=>'require',
      'bank'	  =>'require',
      'branch'	=>'require',
      'bank_name'=>'require',
      'account'	=>'require',
      'phone'	  =>'require',
      'code'    =>'require',
      'usecost' =>'require',
    ];
    protected $message=[
    	'login.require'		=>'登录账号不能为空',
    	'pass.require'		=>'密码不能为空',
    	'qpass.require'		=>'确认密码不能为空',
    	'qpass.confirm'		=>'两次密码不一致',
    	'compay.require'	=>'公司名称不能为空',
    	'province.require'	=>'省份不能为空',
    	'city.require'		=>'市不能为空',
    	'county.require'	=>'区县不能为空',
    	'address.require'	=>'详细地址不能为空',
    	'leader.require'	=>'负责人不能为空',
    	'bank.require'		=>'开户行不能为空',
    	'branch.require'	=>'开户分行不能为空',
    	'bank_name.require'	=>'开户名不能为空',
    	'account.require'	=>'提款账号不能为空',
    	'phone.require'		=>'手机号不能为空',
      'code.require'   =>'验证码不能为空',
      'usecost.require'=>'支付凭证不能为空',
    ];
}