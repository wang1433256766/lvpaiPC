<?php

namespace app\admin\model;

use think\Model;
use think\Db;

class NewsRotaModel extends Model
{
	protected $table = 'too_hd_news';

	public function getNewsByWhere()
	{
		$news = Db::name('hd_news n')->field('n.id, n.title, n.username, n.read_num, n.pl_num, c.name, n.rota_sort, n.add_time')->
		join('hd_article_cate c', 'c.id = n.cate_id')->
		order('rota_sort')->where('n.base', 1)->select();

		foreach ($news as $k => $v)
		{
			$rota_sort = $v['rota_sort'];
			$id = $v['id'];
			$read_num = $v['read_num'];
			$pl_num = $v['pl_num'];
			$news[$k]['rota_sort'] = "<input type='text' value='$rota_sort' size='3' onblur = 'changeSort($id, this.value)' id='$id'>";

			$news[$k]['read_num'] = "<input type='text' value='$read_num' size='3' onblur = 'changeClicks($id, this.value)'>";

			$news[$k]['pl_num'] = "<input text='type' value='$pl_num' size='3' onblur = 'changePl($id, this.value)'>";
		}

		return $news;
	}

	public function getAllNews($where)
	{
		return $this->where('base', 1)->count();
	}

	// 取消新闻轮播
	public function cancelRota($id)
	{
	    try{
            $this->where('id', $id)->update(['base' => 0]);
            return ['code' => 1, 'data' => '', 'msg' => '取消成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
	}
}