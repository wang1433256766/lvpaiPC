<?php
namespace app\admin\model;

use think\Model;
use think\Db;

class NewsModel extends Model
{
	protected $table = 'too_hd_news';
	 
	 /**
     * //查询所有新闻
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getNewsBy($where, $offset, $limit)
    {
        $news = $this->field("too_hd_news.*,too_hd_article_cate.name")
                    ->join("too_hd_article_cate","too_hd_article_cate.id=too_hd_news.cate_id")
                   ->where($where)->limit($offset, $limit)->order("base desc")->select();

        foreach ($news as $k => $v)
        {
        	$id = $v['id'];

        	$read_num = $v['read_num'];
        	$pl_num = $v['pl_num'];

        	$news[$k]['read_num'] = "<input type='text' size='3' value='$read_num' onblur='changeReadNum($id, this.value)'>";

        	$news[$k]['pl_num'] = "<input type='text' size='3' value='$pl_num' onblur='changeCommentNum($id, this.value)' ";
        }

        return $news;
    }
    //总数据
    public function getAllNews($where)
    {
    	return $this->where($where)->count();
    }
    //根据menu_id查询总记录数
    public function MenuNews($id)
    {
    	return Db::name('hd_news')->where('cate_id', $id)->count();
    }
	
	//按ID查询当前新闻并且给阅读量加一
	
	public function getOneNews($id)
	{
		//阅读量加一
		$where['id'] = $id;
		$reads = $this->where($where)->find();
		$read = $reads['reads'];
		$this->where($where)->setField('reads',  ['exp', $read . '+' . 1]);
		//查询并返回数据
		return $this->alias('a')
		->join('__MENU__ b','b.id = a.cate_id')
		->where('a.id',$id)
		->find();
	}

	//删除新闻
	public function delNews($id)
	{
	    try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
	}
	//删除图片
	public function delImg($id)
	{
		$path = $this->where('id',$id)->find('thumb');
		// $path = substr($path['thumb'],1);
		// dump($path);
		// exit();
		if(isset($path) && file_exists($path))
		{
			unlink($path);
		}
	}
	//根据ID查询当前新闻
	public function getOne($id){
		return $this->where('id',$id)->find();
	}
	//修改新闻
	public function newsEdit($param)
	{
		try{
            $result =  $this->where('id',$param['id'])->update($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '修改成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
	}
	//添加新闻
	public function insertNews($data)
	{
		return $this->insert($data);
	}
	public function getAll()
	{
		return $this->select();
	}

	// 得到带有mp3的新闻
	public function getMp3NewsByWhere($where, $offset, $limit)
	{
		$news = Db::name('hd_news n')->field('n.id, n.title, n.username, n.rota_sort, c.name, n.add_time, n.read_num, n.pl_num')->
		join('hd_article_cate c', 'c.id = n.cate_id')->
		order('rota_sort')->where('n.mp3_status', 1)->select();

		foreach ($news as $k => $v)
		{
			$id = $v['id'];
			$rota_sort = $v['rota_sort'];
			$read_num = $v['read_num'];
			$comment_num = $v['pl_num'];

			$news[$k]['rota_sort'] = "<input type='text' size='3' onblur='changeSort($id, this.value)' value='$rota_sort'>";

			$news[$k]['read_num'] = "<input type='text' size='3' onblur='changeReadNum($id, this.value)' value='$read_num'>";

			$news[$k]['pl_num'] = "<input type='text' size='3' onblur='changeCommentNum($id, this.value)' value='$comment_num'>";
		}

		return $news;
	}

	// 得到带有mp3新闻的总记录数
	public function getAllMp3News()
	{
		return $this->where('mp3_status', 1)->count();
	}
}
