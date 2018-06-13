<?php

namespace app\api\model;

use think\Db;
use app\api\controller\Common;

// 专栏新闻模型
class TopicNewsModel
{
	// 得到各种类型
	public function getNews($type, $offset, $pagesize)
	{	
		// 惠说新闻
		if ('say' == $type)
		{
			$news = Db::name('hd_article_topic')->field('id as news_id, title, thumb as pic, read_num, pl_num, add_time')->where('topic_id', 1)->limit($offset, $pagesize)->select();
		}
		else if ('audio' == $type) // 有声旅行
		{
			$news = Db::name('hd_article_topic')->field('id as news_id, title, thumb as pic, read_num, pl_num, add_time')->where('topic_id', 2)->limit($offset, $pagesize)->select();
		}
		else if ('see' == $type) // 不惠你看
		{
			$news = Db::name('hd_article_topic')->field('id as news_id, title, thumb as pic, read_num, pl_num, add_time')->where('topic_id', 4)->limit($offset, $pagesize)->select();
		}
		else // 惠声惠影
		{
			$news = Db::name('hd_topic_video')->field('id as news_id, video_name as title, video_path, video_info as content, add_time, img_path as cover_img')->limit($offset, $pagesize)->select();
		}

		// 处理一下时间
		foreach ($news as $k => $v)
		{
			$time = time() - $v['add_time'];
			$time += 126;

			// 判断发表时间是否超过1天
			if (86400 <= $time)
			{

			}
			else // 小于一天
			{
				if (3600 <= $time)
				{
					$hour = floor($time / 3600);
					$news[$k]['add_time'] = $hour . '小时前';
				}
				else
				{
					if (60 <= $time)
					{
						$minute = floor($time / 60);
						$news[$k]['add_time'] = $minute . '分钟前';
					}
					else
					{
						$news[$k]['add_time'] = '刚刚';
					}
				}
			}
		}

		return $news;
	}

	// 得到相关视频
	public function getRelatedNews($news_id)
	{
		$where['id'] = ['gt', $news_id];
		
		$news = Db::name('hd_topic_video')->field('id as news_id, video_path, img_path, duration, video_name as title')->where($where)->limit(3)->select();

		$news_num = count($news);
		// 判断新闻个数是否有3个
		if (3 > $news_num)
		{
			if (0 == $news_num)
			{
				$other_news = Db::name('hd_topic_video')->field('id as news_id, video_path, img_path, duration, video_name as title')->
				order('id')->limit(3)->select();
			}
			else if (1 == $news_num)
			{
				$other_news = Db::name('hd_topic_video')->field('id as news_id, video_path, img_path, duration, video_name as title')->
				order('id')->limit(2)->select();
			}
			else
			{
				$other_news = Db::name('hd_topic_video')->field('id as news_id, video_path, img_path, duration, video_name as title')->
				order('id')->limit(1)->select();
			}
		}

		foreach ($other_news as $v)
		{
			$news[] = $v;
		}

		foreach ($news as &$v)
		{
			$v['duration'] = transferSecond($v['duration']);
		}
		return $news;
	}

	// 对新闻视频评论
	public function sendVideoComment()
	{
		$res = [
 			'code' => 1,
 			'msg' => '操作成功',
 			'body' => ['sendStatus' => 0]
 		];

		// 接收参数
 		$param = request()->param();
		$cur_user_id = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;
		$news_id = isset($param['news_id']) ? $param['news_id'] : 0;
		$content = isset($param['content']) ? $param['content'] : '';

		if (empty($cur_user_id))
		{
			$res = [
				'code' => 1,
				'msg' => '当前用户id不存在',
				'body' => ['sendStatus' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		if (empty($news_id))
		{
			$res = [
				'code' => 1,
				'msg' => '新闻id不存在',
				'body' => ['sendStatus' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		if ('' === $content)
		{
			$res = [
				'code' => 1,
				'msg' => '评论内容为空',
				'body' => ['sendStatus' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		$arr['user_id'] = $cur_user_id;
		$arr['content'] = $content;
		$arr['video_id'] = $news_id;
		$arr['add_time'] = time();
		$ins_res = Db::name('hd_video_comment')->insert($arr, false, true);

		if ($ins_res)
		{
			$inc_res = Db::name('hd_topic_video')->where('id', $news_id)->setInc('pl_num');
		}
		else
		{
			$res = [
				'code' => 1,
				'msg' => '发送评论失败',
				'body' => ['sendStatus' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		if ($inc_res)
		{
			$res = [
				'code' => 1,
				'msg' => '发送成功',
				'body' => ['sendStatus' => 1,
							'comment_id' => $ins_res]
			];

			return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}
		else
		{
			$res = [
				'code' => 1,
				'msg' => '发送评论成功，但评论数量+1失败',
				'body' => ['sendStatus' => 1]
			];

			return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}
	}

	// 得到视频评论
	public function getVideoComment()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => ['comment' => '']
		];

		// 接收参数
		$param = request()->param();

		$news_id = isset($param['news_id']) ? $param['news_id'] : 0;
		$cur_user_id = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;
		$com_page = isset($param['com_page']) ? $param['com_page'] : 1;

		if (empty($news_id))
		{
			$res = [
				'code' => 1,
				'msg' => '新闻id不存在',
				'body' => ['comment' => '']
			];
			return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		// 定义评论条数
		define('PAGESIZE', 10);

		$offset = ($com_page - 1) * PAGESIZE;

		$comment = Db::name('hd_video_comment c')->field('c.id as comment_id, c.content, c.add_time, c.favor_num, m.nickname, m.headimg')->
		join('mall_member m', 'm.id = c.user_id')->
		order('add_time desc')->limit($offset, PAGESIZE)->select();

		$common = new Common();
		foreach ($comment as $k => $v)
		{
			$comment[$k]['favor_status'] = $common->getFavorStatus(7, $cur_user_id, $v['comment_id']);
			$date = date('Y-m-d h:i:s', $v['add_time']);
			$comment[$k]['add_time'] = handleTime($date);
		}
		
		$comment_num = count($comment);
		$res['body']['comment'] = $comment;
		$res['body']['comment_num'] = $comment_num;
		if ($comment_num < PAGESIZE)
		{
			$res['body']['noMoreComment'] = 1;
		}
		$res['body']['com_page'] = $com_page + 1;

		return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
}