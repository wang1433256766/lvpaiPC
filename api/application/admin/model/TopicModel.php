<?php
// +----------------------------------------------------------------------
// | Zhl
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 http://baiyf.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Belasu <88487088@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Model;
use think\Db;

class TopicModel extends Model
{
    protected $table = 'too_hd_article_topic';

    /**
     * 根据搜索条件获取文章
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getArticleByWhere($where,$limit,$offset)
    {
        $news = Db::name('hd_article_topic t')->
        field('t.id, t.title, m.nickname, c.name, t.read_num, t.like_num as favor_num')->
        join('admin_msg m', 'm.id = t.member_id')->
        join('hd_topic_cate c', 'c.id = t.topic_id')
        ->select();

        foreach ($news as $k => $v)
        {
            $id = $v['id'];
            $read_num = $v['read_num'];
            $favor_num = $v['favor_num'];

            $news[$k]['read_num'] = "<input type='text' size='3' value='$read_num' onblur='changeReadNum($id, this.value)'>";

            $news[$k]['favor_num'] = "<input type='text' size='3' value='$favor_num' onblur='changeFavorNum($id, this.value)'>";
        }

        return $news;
    }
    /**
     * HuiDuAPP获取文章
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getArticleByWheres()
    {
        return $this->field('too_hd_article_topic.id,too_hd_article_topic.thumb,too_hd_article_topic.title,too_hd_article_topic.add_time,too_hd_article_topic.content,too_hd_article_topic.read_num,too_hd_article_topic.share_num,too_hd_article_topic.name,too_admin.username')
        ->join('too_hd_topic_cate',"too_hd_article_topic.topic_id=too_hd_topic_cate.id")
        ->join('too_admin','too_admin.id=too_hd_article_topic.member_id')
        ->limit("6,30")
        ->order('read_num desc')->select();
    }
    /**
     * HuiDuAPP获取轮播新闻
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getRotaArticle()
    {
        return $this->field('too_hd_article_topic.id,too_hd_article_topic.thumb,too_hd_article_topic.title,too_hd_article_topic.add_time,too_hd_article_topic.content,too_hd_article_topic.read_num,too_hd_article_topic.share_num,too_hd_article_topic.name,too_admin.username')
        ->join('too_hd_topic_cate',"too_hd_article_topic.topic_id=too_hd_topic_cate.id")
        ->join('too_admin','too_admin.id=too_hd_article_topic.member_id')
        ->limit("0,5")
        ->order('read_num desc')->select();
    }
    /**
     * HuiDuAPP获取财经新闻
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getCJArticle()
    {
        return $this->field('too_hd_article_topic.id,too_hd_article_topic.thumb,too_hd_article_topic.title,too_hd_article_topic.add_time,too_hd_article_topic.content,too_hd_article_topic.read_num,too_hd_article_topic.share_num,too_hd_article_topic.name,too_admin.username')
        ->join('too_hd_topic_cate',"too_hd_article_topic.topic_id=too_hd_topic_cate.id")
        ->join('too_admin','too_admin.id=too_hd_article_topic.member_id')
        ->limit("15")
        ->where("topic_id",5)
        ->order('add_time desc')->select();
    }
    /**
     * HuiDuAPP获取八卦新闻
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getBGArticle()
    {
        return $this->field('too_hd_article_topic.id,too_hd_article_topic.thumb,too_hd_article_topic.title,too_hd_article_topic.add_time,too_hd_article_topic.content,too_hd_article_topic.read_num,too_hd_article_topic.share_num,too_hd_article_topic.name,too_admin.username')
        ->join('too_hd_topic_cate',"too_hd_article_topic.topic_id=too_hd_topic_cate.id")
        ->join('too_admin','too_admin.id=too_hd_article_topic.member_id')
        ->limit("15")
        ->where("topic_id",7)
        ->order('add_time desc')->select();
    }
    /**
     * HuiDuAPP获取科技新闻
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getKJArticle()
    {
        return $this->field('too_hd_article_topic.id,too_hd_article_topic.thumb,too_hd_article_topic.title,too_hd_article_topic.add_time,too_hd_article_topic.content,too_hd_article_topic.read_num,too_hd_article_topic.share_num,too_hd_article_topic.name,too_admin.username')
        ->join('too_hd_topic_cate',"too_hd_article_topic.topic_id=too_hd_topic_cate.id")
        ->join('too_admin','too_admin.id=too_hd_article_topic.member_id')
        ->limit("15")
        ->where("topic_id",10)
        ->order('add_time desc')->select();
    }
    /**
     * HuiDuAPP获取惠说新闻
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getHsxwArticle()
    {
        return $this->field('too_hd_article_topic.id,too_hd_article_topic.thumb,too_hd_article_topic.title,too_hd_article_topic.add_time,too_hd_article_topic.content,too_hd_article_topic.read_num,too_hd_article_topic.share_num,too_hd_topic_cate.name,too_admin.username')
        ->join('too_hd_topic_cate',"too_hd_topic_cate.id=too_hd_article_topic.topic_id")
        ->join('too_admin','too_admin.id=too_hd_article_topic.member_id')
        ->limit("15")
        ->where("topic_id",1)
        ->order('add_time desc')->select();
    }
    /**
     * HuiDuAPP获取旅游新闻
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getLYArticle()
    {
        return $this->field('too_hd_article_topic.id,too_hd_article_topic.thumb,too_hd_article_topic.title,too_hd_article_topic.add_time,too_hd_article_topic.content,too_hd_article_topic.read_num,too_hd_article_topic.share_num,too_hd_topic_cate.name,too_admin.username')
        ->join('too_hd_topic_cate',"too_hd_topic_cate.id=too_hd_article_topic.topic_id")
        ->join('too_admin','too_admin.id=too_hd_article_topic.member_id')
        ->limit("15")
        ->where("topic_id",8)
        ->order('add_time desc')->select();
    }
    /**
     * HuiDuAPP获取有声旅行新闻
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getYslxArticle()
    {
        return $this->field('too_hd_article_topic.id,too_hd_article_topic.thumb,too_hd_article_topic.title,too_hd_article_topic.add_time,too_hd_article_topic.content,too_hd_article_topic.read_num,too_hd_article_topic.share_num,too_hd_topic_cate.name,too_admin.username')
        ->join('too_hd_topic_cate',"too_hd_topic_cate.id=too_hd_article_topic.topic_id")
        ->join('too_admin','too_admin.id=too_hd_article_topic.member_id')
        ->limit("15")
        ->where("topic_id",2)
        ->order('add_time desc')->select();
    }
    
    /**
     * HuiDuAPP获取有料旅行新闻
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getYllxArticle()
    {
         return $this->field('too_hd_article_topic.id,too_hd_article_topic.thumb,too_hd_article_topic.title,too_hd_article_topic.add_time,too_hd_article_topic.content,too_hd_article_topic.read_num,too_hd_article_topic.share_num,too_hd_topic_cate.name,too_admin.username')
        ->join('too_hd_topic_cate',"too_hd_topic_cate.id=too_hd_article_topic.topic_id")
        ->join('too_admin','too_admin.id=too_hd_article_topic.member_id')
        ->limit("15")
        ->where("topic_id",3)
        ->order('add_time desc')->select();
    }
    

    /**
     * HuiDuAPP获取不惠你看新闻
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getBhnkArticle()
    {
        return $this->field('too_hd_article_topic.id,too_hd_article_topic.thumb,too_hd_article_topic.title,too_hd_article_topic.add_time,too_hd_article_topic.content,too_hd_article_topic.read_num,too_hd_article_topic.share_num,too_hd_topic_cate.name,too_admin.username')
        ->join('too_hd_topic_cate',"too_hd_topic_cate.id=too_hd_article_topic.topic_id")
        ->join('too_admin','too_admin.id=too_hd_article_topic.member_id')
        ->limit("15")
        ->where("topic_id",4)
        ->order('add_time desc')->select();
    }
    /**
     * 获取最热五篇文章
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getHotArticle()
    {
        return $this->field("*")->limit(5)->select();
    }
    /**
     * 获取一篇文章
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getOneArt($id)
    {
        return $this->where('id',$id)->find();
        
    }

    /**
     * 根据搜索条件获取草稿箱文章
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getArticleBox($where,$limit,$offset)
    {
        return $this->field('too_hd_article_topic.*,too_hd_article_topic.name,too_admin.username')
        ->join('too_hd_topic_cate',"too_hd_article_topic.topic_id=too_hd_topic_cate.id")
        ->join('too_admin','too_admin.id=too_hd_article_topic.member_id')
        ->where("status=3")->limit($limit,$offset)->order('add_time desc')->select();
    }
    /**
     * 根据搜索条件获取所有的文章数量
     * @param $where
     */
    public function getAllArticle($where)
    {
        return $this->where($where)->count();
    }
    /**
     * 根据搜索条件获取所有的文章阅读量
     * @param $where
     */
    public function getArticleReads($where)
    {
        return $this->where($where)->sum("read_num");
    }
    /**
     * 根据搜索条件获取所有的文章分享量
     * @param $where
     */
    public function getArticleShare($where)
    {
        return $this->where($where)->sum("share_num");
    }

    /**
     * 添加文章
     * @param $param
     */
    public function insertArticle($param)
    {
        try{

            $result =  $this->save($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加用户成功'];
            }
        }catch( PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    
    /**
     * 编辑文章
     * @param $param
     */
    //修改新闻
    public function textEdit($data)
    {
        try{
            $result =  $this->where('id',$data['id'])->update($data);
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

    /**
     * 根据id获取文章
     * @param $id
     */
    public function getOneArticle($id)
    {
        return $this->where('id', $id)->find();
    }

    /**
     * 删除文章
     * @param $id
     */
    public function delArticle($id)
    {
        try{

            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    /**
     * 发布文章
     * @param $id
     */
    public function ArticlePush($id)
    {
        try{
    
            $result =  $this->where("id",$id)->setField('status',3);
    
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
    
                return ['code' => 1, 'data' => '', 'msg' => '发布成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}