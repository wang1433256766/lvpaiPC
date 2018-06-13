<?php

namespace app\api\controller;

use think\Db;

class Hd 
{
	public function index()
	{
		$row = Db::name('hd_news')->field('title, pic1')->find();

		echo json_encode($row);
		echo 10;
	}
}