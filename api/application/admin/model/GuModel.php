<?php

namespace app\admin\model;

use think\Db;
use think\Model;

class GuModel extends Model
{
	protected $table = 'too_gugu_article';

	public function getGuByWhere($where, $offset, $limit, $btn)
	{
		if ('new' == $btn)
		{
			$gu = Db::name('gugu_article a')->field('a.*, m.nickname')->
			join('mall_member m', 'm.id = a.member_id')->
			order('add_time desc')->limit($offset, $limit)->where($where)->select();
		}
		else if ('hot' == $btn)
		{
			$gu = Db::name('gugu_article a')->field('a.*, m.nickname')->
			join('mall_member m', 'm.id = a.member_id')->
			order('read_num desc')->limit($offset, $limit)->where($where)->select();
		}
		else if ('good' == $btn)
		{
			$gu = Db::name('gugu_article a')->field('a.*, m.nickname')->
			join('mall_member m', 'm.id = a.member_id')->
			order('add_time desc')->limit($offset, $limit)->where(['a.status' => 1])->select();
		}

		foreach ($gu as $k => $v)
		{
			$id = $v['id'];
			$read_num = $v['read_num'];
			$favor_num = $v['like_num'];

			$gu[$k]['read_num'] = "<input type='text' value='$read_num' size='3' onblur='changeReadNum($id, this.value)'>";

			$gu[$k]['like_num'] = "<input type='text' value='$favor_num' size='3' onblur='changeFavorNum($id, this.value)'>";

			// 判断咕咕标题内容长度是否超标
			if (22 < mb_strlen($v['gugu_content']))
			{
				$gu[$k]['gugu_content'] = mb_substr($v['gugu_content'], 0, 22);
			}
		}

		return $gu;
	}

	public function getAllGu($where, $btn)
	{
		$count = Db::name('gugu_article')->where($where)->count();
		if ('good' == $btn)
		{
			$count = Db::name('gugu_article')->where(['status' => 1])->count();
		}
		return $count;
	}

	// 得到封禁的咕咕数量
	public function getBanNum()
	{
		return $this->where('ban', 1)->count();
	}

	// 得到封禁的咕列表
	public function getBanList($where, $offset, $limit)
	{
		$where['ban'] = 1;
		$gu = Db::name('gugu_article a')->field('a.*, m.nickname')->
		join('mall_member m', 'a.member_id = m.id')->
		order('ban_time desc')->where($where)->limit($offset, $limit)->select();

		return $gu;
	}
}