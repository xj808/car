<?php 
/**
 * 验证找回密码
 */
namespace app\shop\validate;
use think\Validate;

class Forget extends Validate
{
	protected $rule = [
      'mobile|手机号'	=>	'require|mobile',
      'verify|手机验证码'	=>	'require|length:4',
      'passwd|密码' => 'require|min:6',
      'spasswd|确认密码' =>  'require|confirm:passwd'
    ];
}