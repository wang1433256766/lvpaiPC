<?php
namespace app\admin\model;

use think\Model;

class AppModel extends Model
{
	protected $table = "too_app";
	//查询所有信息
	public function getAppBy($where, $offset, $limit)
	{
		return $this->where($where)->select();
	}
	//查询所有记录
	public function getAllApp($where)
    {
        return $this->where($where)->count();
    }
    //获取所有信息
    public function getApp()
    {
        return $this->select();
    }
    //根据Id获取APP信息
    public function getOneAppName($name)
    {
        return $this->where('id',$name)->find();
    }
    
    //查询一条appid
    public function  getAppId($id)
    {
        return $this->where($id)->find();
    }
    //根据App_id查询appid
    public function getApp_Id($a_id)
    {
        return $this->field("id")->where($a_id)->find();
    }
     /**
     * 删除应用
     * @param $id
     */
    public function delApp($id)
    {
        try{

            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    /**
     * 更新应用
     * @param $param
     */
    public function editApp($param)
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
    public function getOneApp($id)
    {
        return $this->where('id', $id)->find();
    }
    /**
     * 添加应用
     * @param $param
     */
    public function insertApp($param)
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
     * 根据id删除图片
     * @param $id
     */
    public function delImg($id){
        $path = $this->where('id',$id)->field('img')->find();
        $path = substr($path['img'],1);
        if(!empty($path)){
            @unlink($path);
        }
    }  
}