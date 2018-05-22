<?php 
namespace app\agent\validate;

use think\Validate;

class Forget extends Validate
{
    protected $rule = [
      'phone'  	=> 'require',
      'code'	=>	'require',
      'pass'	=>	'require',
      'qpass' =>  'require|confirm:pass',
    ];
    protected $message=[
      'phone.require'=>'手机号不能为空',
      'code.require'=>'验证码不能为空',
      'pass.require'=>'密码不能为空',
      'qpass.require'=>'确认密码不能为空',
    	'qpass.confirm'=>'两次密码不一致',
    	
    ];

}