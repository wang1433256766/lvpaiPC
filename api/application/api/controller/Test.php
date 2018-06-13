<?php

namespace app\api\controller;

use think\Controller;
use think\Db;
use think\Log;
use think\Cache;

class Test extends Controller
{	
	public function index()
	{
		set_time_limit(0);	
		$content = input('content');
		
		// 如果搜索内容相同，那么将直接返回搜索结果
		if ($content == Cache::get('search_content'))
		{
			$spotInfo = Cache::get('spot_info');
		}	
		else // 搜索内容不同，重新搜索
		{
			$cond = "province Like '%$content%' or city Like '%$content%' or title Like '%$content%'";
			$spotInfo = Db::name('shop_spot')
						->field('id as spot_id,title,desc,thumb,shop_price,market_price, city, province')
						->where($cond) // 模糊查询3个字段
						->select();
			$spotInfo = getTodayOrder($spotInfo);
			Cache::set('spot_info', $spotInfo, 30);
			Cache::set('search_content', $content, 30);
		}
		
		dump($spotInfo);
	}

	public function test1()
	{	
		$arr = [1, 2, 3, 4, 5];
		Cache::set('name', $arr, 5);
	}

	public function test2()
	{
		$res = Cache::get('name');
		// $res = $_SESSION;
		dump($res);
	}

	public function returnEmptyData()
	{
	}
}

class A
{

}