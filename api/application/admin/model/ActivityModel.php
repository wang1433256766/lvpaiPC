<?php

namespace app\admin\model;

use think\Model;
use think\Db;

class ActivityModel extends Model
{
	protected $table = 'too_activity';

	public function getActivityList($where, $offset, $limit)
	{
		$activity = Db::name('activity ac')->field('ac.activity_id, activity_name, ac.rota, ac.status, ac.like_num, ac.read_num, a.username')->
		join('too_admin a', 'a.id = ac.writer_id')->
		where($where)->limit($offset, $limit)->select();

		foreach ($activity as $k => $v)
		{
			$id = $v['activity_id'];
			$favor_num = $v['like_num'];
			$read_num = $v['read_num'];

			$activity[$k]['like_num'] = "<input type='text' value='$favor_num' size='3' onblur = 'changeFavorNum($id, this.value)'>";

			$activity[$k]['read_num'] = "<input type='text' value='$read_num' size='3' onblur = 'changeReadNum($id, this.value)'>";
		}

		return $activity;
	}

	public function getActivity($where)
	{
		return $this->where($where)->count();
	}

	/**
     * 删除活动
     * @param $id
     */
    public function delActivity($id)
    {
        try{

            $this->where('activity_id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除活动成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}