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

class ArticleModel extends Model
{
    protected $table = 'too_hd_news';

    /**
     * 根据搜索条件获取文章
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getArticleByWhere($where,$limit,$offset)
    {
        return $this->field('too_article.*,too_article_cate.name,too_user.username')
                    ->join('too_article_cate',"too_article.cate_id=too_article_cate.id")
                    ->join('too_user','too_user.id=too_article.member_id')
                    ->where($where)->limit($limit,$offset)->order('add_time desc')->select();
    }

    /**
     * 根据搜索条件获取草稿箱文章
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getArticleBox($where,$limit,$offset)
    {
        return $this->field('too_article.*,too_article_cate.name,too_user.username')
        ->join('too_article_cate',"too_article.cate_id=too_article_cate.id")
        ->join('too_user','too_user.id=too_article.member_id')
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
        return $this->where($where)->sum("clicks_num");
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
    public function ArticleEdit($param)
    {
        try{

            $result =  $this->save($param, ['id' => $param['id']]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '编辑成功'];
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