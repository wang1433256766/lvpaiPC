<?php
namespace app\admin\model;

use think\Model;

class MenuModel extends Model
{
	protected $table = "too_menu";
	//查询所有信息
	public function getMenuBy($where)
	{
		return $this->where($where)->order('sort','asc')->paginate(8);
	}
	//查询所有记录
	public function getAllMenu($where)
    {
        return $this->where($where)->count();
    }
    //按APPID查询记录
    public function getAllMenuwhere($where)
    {
        return $this->where($where)->select();
    }    
    //获取所有信息
    public function getMenu()
    {
        return $this->select();
    }
     /**
     * 删除
     * @param $id
     */
    public function delMenu($id)
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
    public function editMenu($param)
    {
        try{

            $result =  $this->validate('MenuValidate')->save($param, ['id' => $param['id']]);

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
     * 根据app_id修改状态
     * @param $where
     */
    public function updateAllMenu($where)
    {
        return $this->where($where)->update(['status' => 2]);
    }     
    /**
     * 根据id获取信息
     * @param $id
     */
    public function getOneMenu($id)
    {
        return $this->where('id', $id)->find();
    }
    /**
     * 插入信息
     * @param $param
     */
    public function insertMenu($param)
    {
        try{

            $result =  $this->validate('MenuValidate')->save($param);
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
    //拼接
    public function arrMenu()
    {
        $res = $this->getMenu();
        $arr = [];
            foreach ($res as $key => $value) {
                  $arr[$value['id']] = $value['name'];  
            }
            return $arr;
    }       
}