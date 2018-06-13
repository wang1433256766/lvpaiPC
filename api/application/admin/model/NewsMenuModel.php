<?php
namespace app\admin\model;
use think\Model;

class NewsMenuModel extends Model
{
	protected $table = 'too_hd_article_cate';
	 
	 /**
     * //查询所有新闻菜单
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getNewsMenuBy($where, $offset, $limit)
    {

        return $this->where($where)->limit($offset, $limit)->select();
    }
	 /**
     * //查询当前ID下的菜单标题
     * @param $id
     */
    public function getOneNewsMenu($id)
    {
    	return $this->where('id',$id)->find();
    } 
    //查询所有新闻菜单
    public function getNewsMenu()
    {
        return $this->select();
    }
    //查询总数据
    public function getAllMenu()
    {
        return $this->count();
    }
    //根据theme_id查询数据
    public function getTmenuId($id)
    {
        return $this->where('theme_id',$id)->count();
    }   
    //修改记录
    public function editNewsMenu($data)
    {
        try{
            $result =  $this->where('id',$data['id'])->update($data);
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
    //删除菜单
    public function delNewsMenu($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    //添加菜单
    public function insertNewsMenu($data)
    {
        return $this->insert($data);
    }	
}