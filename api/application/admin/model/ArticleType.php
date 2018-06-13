<?php
// +----------------------------------------------------------------------
// | Zhl HuiDu
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 http://baiyf.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Belasu <belasu@foxmail.com>
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Model;
use think\Db;

class ArticleType extends Model
{

    protected $table = "too_hd_article_cate";

    /**
     * 获取所有文章栏目
     */
    public function getArtType()
    {

        return $this->select();
    }
    //获取一条栏目
    public function getOneArtiType($id)
    {
        return $this->where('id',$id)->find();
    }
    //获取栏目总数
    public function getArtiTypeNums($where)
    {
       return $this->where($where)->count();
    }
    /**
     * 根据where获取所有视频栏目
     */
   public function  getArtiTypes($where, $offset, $limit)
   {
       return $this->where($where)->limit($offset,$limit)->select();
   }
     /**
     * 获取所有视频栏目
     */
    public  function getAllArtiType()
    {
    	 return $this->select();
       
    }
    /**
     * 删除栏目
     */
    public function delArtiType($id)
    {               
       try{        
             $this->where('id', $id)->delete();
                return ['code' => 1, 'data' => '', 'msg' => '删除成功'];
        
            }catch( PDOException $e){
                return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
          }
        
    }
    //添加栏目
    public function insertArtiType($param)
    {
        try{

            $result =  $this->save($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加成功'];
            }
            //PDOException  异常类
        }catch( PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    /**
     * 编辑栏目
     * @param $param
     */
    public function ArtiTypeEdit($param)
    {
        try{
    
            $result =  $this->save($param, ['id' => $param['id']]);
    
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
    
                return ['code' => 1, 'data' => '', 'msg' => '编辑栏目成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    
    //拼接
    public function arrArtype()
    {
        $res = $this->getArtType();
        $arr = [];
        foreach ($res as $key => $value) {
            $arr[$value['id']] = $value['name'];
        }
        return $arr;
    }
    

   
}