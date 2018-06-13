<?php

namespace app\api\controller;

use think\Db;
use app\api\model\TravelModel;
use app\api\controller\Common;

class Travel
{
	// 得到上面五个轮播游记，根据发表时间来倒序得到
	public function getNewTravel()
	{	
		$res = [
			'code' => 0,
			'msg' => '操作失败',
			'body' => []
		];

		// 接收参数
		$travel = Db::name('travels t')->field('t.travels_id, t.title, t.add_time, t.pic1, m.nickname, m.headimgurl, t.address')->
		join('hd_member m', 'm.id = t.user_id')
		->order('add_time desc')->limit(5)->select();

		for ($i=0; $i<count($travel); $i++)
		{
			$time = substr($travel[$i]['add_time'], 5, 5);
			$travel[$i]['add_time'] = str_replace('-', '|', $time);
		}	
		dump($travel);
	}	


	// 得到游记详情
	public function getTravelDetail()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => []
		];	

		// 接收参数
		$param = request()->param();

		$travel_id = isset($param['travel_id']) ? $param['travel_id'] : 0;
		$cur_user_id = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;
		$m_id_code = isset($param['m_id_code']) ? $param['m_id_code'] : 0;

		if (0 == $travel_id)
		{
			$res['msg'] = '游记id不存在';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}

	 	// 得到该篇游记
		$travel = Db::name('travels t')->field('t.id as travel_id, t.title, t.address, t.add_time, t.read_num, t.reply_num, m.nickname, m.id as user_id, m.headimg, t.content, t.play_time, t.play_num, t.person_price, t.trip_days')->
		join('mall_member m', 'm.id = t.user_id')->
		where('t.id', $travel_id)->find();
		$travel['content'] = str_replace('#', '<', $travel['content']);

		// 得到该用户游记数量和粉丝数量
		$travel_num = Db::name('travels')->where('user_id', $travel['user_id'])->count();
		$fans_num = Db::name('hd_fans')->where('user_id', $travel['user_id'])->count();

		$user_msg['user_id'] = $travel['user_id'];
		$user_msg['travel_num'] = $travel_num;
		$user_msg['fans_num'] = $fans_num;
		$user_msg['nickname'] = $travel['nickname'];
		$user_msg['headimg'] = $travel['headimg'];

		// unset($travel['user_id']);
		unset($travel['nickname']);
		unset($travel['headimg']);

		// 得到用户的关注状态
		$common = new Common();
		$follow_status = $common->getFollowStatus($cur_user_id, $user_msg['user_id']);
		$user_msg['follow_status'] = $follow_status;

		// 得到点赞状态
		$favor_status = $common->getFavorStatus(9, $cur_user_id, $travel['travel_id']);
		$travel['favor_status'] = $favor_status;

		$common = new Common();
		$travel['belong_me'] = $common->getBelongStatus($travel['user_id'], $cur_user_id);
		// 调用阅读接口
		$common->read(4, $m_id_code, $travel['travel_id']);

		$res['body']['travel'] = $travel;
		$res['body']['user'] = $user_msg;

		return json($res);
	}

	// 对游记发表评论
	public function sendComment()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => ['sendStatus' => 0]
		];

		// 接收参数
		$param = request()->param();
		
		$content = isset($param['content']) ? $param['content'] : '';
		$cur_user_id = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;
		$travel_id = isset($param['travel_id']) ? $param['travel_id'] : 0;

		if ('' === $content)
		{
			$res['msg'] = '内容为空';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}
		if (0 == $cur_user_id)
		{
			$res['msg'] = '当前用户id不存在';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}
		if (0 == $travel_id)
		{
			$res['msg'] = '评论的游记id不存在';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}

		$arr = [];
		$arr['member_id'] = $cur_user_id;
		$arr['travels_id'] = $travel_id;
		$arr['content'] = $content;
		$ins_res = Db::name('travels_comment')->insert($arr);

		// 评论成功，该游记评论数量+1
		if ($ins_res)
		{
			Db::name('travels')->where('id', $travel_id)->setInc('reply_num');
			$res['msg'] = '评论成功';
			$res['body']['sendStatus'] = 1;
		}
		return json_encode($res, JSON_UNESCAPED_UNICODE);
	}

	// 获得游记评论列表
	public function getComments()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => []
		];

		// 接收参数 
		$param = request()->param();

		$travel_id = isset($param['travel_id']) ? $param['travel_id'] : 0;
		$cur_user_id = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;

		define('COMPAGESIZE', 10);
		$com_page = isset($param['com_page']) ? $param['com_page'] : 1;

		$offset = ($com_page - 1) * COMPAGESIZE;

		if (0 == $travel_id)
		{
			$res['msg'] = '游记id不存在';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}

		$comments = Db::name('travels_comment c')->field('c.id as comment_id, c.member_id, c.favor_num, c.add_time, c.content, m.nickname, m.headimg')->
		join('mall_member m', 'm.id = c.member_id')->
		where('c.travels_id', $travel_id)->limit($offset, COMPAGESIZE)->select();

		$common = new Common();
		// 处理时间和点赞状态
		foreach ($comments as $k => $v)
		{
			$comments[$k]['add_time'] = handleTime($v['add_time']);
			$comments[$k]['favor_status'] = $common->getFavorStatus(8, $cur_user_id, $v['comment_id']);
		}
		$res['body']['comment'] = $comments;

		if (count($comments) < COMPAGESIZE)
		{
			$res['body']['noMoreComment'] = 1;
		}
		$res['body']['com_page'] = $com_page + 1;

		return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	// 游记发现，根据地址来找到目的地
	public function travelFind()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => []
		];

		// 接收参数
		$param = request()->param();

		$travel_id = isset($param['travel_id']) ? $param['travel_id'] : 0;

		if (0 == $travel_id)
		{
			$res['msg'] = '游记id不存在';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}
		
		// 根据游记中的地址来搜索景点
		$address = Db::name('travels')->where('id', $travel_id)->value('address');

		$spotInfo = Db::name('shop_spot')
					->field('id as spot_id,title,desc,thumb,shop_price,market_price')
					->where('title','like', "%$address%")
					->limit(2)
					->select();
		$spotInfo = getTodayOrder($spotInfo);
		foreach ($spotInfo as $k => $v)
		{
			$spotInfo[$k]['today'] = isset($v['today']) ? $v['today'] : 0;
		}
		$res['body']['spotInfo'] = $spotInfo;

		return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}


	// 得到游记数据
	public function getTravelData()
	{	
		$res = [
			'code' => 1,
			'msg' => '操作成功',
		];	

		// 页面大小
		define('PAGESIZE', 10);

		$travel_obj = new TravelModel();

		// 接收参数
		$param = request()->param();

		// 默认显示为最热，当点击的时候切换为最新
		$choose = isset($param['btn']) ? $param['btn'] : 'hot';

		$page = isset($param['page']) ? $param['page'] : 1;
		$offset = ($page - 1) * PAGESIZE;

		// 全部游记
		$all_travel = $travel_obj->getAllTravel($choose, $offset, PAGESIZE); 

		// 当第一页的时候，才会有轮播图
		if (1 == $page)
		{
			// 轮播图
			$new_travel = $travel_obj->getNewTravel();
			$res['body']['banner_img'] = $new_travel;
		}
		

		$res['body']['travel'] = $all_travel;
		$res['body']['page'] = $page + 1;

		if (count($all_travel) < PAGESIZE)
		{
			$res['body']['noMoreData'] = 1;
		}		
		
		return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
}