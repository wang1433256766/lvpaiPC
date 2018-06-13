<?php

namespace app\admin\model;

use think\Model;

class ChangeModel extends Model
{
	protected $table = 'too_score_change';

	public function getChangeByWhere($where, $offset, $limit)
    {
        return $this->field('u.nickname, too_score_change.id, too_score_change.change_code, too_score_change.status, a.phone')->
        join('too_mall_member u', 'u.id = too_score_change.member_id')->
       	join('too_score_address a', 'a.member_id = too_score_change.member_id')->
        where($where)->limit($offset, $limit)->select();
    }

    public function getAllChange($where)
    {
    	return $this->where($where)->count();
    }

}