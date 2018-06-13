<?php

namespace app\admin\model;

use think\Db;

// 文创产品模型
class ProductModel
{
	public function getProductByWhere($where, $offset, $limit)
	{
		$product = Db::name('paradise_product')
				   ->field('id, name, score, cash, stock, end_time')
				   ->limit($offset, $limit)
				   ->where($where)
				   ->select();

		// 处理产品兑换结束时间
		foreach ($product as $k => $v)
		{
			$product[$k]['end_time'] = date('Y-m-d');
		}

		return $product;
	}

	public function getAllProduct($where)
	{	
		return Db::name('paradise_product')->where($where)->count();
	}
}	