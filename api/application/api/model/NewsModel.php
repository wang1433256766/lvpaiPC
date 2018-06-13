<?php

namespace app\api\model;

use think\Db;

class NewsModel 
{
	// 首页搜索得到的结果
	public function getSearchRes($content)
	{
		define('PAGESIZE', 10);
		$param = request()->param();
		$page = isset($param['page']) ? $param['page'] : 1;

		$offset = ($page - 1) * PAGESIZE; 

		//景点	
		$spotInfo = Db::name('shop_spot')
					->field('id as spot_id,title,desc,thumb,shop_price,market_price')
					->where('city','like', "%$content%")
					->whereOr('province','like', "%$content%")
					->whereOr('title', 'like', "%$content%")
					->limit($offset, PAGESIZE)
					->select();
		$spotInfo = getTodayOrder($spotInfo);
		foreach ($spotInfo as $k => $v)
		{
			$spotInfo[$k]['today'] = isset($v['today']) ? $v['today'] : 0;
		}
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => ['result' => []]
		];
		if ($spotInfo)
		{
			$res['body']['result'] = $spotInfo;
		}

		if (count($spotInfo) < PAGESIZE)
		{
			$res['body']['noMoreData'] = 1;
		}
		$res['body']['page'] = $page + 1;


		return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
}