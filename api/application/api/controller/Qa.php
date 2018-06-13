<?php

namespace app\api\controller;

use think\Db;
use think\Log;
use think\Controller;

// 问答接口
class Qa extends Controller
{
	public function test()
	{
		$ch = curl_init();

		$arr['title'] = 'H5是什么？';	
		$arr['content'] = '听说H5好厉害';
		// $arr['img'] = config('img')[0] . ',' .  config('img')[1];

		curl_setopt($ch, CURLOPT_URL, 'http://zhlsfnoc.com/api/qa/sendQuestion?user_id=649847');
		// curl_setopt($ch, CURLOPT_URL, 'localhost/api/qa/access?user_id=100');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $arr);

		$res = curl_exec($ch);
		curl_close($ch);
		dump($res);
	}

	// 提出问题
	public function sendQuestion()
	{
		$res = [
			'code' => 0,
			'msg' => '操作失败',
			'body' => []
		];
		// 接收参数
		$param = request()->param();

		// 先判断是否有图片
		$img = isset($param['img']) ? $param['img'] : 0;
		if ($img)
		{
			$img = str_replace('data:image/jpeg;base64,', '', $img);

			$imgArr = explode(',', $img);

			// 处理图片
			for ($i=0; $i<count($imgArr); $i++)
			{
				$filename = md5(uniqid() . microtime(true) . mt_rand(0, 9999999)) . '.jpg';
				$data = str_replace('data:image/jpeg;base64,', '', $imgArr[$i]);
				$data = base64_decode($data);

				// 判断文件夹是否存在
				if (is_dir('uploads/qa/' . date('Ymd')))
				{	
					$put_res = file_put_contents('uploads/qa/' . date('Ymd') . '/' . $filename, $data);
					
				}
				else // 文件夹不存在
				{
					$cre_res = mkdir('uploads/qa/' . date('Ymd'));
					// 判断文件夹是否创建成功
					if ($cre_res)
					{	
						$put_res = file_put_contents('uploads/qa/' . date('Ymd') . '/' . $filename, $data);
					}
					else // 文件夹创建失败
					{
						$put_res = 0;
					}
				}

				$imgArr[$i] = 'http://zhlsfnoc.com/uploads/qa/' . date('Ymd') . '/' . $filename;
			}

			$img = implode($imgArr, ',');
			$param['img'] = $img;


			// 判断生成图片是否成功
			if ($put_res)
			{
				log::write('插入问题表');
				$ins_res = Db::name('qa_question')->insert($param);
			}
			else // 生成图片失败
			{
				$ins_res = Db::name('qa_question')->insert($param);
			}

		}
		else // 无图
		{
			// exit('1');
			$ins_res = Db::name('qa_question')->insert($param);
		}

		return $ins_res;	
	}

	// 发表回答
	public function sendAnswer()
	{
		$res = [
			'code' => 0,
			'msg' => '操作失败',
			'body' => []
		];	

		// 接收参数
		$param = request()->param();
		foreach ($param as $k => $v)
		{
			$filename = md5(uniqid() . microtime(true) . mt_rand(0, 99999)) . '.jpg';
			// 判断是图片还是文字
			if ('img' == substr($k, 0, 3))
			{
				$data = str_replace('data:image/jpeg;base64,', '', $v);
				$data = base64_decode($data);
				// 存放图片之前，先判断文件夹是否存在
				if (is_dir('uploads/qa/answer/' . date('Ymd')))
				{
					$put_res = file_put_contents('uploads/qa/answer/' . $filename, $data);
				}
				else // 文件夹不存在，所以创建文件夹
				{
					$mk_res = mkdir('uploads/qa/answer/' . date('Ymd'));

					// 判断创建文件夹是否成功
					if ($mk_res)
					{
						$put_res = file_put_contents('uploads/qa/answer/' . $filename, $data);
					}
					else // 创建文件夹失败
					{
						return '创建文件夹失败';
					}
				}	

				// 判断图片是否存入成功
				if ($put_res)
				{
					$arr[] = ROOT_PATH . 'public/uploads/qa/answer/' . $filename;
				}
				else // 图片存入失败
				{
					return '图片存入失败';
				}
			}
			else // 文字
			{	
				$arr[] = $v;
			}
		}
		$xiba['content'] = implode($arr, '\n');
		$xiba['user_id'] = 1;
		$xiba['question_id'] = 1;
		return Db::name('qa_answer')->insert($xiba);
	} 

	public function answer()	
	{
		$ch = curl_init();



		$arr['content'] = ['img1' => config('img')[0], 'str1' => 'abc', 'img2' => config('img')[1], 'str2' => 'zzz'];

		// dump($arr['content']);
			
		curl_setopt($ch, CURLOPT_URL, 'localhost/api/qa/sendAnswer?user_id=10');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $arr['content']);

		$res = curl_exec($ch);
		curl_close($ch);
		dump($res);
	}


	// 问答列表
	public function getQaData()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => []
		];

		define('PAGESIZE', 10);

		// 接收参数
		$param = request()->param();

		// 选择一种排序
		$choose = isset($param['btn']) ? $param['btn'] : 'hot';	
		$page = isset($param['page']) ? $param['page'] : '1';
		$user_id = isset($param['user_id']) ? $param['user_id'] : '0';

		// 只有第一页才需要获取用户信息
		if (1 == $page)
		{
			if (empty($user_id))
			{
				$res['body']['userInfo'] = [];
			}
			else
			{
				$answer = Db::name('qa_answer')->field('favor_num')->where('user_id', $user_id)->select();
				$favor_num = 0;
				foreach ($answer as $k => $v)
				{
					$favor_num += $v['favor_num'];
				}
				

				$question = Db::name('qa_question')->field('read_num')->where('user_id', $user_id)->select();
				$read_num = 0;
				foreach ($question as $k => $v)
				{
					$read_num += $v['read_num'];
				}

				$user = Db::name('mall_member')->field('id as user_id, nickname, headimg')->where('id', $user_id)->find();
				$user['read_num'] = $read_num;
				$user['favor_num'] = $favor_num;

				$res['body']['userInfo'] = $user;
			}
		}
		$offset = ($page - 1) * PAGESIZE;

		// 根据选择来拿到问题
		if ('hot' == $choose)
		{
			$question = Db::name('qa_question')->field('id as q_id, title, fans_num, read_num, answer_num, img, user_id')->limit($offset, PAGESIZE)->order('fans_num desc')->select();
		}
		else
		{
			$question = Db::name('qa_question')->field('id as q_id, title, fans_num, read_num, answer_num, img, user_id')->limit($offset, PAGESIZE)->order('add_time desc')->select();
		}

		// dump($question);
		// exit();
		// 拿到每一个问题下的最高点赞数量的回答
		for ($i=0; $i<count($question); $i++)
		{
			$answer = Db::name('qa_answer a')->field('a.id as a_id, a.content, a.favor_num, m.nickname, m.headimg')->
			join('mall_member m', 'a.user_id = m.id')->
			where('a.question_id', $question[$i]['q_id'])->order('favor_num desc')->find();
			
			$question[$i]['a_id'] = $answer['a_id'];
			$question[$i]['content'] = $answer['content'];
			$question[$i]['favor_num'] = $answer['favor_num'];
			$question[$i]['nickname'] = $answer['nickname'];
			$question[$i]['headimg'] = $answer['headimg'];

			$question[$i]['img'] = explode(',', $question[$i]['img'])[0];
 		}
		
		$res['body']['qa'] = $question;
		$res['body']['page'] = $page + 1;

		if (count($question) < PAGESIZE)
		{
			$res['body']['noMoreData'] = 1;
		}

		return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	// 问答详情
	public function qaDetail()
	{
		$res = [
			'code' => 0,
			'msg' => '操作失败',
			'body' => []	
		];

		// 接收参数
		$param = request()->param();

		$q_id = isset($param['q_id']) ? $param['q_id'] : 0;
		$a_id = isset($param['a_id']) ? $param['a_id'] : 0;
	
		if (empty($q_id))
		{
			$res = [
				'code' => 1,
				'msg' => '没有问题id',
				'body' => []
			];
			return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		}		

		$question = Db::name('qa_question q')->field('q.id as q_id, q.title, q.content, q.read_num, q.answer_num, q.fans_num, q.add_time, m.nickname, m.headimgurl')->
		join('hd_member m', 'm.id = q.user_id')->
		where('q.id', $param['q_id'])->find();
		dump($question);
		exit();

		// 有可能这个问题没有人回答，所以这里判断a_id
		if ($a_id)
		{
			// 因为在问答列表中已经判断该回答是最高赞回答，所以无需倒序
			$answer = Db::name('qa_answer a')->field('a.id as a_id, a.content as a_content, a.comment_num, a.favor_num, a.add_time, m.nickname, m.headimgurl')->
			join('hd_member m', 'm.id = a.user_id')->
			where('a.id', $param['a_id'])->find();

			// 判断是否为金牌回答
			$answer['gold'] = $answer['favor_num'] >= 50 ? 1 : 0;
		}
		

		$comment_num = Db::name('qa_answer')->field('comment_num')->where('question_id', $param['q_id'])->select();

		$comment_count = 0;
		foreach ($comment_num as $v)
		{
			foreach ($v as $value)
			{
				$comment_count += $value;
			}
		}
		$question['comment_count'] = $comment_count;

		$arr['question'] = $question;
		if ($a_id)
		{
			$arr['answer'] = $answer;
		}
		
		dump($arr);		
	}

	// 回答详情
	public function answerDetail()
	{
		$res = [
			'code' => 0,
			'msg' => '操作失败', 
			'body' => []
		];

		// 接收参数
		$param = request()->param();

		$question = Db::name('qa_question')->field('id as q_id, title')->where('id', $param['q_id'])->find();
		// dump($question);

		$answer = Db::name('qa_answer a')->field('a.id as a_id, a.favor_num, a.comment_num, a.content, m.nickname, m.headimgurl, a.add_time')->
		join('hd_member m', 'm.id = a.user_id')->
		where('a.id', $param['a_id'])->find();

		$answer['gold'] = $answer['favor_num'] >= 50 ? 1 : 0;

		$arr['question'] = $question;
		$arr['answer'] = $answer;
		dump($arr);
	}

	// 下一个回答
	public function nextAnswer()
	{
		$res = [
			'code' => 0,
			'msg' => '操作失败',
			'body' => []
		];

		// 接收参数
		$param = request()->param();

		$answer_ids = Db::name('qa_answer')->field('id')->where('question_id', $param['q_id'])->order('favor_num desc')->limit(2)->select();
		
		$next_id = $answer_ids[1]['id'];
		$q_id = $param['q_id'];

		$this->redirect("answerDetail?a_id=$next_id&q_id=$q_id");
	}


	public function asd()
	{
		$user = Db::name('mall_member')->where('id', 6)->find();

		dump($user);
	}
}