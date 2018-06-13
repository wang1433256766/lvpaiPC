<?php
// +----------------------------------------------------------------------
// | snake
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 http://baiyf.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: NickBai <1902822973@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Model;
use think\Request;
class ApplyModel extends Model
{
  protected $table = "too_form";
	//查询报名所有信息
	public function getApply($where,$offset, $limit)
	{
	 return $this->field('too_form.*,title')
            ->join('too_vote', 'too_form.vid = too_vote.id')
            ->where($where)->limit($offset, $limit)->order('id desc')->select();
	}
	//查询所有记录
	public function getAllAlbum($where)
    {
        return $this->where($where)->count();
    }
    //获取所有信息
    public function getAlbum()
    {
    	
        return $this->select();
    }
     /**
     * 根据ID删除报名
     * @param $id
     */
    public function delApply($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    /**
     * 更新报名管理
     * @param $param
     */
    public function editApply($param)
    {
      
        try{

            $result =  $this->save($param, ['id' => $param['id']]);

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
     * 根据id获取信息
     * @param $id
     */
    public function getOneapply($id)
    {
        return $this->where('id', $id)->find();
    }
    /**
     * 插入管理员信息
     * @param $param
     */
    public function insertAlbum($param)
    {
        try{

            $result =  $this->Validate('ApplicationValidate')->save($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加成功'];
            }
        }catch( PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    } 
    /**
     * 根据id删除信息
     * @param $id
     */
  
}

    