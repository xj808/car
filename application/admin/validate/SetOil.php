<?php 
namespace app\admin\validate;

use think\Validate;

class SetOil extends Validate
{
    protected $rule = [
      'name|油品名称'	=>	'require',
      'cover|油品图片' => 'require',
      'price|油品价钱' => 'require',
      'about|油品简介' => 'require',
    ];
}