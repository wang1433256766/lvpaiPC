<?php
namespace app\mobile\controller;
use think\Db;
use think\Controller;
use think\Request;
use think\Log;
use think\Session;
use think\Config;
use com\PHPQRCode;

class Index extends Base
{
	public function index()
	{
       $this->redirect('/mobile/show/show');
	}

}