<?php

namespace app\api\model;

use think\Db;

class ParadiseModel
{
	// 得到文创产品订单信息
	public function getProductOrderMsg($order_sn)
	{
		$order = Db::name('paradise_order')
				 ->field('product_id, freight, pay_time')
				 ->where('order_sn', $order_sn)
				 ->find();

		$product = Db::name('paradise_product')
				   ->field('name, cash, score')
				   ->where('id', $order['product_id'])
				   ->find();
		$product['travel_date'] = '';
		$product['freight'] = $order['freight'];
		$product['ticket_num'] = '';
		$product['order_total'] = '';
		$product['rebate_total'] = '';

		$cash = $product['cash'];
		$freight = $product['freight'];
		$product['cash'] = "$cash";
		$product['freight'] = "$freight";

		return $product;
	}

	// 得到门票订单信息
	public function getTicketOrderMsg($order_sn)
	{
		$order = $bool = Db::name('spot_order')
				->where('order_sn', $order_sn)
				->field('ticket_name as name, price as cash, num as ticket_num, travel_date, order_total, rebate_total')
				->find();
		$order['score'] = '';
		$order['freight'] = '';	

		return $order; 
	}
}