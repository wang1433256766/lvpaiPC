<?php

namespace app\admin\model;

use think\Model;

class TravelModel extends Model
{
	protected $table = 'too_travels';

	public function getTravelByWhere($where, $offset, $limit, $btn)
    {
        // 根据$status来选择最热和最新数据
        if ('new' == $btn)
        {
            $travel = $this->field('m.nickname, too_travels.*')->
            join('mall_member m', "m.id = too_travels.user_id")->
            where($where)->order('add_time desc')->limit($offset, $limit)->select();
        }
        else if ('hot' == $btn)
        {
            $travel = $this->field('m.nickname, too_travels.*')->
            join('mall_member m', "m.id = too_travels.user_id")->
            where($where)->order('read_num desc')->limit($offset, $limit)->select();
        }
        else
        {

        }

        foreach ($travel as $k => $v)
        {
            $id = $v['id'];
            $favor_num = $v['favor_num'];
            $read_num = $v['read_num'];
            $reply_num = $v['reply_num'];

            $travel[$k]['favor_num'] = "<input type='text' size='3' value='$favor_num' onblur='changeFavorNum($id, this.value)'>";

            $travel[$k]['read_num'] = "<input type='text' size='3' value='$read_num' onblur='changeReadNum($id, this.value)'>";

            $travel[$k]['reply_num'] = "<input type='text' size='3' value='$reply_num' onblur='changeReplyNum($id, this.value)'>";
        }

        return $travel;
    }

    public function getAllTravel($where)
    {
        return $this->where($where)->count();
    }


    /**
     * 删除游记
     * @param $id
     */
    public function delTravel($id)
    {
        try{

            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除游记成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}