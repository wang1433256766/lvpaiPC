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

class RotationModel extends Model
{
    protected $table = "too_rotation";

    /**
     * 获取所有广告
     */
    public function getRotationByWhere($where,$limit,$offset)
    {

        return $this->where($where)->limit($limit,$offset)->order('addtime desc')->select();
    }
    /**
     * 获取所有广告总数
     */
    public function getAllRota($where)
    {
    	return $this->where($where)->count();
    }
    /*
    *
    * 上传广告
    * @param $param
    */
    public function submitRota($data)
    {
        try{

            $result =  $this->save($data);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '上传成功'];
                
            }
        }catch( PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
     /**
     * 编辑广告信息
     * @param $param
     */
    public function editRota($param)
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
     * 删除一条广告
     * @param $id
     */
    public function delRota($id)
    {
        try{

            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    /**
     * 根据ID获取一条广告
     * @param $id
     */
    public function getOneRota($id)
    {
        return $this->where('id',$id)->find();
    }
    /**
     * 获取所有新闻广告
     */
    public function getNewsRotation()
    {

        return $this->where("status",2)->limit(0,7)->order('addtime desc')->select();
    }
    /**
     * 获取所有视频广告
     */
    public function getVideoRotation()
    {
    
        return $this->where("status",1)->limit(0,7)->order('addtime desc')->select();
    }
   
}