<?php 
namespace app\agent\validate;

use think\Validate;

class Login extends Validate
{
    protected $rule = [
      'login|登录账号'  	=> 'require',
      'pass|密码'	=>	'require',
      'code|验证码'	=>	'require|confirm:verify'
    ];

}





 ?>