<?php
namespace app\admin\model;

use think\Model;

class ThemeModel extends Model
{
	protected $table = "too_hd_topic_cate";
	public function getThemeBy($where, $offset, $limit)
    {

        return $this->where($where)->limit($offset, $limit)->select();
    }
	//根据id查询记录
	public function getOneTheme($id)
	{
		return $this->where('id',$id)->find();
	}
	//查询记录数
	public function getAllTheme($where)
	{
		return $this->where($where)->count();
	}
	//查询所有记录
	public function getTheme()
	{
		return $this->select();
	}
	//添加数据
	public function insertTheme($data)
	{
		return $this->insert($data);
	}
	//删除图片
	public function delImg($id)
	{
		$path = $this->where('id',$id)->find();
		$path = substr($path['imgpath'],1);
		if(isset($path) && file_exists($path))
		{
			unlink($path);
		}
	}
	//删除数据
	public function delTheme($id)
	{
		try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
	}
	//修改记录
	public function editTheme($data)
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
}