<?php
namespace app\admin\model;

use think\Model;

class MenuTypeModel extends Model
{
	protected $table = "too_menu_type";
	//查询所有信息
	public function getMenuTypeBy($where,$offset,$limit)
	{
		return $this->where($where)->limit($offset,$limit)->select();
	}
	//查询所有记录
	public function getAllMenuType($where)
    {
        return $this->where($where)->count();
    }
    //获取所有信息
    public function getMenuType()
    {
        return $this->select();
    }
     /**
     * 删除
     * @param $id
     */
    public function delMenuType($id)
    {
        try{

            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    /**
     * 更新
     * @param $param
     */
    public function editMenuType($param)
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
    public function getOneMenuType($id)
    {
        return $this->where('id', $id)->find();
    }
    /**
     * 插入信息
     * @param $param
     */
    public function insertMenuType($param)
    {
        try{

            $result =  $this->save($param);
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
}