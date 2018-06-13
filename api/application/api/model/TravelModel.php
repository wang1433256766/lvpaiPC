<?php

namespace app\api\model;

use think\Model;
use think\Db;

class TravelModel extends Model
{	
	protected $table = 'too_travels';

	// 得到最新的五个游记，也是轮播图
	public function getNewTravel()
	{
		$travel = Db::name('travels t')->field('t.id as travel_id, t.pic1, t.add_time, t.title, t.address, m.nickname, m.headimg')->
		join('admin_msg m', 'm.admin_id = t.user_id')->
		order('add_time desc')->limit(5)->select();

		foreach ($travel as $k => $v)
		{
			$time = substr($v['add_time'], 5, 5);
			$travel[$k]['add_time'] = str_replace('-', '|', $time);
		}

		return $travel;
	}

	public function getAllTravel($choose, $offset, $pagesize)
	{
		if ('hot' == $choose)
		{
			$travel = Db::name('travels t')->field('t.id as travel_id, t.pic1, t.title, t.address, t.favor_num, t.read_num, t.reply_num, m.nickname, m.headimg')->
			join('admin_msg m', 'm.admin_id = t.user_id')->
			order('t.read_num desc')->limit($offset, $pagesize)->select();
		}
		else
		{
			$travel = Db::name('travels t')->field('t.id as travel_id, t.pic1, t.title, t.address, t.favor_num, t.read_num, t.reply_num, m.nickname, m.headimg')->
			join('admin_msg m', 'm.admin_id = t.user_id')->
			order('t.add_time desc')->limit($offset, $pagesize)->select();
		}


		return $travel;
	}
}