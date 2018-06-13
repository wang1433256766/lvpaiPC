<?php



namespace app\admin\model;



use think\Model;

use think\Db;



class CommentModel extends Model

{	




	// 得到咕咕评论列表

	public function getGuCommentList($where, $offset, $limit)

	{

		$where['ban'] = 0;



		$comment = Db::name('gugu_comment c')->field('c.id, c.info, c.favor_num, c.add_time, m.nickname')->

		join('mall_member m', 'm.id = c.member_id')->

		order('add_time desc')->limit($offset, $limit)->where($where)->select();



		foreach ($comment as $v)

		{

			$v['info'] = $this->filterLength($v['info']);



			$id = $v['id'];

			$favor_num = $v['favor_num'];



			$v['favor_num'] = "<input type='text' value='$favor_num' size='3' onblur='changeGuCommentFavorNum($id, this.value)'>";

		}
		return $comment;

	}



	// 得到咕咕评论数量

	public function getGuCommentNum()

	{

		return Db::name('gugu_comment')->count();

	}



	// 得到封禁的咕咕评论数量

	public function getGuBanNum()

	{

		return Db::name('gugu_comment')->where('ban', 1)->count();

	}



	// 得到封禁的咕咕评论列表

	public function getGuBanList($where, $offset, $limit)

	{

		$where['ban'] = 1;



		$comment = Db::name('gugu_comment c')->field('c.id, c.info, c.favor_num, c.ban_time, m.nickname')->

		join('mall_member m', 'm.id = c.member_id')->

		order('ban_time desc')->limit($offset, $limit)->where($where)->select();



		foreach ($comment as &$v)

		{

			$v['info'] = $this->filterLength($v['info']);

		}

		return $comment;

	}

	// 过滤评论的内容，对内容做一个长度的判断
	public function filterLength($content)
	{
		$length = mb_strlen($content);
		if (38 < $length)
		{
			return mb_substr($content, 0, 38,'utf-8') . '...';
		}
		else
		{
			return $content;
		}
	}
//商品评论
	public function getGoodsCommentList($where, $offset, $limit)

	{
		//$where['status'] = 0;

		$comment = Db::name('shop_spot_comment a')->

		join('mall_member b', 'a.member_id=b.id')->

		join('spot_order c','a.order_id= c.id')->

		field('a.id, c.order_sn, a.content, a.add_time, b.nickname, c.ticket_name')->

		order('add_time desc')->limit($offset, $limit)->where('a.status',0)->select();

		foreach ($comment as $k=>$v)

		{

			$comment[$k]['content'] = $this->filterLength($v['content']);

			$comment[$k]['add_time']=date('Y-m-d',$v['add_time']);

			//$id = $v['id'];

		}
		return $comment;


	}	
//被禁的商品评论
public function GoodsBanList($where, $offset, $limit)

	{
		//$where['status'] = 0;

		$comment = Db::name('shop_spot_comment a')->

		join('mall_member b', 'a.member_id=b.id')->

		join('spot_order c','a.order_id= c.id')->

		field('a.id, c.order_sn, a.content, a.add_time, b.nickname, c.ticket_name')->

		order('add_time desc')->limit($offset, $limit)->where('a.status',1)->select();

		foreach ($comment as $k=>$v)

		{

			$comment[$k]['content'] = $this->filterLength($v['content']);

			$comment[$k]['add_time']=date('Y-m-d',$v['add_time']);

			//$id = $v['id'];

		}
		return $comment;


	}		

}	