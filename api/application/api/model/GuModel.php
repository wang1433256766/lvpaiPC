<?php

namespace app\api\model;

use think\Db;
use think\Log;
use app\api\controller\Common;

class GuModel
{
	public function getGu($index, $offset, $pagesize, $user_id,$where)
	{
		// $where['ban'] = 0;
		// 1是最新
		if (1 == $index)
		{
			$gu = Db::name('gugu_article a')->field('a.id as article_id, m.id as member_id, a.add_time, a.gugu_content, a.video_path, a.cover_img, a.address, a.like_num as favor_num, a.read_num, m.nickname, m.headimg as headimgurl, a.img_path, a.comment_num, a.duration')->
			join('mall_member m', 'm.id = a.member_id')->
			where($where)->
			order('add_time desc')->limit($offset, $pagesize)->select();
		}
		else if (2 == $index) // 2是最热
		{
			$gu = Db::name('gugu_article a')->field('a.id as article_id, m.id as member_id, a.add_time, a.gugu_content, a.video_path, a.cover_img, a.address, a.like_num as favor_num, a.read_num, m.nickname, m.headimg as headimgurl, a.img_path, a.comment_num, a.duration')->
			join('mall_member m', 'm.id = a.member_id')->
			order('like_num desc')->limit($offset, $pagesize)->select();
		}
		else if (3 == $index) // 3是关注
		{
			$gu = Db::name('gugu_article a')->field('a.id as article_id, m.id as member_id, a.add_time, a.gugu_content, a.video_path, a.cover_img, a.address, a.like_num as favor_num, a.read_num, m.nickname, m.headimg as headimgurl, a.img_path, a.comment_num, a.duration')->
			join('mall_member m', 'm.id = a.member_id')->
			order('add_time desc')->where('a.status', 1)->limit($offset, $pagesize)->select();
		}

		foreach ($gu as $k => $v)
		{
			// 将视频时长转化为秒数		
			if ($v['duration'])
			{
				$gu[$k]['duration'] = substr($v['duration'], 3, 5);
			}	

			// 处理时间
			$time = time() - strtotime($v['add_time']);
			$time += 126;
			// 判断时间是否超过1天
			if (86400 <= $time)
			{
				$gu[$k]['add_time'] = substr($v['add_time'], 5, 11);
			}
			else // 小于1天 
			{
				// 判断时间是否超过1小时
				if (3600 <= $time)
				{
					$hour = floor($time / 3600);
					$gu[$k]['add_time'] = $hour . '小时前';
				}
				else // 小于1小时
				{
					// 判断时间是否超过1分钟
					if (60 <= $time)
					{
						$minute = floor($time / 60);
						$gu[$k]['add_time'] = $minute . '分钟前';
					}
					else // 小于1分钟
					{	
						$gu[$k]['add_time'] = '刚刚';
					}
				}
			}

			// 处理图片
			$gu[$k]['img'] = explode(',', $v['img_path']);
			unset($gu[$k]['img_path']);

			// 处理点赞状态，判断是否有用户
			if (empty($user_id))
			{
				$gu[$k]['favor_status'] = 0;
			}
			else // 有用户
			{
				$cond = [
					'cur_user_id' => ['eq', $user_id],
					'type' => ['eq', 1],
					'post_id' => ['eq', $v['article_id']]
				];

				$status = Db::name('hd_favor')->where($cond)->value('status');
				$gu[$k]['favor_status'] = empty($status) ? 0 : $status;
			}
		}
		return $gu;
	}

	// 发表纯文字咕咕
	public function sendWordGu($param)
	{
		return Db::name('gugu_article')->insert($param);
	}

	// 发表图片+文字咕咕
	public function sendPicGu($param)
	{
		$img_arr = explode(',', $param['img']);

		$img_path = '';

		foreach ($img_arr as $k => $v)
		{
			if (empty($v))
			{
				continue;
			}
				$data = base64_decode($v);

				$filename_str = $this->getFilename();
				$filename = ROOT_PATH . 'public/uploads/gugu/' . $filename_str;
				file_put_contents($filename, $data);
				$filename_str = 'http://zhlsfnoc.com/uploads/gugu/' . $filename_str;
				if (0 == $k)
				{
					$img_path .= $filename_str;
				}
				else
				{
					$img_path .= ',' . $filename_str;
				}
		}

		$param['img_path'] = $img_path;
		unset($param['img']);

		return Db::name('gugu_article')->insert($param);
	}

	// 得到文件名
	public function getFilename()
	{
		// 先判断今天的目录是否存在
		$filename = ROOT_PATH . 'public/uploads/gugu/' . date('Ymd');

		$str = md5(mt_rand(0, 999999) . microtime(true) . uniqid());
		if (is_dir($filename))
		{
			return date('Ymd') . '/' . $str . '.jpg';
		}
		else
		{
			mkdir($filename);
			return date('Ymd') . '/' . $str . '.jpg';
		}
	}

	// 得到咕咕详情
	public function getGuDetail()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => ['gu' => '']
		];

		// 接收参数
		$param = request()->param();

		$id = isset($param['article_id']) ? $param['article_id'] : 0;
		$cur_user_id = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;
		$m_id_code = isset($param['m_id_code']) ? $param['m_id_code'] : 0;

		// 如果存在手机标识码
		if ($m_id_code)
		{
			$common = new Common();
			$common->read(3, $m_id_code, $id);
		}

		if (empty($id))
		{
			$res = [
				'code' => 1,
				'msg' => '咕咕id不存在',
				'body' => ['gu' => '']
			];
			return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		$gu = Db::name('gugu_article a')->field('a.id as article_id, a.img_path, a.gugu_content, a.add_time, a.video_path, a.like_num, a.comment_num, a.address, a.cover_img, a.member_id, a.duration, m.nickname, m.headimg')->
		join('mall_member m', 'm.id = a.member_id')->
		where('a.id', $id)->find();

		
		$gu['img_path'] = explode(',', $gu['img_path']);

		if ($gu['duration'])
		{
			$gu['duration'] = substr($gu['duration'], 3, 5);
		}
		$common = new Common();

		$gu['belong_me'] = $common->getBelongStatus($gu['member_id'], $cur_user_id);

		$common = new Common();
		$gu['follow_status'] = $common->getFollowStatus($cur_user_id, $gu['member_id']);

		$gu['favor_status'] = $common->getFavorStatus(1, $cur_user_id, $gu['article_id']);

		// 中间的景区信息
		$spotInfo = Db::name('shop_spot')
					->field('id as spot_id,title,desc,thumb,shop_price,market_price')
					->where('id','in', 1) // 1是死的
					->where('status',1)
					->select();
		$spotInfo = getTodayOrder($spotInfo);

		$res['body']['gu'] = $gu;
		$res['body']['spot_info'] = $spotInfo;
		return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	// 得到咕咕的评论
	public function getGuComment()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => ['comment' => '']
		];			

		// 接收参数
		$param = request()->param();
			
		$article_id = isset($param['article_id']) ? $param['article_id'] : 0;
		$cur_user_id = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;
		$com_page = isset($param['com_page']) ? $param['com_page'] : 1;

		// 定义评论条数
		define('COMPAGESIZE', 10);

		$offset = ($com_page - 1) * COMPAGESIZE;

		$comment = Db::name('gugu_comment c')->field('c.id as comment_id, c.info as content, c.add_time, c.favor_num, m.nickname, m.headimg')->
		join('mall_member m', 'm.id = c.member_id')->
		limit($offset, COMPAGESIZE)->order('add_time desc')->where('gugu_id', $article_id)->select();

		$common = new Common();
		foreach ($comment as $k => $v)
		{
			$comment[$k]['favor_status'] = $common->getFavorStatus(2, $cur_user_id, $v['comment_id']);
			$comment[$k]['add_time'] = handleTime($v['add_time']);

		}
		$res['body']['comment'] = $comment;
		if (COMPAGESIZE > count($comment))
		{
			$res['body']['noMoreComment'] = 1;
 		}
 		$res['body']['com_page'] = $com_page + 1;
		return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);			
	}
}
