<?php

namespace app\api\controller;

use think\Controller;

use think\Db;

use think\Request;

use app\api\model\GuModel;

use think\Log;





/*

  咕咕详情页面

 */

class Gugu extends Controller

{

	

	// 得到咕咕页面

	public function getGuData()

	{

		$res = [

			'code' => 1,

			'msg' => '操作成功',

			'body' => ['gugu' => []]	

		];	



		// 页面大小

		define('PAGESIZE', 10);



		// 接收参数

		$param = request()->param();



		// 当前页面、页面类型、当前用户id

		$page = isset($param['page']) ? $param['page'] : 1;

		$index = isset($param['index']) ? $param['index'] : 1;

		$user_id = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;

		//我的咕咕参数
		  if(isset($param['member_id']))
		     {
		        	$where="a.member_id=".$param['member_id'];
			$user_id=$param['member_id'];
		      }
                                      else{
                                                     $where='';
                                                 }

		

		// 偏移量

		$offset = ($page - 1) * PAGESIZE;



		$gu_m = new GuModel();



		$gu = $gu_m->getGu($index, $offset, PAGESIZE, $user_id,$where);

		if (0 == $user_id)
		{
			foreach ($gu as $k => $v)
			{
				$gu[$k]['belong_me'] = 0;
			}
		}
		else
		{
			foreach ($gu as $k => $v)
			{
				if ($user_id == $v['member_id'])
				{
					$gu[$k]['belong_me'] = 1;
				}
				else
				{
					$gu[$k]['belong_me'] = 0;
				}
			}
		}

		if ($gu)

		{

			$res = [

				'code' => 1,

				'msg' => '操作成功',

				'body' => ['gugu' => $gu,

							'page' => $page + 1]

			];



			// 判断当前页面数据是否达到最大值

			if (count($res['body']['gugu']) < PAGESIZE)

			{

				$res['body']['noMoreData'] = 1;

			}

		}

		else

		{

			$res['body']['noMoreData'] = 1;	

		}



		return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

	}



	// 对咕咕文章发表评论

	public function sendGuComment()

	{

		$res = [

			'code' => 0,

			'msg' => '操作失败',

			'body' => []

		];	



		// 接收参数

		$param = request()->param();

		$comment['member_id'] = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;

		$comment['gugu_id'] = isset($param['article_id']) ? $param['article_id'] : 0;

		$comment['info'] = isset($param['content']) ? $param['content'] : '';



		// 判断评论内容是否为空

		if ('' === $comment['info'])

		{

			$res = [

				'code' => 1,

				'msg' => '评论内容为空',

				'code' => ['sendStatus' => 0]

			];

			return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		}



		// 判断评论人id是否为空

		if (empty($comment['member_id']))

		{

			$res = [

				'code' => 1,

				'msg' => '评论人id为空',

				'code' => ['sendStatus' => 0]

			];

			return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		}



		$ins_res = Db::name('gugu_comment')->insert($comment, false, true);

		// 判断发表评论是否成功

		if ($ins_res)

		{

			Db::name('gugu_article')->where('id', $comment['gugu_id'])->setInc('comment_num');

			$res = [

				'code' => 1,

				'msg' => '操作成功',

				'body' => ['sendStatus' => 1,

							'comment_id' => $ins_res]

			];

		}
          //消息提醒表插入数据
        $msg['sender_id']=$comment['member_id'] ;
        $msg['receive_id']=db::name('gugu_article')->where('id',$comment['gugu_id'])->value('member_id'); 
        $msg['msg_content']=$comment['info'];
        $a=db::name('mall_member')->where('id',$comment['member_id'])->value('nickname');
        $msg['msg_title']=$a.'评论了你的咕咕';
        $msg['msg_time']=date('Y-m-d H:i:s',time());
        $msg['msg_type']=2;
        $msg['msg_status']=0;
        $result=db::name('member_message')->insert($msg);

		return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

	}



	// 发表咕咕

	public function sendGu()

	{

		$res = [

			'code' => 1,

			'msg' => '操作成功',

			'body' => ['sendStatus' => 0]

		];



		// 接受参数

		$param = request()->param();



		$param['member_id'] = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;

		unset($param['cur_user_id']);

		$param['address'] = isset($param['address']) ? $param['address'] : '';



		$type = isset($param['type']) ? $param['type'] : '';

		unset($param['type']);

		$param['gugu_content'] = isset($param['content']) ? $param['content'] : '';

		unset($param['content']);

		// 判断用户是否登录

		if (empty($param['member_id']))

		{

			$res = [

				'code' => 1,

				'msg' => '用户id不存在',

				'body' => ['sendStatus' => 0]

			];

			return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		}



		// 判断咕咕的类型是否存在

		if ('' === $type)

		{

			$res = [

				'code' => 1,

				'msg' => '发表的咕咕类型不存在',

				'body' => ['sendStatus' => 0]

			];

			return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		}



		// 咕咕类型有3种，0：文字，1：文字+图片，2：文字+视频

		$gu = new GuModel();

		if (0 == $type)

		{

			if ('' ===  $param['gugu_content'])

			{

				$res = [

					'code' => 1,

					'msg' => '发表内容为空',

					'body' => ['sendStatus' => 0]

				];

				return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

			}

			$sendStatus = $gu->sendWordGu($param);

		}

		else if (1 == $type)

		{

			$sendStatus = $gu->sendPicGu($param);

		}

		else  // 发表视频+文字

		{

			$gu_obj = new GuModel();



			// 获取视频文件

		    $fillName = $_FILES['file']['name'];



			$video_destination = $this->getVideoPath();		

			// 获得图片存放路径
			$img_destination = 'uploads/gugu/' . $gu_obj->getFilename();



			move_uploaded_file($_FILES["file"]["tmp_name"], $video_destination);


			// 获得视频第一帧作为封面图
			exec("ffmpeg -i ".$video_destination." -y -f mjpeg -ss 1 -t 0.001 -s 320x240 ". $img_destination);



			$img_destination = $this->rotate($img_destination);


			// 获取视频时长
			$duration =  exec("ffmpeg -i ".$video_destination." 2>&1 | grep 'Duration' | cut -d ' ' -f 4 | sed s/,//");//总长度

			

			$param['video_path'] = 'http://zhlsfnoc.com/' . $video_destination;

			$param['cover_img'] = 'http://zhlsfnoc.com/' . $img_destination;

			$param['duration'] = $duration;	



			$sendStatus = Db::name('gugu_article')->insert($param);

		}


		// 当咕咕发表成功时，判断是否完成每日任务 
		if ($sendStatus)
			{
				// 任务条件
				$task_cond['user_id'] = $param['member_id'];
				$task_cond['type'] = 2;

				// 查询任务表
				$task = Db::name('member_task')
						->field('id, finish_time')
						->where($task_cond)
						->find();

				// 判断记录是否为空
				if (empty($task))
				{
					$task_cond['finish_time'] = time();

					$ins_res = Db::name('member_task')->insert($task_cond);
					if ($ins_res)
					{
						Db::name('mall_member')->where('id', $task_cond['user_id'])->setInc('score', 5);
					}
				}
				else // 当记录不为空的时候，判断完成任务时间
				{
					// 得到零点时间
					$zerotime = strtotime(date("Y-m-d"));

					// 如果已经到了新的一天，那么更新任务完成时间
					if ($task['finish_time'] < $zerotime)
					{
						$upd_param['finish_time'] = time();

						$upd_res = Db::name('member_task')->where('id', $task['id'])->update($upd_param);

						if ($upd_res)
						{
							Db::name('mall_member')->where('id', $param['member_id'])->setInc('score', 5);
						}
					}
				}
					
				$res = [

					'code' => 1,

					'msg' => '发表成功',

					'body' => ['sendStatus' => 1]

				];

			}	



		return json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

	}



	// 视频走一波

	public function ha()

	{

		ini_set('memory_limit', '500M');



		dump(file_exists('01.mp4'));	



		$arr = [

			6, file_get_contents('1.jpg'), '阿西坝', '长沙'

		];



		$str_data = implode('#####', $arr);



		$ch = curl_init();	

		// www.zhlsfnoc.com

		$url = 'http://www.zhlsfnoc.com/api/gugu/test';

		curl_setopt($ch, CURLOPT_URL, $url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		curl_setopt($ch, CURLOPT_HEADER, 0);

		curl_setopt($ch, CURLOPT_POST, true);

		curl_setopt($ch, CURLOPT_POSTFIELDS, $str_data);

		// curl_setopt($ch, CURLOPT_UPLOAD, true);



		$res = curl_exec($ch);

		curl_close($ch);

		dump($res);

	}				



	public function test()

	{

		$param = request()->param();

		dump($param);

	}



	public function access()

	{

		dump($_FILES);

	}



	public function getVideoPath()

	{

		// 先判断目录是否存在

		$video_path = 'uploads/gugu/video/' . date('Ymd');



		if (is_dir($video_path))

		{

			return $video_path . '/' . $this->getUuid() . '.mp4';

		}

		else

		{

			mkdir($video_path);

			return $video_path . '/' . $this->getUuid() . '.mp4';

		}

	}



	// 得到唯一id

	public function getUuid()

	{

		return md5(uniqid() . mt_rand(0, 99999) . microtime(true));

	}



	// 将图片旋转一下

	public function rotate($filename)

	{

		// File and rotation

		$degrees = -90;



		// Content type

		header('Content-type: image/jpeg');



		// Load

		$source = imagecreatefromjpeg($filename);



		// Rotate

		$rotate = imagerotate($source, $degrees, 0);



		unlink($filename);

		// Output

		$res = imagejpeg($rotate, $filename);

		return $filename;

	}



	// 咕咕详情页面

	public function getGuDetail()

	{

		$gu_model = new GuModel();

		$res_json = $gu_model->getGuDetail();



		echo $res_json;

	}



	// 得到咕咕的评论

	public function getGuComment()

	{

		$gu_model = new GuModel();

		$res_json = $gu_model->getGuComment();

		echo $res_json;

 	}



 	// 接收文件

 	public function reciveFile()

 	{

 		header('Content-type: text/json; charset=UTF-8' );

 		$res = move_uploaded_file($_FILES['file']['tmp_name'], '999.mp4');



 		dump($res);

 	}



 	public function sendFile()

 	{

 		$ch = curl_init();



 		$url = 'zhlsfnoc.com/api/gugu/reciveFile';

 		$file = file_get_contents('02.mp4');

 		// $arr

 		curl_setopt($ch, CURLOPT_URL, $url);

 		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

 		curl_setopt($ch, CURLOPT_POST, true);

 		curl_setopt($ch, CURLOPT_POSTFIELDS, $arr);

 	}



 	public function trueCommentNum()

 	{

 		set_time_limit(0);

 		$gu_id = Db::name('gugu_article')->field('id')->select();



		foreach ($gu_id as $k => $v)

		{

			$comment_num = Db::name('gugu_comment')->where('gugu_id', $v['id'])->count();

			Db::name('gugu_article')->where('id', $v['id'])->update(['comment_num' => $comment_num]);

		} 		

 	}

}