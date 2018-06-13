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

class VideoModel extends Model
{

    protected $table = "too_hd_topic_video";

    /**
     * 获取所有视频
     */
    public function getVideosByWhere($where,$limit,$offset)
    {
        return $this->field("too_hd_topic_video.*,too_hd_video_type.type_name")
                    ->join("too_hd_video_type","too_hd_topic_video.type_id=too_hd_video_type.type_id")
                    ->where($where)
                    ->limit($limit,$offset)
                    ->order('add_time desc')
                    ->select();      		 
    }
    /**
     * 获取所有视频总数
     */
    public function getAllVideo($where)
    {
    	return $this->where($where)->count();
    }
    /*
    *
    * 上传视频
    * @param $param
    */
    public function submitVideo($data)
    {
        try{

            $result =  $this->save($data);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '上传视频成功'];
                
            }
        }catch( PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
     /**
     * 编辑视频信息
     * @param $param
     */
    public function editVideo($param)
    {
        try{

            $result =  $this->save($param, ['id' => $param['id']]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '编辑视频成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
     /**
     * 删除一条视频
     * @param $id
     */
    public function delVideo($id)
    {
        try{

            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除视频成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    public function getOneVideo($id)
    {
        return $this->where('id',$id)->find();
    }

   
}