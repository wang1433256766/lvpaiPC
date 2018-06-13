<?php
namespace app\admin\model;

use think\Model;

class VoteModel extends Model
{
  protected $table = "too_vote";
	//查询所有信息
	public function getVotely($where)
	{
		return $this->where($where)->select();
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
     * 删除回复
     * @param $id
     */
    public function delAlbum($id)
    {
        try{

            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    /**
     * 更新回复
     * @param $param
     */
    public function editVote($param)
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
     * 插入管理员信息
     * @param $param
     */
    public function insertVote($param)
    {   
    try{
            $result =  $this->Validate('')->save($param);
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
     * 根据id获取信息
     * @param $id
     */
    public function getOneVote($id)
    {
        return $this->where('id', $id)->find();
    }
    /**
     * 插入管理员信息
     * @param $param
     */
    /* public function insertAlbum($param)
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
    }  */
    
    
    /**
     * 删除回复
     * @param $id
     */
    public function delVote($id)
    {
        try{
    
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];
    
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    
    
    /**
     * 根据id删除信息
     * @param $id
     */
    public function delImg($id){
        $path = $this->where('id',$id)->field('picurl')->find();
        $path = substr($path['picurl'],1);
        if(!empty($path)){
            @unlink($path);
        }
    }  
}
