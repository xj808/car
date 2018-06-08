<?php
namespace app\admin\controller;
use app\base\controller\Admin;
use think\Db;
/**
* 小程序发票管理
*/
class SmallInvoice extends Admin
{
	
	public function index()
	{
		$page = input('post.page');
	}
}