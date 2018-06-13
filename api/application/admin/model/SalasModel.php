<?php

namespace app\admin\model;

use think\Db;

// 旅行视频模型
class SalasModel
{
	//获取渠道
	public function getChannelByWhere($where, $offset, $limit)
	{
		$video = Db::name('mall_member')
				 ->field('id, name, sala_type, mobile, nickname, status,add_time,score')
				 ->limit($offset, $limit)
				 ->where($where)
				 ->select();
		return $video;
	}
	//获取直销
	public function getDirectByWhere($where, $offset, $limit)
	{
		$video = Db::name('mall_member')
				 ->field('id, name, sala_type, mobile, nickname, status,add_time,score')
				 ->limit($offset, $limit)
				 ->where($where)
				 ->select();
		return $video;
	}

	public function getMemberVideo($where)
	{
		return Db::name('mall_member')->where($where)->count();
	}	
}