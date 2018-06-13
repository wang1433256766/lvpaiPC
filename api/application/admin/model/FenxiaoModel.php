<?php

namespace app\admin\model;

use think\Db;

// 旅行视频模型
class FenxiaoModel
{
	public function getMemberByWhere($where, $offset, $limit)
	{
		$video = Db::name('mall_member')
				 ->field('id, name, type, mobile, nickname, status,add_time,score')
				 ->limit($offset, $limit)
				 ->where($where)
				 //->whereOr("type",1)
				 ->select();
		return $video;
	}
	//二级分销
	public function getSecondByWhere($where, $offset, $limit)
	{
		$video = Db::name('mall_member as mall')
				 ->field('mall.id, mall.name, mall.type, mall.mobile, mall.nickname, mall.status,mall.add_time,mall.score,too_mall_member.name as super_name')
				  ->join("too_mall_member","too_mall_member.id = mall.parent_id")
				 ->limit($offset, $limit)
				 ->where($where)
				 ->where("mall.type",2)
				 ->select();
		return $video;
	}

	public function getMemberVideo($where)
	{
		return Db::name('mall_member')->where($where)->count();
	}	
}