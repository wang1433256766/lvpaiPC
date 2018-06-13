<?php

namespace app\api\controller;

use think\Db;
use think\Log;
use think\Controller;

class Homepage extends Controller
{
	/*
		得到首页数据，包括轮播图、资讯、每周推荐、资讯、游记、问答
	*/
	public function getHomepageData()
	{
		$res = [
			'code' => 0,
			'msg' => '操作失败',
			'body' => []
		];

		// 得到轮播图
		// 得到景区轮播
		$spot = Db::name('shop_spot')->field('id as banner_id, thumb, rota_sort')->where(['rota' => 1, 'status' => 1])->order('rota_sort')->select();


		// 得到活动轮播	
		$activity = Db::name('activity')->field('activity_id as banner_id, img_path, rota_sort')->where(['rota' => 1, 'status' => 1])->order('rota_sort')->select();	


		if (empty($activity) && empty($spot))
		{
			$banner_img = 0;
		}
		else if (empty($activity) && !empty($spot))
		{	
			$banner_img['spot'] = $spot;
		}
		else if (!empty($activity) && empty($spot))
		{
			$banner_img['activity'] = $activity;
		}
		else
		{
			$banner_img['spot'] = $spot;
			$banner_img['activity'] = $activity; 
		}
		

		$r_travels = Db::name('travels t')->field('t.id as travels_id, t.title, t.add_time, t.pic1, t.address, t.read_num, t.reply_num, m.nickname, m.headimg as headimgurl')->
		join('mall_member m', 'm.id = t.user_id')->
		order('t.favor_num desc')->limit(3)->select();


		for ($i=0; $i<count($r_travels); $i++)
		{	
			$time = substr($r_travels[$i]['add_time'], 5, 5);
			$r_travels[$i]['add_time'] = str_replace('-', '|', $time);
		}


		// 判断是否得到推荐游记
		if (is_array($r_travels) && is_array($banner_img))
		{
			$res = [	
				'code' => 1,
				'msg' => '操作成功',
				'body' => ['banner_img' => $banner_img,
							'r_travels' => $r_travels]
			];
		}

		$new_travels = Db::name('travels t')->field('t.id as travels_id, t.title, t.pic1, t.read_num, t.reply_num, t.address, m.nickname, m.headimg as headimgurl')->
		join('mall_member m', 'm.id = t.user_id')->
		order('t.add_time desc')->limit(3)->select();
		
		// 判断是否得到新游记
		if ($new_travels)
		{
			// 判断$res数组前面是否得到内容
			if (1 == $res['code'])
			{
				$res['body']['new_travels'] = $new_travels;
			}
			else
			{
				$res = [
					'code' => 1,
					'msg' => '操作成功',
					'body' => ['new_travels' => $new_travels]
				];
			}
		}



		// 得到发表时间最新的3个问题
		$question = Db::name('qa_question')->field('id as q_id, title, img, answer_num, read_num')->order('add_time desc')->limit(3)->select();



		for ($i=0; $i<count($question); $i++)
		{
			$answer = Db::name('qa_answer a')->field('a.id as a_id, a.content, m.nickname, m.headimg as headimgurl')->
			join('mall_member m', 'm.id = a.user_id')->
			order('a.favor_num desc')->where('a.question_id', $question[$i]['q_id'])->find();

			$question[$i]['a_id'] = $answer['a_id'];
			$question[$i]['content'] = $answer['content'];
			$question[$i]['nickname'] = $answer['nickname'];
			$question[$i]['headimgurl'] = $answer['headimgurl'];
		}

		

		// 判断是否得到问答
		if ($question)
		{
			// 判断前面是否得到内容
			if (1 == $res['code'])
			{
				$res['body']['qa'] = $question;
			}
			else
			{
				$res = [
					'code' => 1,
					'msg' => '操作成功',
					'body' => ['qa' => $question]
				];
			}
		}

		

		// 得到头条和资讯
		// 得到头条新闻
		$headNews = Db::name('hd_news')->field('id, title, pic1')->where('cate_id', 8)->find();

		if ($headNews)
		{
			$headNews['name'] = '会读头条';
			if (1 == $res['code'])
			{
				$res['body']['head_news'] = $headNews; 
			}
			else
			{
				$res = [
					'code' => 1,
					'msg' => '操作成功',
					'body' => ['head_news' => $headNews]
				];
			}
		}

		// 得到旅游新闻
		$travelNews = Db::name('hd_news n')->field('n.id, title, pic1, name, pl_num, n.read_num, n.content')->
		join('too_hd_article_cate c', "c.id = n.cate_id")->
		where('cate_id', 5)->order('add_time desc')->find();

		$news_arr[] = $travelNews;

		// 得到文化新闻
		$cultrueNews = Db::name('hd_news n')->field('n.id, title, pic1, name, pl_num, n.read_num, n.content')->
		join('too_hd_article_cate c', "c.id = n.cate_id")->
		where('cate_id', 18)->order('add_time desc')->find();

		$news_arr[] = $cultrueNews;

		// 得到科技新闻
		$scienceNews = Db::name('hd_news n')->field('n.id, title, pic1, name, pl_num, n.read_num, n.content')->
		join('too_hd_article_cate c', "c.id = n.cate_id")->
		where('cate_id', 4)->order('add_time desc')->find();

		$news_arr[] = $scienceNews;

		// 处理新闻的内容
		foreach ($news_arr as $k => $v)
		{
			if ('会读头条' != $v['name'])
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
				
				$news_arr[$k]['content'] = $content . '...';
			}
		}

		// 得到专题新闻
		$topicNews = Db::name('hd_article_topic')->field('too_hd_article_topic.id, title, thumb, read_num, pl_num')->order('add_time desc')->find();
		$topicNews['name'] = '专题';
		$news_arr[] = $topicNews;


		if ($news_arr)
		{
			if (1 == $res['code'])
			{
				$res['body']['news'] = $news_arr;
			}
			else
			{
				$res = [
					'code' => 1,
					'msg' => '操作失败',
					'body' => ['news' => $news_arr]
				];
			}
		}

		return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}	
}
