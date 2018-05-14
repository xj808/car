<?php 
namespace app\agent\validate;

use think\Validate;

class Login extends Validate
{
    protected $rule = [
      'login'  	=> 'require',
      'pass'	=>	'require',
      'code'	=>	'require|confirm:verify'
    ];
    protected $message=[
      'login.require'=>'登录账号不能为空',
      'pass.require'=>'密码不能为空',
      'code.require'=>'验证码不能为空',
    	'code.confirm'=>'验证码错误',
    	
    ];

}





 ?>