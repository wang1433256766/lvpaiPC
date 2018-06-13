<?php

namespace app\api\controller;

use think\Db;
use app\api\model\TopicNewsModel;
use app\api\controller\Common;
use app\api\model\NewsModel;

class News
{
	// 资讯接口
	public function getNewsData()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => ['news' => []]
		];

		$page = input('page');
		$index = input('index');

		// 接收当前页数和当前在哪个分类
		$page = isset($page) ? $page : 1;
		$index = isset($index) ? $index : 1;


		$offset = ($page - 1) * 12;

		// 判断是哪个分类，1是热点
		if (1 == $index)
		{
			// 热点分类下的第一页有轮播图和语音播放新闻
			if (1 == $page)
			{
				$banner_img = Db::name('hd_news')->field('id as banner_id, pic1')->where('base', 1)->limit(5)->select();

				// 拿到音频有两种写法，1：和所有新闻一起拿，然后用mp3_status分开
					// 2：直接拿到三条，2里面又有两种写法，2.1：二表联查，2.2：for循环来拿到分类
				$mp3 = Db::name('hd_news n')->field('n.id as news_id, n.thumb, n.title, n.pic1, n.read_num, n.pl_num, c.name, n.duration')->
				join('hd_article_cate c', 'c.id = n.cate_id')->
				order('rota_sort')->limit(3)->where('n.mp3_status', 1)->select();

				// 将时间字符串转化为秒数
				foreach ($mp3 as $k => $v)
				{
					$mp3[$k]['duration'] = transferSecond($v['duration']);
				}
				
				$news = Db::name('hd_news n')->field('n.id as news_id, title, content, pic1, read_num, pl_num, c.name')->
				join('hd_article_cate c', 'c.id = n.cate_id')->
				limit($offset, 9)->order('n.add_time desc')->select();
				
				// 第4个新闻不需要内容
				foreach ($news as $k => $v)
				{
					if (0 == (($k+4) % 4))
					{
						unset($news[$k]['content']);
					}
					else
					{
						$pattern = '/(<\/?([a-zA-Z\w]+)[^>]*>)|&\w+;/u';
						// 处理html标签
						$content = preg_replace($pattern, '', $v['content']);
						// 处理换行符和tab符号
						$content = preg_replace('/\r\n\t*/', '', $content);
						// 截取前面28个字
						$content = mb_substr($content, 0, 30);
						// 统一编码
						$content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
						
						$news[$k]['content'] = $content . '...';
					}
				}

			}
			else // 除开第一页的其他页面，不需要轮播图和语音播放类型的新闻
			{
				$news = Db::name('hd_news n')->field('n.id as news_id, title, content, pic1, read_num, pl_num, c.name')->
				join('hd_article_cate c', 'c.id = n.cate_id')->
				limit($offset, 12)->order('n.add_time desc')->select();
				
				foreach ($news as $k => $v)
				{
					if (0 == (($k+1) % 4))
					{

						unset($news[$k]['content']);
					}
					else
					{
						$pattern = '/(<\/?([a-zA-Z\w]+)[^>]*>)|&\w+;/u';
						// 处理html标签
						$content = preg_replace($pattern, '', $v['content']);
						// 处理换行符和tab符号
						$content = preg_replace('/\r\n\t*/', '', $content);
						// 截取前面28个字
						$content = mb_substr($content, 0, 30);
						// 统一编码
						$content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
						
						$news[$k]['content'] = $content . '...';
					}
				}
			}
		}
		else if (2 == $index) // 2是旅游
		{
			$news = Db::name('hd_news n')->field('n.id as news_id, title, content, read_num, n.read_num, pic1, c.name, pl_num')->
			join('hd_article_cate c', 'c.id = n.cate_id')->
			order('n.add_time desc')->where('n.cate_id', 5)->limit($offset, 12)->select();

			foreach ($news as $k => $v)
			{
				if (0 == (($k+1) % 4))
				{
					unset($news[$k]['content']);
				}
				else
				{
					$pattern = '/(<\/?([a-zA-Z\w]+)[^>]*>)|&\w+;/u';
						// 处理html标签
						// $content = preg_replace($pattern, '', $v['content']);
						// 处理换行符和tab符号
						// $content = preg_replace('/\r\n\t*/', '', $content);
						// 截取前面28个字 
						// $content = mb_substr($content, 0, 30);
						// 统一编码
						// $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
						
					$content = preg_replace($pattern, '', $v['content']);

					$content = preg_replace('/\n/', '', $content);
					$content = preg_replace('/\s/', '', $content);
					$content = mb_substr($content, 0, 28);

					$news[$k]['content'] = $content . '...';
				}
			}
		}
		else if (3 == $index) // 3是文化
		{
			$news = Db::name('hd_news n')->field('n.id as news_id, title, content, read_num, pl_num, c.name, pic1')->
			join('hd_article_cate c', 'c.id = n.cate_id')->
			order('n.add_time desc')->limit($offset, 12)->where('cate_id', 18)->select();

			foreach ($news as $k => $v)
			{
				if (0 == (($k+1) % 4))
				{
					unset($news[$k]['content']);
				}
				else
				{
					$pattern = '/(<\/?([a-zA-Z\w]+)[^>]*>)|&\w+;/u';

					$content = preg_replace($pattern, '', $v['content']);
					$content = preg_replace('/\n/', '', $content);
					$content = preg_replace('/\s/', '', $content);
					$content = mb_substr($content, 0, 28) . '...';

					$news[$k]['content'] = $content;
				}
			}

		}
		else if (4 == $index) // 4是财经
		{
			$news = Db::name('hd_news n')->field('n.id as news_id, title, content, read_num, pl_num, c.name, pic1')->
			join('hd_article_cate c', 'c.id = n.cate_id')->
			order('n.add_time desc')->limit($offset, 12)->where('cate_id', 3)->select();

			foreach ($news as $k => $v)
			{
				if (0 == (($k+1) % 4))
				{
					unset($news[$k]['content']);
				}
				else
				{
					$pattern = '/(<\/?([a-zA-Z\w]+)[^>]*>)|&\w+;/u';

					$content = preg_replace($pattern, '', $v['content']);
					$content = preg_replace('/\n/', '', $content);
					$content = preg_replace('/\s/', '', $content);
					$content = mb_substr($content, 0, 28) . '...';

					$news[$k]['content'] = $content;
				}
			}
		}
		else if (5 == $index) // 5是科技
		{
			$news = Db::name('hd_news n')->field('n.id as news_id, title, content, read_num, pl_num, c.name, pic1')->
			join('hd_article_cate c', 'c.id = n.cate_id')->
			order('n.add_time desc')->limit($offset, 12)->where('cate_id', 4)->select();
			

			foreach ($news as $k => $v)
			{
				if (0 == (($k+1) % 4))
				{
					unset($news[$k]['content']);
				}
				else
				{
					$pattern = '/(<\/?([a-zA-Z\w]+)[^>]*>)|&\w+;/u';

					$content = preg_replace($pattern, '', $v['content']);
					$content = preg_replace('/\n/', '', $content);
					$content = preg_replace('/\s/', '', $content);
					$content = mb_substr($content, 0, 28) . '...';

					$content = 

					$news[$k]['content'] = $content;
				}
			}
		}
		else if (6 == $index) // 6是娱乐
		{
			$news = Db::name('hd_news n')->field('n.id as news_id, title, content, read_num, pl_num, c.name, pic1')->
			join('hd_article_cate c', 'c.id = n.cate_id')->
			order('n.add_time desc')->limit($offset, 12)->where('cate_id', 2)->select();
			

			foreach ($news as $k => $v)
			{
				if (0 == (($k+1) % 4))
				{
					unset($news[$k]['content']);
				}
				else
				{
					$pattern = '/(<\/?([a-zA-Z\w]+)[^>]*>)|&\w+;/u';

					$content = preg_replace($pattern, '', $v['content']);
					$content = preg_replace('/\n/', '', $content);
					$content = preg_replace('/\s/', '', $content);
					$content = mb_substr($content, 0, 28) . '...';


					$news[$k]['content'] = $content;
				}
			}
		}

		if (1 == $index && 1 == $page)
		{
			$res = [
				'code' => 1,
				'msg' => '操作成功',
				'body' => ['banner_img' => $banner_img,
							'mp3_news' => $mp3,
							'news' => $news]
			];
			if (count($news) < 9)
			{
				$res['body']['noMoreData'] = 1;
			}
			$res['body']['page'] = $page + 1;
		}
		else
		{		
			$res = [
				'code' => 1,
				'msg' => '操作成功',
				'body' => ['news' => $news]
			];		

			if (count($news) < 12)
			{
				$res['body']['noMoreData'] = 1;
			}
			$res['body']['page'] = $page + 1;
		}

		return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	// 会读搜索，增加搜索历史，返回搜索结果，还有判断是否将此次搜索内容放入热门搜索关键词中
	public function search()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => ['result' => 0]
		];

		// 接收参数
		$param = request()->param();
		
		$param['m_id_code'] = isset($param['m_id_code']) ? $param['m_id_code'] : 0;
		$param['content'] = isset($param['content']) ? $param['content'] : '';
		$type = isset($param['type']) ? $param['type'] : ''; // 默认为首页搜索 

		// 判断是否有搜索类型
		if ('' === $type)
		{
			$res = [
				'code' => 1,
				'msg' => '缺少搜索类型',
				'body' => ['result' => '']
			];
			return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		unset($param['type']);

		// 判断手机标识码是否存在
		if (empty($param['m_id_code']))
		{	
			$res = [
				'code' => 1,
				'msg' => '手机标识码不存在！',
				'body' => ['searchRes' => '']
			];
			return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		// 判断搜索内容是否为空
		if ('' === $param['content'])
		{
			$res = [
				'code' => 1,
				'msg' => '内容为空，无法搜索!',
				'body' => ['searchRes' => '']
			];
			return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		$cond = [
			'm_id_code' => ['eq', $param['m_id_code']],
			'content' => ['eq', $param['content']]
		];


		// 往搜索历史表增加记录
		$history = Db::name('hd_search_history')->field('id, content')->where($cond)->find();

		// 判断是否为同一台手机搜索相同的内容
		if ($history)
		{	
			$update_time = date('Y-m-d h:i:s');
			$upd_res = Db::name('hd_search_history')->where('id', $history['id'])->update(['add_time' => $update_time]);
		}		
		else // 插入新内容
		{
			$ins_res = Db::name('hd_search_history')->insert($param);
		}

		// 判断搜索范围
		if (0 == $type) // 0表示首页搜索
		{
			$json_res = $this->searchAll($param['content']);
			echo $json_res;
			exit();
		}
		else if (1 == $type) // 1表示资讯搜索
		{
			// 往热门关键词表中增加记录
			// 先判断该内容是否已经被人搜索
			$keyword_id = Db::name('hd_keyword')->where('keyword', $param['content'])->value('id');	
			if ($keyword_id)
			{
				// 搜索次数加1
				Db::name('hd_keyword')->where('id', $keyword_id)->setInc('search_num');
			}
			else  // 此内容还没有人搜索，将此内容加入关键词表中
			{
				$keyword_arr['keyword'] = $param['content'];
				Db::name('hd_keyword')->insert($keyword_arr);
			}
			define('PAGESIZE', 10);
			$page = isset($param['page']) ? $param['page'] : 1;

			$offset = ($page - 1) * PAGESIZE; 
			// 搜索内容
			$content = $param['content'];
			// 搜索结果
			$where['title'] = ['like', "%$content%"];
			$news = Db::name('hd_news')->field('id as news_id, title, read_num, pl_num, cate_id, content, pic1')->
			limit($offset, PAGESIZE)->
			where("title like '%$content%'")
			->select();

			$pattern = '/(<\/?([a-zA-Z\w]+)[^>]*>)|&\w+;/u';

			// 处理内容
			for ($i=0; $i<count($news); $i++)
			{
				$content = preg_replace($pattern, '', $news[$i]['content']);

				$content = preg_replace('/\r\n\t*\s*/', '', $content);
				// \r\n\t*
				$content = mb_substr($content, 0, 31) . '...';	

				$news[$i]['content'] = $content;

				// 找到类型
				$type = Db::name('hd_article_cate')->where('id', $news[$i]['cate_id'])->value('name');
					$news[$i]['cate_id'] = $type;
				
			}
			if (count($news) < PAGESIZE)
			{
				$res['body']['noMoreData'] = 1;
			}
			$res['body']['page'] = $page + 1;

			$res['body']['result'] = $news;
		}


		return json($res);
	} 

	// 得到会读搜索历史
	public function getSearchHistory()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => []
		];

		// 接收参数
		$param = request()->param();

		$param['m_id_code'] = isset($param['m_id_code']) ? $param['m_id_code'] : 0;

		if (empty($param['m_id_code']))
		{
			$res = [
				'code' => 1,
				'msg' => '手机标识码不存在！',
				'body' => ['getHistory' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		$history = Db::name('hd_search_history')->field('content')->where('m_id_code', $param['m_id_code'])->order('add_time desc')->limit(6)->select();

		$arr = [];
		foreach ($history as $k => $v)
		{
			$arr[] = $v['content'];
		}

		$res['body']['searchHistory'] = $arr;

		return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	// 清空会读搜索历史
	public function clearSearchHistory()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => ['clearStatus' => 0]
		];	

		$param = request()->param();
		$m_id_code = isset($param['m_id_code']) ? $param['m_id_code'] : 0;


		if (empty($m_id_code))
		{
			$res = [
				'code' => 1,
				'msg' => '手机标识码不存在！',
				'body' => ['clearStatus' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}


		// 接收标识码，清除该手机的搜索历史
		$clear_res = Db::name('hd_search_history')->where('m_id_code', $m_id_code)->delete();

		if ($clear_res)
		{
			$res = [
				'code' => 1,
				'msg' => '操作成功',
				'body' => ['clearStatus' => 1]
			];
		}

		return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	// 会读发现的热门搜索和大家都在看
	public function find()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => []
		];

		// 得到11个热搜关键词
		$keyword = Db::name('hd_keyword')->field('keyword')->order('search_num desc')->limit(11)->select();
		
		$arr = [];
		foreach ($keyword as $k => $v)
		{
			$arr[] = $v['keyword'];
		}
		if ($arr)
		{
			$res = [
				'code' => 1,
				'msg' => '操作成功',
				'body' => ['keyword' => $arr]
			];
		}

		// 得到3个不同类型但阅读量最高的新闻	
		$week = date('l');
		switch ($week) {	
			case 'Monday':
				$news[] = Db::name('hd_news')->field('id as news_id, title, pic1')->where('cate_id', 1)->order('read_num')->find();
				$news[] = Db::name('hd_news')->field('id as news_id, title, pic1')->where('cate_id', 2)->order('read_num')->find();
				$news[] = Db::name('hd_news')->field('id as news_id, title, pic1')->where('cate_id', 3)->order('read_num')->find();
				break;
			case 'Tuesday':
				$news[] = Db::name('hd_news')->field('id as news_id, title, pic1')->where('cate_id', 1)->order('read_num')->find();
				$news[] = Db::name('hd_news')->field('id as news_id, title, pic1')->where('cate_id', 2)->order('read_num')->find();
				$news[] = Db::name('hd_news')->field('id as news_id, title, pic1')->where('cate_id', 4)->order('read_num')->find();
				break;
			case 'Wednesday':
				$news[] = Db::name('hd_news')->field('id as news_id, title, pic1')->where('cate_id', 4)->order('read_num')->find();
				$news[] = Db::name('hd_news')->field('id as news_id, title, pic1')->where('cate_id', 2)->order('read_num')->find();
				$news[] = Db::name('hd_news')->field('id as news_id, title, pic1')->where('cate_id', 3)->order('read_num')->find();
				break;
			case 'Thursday':
				$news[] = Db::name('hd_news')->field('id as news_id, title, pic1')->where('cate_id', 4)->order('read_num')->find();
				$news[] = Db::name('hd_news')->field('id as news_id, title, pic1')->where('cate_id', 5)->order('read_num')->find();
				$news[] = Db::name('hd_news')->field('id as news_id, title, pic1')->where('cate_id', 7)->order('read_num')->find();
				break;
			case 'Friday':
				$news[] = Db::name('hd_news')->field('id as news_id, title, pic1')->where('cate_id', 7)->order('read_num')->find();
				$news[] = Db::name('hd_news')->field('id as news_id, title, pic1')->where('cate_id', 8)->order('read_num')->find();
				$news[] = Db::name('hd_news')->field('id as news_id, title, pic1')->where('cate_id', 18)->order('read_num')->find();
				break;
			case 'Saturday':
				$news[] = Db::name('hd_news')->field('id as news_id, title, pic1')->where('cate_id', 7)->order('read_num')->find();
				$news[] = Db::name('hd_news')->field('id as news_id, title, pic1')->where('cate_id', 8)->order('read_num')->find();
				$news[] = Db::name('hd_news')->field('id as news_id, title, pic1')->where('cate_id', 1)->order('read_num')->find();
				break;
			default:
				$news[] = Db::name('hd_news')->field('id as news_id, title, pic1')->where('cate_id', 18)->order('read_num')->find();
				$news[] = Db::name('hd_news')->field('id as news_id, title, pic1')->where('cate_id', 5)->order('read_num')->find();
				$news[] = Db::name('hd_news')->field('id as news_id, title, pic1')->where('cate_id', 3)->order('read_num')->find();
				break;
		}

		$res['body']['news'] = $news;

		return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	// 得到新闻详情
	public function getNewsDetail()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => ['news' => 0]
		];



		// 接受参数
		$param = request()->param();
		$user_id = isset($param['cur_user_id']) ? $param['cur_user_id'] : '0';
		$news_id = isset($param['news_id']) ? $param['news_id'] : '0';	
		$m_id_code = isset($param['m_id_code']) ? $param['m_id_code'] : 0;

		// 如果手机标识码存在
		if ($m_id_code)
		{
			// 调用阅读方法
			$common = new Common();
			$read_res = $common->read(0, $m_id_code, $news_id);
		}

		// 判断是否有新闻id
		if (empty($news_id))
		{
			$res = [
				'code' => 1,
				'msg' => '新闻id不存在!',
				'body' => ['news' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);	
		}

		// 得到新闻
		$news = Db::name('hd_news n')->field('n.id as news_id, title, content, read_num, n.add_time, m.nickname, m.headimg')->
		join('admin_msg m', 'm.id = n.writer_id')
		->where('n.id', $news_id)->find();

		// 得到该条新闻的评论总数
		$comment_num = Db::name('hd_comment')->where('article_id', $news_id)->count();
		$news['comment_num'] = $comment_num;

		// 得到当前新闻的id
		$news_id = $news['news_id'];

		// 得到上一篇和下一篇新闻
		$next_news = Db::name('hd_news n')->field('n.id as news_id, n.title, n.read_num, n.pl_num, n.pic1, c.name')->
		join('hd_article_cate c', 'c.id = n.cate_id')->limit(1)->where("n.id > $news_id")->find();

		$before_news = Db::name('hd_news n')->field('n.id as news_id, n.title, n.read_num, n.pl_num, n.pic1, c.name')->
		join('hd_article_cate c', 'c.id = n.cate_id')->limit(1)->where("n.id < $news_id")->order('n.id desc')->find();

		$res['body']['middle_news'][] = $before_news;
		$res['body']['middle_news'][] = $next_news;

		// 解决第一篇新闻没有上一篇新闻和最后一篇新闻没有下一篇新闻的问题
		if (empty($res['body']['middle_news'][0]))
		{
			$last_news = Db::name('hd_news n')->field('n.id as news_id, n.title, n.read_num, n.pl_num, n.pic1, c.name')->
			join('hd_article_cate c', 'c.id = n.cate_id')->order('n.id desc')->find();
			$res['body']['middle_news'][0] = $last_news;
		}
		if (empty($res['body']['middle_news'][1]))
		{
			$first_news = Db::name('hd_news n')->field('n.id as news_id, n.title, n.read_num, n.pl_num, n.pic1, c.name')->
			join('hd_article_cate c', 'c.id = n.cate_id')->order('n.id')->find();
			$res['body']['middle_news'][1] = $first_news;
		}



		$res['body']['news'] = $news;
		
		$content = $res['body']['news']['content'];

		$res['body']['news']['content'] = str_replace('"', '\'', $content);
		
		// 得到收藏状态，先判断用户是否登录
		if (empty($user_id))
		{
			$res['body']['news']['collect_status'] = 0;
		}
		else // 用户已登录
		{
			$res['body']['news']['collect_status'] = $this->getCollectStatus(1, $news_id, $user_id);
		}



		return json_encode($res, JSON_HEX_TAG | JSON_HEX_AMP);
	}

	// 得到普通新闻的评论
	public function getNewsComment()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => ['comment' => 0]
		];

		$param = request()->param();
		$param['news_id'] = isset($param['news_id']) ? $param['news_id'] : 0;
		$param['cur_user_id'] = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;

		if (empty($param['news_id']))
		{
			$res = [
				'code' => 1,
				'msg' => '新闻id不存在',
				'body' => ['comment' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}

		// 定义评论的显示条数
		define('COMPAGESIZE', 10);

		// 评论的当前页数
		$com_page = isset($param['com_page']) ? $param['com_page'] : 1;

		$offset = ($com_page - 1) * COMPAGESIZE;

		// 得到评论
		$comments = Db::name('hd_comment c')->field('c.add_time, c.favor_num, c.content, c.member_id, c.id as comment_id, m.nickname, m.headimg')->
		join('mall_member m', 'm.id = c.member_id')
		->limit($offset, COMPAGESIZE)->where('c.article_id', $param['news_id'])->order('add_time desc')->select();

		// 处理评论的时间和点赞状态 
		for ($i=0; $i<count($comments); $i++)
		{
			// 得到当前时间与评论时间的差值
			$time = time() - strtotime($comments[$i]['add_time']);
			$time += 126;
			// 判断评论时间是否超过一天
			if (86400 <= $time)
			{
				// 评论时间超过一天，不作处理
			}
			else  // 评论时间小于1天
			{
				// 判断评论时间是否超过1小时
				if (3600 <= $time)
				{
					$hour = floor($time / 3600);
					$comments[$i]['add_time'] = $hour . '小时前';
				}
				else // 评论时间小于1小时
				{
					// 判断评论时间小于1分钟
					if (60 < $time)
					{
						$minute = floor($time / 60);
						$comments[$i]['add_time'] = $minute . '分钟前';
					}
					else // 评论时间小于1分钟
					{
						$comments[$i]['add_time'] = '刚刚';
					}
				}
			}

			// 处理点赞状态
			$favor_status = $this->getFavorStatus(0, $param['cur_user_id'], $comments[$i]['comment_id']);
			$comments[$i]['favor_status'] = $favor_status;
		}

		$res['body']['comment'] = $comments;
		$res['body']['comment_num'] = count($comments);
		if (count($comments) < COMPAGESIZE)
		{
			$res['body']['noMoreComment'] = 1;
		}
		$res['body']['com_page'] = $com_page + 1;

		return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	// 得到会读专栏
	public function getSpecialColumn()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => ['news' => 0]
		];

		// 得到4种类型的新闻
		for ($i=1; $i<=5; $i++)
		{
			$type = Db::name('hd_topic_cate')->field('id, name')->where('id', $i)->find();
			if ((3 == $i) || (5 == $i))
			{
				continue;
			}

			$typeArr[] = $type;
		}

		// 得到惠声惠影最新的视频
		$video_news = Db::name('hd_topic_video')->field('img_path as thumb, add_time')->order('add_time desc')->find();
		$video_news['type'] = '惠声惠影';

		// 处理惠声惠影的时间
		$video_time = time() - strtotime($video_news['add_time']);
				$video_time += 126;
				if (86400 < $video_time)
				{
					$video_news['add_time'] = substr($video_news['add_time'], 0, 10) . ' 更新';
				}
				else  // 没有超过一天
				{
					$video_news['add_time'] = substr($video_news['add_time'], -8, 5) . ' 更新';
				}


		// 得到每种类型最新的新闻
		foreach ($typeArr as $v)
		{	
			$news = Db::name('hd_article_topic')->field('thumb, add_time')->order('add_time desc')->where('topic_id', $v['id'])->find();
			$news['type'] = $v['name'];

			// 判断最新新闻更新时间是否超过一天
			if (isset($news['add_time']))
			{
				$time = time() - strtotime($news['add_time']);
				$time += 126;
				if (86400 < $time)
				{
					$news['add_time'] = substr($news['add_time'], 0, 10) . ' 更新';
				}
				else  // 没有超过一天
				{
					$news['add_time'] = substr($news['add_time'], -8, 5) . ' 更新';
				}
			}			
			$newsArr[] = $news;
		}	
		$newsArr[] = $video_news;

		$res['body']['news'] = $newsArr;

		return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	// 得到会读专栏4个类型的新闻列表
	public function getSpecialColumnList()
	{
		$res = [	
			'code' => 1,
			'msg' => '操作成功',
			'body' => ['news' => 0]
		];

		// 接收参数
		$param = request()->param();

		$type = isset($param['type']) ? $param['type'] : 0;
		$page = isset($param['page']) ? $param['page'] : 1;

		// 定义页面大小
		define('PAGESIZE', 10);

		$offset = ($page - 1) * PAGESIZE;

		if (empty($type))
		{
			$res = [
				'code' => 1,
				'msg' => '请选择一个类型',
				'body' => ['news' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		$news_obj = new TopicNewsModel();
		$news = $news_obj->getNews($type, $offset, PAGESIZE);
		$res['body']['news'] = $news;

		if (count($news) < PAGESIZE)
		{
			$res['body']['noMoreData'] = 1;
		}
		$res['body']['page'] = $page + 1;

		return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	// 发表普通新闻评论
	public function sendNewsComment()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => ['sendStatus' => 0]
		];

		// 接受参数
		$param = request()->param();
		$user_id = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;  // 当前用户id
		$news_id = isset($param['news_id']) ? $param['news_id'] : 0;  // 当前新闻id
		$content = isset($param['content']) ? $param['content'] : '';

		// 判断是否有用户
		if (empty($user_id))
		{	
			$res = [
				'code' => 1,
				'msg' => '用户id不存在',
				'body' => ['sendStatus' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}
		// 判断是否有新闻id
		if (empty($news_id))
		{
			$res = [
				'code' => 1,
				'msg' => '新闻id不存在',
				'body' => ['sendStatus' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}
		// 判断评论内容是否为空
		if ('' === $content)
		{
			$res = [
				'code' => 1,
				'msg' => '评论内容为空',
				'body' => ['sendStatus' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		$arr['article_id'] = $news_id;	
		$arr['member_id'] = $user_id;
		$arr['content'] = $content;

		$insert_res = Db::name('hd_comment')->insert($arr, false, true);
			
		// 判断发表评论是否成功
		if ($insert_res)
		{
			Db::name('hd_news')->where('id', $param['news_id'])->setInc('pl_num');
			$res = [
				'code' => 1,
				'msg' => '操作成功',
				'body' => ['sendStatus' => '1',
							'comment_id' => $insert_res]
			];
		}
		return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	// 得到专题新闻详情
	public function getTopicNewsDetail()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => ['news' => 0]
		];

		// 接收参数
		$param = request()->param();

		$param['news_id'] = isset($param['news_id']) ? $param['news_id'] : 0;
		$param['cur_user_id'] = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;
		$m_id_code = isset($param['m_id_code']) ? $param['m_id_code'] : 0;

		// 如果手机标识码存在
		if ($m_id_code)
		{
			$common = new Common();
			$common->read(1, $m_id_code, $param['news_id']);
		}
 
		if (empty($param['news_id']))
		{
			$res = [
				'code' => 1,
				'msg' => '新闻id不存在',
				'body' => ['news' => 0]
			];	
			return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		$news = Db::name('hd_article_topic n')->field('n.id as news_id, n.title, n.content, n.read_num, n.add_time, m.nickname, m.headimg, n.audio_path')->
		join('admin_msg m', 'm.id = n.member_id')->
		where('n.id', $param['news_id'])->find();	

		// 得到专题新闻的总评论数
		$comment_num = Db::name('hd_topic_news_comment')->where('news_id', $param['news_id'])->count();
		$news['comment_num'] = $comment_num;
			
		$news_id = $news['news_id'];

		// 得到上一篇新闻和下一篇新闻
		$before_news = Db::name('hd_article_topic n')->field('n.id as news_id, n.title, n.thumb as pic1, n.read_num, n.pl_num, c.name')->
		join('hd_topic_cate c', 'c.id = n.topic_id')->
		where("n.id < $news_id")->order('n.id desc')->find();
		
		$next_news = Db::name('hd_article_topic n')->field('n.id as news_id, n.title, n.thumb as pic1, n.read_num, n.pl_num, c.name')->
		join('hd_topic_cate c', 'c.id = n.topic_id')->
		where("n.id > $news_id")->limit(0, 1)->find();


		$res['body']['middle_news'][] = $before_news;
		$res['body']['middle_news'][] = $next_news;
		$res['body']['news'] = $news;
		// 解决第一篇没有上一篇和最后一篇没有下一篇新闻的问题
		if (empty($res['body']['middle_news'][0]))
		{
			$last_news = Db::name('hd_article_topic n')->field('n.id as news_id, n.title, n.thumb as pic1, n.read_num, n.pl_num, c.name')->
			join('hd_topic_cate c', 'c.id = n.topic_id')->order('n.id desc')->find();
			$res['body']['middle_news'][0] = $last_news;
		}
		if (empty($res['body']['middle_news'][1]))
		{
			$first_news = Db::name('hd_article_topic n')->field('n.id as news_id, n.title, n.thumb as pic1, n.read_num, n.pl_num, c.name')->
			join('hd_topic_cate c', 'c.id = n.topic_id')->order('n.id')->find();
			$res['body']['middle_news'][1] = $first_news;
		}

		// 得到收藏状态
		if (empty($param['cur_user_id']))
		{
			$res['body']['news']['collect_status'] = 0;
		}
		else
		{
			$res['body']['news']['collect_status'] = $this->getCollectStatus(2, $news_id, $param['cur_user_id']);
		}



		return json($res);
	}

	// 得到专题新闻的评论
	public function getTopicNewsComment()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => ['comment' => 0]
		];	

		$param = request()->param();
		$param['com_page'] = isset($param['com_page']) ? $param['com_page'] : 1;
		$param['news_id'] = isset($param['news_id']) ? $param['news_id'] : 0;
		$param['cur_user_id'] = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;

		// 定义评论条数
		define('COMPAGESIZE', 10);
		$offset = ($param['com_page'] - 1) * COMPAGESIZE;

		// 得到该专题新闻下的评论，根据发表时间倒序 
		$comment = Db::name('hd_topic_news_comment c')->field('c.id as comment_id, c.content, c.add_time, c.favor_num, m.nickname, m.headimg')->
		join('mall_member m', 'm.id = c.user_id')->
		order('add_time desc')->limit($offset, COMPAGESIZE)->where('news_id', $param['news_id'])->select();

		// 处理点赞状态，先判断用户是否登录
		if (empty($param['cur_user_id']))
		{
			// 用户未登录的话，那么点赞状态都是0
			foreach ($comment as $k => $v)
			{
				$comment[$k]['favor_status'] = 0;
			}
		}
		else // 用户已登录 
		{
			foreach ($comment as $k => $v)
			{
				$favor_status = $this->getFavorStatus(6, $param['cur_user_id'], $v['comment_id']);
				$comment[$k]['favor_status'] = $favor_status;
			}
		}
		$res['body']['comment_num'] = count($comment);
		$res['body']['comment'] = $comment;

		if ($res['body']['comment_num'] < COMPAGESIZE)
		{
			$res['body']['noMoreComment'] = 1;
		} 
		$res['body']['com_page'] = $param['com_page'] + 1;

		return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	// 对专题新闻发表评论
	public function sendTopicNewsComment()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => ['sendStatus' => 0]
		];	

		// 接收参数
		$param = request()->param();

		$param['user_id'] = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;
		unset($param['cur_user_id']);
		$param['news_id'] = isset($param['news_id']) ? $param['news_id'] : 0;
		$param['content'] = isset($param['content']) ? $param['content'] : '';

		// 判断用户id、新闻id、内容是否为空
		if (empty($param['user_id']))
		{
			$res = [
				'code' => 1,
				'msg' => '当前用户id不存在',
				'body' => ['sendStatus' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}
		if (empty($param['news_id']))
		{
			$res = [
				'code' => 1,
				'msg' => '新闻id不存在',
				'body' => ['sendStatus' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}
		if ('' === $param['content'])
		{
			$res = [
				'code' => 1,
				'msg' => '内容为空',
				'body' => ['sendStatus' => 0]
			];
			return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}


		$ins_res = Db::name('hd_topic_news_comment')->insert($param, false, true);
		if ($ins_res)
		{
			// 增加专题新闻的评论量
			Db::name('hd_article_topic')->where('id', $param['news_id'])->setInc('pl_num');
			$res = [
				'code' => 1,
				'msg' => '发送成功',
				'body' => ['sendStatus' => 1,
							'comment_id' => $ins_res]
			];
		}
		return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	// 得到点赞状态
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
	* 得到收藏状态
	* @param int $type 收藏类型
	* @param int $post_id 被收藏的东西的id
	* @param int $cur_user_id 当前用户id
	* @return bool 0或1  收藏状态
	*/
	public function getCollectStatus($type, $post_id, $cur_user_id)
	{
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

	// 将普通新闻评论数弄正确
	public function newsCommentTrue()
	{
		set_time_limit(0);	
		$news_id = Db::name('hd_news')->field('id')->select();

		foreach ($news_id as &$v)
		{
			$comment_num = Db::name('hd_comment')->where('article_id', $v['id'])->count();
			Db::name('hd_news')->where('id', $v['id'])->update(['pl_num' => $comment_num]);
		}
	}

	// 得到惠声惠影新闻详情
	public function getVideoDetail()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => ['news' => '']
		];

		// 接收参数 
		$param = request()->param();

		$news_id = isset($param['news_id']) ? $param['news_id'] : 0;
		$cur_user_id = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;
		$m_id_code = isset($param['m_id_code']) ? $param['m_id_code'] : 0;

		// 如果手机标识码存在
		if ($m_id_code)
		{
			$common = new Common();
			$common->read(2, $m_id_code, $news_id);
		}

		// 判断新闻id是否存在
		if (empty($news_id))
		{
			$res = [
				'code' => 1,
				'msg' => '新闻id不存在',
				'body' => ['news' => '']
			];
			return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		// 当前这篇新闻
		$news = Db::name('hd_topic_video')->field('id as news_id, video_name as title, video_info as content, read_num, pl_num as comment_num, img_path, video_path, add_time, duration')->
		where('id', $news_id)->find();

		$common_obj = new Common();
		$collect_status = $common_obj->getCollectStatus(6, $news_id, $cur_user_id);

		$news['collect_status'] = $collect_status;
		// 将时长转换为秒数
		$news['duration'] = transferSecond($news['duration']);

		// 得到相关推荐新闻，下面三篇
		$news_model = new TopicNewsModel();
		$other_news = $news_model->getRelatedNews($news_id);

		$res['body']['news'] = $news;
		$res['body']['other_news'] = $other_news;
	
		return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);		
 	}	


 	// 对惠声惠影视频发送评论
 	public function sendVideoComment()
 	{
		$topic_news_model = new TopicNewsModel();

		$res_json = $topic_news_model->sendVideoComment(); 		

		echo $res_json;
 	}	

 	// 得到惠声惠影视频的评论
 	public function getVideoComment()
 	{
		$topic_news_model = new TopicNewsModel();
 		$res_json = $topic_news_model->getVideoComment();
 		echo $res_json;
 	}

	// 搜索全部
 	public function searchAll($content)
 	{	
 		$news_model = new NewsModel();
 		return $news_model->getSearchRes($content);
 	}
}