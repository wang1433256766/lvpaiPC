<?php
namespace app\admin\model;

use think\Model;

class MtextModel extends Model
{
	protected $table = "too_mtext";
	//查询所有信息
	public function getMtextBy($where,$offset,$limit)
	{
		return $this->where($where)->limit($offset,$limit)->select();
	}
    //带条件的信息查询
    public function getMtextwhere($where)
    {
        return $this->where($where)->select();
    }   
	//查询所有记录
	public function getAllMtext($where)
    {
        return $this->where($where)->count();
    }
    //获取所有信息
    public function getMtext()
    {
        return $this->select();
    }
     /**
     * 删除回复
     * @param $id
     */
    public function delMtext($id)
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
    public function editMtext($param)
    {
        try{

            $result =  $this->Validate('MtextValidate')->save($param, ['id' => $param['id']]);

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
    public function getOneMtext($id)
    {
        return $this->where('id', $id)->find();
    }
    /**
     * 插入管理员信息
     * @param $param
     */
    public function insertMtext($param)
    {
        try{

            $result =  $this->Validate('MtextValidate')->save($param);
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
    public function delImg($id){
        $path = $this->where('id',$id)->field('img')->find();
        $path = substr($path['img'],1);
        if(!empty($path) && file_exists($path)){
            unlink($path);
        }
    }      
}