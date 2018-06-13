<?php

namespace app\api\controller;

use think\Db;
use think\Controller;
use app\api\controller\News;

// Common控制器，放一些公用的接口
class Common extends Controller
{
	// 关注
	public function follow()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功', 
			'body' => ['followStatus' => 0]
		];

		// 接收参数
		$param = request()->param();
		
		$param['user_id'] = isset($param['user_id']) ? $param['user_id'] : 0;
		$param['cur_user_id'] = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;

		// 判断被关注人id是否存在
		if (empty($param['user_id']))
		{
			$res = [
				'code' => 1,
				'msg' => '被关注人id不存在',
				'body' => ['followStatus' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		// 判断关注人id是否存在
		if (empty($param['cur_user_id']))
		{
			$res = [
				'code' => 1,
				'msg' => '关注人id不存在',
				'body' => ['followStatus' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		// 先判断该记录是否存在
		$cond = [
			'user_id' => ['eq', $param['user_id']],
			'fans_id' => ['eq', $param['cur_user_id']],
 		]; 
 		$fansArr = Db::name('hd_fans')->field('id, status')->where($cond)->find();
 		if (is_array($fansArr)) 
 		{
 			// 判断关注状态
 			if (0 == $fansArr['status'])
 			{	
 				$upd_res = Db::name('hd_fans')->where('id' ,$fansArr['id'])->update(['status' => 1]);
 				// 判断是否插入成功
 				if ($upd_res)
 				{
 					$res = [
 						'code' => 1,
 						'msg' => '操作成功',
 						'body' => ['followStatus' => 1]
 					];
 				}
 				else // 插入失败
 				{

 				}
 			}
 			else // 已关注
 			{ 

 			}
 		}
 		else // 记录不存在，重新往表中插入一条记录
 		{
 			$arr['user_id'] = $param['user_id'];
 			$arr['fans_id'] = $param['cur_user_id'];
 			$arr['status'] = 1;
 			$ins_res = Db::name('hd_fans')->insert($arr);

			// 插入记录成功 				
			if ($ins_res)
			{
				$res = [
					'code' => 1,
					'msg' => '操作成功',
					'body' => ['followStatus' => 1]
				];
			} 		
			else // 插入记录失败
			{

			}

 		}
 		return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	// 取消关注
	public function cancelFollow()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => ['cancelStatus' => 0]
		];

		// 接收参数
		$param = request()->param();

		$param['user_id'] = isset($param['user_id']) ? $param['user_id'] : 0;
		$param['cur_user_id'] = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;

		// 判断被关注人id是否存在
		if (empty($param['user_id']))		
		{
			$res = [
				'code' => 1,
				'msg' => '被关注人id不存在',
				'body' => ['cancelStatus' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		// 判断关注人id是否存在
		if (empty($param['cur_user_id']))		
		{
			$res = [
				'code' => 1,
				'msg' => '关注人id不存在',
				'body' => ['cancelStatus' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		$cond = [
			'user_id' => ['eq', $param['user_id']],
			'fans_id' => ['eq', $param['cur_user_id']]
		];

		$fansArr = Db::name('hd_fans')->field('id, status')->where($cond)->find();

		// 判断是否有记录
		if (is_array($fansArr))
		{
			// 判断关注状态
			if (1 == $fansArr['status'])
			{
				$upd_res = Db::name('hd_fans')->where('id', $fansArr['id'])->update(['status' => 0]);
				// 判断取消关注是否成功
				if ($upd_res)
				{
					$res = [
						'code' => 1,
						'msg' => '操作成功',
						'body' => ['cancelStatus' => 1]
					];
				}
				else // 取消关注失败
				{

				}
			}
			else // 是取消关注的状态，所以无需改变
			{

			}
		}	
		else // 没有记录，就不存在取消关注
		{

		}
		return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	// 点赞
	public function favor()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => ['favorStatus' => 0]
		];

		// 接收参数，先判断是否已经点过赞
		$param = request()->param();

		$param['type'] = isset($param['type']) ? $param['type'] : '';
		$param['post_id'] = isset($param['post_id']) ? $param['post_id'] : 0;
		$param['user_id'] = isset($param['user_id']) ? $param['user_id'] : 0;
		$param['cur_user_id'] = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;

		// 判断点赞类型是否存在
		if ('' === $param['type'])
		{
			$res = [
				'code' => 1,
				'msg' => '点赞类型不存在',
				'body' => ['favorStatus' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		// 判断点赞的那个东西的id是否存在，如新闻评论id、咕咕文章id
		if (empty($param['post_id']))
		{
			$res = [
				'code' => 1,
				'msg' => '点赞的这个东西的id不存在',
				'body' => ['favorStatus' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		// 判断点赞人id是否存在 
		if (empty($param['cur_user_id']))
		{
			$res = [
				'code' => 1,
				'msg' => '点赞人id不存在',
				'body' => ['favorStatus' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}


		$cond = [
			'type' => ['eq', $param['type']],
			'post_id' => ['eq', $param['post_id']],
			'cur_user_id' => ['eq', $param['cur_user_id']],
		];

		// 从点赞表中拿到一个数组，如果拿到，意味着已经生成一条记录
		// 生成一条记录的意思是已点过赞，或者点赞后取消点赞
		$favor = Db::name('hd_favor')->field('id, status')->where($cond)->find();

		// 判断该记录是否存在
		if (is_array($favor))
		{
			if (empty($favor['status'])) // $status为0，代表未点赞
			{
				// dump($favor);
				// exit();
				// 点赞成功，改变点赞状态
				$favor_res = Db::name('hd_favor')->where('id', $favor['id'])->update(['status' => 1]);

				// 当点赞成功后
				if ($favor_res)
				{
				// 给该东西点赞量+1，先判断该东西类型
					switch ($param['type']) {
						case '1':  // 咕咕文章点赞数量+1
							$inc_res = Db::name('gugu_article')->where('id', $param['post_id'])->setInc('like_num');
							break;
						case '0': // 会读新闻评论点赞
							$inc_res = Db::name('hd_comment')->where('id', $param['post_id'])->setInc('favor_num');
							break;
						case '2': // 给咕咕评论点赞数量加1
							$inc_res = Db::name('gugu_comment')->where('id', $param['post_id'])->setInc('favor_num');
							break;
						case '3': // 给回答点赞
							$inc_res = Db::name('qa_answer')->where('id', $param['post_id'])->setInc('favor_num');
							break;
						case '4': // 给游记点赞
							$inc_res = Db::name('travels')->where('travels_id', $param['post_id'])->setInc('favor_num');
							break;
						case '5': // 游记评论点赞
							$inc_res = Db::name('travels_comment')->where('id', $param['post_id'])->setInc('favor_num');
							break;
						case '6': 	// 给专题新闻评论点赞数量+1
						$inc_res = Db::name('hd_topic_news_comment')->where('id', $param['post_id'])->setInc('favor_num');
						case '7': // 视频新闻评论点赞
							$inc_res = Db::name('hd_video_comment')->where('id', $param['post_id'])->setInc('favor_num');
							break;
						case '8': // 游记评论点赞
							$inc_res = Db::name('travels_comment')->where('id', $param['post_id'])->setInc('favor_num');
							break;
						case '9': // 给游记点赞
							$inc_res = Db::name('travels')->where('id', $param['post_id'])->setInc('favor_num');
							break;
						case '10': // 给旅行视频的评论点赞
							$inc_res = Db::name('travel_video_comment')->where('id', $param['post_id'])->setInc('favor_num');
							break;

						default:
							# code...
							break;
					}

					// 判断点赞数量+1是否成功
					if ($inc_res)
					{
						$res = [
							'code' => 1,
							'msg' => '操作成功',
							'body' => ['favorStatus' => 1]
						];
					}
				}

			}
		}
		else // 该记录未存在
		{
			$ins['type'] = $param['type'];
			$ins['cur_user_id'] = $param['cur_user_id'];
			$ins['post_id'] = $param['post_id'];
			$ins['status'] = 1;
			// 向点赞表中插入记录
			$ins_res = Db::name('hd_favor')->insert($ins);
			// 判断点赞表中插入记录是否成功
			if ($ins_res)
			{	
				switch ($param['type']) {
					case '1': // 给咕咕文章点赞数量加1
						$inc_res = Db::name('gugu_article')->where('id', $param['post_id'])->setInc('like_num');
						break;
					case '0': // 给新闻评论点赞数量加1
						$inc_res = Db::name('hd_comment')->where('id', $param['post_id'])->setInc('favor_num');
						break;
					case '2': // 给咕咕评论点赞数量加1
						$inc_res = Db::name('gugu_comment')->where('id', $param['post_id'])->setInc('favor_num');
						break;
					case '3': // 给回答点赞数量+1
						$inc_res = Db::name('qa_answer')->where('id', $param['post_id'])->setInc('favor_num');
						break;
					case '4': // 给游记点赞数量+1
						$inc_res = Db::name('travels')->where('travels_id', $param['post_id'])->setInc('favor_num');
						break;
					case '5': // 给游记评论点赞数量+1
						$inc_res = Db::name('travels_comment')->where('id', $param['post_id'])->setInc('favor_num');
						break;
					case '6': 	// 给专题新闻评论点赞数量+1
						$inc_res = Db::name('hd_topic_news_comment')->where('id', $param['post_id'])->setInc('favor_num');
						break;
					case '7': // 视频新闻评论点赞
							$inc_res = Db::name('hd_video_comment')->where('id', $param['post_id'])->setInc('favor_num');
					case '8': // 游记评论点赞
							$inc_res = Db::name('travels_comment')->where('id', $param['post_id'])->setInc('favor_num');
							break;
					case '9': // 给游记点赞
							$inc_res = Db::name('travels')->where('id', $param['post_id'])->setInc('favor_num');
							break;
						case '10': // 给旅行视频的评论点赞
							$inc_res = Db::name('travel_video_comment')->where('id', $param['post_id'])->setInc('favor_num');
					default:
						# code...
						break;
				}
				// 判断是否改变点赞状态
				if ($inc_res)
				{
					$res = [
						'code' => 1,
						'msg' => '操作成功',
						'body' => ['favorStatus' => 1] 
					];

				}
			}
		}
	
		return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	// 取消点赞
	public function cancelFavor()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => ['cancelStatus' => 0]
		];

		// 接收参数
		$param = request()->param();

		$param['type'] = isset($param['type']) ? $param['type'] : '';
		$param['post_id'] = isset($param['post_id']) ? $param['post_id'] : 0;
		$param['cur_user_id'] = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;


		// 先判断取消点赞类型是否存在
		if ('' === $param['type'])
		{
			$res = [
				'code' => 1,
				'msg' => '取消点赞类型不存在',
				'body' => ['favorStatus' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		// 先判断取消点赞的这个东西的id是否存在 
		if (empty($param['post_id']))
		{
			$res = [
				'code' => 1,
				'msg' => '取消点赞的东西的id不存在',
				'body' => ['favorStatus' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		// 判断当前用户id是否存在
		if (empty($param['cur_user_id']))
		{
			$res = [
				'code' => 1,
				'msg' => '当前用户id不存在',
				'body' => ['favorStatus' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		// 判断3种条件，点赞人ID、被点赞的东西的id，该东西的类型
		$where = [
			'type' => ['eq', $param['type']],
			'post_id' => ['eq', $param['post_id']],
			'cur_user_id' => ['eq', $param['cur_user_id']],
		];
		// 先获得点赞状态，查看你是否已经点过赞
		$status = Db::name('hd_favor')->field('id, status')->where($where)->find();

		// 判断是否已点赞
		if (is_array($status)) // 已点赞，可以取消点赞
		{	
			// 判断点赞状态
			if (1 == $status['status'])
			{
				// 将点赞状态更改成未点赞
				$upd_res = Db::name('hd_favor')->where('id', $status['id'])->update(['status' => 0]);
				// 判断更改状态是否成功
				if ($upd_res)
				{
					// 更改成功之后，要将点赞数量减1
					switch ($param['type']) {
						case '0': // 新闻评论点赞数量-1
							$dec_res = Db::name('hd_comment')->where('id', $param['post_id'])->setDec('favor_num');
							break;	
						case '1': // 咕咕文章点赞数量-1
							$dec_res = Db::name('gugu_article')->where('id', $param['post_id'])->setDec('like_num');
							break;
						case '2' : // 咕咕文章的评论点赞数量-1
							$dec_res = Db::name('gugu_comment')->where('id', $param['post_id'])->setDec('favor_num');
							break;
						case '3' : // 回答点赞数量-1
							$dec_res = Db::name('qa_answer')->where('id', $param['post_id'])->setDec('favor_num');
							break;
						case '4' : // 游记点赞数量-1
							$dec_res = Db::name('travels')->where('travels_id', $param['post_id'])->setDec('favor_num');
							break;
						case '5' : // 游记评论点赞数量-1
							$dec_res = Db::name('travels_comment')->where('id', $param['post_id'])->setDec('favor_num');
							break;
						case '6' : // 专题新闻评论点赞数量-1
							$dec_res = Db::name('hd_topic_news_comment')->where('id', $param['post_id'])->setDec('favor_num');
							break;
						case '7': // 视频新闻评论点赞
							$dec_res = Db::name('hd_video_comment')->where('id', $param['post_id'])->setDec('favor_num');
							break;
						case '8': // 游记评论点赞
							$dec_res = Db::name('travels_comment')->where('id', $param['post_id'])->setDec('favor_num');
							break;
						case '9': // 给游记点赞
							$dec_res = Db::name('travels')->where('id', $param['post_id'])->setDec('favor_num');
							break;
						case '10': // 给旅行视频的评论点赞
							$dec_res = Db::name('travel_video_comment')->where('id', $param['post_id'])->setDec('favor_num');
						default:
							# code...
							break;
					}
				}
				if ($dec_res)
				{
					$res = [
						'code' => 1,
						'msg' => '操作成功',
						'body' => ['cancelStatus' => 1]
					];
				}
			}
		}

		return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}







	// 收藏    type 1.普通新闻  2.专题新闻  3.游记  4.问答  5.景点门票  6.视频新闻
	public function collect()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => ['collectStatus' => 0]
		];

		// 接收参数
		$param = request()->param();

		$param['type'] = isset($param['type']) ? $param['type'] : '';
		$param['post_id'] = isset($param['post_id']) ? $param['post_id'] : 0;
		$param['member_id'] = isset($param['member_id']) ? $param['member_id'] : 0;
		// 判断参数是否存在
		if (empty($param['type'])||empty($param['post_id'])||empty($param['member_id']))
		{
			$res = [
				'code' => 1,
				'msg' => '缺少参数',
				'body' => ['collectStatus' => 0]
			];		
			return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}


		// 先判断该用户是否收藏该东西
		$collect_cond = [
			'type' => $param['type'],
			'post_id' => $param['post_id'],
			'member_id' => $param['member_id']
		];	

		$collect_res = Db::name('member_collect')->field('id, status')->where($collect_cond)->find();

		// 判断记录是否存在
		if (is_array($collect_res))
		{
			// 判断收藏状态是否为0
			if ($collect_res['status']==0)
			{
				$upd_res = Db::name('member_collect')->where('id', $collect_res['id'])->update(['status' => 1]);
				if ($upd_res)
				{
					// 选择类型 type 1.普通新闻  2.专题新闻  3.游记  4.问答  5.景点门票
					switch ($param['type']) {
						case 1: // 普通新闻
							$inc_res = Db::name('hd_news')->where('id', $param['post_id'])->setInc('collect_num');
							break;
						case 2: // 专题新闻
							$inc_res = Db::name('hd_article_topic')->where('id', $param['post_id'])->setInc('collect_num');
							break;
						case 3: // 游记
							$inc_res = Db::name('travels')->where('id', $param['post_id'])->setInc('collect_num');
							break;
						case 4: // 回答
							$inc_res = Db::name('qa_answer')->where('id', $param['post_id'])->setInc('collect_num');
							break;
						case 5:
						       $inc_res=1;
						       //门票暂时没有收藏数量展示
						       break;
						case 6: // 视频新闻的收藏数量+1
							$inc_res = Db::name('hd_topic_video')->where('id', $param['post_id'])->setInc('collect_num');
							break;
						case 7: // 旅行视频的收藏数量+1
							$inc_res = Db::name('travel_video')->where('id', $param['post_id'])->setInc('collect_num');
							break;	
						default:
							
							break;
					}

					// 判断收藏数量是否+1
					if ($inc_res)
					{
						$res = [
							'code' => 1,
							'msg' => '收藏成功',
							'body' => ['collectStatus' => 1]
						];
					}
					else // 收藏数量+1失败
					{ 
						$res = [
							'code' => 1,
							'msg' => '收藏成功，但是收藏数量+1失败',
							'body' => ['collectStatus' => 1]
						];
					}
				}
				else
				{
					$res = [
						'code' => 1,
						'msg' => '收藏失败，更新状态失败',
						'body' => ['collectStatus' => 0]
					];
				}
			}
			else // 状态为1
			{
				$res = [
					'code' => 1,
					'msg' => '已经收藏',
					'body' => ['collectStatus' => 0]
				];
			}
		}
		else // 记录不存在
		{
			$collect_cond['add_time'] = time();
			$ins_res = Db::name('member_collect')->insert($collect_cond);
			if ($ins_res)
			{
				// 类型选择
				switch ($param['type']) {
					case 1: // 普通新闻
						$inc_res = Db::name('hd_news')->where('id', $param['post_id'])->setInc('collect_num');
						break;
					case 2: // 专题新闻
						$inc_res = Db::name('hd_article_topic')->where('id', $param['post_id'])->setInc('collect_num');
						break;
					case 3: // 游记
						$inc_res = Db::name('travels')->where('id', $param['post_id'])->setInc('collect_num');
						break;
					case 4: // 回答
						$inc_res = Db::name('qa_answer')->where('id', $param['post_id'])->setInc('collect_num');
						break;
					case 5://门票
					     	 $inc_res=1;
						   //门票暂时没有收藏数量展示
						 break;	
					case 6: // 视频新闻的收藏数量+1
							$inc_res = Db::name('hd_topic_video')->where('id', $param['post_id'])->setInc('collect_num');
							break;	
					case 7: // 旅行视频的收藏数量+1
							$inc_res = Db::name('travel_video')->where('id', $param['post_id'])->setInc('collect_num');
							break;
					default:
						# code...
						break;
				}

				// 判断收藏数量+1是否成功
				if ($inc_res)
				{
					$res = [
						'code' => 1,
						'msg' => '收藏成功',
						'body' => ['collectStatus' => 1]
					];
				}
				else // 收藏成功，但是收藏数量+1失败
				{
					$res = [
						'code' => 1,
						'msg' => '收藏成功，但是收藏数量+1失败',
						'body' => ['collectStatus' => 0]
					];
				}
			}
			else
			{
				$res = [
					'code' => 1,
					'msg' => '收藏失败，插入记录失败',
					'body' => ['collectStatus' => 0]
				];
			}
		}

		return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	// 取消收藏
	public function cancelCollect()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => ['cancelStatus' => 0]
		];

		// 接收参数
		$param = request()->param();

		$param['type'] = isset($param['type']) ? $param['type'] : '';
		$param['post_id'] = isset($param['post_id']) ? $param['post_id'] : 0;
		$param['member_id'] = isset($param['member_id']) ? $param['member_id'] : 0;

		// 判断取消收藏类型是否存在
		if ('' === $param['type']||empty($param['post_id'])||empty($param['member_id']))
		{
			$res = [
				'code' => 1,
				'msg' => '缺少参数',
				'body' => ['cancelStatus' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}



		// 先判断该用户以前是否收藏过该东西
		$collect_cond = [
			'type' => $param['type'],
			'post_id' => $param['post_id'],
			'member_id' => $param['member_id']
		];

		$collect_res = Db::name('member_collect')->field('id, status')->where($collect_cond)->find();

		// 是数组的话，代表有记录存在
		
		if (is_array($collect_res))
		{
			// 判断收藏状态是否为0
			if ($collect_res['status']==0)
			{
				$res = [
					'code' => 1,
					'msg' => '是已取消收藏状态，所以无法取消收藏',
					'body' => ['collectStatus' => 0]
				];
			}
			else // 收藏状态为1，可以取消收藏
			{
				$collect_cond = [
					'type' => $param['type'],
					'post_id' => $param['post_id'],
					'member_id' => $param['member_id']
				];
                 //********改成了删除记录***********//
				$upd_res = Db::name('member_collect')->where($collect_cond)->delete();

				// 判断更改状态是否成功
				if ($upd_res)
				{
					// 类型选择
					switch ($param['type']) {
						case 1: // 普通新闻
 							$dec_res = Db::name('hd_news')->where('id', $param['post_id'])->setDec('collect_num');
							break;
						case 2: // 专题新闻
							$dec_res = Db::name('hd_article_topic')->where('id', $param['post_id'])->setDec('collect_num');
							break;
						case 3: // 游记
							$dec_res = Db::name('travels')->where('id', $param['post_id'])->setDec('collect_num');
							break;
						case 4: // 回答
							$dec_res = Db::name('qa_answer')->where('id', $param['post_id'])->setDec('collect_num');
							break;
						case 5:
						 	$dec_res=1;
						       //门票暂时没有收藏数量展示
						    break;	
						case 6: // 视频新闻的收藏数量-1
							$dec_res = Db::name('hd_topic_video')->where('id', $param['post_id'])->setDec('collect_num');
							break;
						case 7: // 旅行视频的收藏数量-1
							$dec_res = Db::name('travel_video')->where('id', $param['post_id'])->setDec('collect_num');
							break;
						default:
							# code...
							break;
					}
					// 判断收藏数量-1是否成功
					if ($dec_res)
					{
						$res = [
							'code' => 1,
							'msg' => '取消收藏成功',
							'body' => ['cancelStatus' => 1]
						];	
					}
					else // 收藏数量-1失败
					{
						$res = [
							'code' => 1,
							'msg' => '取消收藏成功，但收藏数量-1失败',
							'body' => ['cancelStatus' => 0]
						];	
					}
				}
				else
				{
					$res = [
						'code' => 1,
						'msg' => '更新状态失败，取消收藏失败',
						'body' => ['collectStatus' => 0]
					];
				}
			}
		}
		else // 不是数组的话，代表没有记录存在，所以无法取消收藏
		{
			$res = [
				'code' => 1,
				'msg' => '以前都没有收藏过，无法取消',
				'body' => ['cancelStatus' => 0]
			];
		}

		return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	// 得到粉丝量
	public function getFansNum()
	{
		// 只需要接收当前用户id
		$cur_user_id = input('cur_user_id');

		if (empty($cur_user_id))
		{
			return '缺少当前用户id';
		}

		// 条件
		$cond = [
			'user_id' => ['eq', $cur_user_id],
			'status' => ['eq', 1]
		];

		$fans_num = Db::name('hd_fans')->where($cond)->count();
		return '粉丝数量为：' . $fans_num;
	}

	/**
	* 得到收藏状态
	* @param int $type 收藏类型
	* @param int $post_id 被收藏的东西的id
	* @param int $cur_user_id 当前用户id
	* @return bool 0或1  收藏状态
	*/
	public function getCollectStatus($type, $post_id, $cur_user_id)
	{
		// 直接判断用户id是否存在
		if (empty($cur_user_id))
		{
			return 0;
		}

		// 多字段相等判断条件
		$collect_cond = [
			'type' => $type,
			'post_id' => $post_id,
			'member_id' => $cur_user_id
		];

		$collect_res = Db::name('member_collect')->field('status')->where($collect_cond)->find();
		if (is_array($collect_res))
		{
			return $collect_res['status'];
		}
		else
		{
			return 0;
		}
	}

	/**
	* 得到点赞状态
	* @param int $type 点赞类型
	* @param int $cur_user_id 当前用户id
	* @param int $post_id 被点赞的那个东西的id
	* return bool
	*/
	public function getFavorStatus($type, $cur_user_id, $post_id)
	{
		if (empty($cur_user_id))
		{
			return 0;
		}

		// 先查看点赞表中记录是否存在
		$favor_cond = [
			'type' => ['eq', $type],
			'cur_user_id' => ['eq', $cur_user_id],
			'post_id' => ['eq', $post_id]
		];

		$favor_status = Db::name('hd_favor')->where($favor_cond)->value('status');

		// 当记录为空时，返回点赞状态0
		if (empty($favor_status))
		{
			return 0;
		}
		else // 当有记录时，返回点赞状态
		{
			return $favor_status;
		}
	}

	/**
	* 得到关注状态
	* @param int cur_user_id 当前用户id
	* @param int user_id 被关注人id
	* @return bool 
	*/
	public function getFollowStatus($cur_user_id, $user_id)
	{
		// 判断当前用户id是否存在
		if (empty($cur_user_id))
		{
			return 0;
		}

		// 多字段相等条件
		$cond = [
			'user_id' => $user_id,
			'fans_id' => $cur_user_id,
			'status' => 1
		];

		$status = Db::name('hd_fans')->where($cond)->value('status');

		return $status ? 1 : 0;
	}

	// 转发咕咕
	public function forward()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => ['forwardStatus' => 0]
		];

		// 接收参数
		$param = request()->param();

		$gu_id = isset($param['article_id']) ? $param['article_id'] : 0;
		$cur_user_id = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;

		if (empty($cur_user_id))
		{
			$res = [
				'code' => 1,
				'msg' => '没有用户id',
				'body' => ['forwardStatus' => 0]
			];
			return json_encode($res);
		}

		if (empty($gu_id))
		{
			$res = [
				'code' => 1,
				'msg' => '没有咕咕id',
				'body' => ['forwardStatus' => 0]
			];
			return json_encode($res);
		}

		$gu = Db::name('gugu_article')->field('id as forward_id, gugu_content, img_path, cover_img')->find();
		$gu['type'] = 0;
		$gu['cur_user_id'] = $cur_user_id;
		
		$res['body']['forwardMsg'] = $gu;


		return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}	

	// 阅读
	public function read($type, $m_id_code, $post_id)
	{
		if (empty($m_id_code))
		{
			return;
		}
		$cond['type'] = $type;
		$cond['m_id_code'] = $m_id_code;
		$cond['post_id'] = $post_id;

		$res = Db::name('hd_read')->field('id, status')->where($cond)->find();

		// 判读是否为数组，
		if (is_array($res))
		{
			if (0 == $res['status'])
			{

			}
		}
		else // 不是数组，表示还没有阅读过，新增一条记录，且阅读量加1
		{
			$cond['add_time'] = time();
			$ins_res = Db::name('hd_read')->insert($cond);

			if ($ins_res)
			{
				switch ($type) {
					case '0': // 普通新闻阅读量+1
						$inc_res = Db::name('hd_news')->where('id', $post_id)->setInc('read_num');
						break;
					case '1': // 专题新闻阅读量+1
						$inc_res = Db::name('hd_article_topic')->where('id', $post_id)->setInc('read_num');
						break;
					case '2': // 视频详情阅读量+1
						$inc_res = Db::name('hd_topic_video')->where('id', $post_id)->setInc('read_num');
						break;
					case '3': // 咕咕详情阅读量+1
						$inc_res = Db::name('gugu_article')->where('id', $post_id)->setInc('read_num');
						break;
					case '4': // 游记详情阅读量+1
						$inc_res = Db::name('travels')->where('id', $post_id)->setInc('read_num');
						break;
					case '5': // 精选视频阅读量+1
						$inc_res = Db::name('travel_video')->where('id', $post_id)->setInc('read_num');
						break;
					default:
						# code...
						break;
				}
			}
		}
	}

	// 属于我
	public function getBelongStatus($user_id, $cur_user_id)
	{	
		if (0 == $cur_user_id)
		{
			return 0;
		}
		if ($user_id == $cur_user_id)
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}

	// 分享
	public function share()
	{
		// $title = '你和我';
		// $this->assign('title', $title);
		// $content = $this->fetch(); 
		// //这里的 fetch() 就是获取输出内容的函数,现在$content变量里面,就是要显示的内容了
		// $fp = fopen("0001.html", "w");


		// fwrite($fp, $content);
		// fclose($fp);
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => []
		];	

		// 接收参数
		$param = request()->param();
		$type = isset($param['type']) ? $param['type'] : 0;
		$post_id = isset($param['post_id']) ? $param['post_id'] : 0;
		if (0 == $post_id)
		{
			$res['msg'] = '没有被分享的东西的id';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}
		if (0 == $type)
		{
			$res['msg'] = '没有分享类型';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}

		// 1-普通新闻		
		if (1 == $type)
		{	
			$news = Db::name('hd_news')
				   ->field('id as post_id, title, pic1, content')
				   ->where('id', $post_id)
				   ->find();

			$pattern = '/(<\/?([a-zA-Z\w]+)[^>]*>)|&\w+;/u';
				// 处理html标签
				$content = preg_replace($pattern, '', $news['content']);
				// 处理换行符和tab符号
				$content = preg_replace('/\r\n\t*/', '', $content);
				// 截取前面28个字
				$content = mb_substr($content, 0, 30);
				// 统一编码
				$content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
					
			

			$this->assign('news', $news);
			$page_content = $this->fetch();

			$news['content'] = $content;
			$destination = getDestination(3);

			$url = 'http://zhlsfnoc.com/' . $destination;

			$fp = fopen($destination, 'w');
			fwrite($fp, $page_content);
			fclose($fp);

			$news['url'] = $url;
			$res['body']['share_msg'] = $news;
		}
		else if(2 == $type) // 2-专题新闻
		{	
			$news = Db::name('hd_article_topic')
				   ->field('id as post_id, title, thumb as pic1, content')
				   ->where('id', $post_id)
				   ->find();

			$pattern = '/(<\/?([a-zA-Z\w]+)[^>]*>)|&\w+;/u';
				// 处理html标签
				$content = preg_replace($pattern, '', $news['content']);
				// 处理换行符和tab符号
				$content = preg_replace('/\r\n\t*/', '', $content);
				// 截取前面28个字
				$content = mb_substr($content, 0, 30);
				// 统一编码
				$content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
					
			

			$this->assign('news', $news);
			$page_content = $this->fetch();

			$news['content'] = $content;
			$destination = getDestination(3);

			$url = 'http://zhlsfnoc.com/' . $destination;

			$fp = fopen($destination, 'w');
			fwrite($fp, $page_content);
			fclose($fp);

			$news['url'] = $url;
			$res['body']['share_msg'] = $news;
		}
		else if(3 == $type) // 3-咕咕
		{	
			$news = Db::name('gugu_article')
				   ->field('id as post_id, img_path, gugu_content as title, cover_img, video_path')
				   ->where('id', $post_id)
				   ->find();


			if (('' == $news['img_path']) && ('' == $news['video_path']))
			{

			}
			else if ($news['img_path'])
			{
				$page_data = [];
				$page_data['title'] = '';
				$page_data['content'] = $news['title'];

				$img = explode(',', $news['img_path']);
				$page_data['img'] = $img;
				

				$news['pic1'] = $img[0];


				unset($news['cover_img']);
				unset($news['img_path']);
				$news['content'] = '';

				$this->assign('news', $page_data);
				$page_content = $this->fetch();
			
				$destination = getDestination(3);

				$url = 'http://zhlsfnoc.com/' . $destination;

				$fp = fopen($destination, 'w');
				fwrite($fp, $page_content);
				fclose($fp);

				$news['url'] = $url;
				$res['body']['share_msg'] = $news;
					
			}
			else
			{		
				unset($news['img_path']);
				$news['pic1'] = $news['cover_img'];
				unset($news['cover_img']);
				$news['content'] = '';

				$page_data = [];

				$page_data['video_path'] = $news['video_path'];
				$page_data['content'] = $news['title'];
				$page_data['cover_img'] = $news['pic1'];
				$page_data['title'] = '';
				$page_data['img'] = '';


				unset($news['video_path']);
				$this->assign('news', $page_data);
				$page_content = $this->fetch();
			
				$destination = getDestination(3);

				$url = 'http://zhlsfnoc.com/' . $destination;

				$fp = fopen($destination, 'w');
				fwrite($fp, $page_content);
				fclose($fp);

				$news['url'] = $url;
				$res['body']['share_msg'] = $news;
			}	
		}

		return json_encode($res, JSON_UNESCAPED_UNICODE);
	}
}