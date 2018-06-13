<?php

namespace app\api\controller;

use think\Db;
use app\api\controller\Common;

// 旅行视频类
class Travelvideo 
{	
	// 得到精选视频
	public function getVideo()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => []
		];

		// 接收参数 
		$param = request()->param();

		$page = isset($param['page']) ? $param['page'] : 1;

		define('PAGESIZE', 10);

		$offset = ($page - 1) * PAGESIZE; 
 
		$video = Db::name('travel_video')
				 ->field('id as video_id, title, add_time, read_num, comment_num, cover_img, video_path')
				 ->order('add_time desc')
				 ->limit($offset, PAGESIZE)
				 ->select();
		foreach ($video as $k => $v)
		{
			$video[$k]['add_time'] = substr($v['add_time'], 5, 11);
		}

		if (count($video) < PAGESIZE)
		{
			$res['body']['noMoreData'] = 1;
		}
		$res['body']['page'] = $page + 1;
		$res['body']['video_list'] = $video;

		return json_encode($res, JSON_UNESCAPED_UNICODE);
	}	

	// 精选视频详情
	public function getVideoDetail()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => []
		];

		// 接受参数
		$param = request()->param();

		$video_id = isset($param['video_id']) ? $param['video_id'] : 0;
		$m_id_code = isset($param['m_id_code']) ? $param['m_id_code'] : 0;
		$cur_user_id = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;

		if (0 == $video_id)
		{
			$res['msg'] = '视频id不存在';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}

		// 调用阅读函数
		$common = new Common();
		$common->read(5, $m_id_code, $video_id);

		$video = Db::name('travel_video')
				 ->field('id as video_id, title, add_time, read_num, comment_num, cover_img, video_path')
				 ->find();
		$video['add_time'] = substr($video['add_time'], 5, 5);

		// 得到收藏状态
		$video['collect_status'] = $common->getCollectStatus(7, $video_id, $cur_user_id);

		$other_video = $this->getOtherVideo($video_id);

		$res['body']['video'] = $video;
		$res['body']['other_video'] = $other_video;

		return json_encode($res, JSON_UNESCAPED_UNICODE);	
	}


	// 得到相关推荐
	public function getOtherVideo($video_id)
	{
		// 得到id比当前视频大的视频
		$where['id'] = ['gt', $video_id];

		$other_video = Db::name('travel_video')
					   ->field('id as video_id, title, cover_img')
					   ->where($where)
					   ->limit(3)
					   ->select();
		$video_num = count($other_video); // 得到数组长度

		// 因为是其他视频，所以要加个条件
		$cond['id'] = ['neq', $video_id];

		if (3 > $video_num)
		{
			if (0 == $video_num)
			{
				$other_video2 = Db::name('travel_video')
				->field('id as video_id, title, cover_img')
				->limit(3)	  
				->where($cond)
				->select();   	   
			}
			else if (1 == $video_num)
			{
				$other_video2 = Db::name('travel_video')
				->field('id as video_id, title, cover_img')
				->limit(2)	
				->where($cond)
				->select(); 
			}
			else
			{
				$other_video2 = Db::name('travel_video')
				->field('id as video_id, title, cover_img')
				->limit(1)
				->where($cond)
				->select(); 
			}
		}

		foreach ($other_video2 as $k => $v)
		{
			$other_video[] = $v;
		}	

		return $other_video;
	}

	// 对旅行视频发表评论
	public function sendComment()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => []
		];

		// 接收参数 
		$param = request()->param();

		$cur_user_id = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;
		$video_id = isset($param['video_id']) ? $param['video_id'] : 0;
		$content = isset($param['content']) ? $param['content'] : '';

		if (0 == $cur_user_id)
		{
			$res['msg'] = '当前用户id不存在';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}

		if (0 == $video_id)
		{
			$res['msg'] = '视频id不存在';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}	

		if ('' === $content)
		{
			$res['msg'] = '内容为空';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}

		unset($param['cur_user_id']);
		$param['user_id'] = $cur_user_id;
		$ins_res = Db::name('travel_video_comment')
				   ->insert($param);

		if ($ins_res)
		{
			// 旅行视频评论数量+1
			Db::name('travel_video')->where('id', $video_id)->setInc('comment_num');
			$res['msg'] = '发表成功';
			$res['body']['sendStatus'] = 1;
		}

		return json_encode($res, JSON_UNESCAPED_UNICODE);
	}

	// 得到旅行视频的评论列表
	public function getComment()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => []
		];

		// 接收参数
		$param = request()->param();

		$cur_user_id = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;
		$video_id = isset($param['video_id']) ? $param['video_id'] : 0;

		$com_page = isset($param['com_page']) ? $param['com_page'] : 1;

		define('COMPAGESIZE', 10);
		$offset = ($com_page - 1);

		if (0 == $video_id)
		{
			$res['msg'] = '视频id不存在';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}

		$comment = Db::name('travel_video_comment c')
				   ->field('c.id as comment_id, c.content, c.add_time, c.favor_num, m.nickname, m.headimg')
				   ->join('mall_member m', 'm.id = c.user_id')
				   ->order('add_time desc')
				   ->limit($offset, COMPAGESIZE)
				   ->where('video_id', $video_id)
				   ->select();

		$common = new Common();
		// // 处理时间和点赞状态
		foreach ($comment as $k => $v)
		{
			$comment[$k]['add_time'] = handleTime($v['add_time']);
			$comment[$k]['favor_status'] = $common->getFavorStatus(10, $cur_user_id, $v['comment_id']);
		}
		$res['body']['comment'] = $comment;
		if (COMPAGESIZE > count($comment))
		{
			$res['body']['noMoreComment'] = 1;
		}
		$res['body']['com_page'] = $com_page + 1;
		return json_encode($res, JSON_UNESCAPED_UNICODE);
	}

	// 咕咕视频列表 
	public function getGuVideo()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => []
		];

		// 接收参数
		$param = request()->param();

		$cur_user_id = isset($param['cur_user_id']) ? $param['cur_user_id']: 0;
		$page = isset($param['page']) ? $param['page'] : 1;

		define('PAGESIZE', 10);
		$offset = ($page - 1) * PAGESIZE;

		$where['video_path'] = ['neq', ''];
		$gu = Db::name('gugu_article g')
			  ->field('g.id as article_id, g.gugu_content, g.video_path, g.cover_img, g.address, g.like_num as favor_num, g.comment_num, g.add_time, m.nickname, m.headimg, m.id as user_id')
			  ->join('mall_member m', 'm.id = g.member_id')
			  ->limit($offset, PAGESIZE)
			  ->order('add_time desc')
			  ->where($where)
			  ->select();

		$common = new Common();
		// 处理点赞状态和判断该咕咕视频是否属于我，处理发表时间
		foreach ($gu as $k => $v)
		{
			$gu[$k]['belong_me'] = $common->getBelongStatus($v['user_id'], $cur_user_id);
			$gu[$k]['favor_status'] = $common->getFavorStatus(1, $cur_user_id, $v['article_id']);
			$gu[$k]['add_time'] = handleTime($v['add_time']);
			
		}

		$res['body']['gu_list'] = $gu;

		if (PAGESIZE > count($gu))
		{
			$res['body']['noMoreData'] = 1;
		}
		$res['body']['page'] = $page + 1;

		return json_encode($res, JSON_UNESCAPED_UNICODE);
	}
}