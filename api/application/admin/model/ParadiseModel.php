<?php

namespace app\admin\model;

use think\Db;
use think\Log;

class ParadiseModel
{
	// 得到轮播列表
	public function getBannerByWhere($where, $offset, $limit)
	{
		// 加个status条件，状态
		$where['status'] = 1;
		$banner = Db::name('paradise_activity')
				  ->field('id, title, sort, add_time, end_time, cover_img')
				  ->order('sort')
				  ->limit($offset, $limit)
				  ->where($where)
				  ->select();

		foreach ($banner as $k => $v)
		{
			$banner[$k]['end_time'] = date('Y-m-d h:i:s', $v['end_time']);
			
			$sort = $v['sort'];
			$id = $v['id'];
			// 编辑轮播顺序
			$banner[$k]['sort'] = "<input type='text' size='3' value='$sort' onblur='changeSort($id, this.value)'";			
		}

		return $banner;
	}

	// 得到轮播个数
	public function getAllBanner($where)
	{
		$where['status'] = 1;
		return Db::name('paradise_activity')->where($where)->count();
	}

	// 得到订单列表
	public function getOrderByWhere($where, $offset, $limit)
	{
		// 先得到所有订单
		$order = Db::name('paradise_order')
				 ->field('id, order_sn, user_id, product_id, address_id, add_time, status')
				 ->order('add_time desc')
				 ->limit($offset, $limit)
				 ->where($where)
				 ->select();

		$arr = [];
		foreach ($order as $k => $v)
		{
			// 用户名
			$order[$k]['nickname'] = Db::name('mall_member')->where('id', $v['user_id'])->value('nickname');

			// 商品信息
			$product = Db::name('paradise_product')->field('name, cash, score')->where('id', $v['product_id'])->find();

			// 商品名称和支付
			$order[$k]['name'] = $product['name'];
			$order[$k]['pay'] = $product['cash'];


			// 收货地址信息
			$address_msg = Db::name('member_address')->field('phone, username')->where('id', $v['address_id'])->find();

			// 收货人电话
			$order[$k]['phone'] = $address_msg['phone'];

			// 收货人姓名
			$order[$k]['username'] = $address_msg['username'];
		}
		
		return $order;
	}

	// 得到订单个数
	public function getAllOrder($where)
	{
		return Db::name('paradise_order')->where($where)->count();
	}
}

