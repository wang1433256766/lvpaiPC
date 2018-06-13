<?php
namespace app\index\controller;

use think\Db;
use think\Controller;
use think\Request;
use think\Session;

class Member extends Controller{
	public function index(){
		return $this->fetch();	
	}

	public function member_bus(){
		session::set('member_id',1);
		return $this->fetch();	
	}

	public function member_comment(){
		if(session::has('member_id')){
			$member_id = session::get('member_id');
			$member = Db::name('mall_member')->where('id',$member_id)->find();
			$info = Db::name('shop_spot_comment')->where('member_id',$member_id)->select();
			foreach ($info as $k => $v) {
				$info[$k]['title'] = Db::name('shop_spot_ticket')->where('id',$v['ticket_id'])->value('title');
				$info[$k]['price'] = Db::name('shop_spot_ticket')->where('id',$v['ticket_id'])->value('shop_price');
			}
		}
		$this->assign('info',$info);
		$this->assign('member',$member);
		return $this->fetch();	
	}

	public function member_client(){
		if(session::has('member_id')){
			$member_id = session::get('member_id');
			$order_promote = Db::name('spot_order')->where('promote_id',$member_id)->field('sum(order_total) as total_amount,member_id')->group('member_id')->select();
			foreach ($order_promote as $k => $v) {
				$order_promote[$k]['nickname'] = Db::name('mall_member')->where('id',$v['member_id'])->value('nickname');
				$order_promote[$k]['mobile'] = Db::name('mall_member')->where('id',$v['member_id'])->value('mobile');
			}
			//echo Db::name('spot_order')->getLastSql();
			//dump($order_promote);
		}
		$this->assign('order_promote',$order_promote);
		return $this->fetch();	
	}

	public function member_details(){
		if(session::has('member_id')){
			$member_id = session::get('member_id');
			$today_sum = Db::name('member_promote')->where('member_id',$member_id)->whereTime('add_time','today')->count();
			$info['today_sum'] = $today_sum;

			$today_price = Db::name('member_promote')->where('member_id',$member_id)->whereTime('add_time','today')->field('sum(total) as price')->find();
			$info['today_price'] = $today_price;

			$promote_sum = Db::name('member_promote')->where('member_id',$member_id)->count();
			$info['promote_sum'] = $promote_sum;

			$promote_price = Db::name('member_promote')->where('member_id',$member_id)->field('sum(total) as price')->find();
			$info['promote_price'] = $promote_price;

			$order_promote_time = Db::name('member_promote')->where('member_id',$member_id)->field("FROM_UNIXTIME(add_time,'%Y-%m-%d') as days")->order('add_time desc')->group('days')->select();
			$info['order_promote_time'] = $order_promote_time;

			$order_promote = Db::name('member_promote')->where('member_id',$member_id)->order('add_time')->select();
			foreach ($order_promote as $k => $v) {
				$order_promote[$k]['title'] = Db::name('shop_spot_ticket')->where('id',$v['ticket_id'])->value('title');
				$order_promote[$k]['num'] = Db::name('spot_order')->where('id',$v['order_id'])->value('num');
			}
			$info['order_promote'] = $order_promote;
			//dump($order_promote);
			//echo Db::name('member_promote')->getLastSql();
		}
		$this->assign('info',$info);
		return $this->fetch();	
	}
}