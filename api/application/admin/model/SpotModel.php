<?php

namespace app\admin\model;

use think\Model;

class SpotModel extends Model
{
	protected $table = 'too_mall_spot';

	// 得到轮播景区
	public function getRotaSpot()
	{
		return $this->field('id, title, address, add_time, status')->where('rota', 1)->select();
	}

	// 得到轮播景区个数
	public function getRotaSpotCount($where)
	{
		return $this->where('rota', 1)->count();
	}

	/**
     * 取消景区轮播
     * @param $id
     */
    public function cancelSpotRota($id)
    {
        try{

            $this->where('id', $id)->update(['rota' => 0]);
            return ['code' => 1, 'data' => '', 'msg' => '删除管理员成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}