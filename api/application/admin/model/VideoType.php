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

class VideoType extends Model
{

    protected $table = "too_hd_video_type";

    /**
     * 获取所有视频
     */
    public function getVideoType()
    {

        return $this->limit(0,6)->order('type_sort desc')->select();
    }
    //获取一条栏目
    public function getOneVdtype($id)
    {
        return $this->where('type_id',$id)->find();
    }
    //获取栏目总数
    public function getVdTypeNums($where)
    {
       return $this->where($where)->count();
    }
    /**
     * 根据where获取所有视频栏目
     */
   public function  getVideoTypes($where, $offset, $limit)
   {
       return $this->where($where)->limit($offset,$limit)->select();
   }
     /**
     * 获取所有视频栏目
     */
    public  function getAllType()
    {
    	 return $this->select();
       
    }
    /**
     * 删除视频栏目
     */
    public function delVdtype($id)
    {               
       try{        
             $this->where('type_id', $id)->delete();
                return ['code' => 1, 'data' => '', 'msg' => '删除成功'];
        
            }catch( PDOException $e){
                return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
          }
        
    }
    //添加栏目
    public function insertVdtype($param)
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
     * 编辑视频栏目
     * @param $param
     */
    public function VdtypeEdit($param)
    {
        try{
    
            $result =  $this->save($param, ['type_id' => $param['type_id']]);
    
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
    
                return ['code' => 1, 'data' => '', 'msg' => '编辑视频栏目成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    
    //拼接
    public function arrVdtype()
    {
        $res = $this->getAllType();
        $arr = [];
        foreach ($res as $key => $value) {
            $arr[$value['type_id']] = $value['type_name'];
        }
        return $arr;
    }
    

   
}